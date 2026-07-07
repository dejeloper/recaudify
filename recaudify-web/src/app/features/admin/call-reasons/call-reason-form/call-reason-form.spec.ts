import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { CallReasonsService } from '@core/services/call-reasons.service';
import { ToastService } from '@core/services/toast.service';
import { CallReasonForm } from './call-reason-form';

async function setup(id?: string) {
  const service = {
    getById: vi
      .fn()
      .mockReturnValue(of({ id: 5, name: 'Ausente', color: '#ff0000', active: true })),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [CallReasonForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: CallReasonsService, useValue: service },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(CallReasonForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, service, toast, navigate };
}

describe('CallReasonForm', () => {
  it('creates a call reason and navigates back on save', async () => {
    const { comp, service, toast, navigate } = await setup();
    comp.formName = 'No contesta';
    comp.formColor = '#00ff00';
    comp.formActive = true;

    comp.save();

    expect(service.create).toHaveBeenCalledWith('No contesta', '#00ff00', true);
    expect(navigate).toHaveBeenCalledWith(['/admin/call-reasons']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('creates a call reason with null color when empty', async () => {
    const { comp, service } = await setup();
    comp.formName = 'No contesta';
    comp.formColor = '';

    comp.save();

    expect(service.create).toHaveBeenCalledWith('No contesta', null, true);
  });

  it('does not save when the form is invalid', async () => {
    const { comp, service } = await setup();
    comp.formName = '';

    comp.save();

    expect(service.create).not.toHaveBeenCalled();
  });

  it('loads the call reason in edit mode', async () => {
    const { comp, service } = await setup('5');

    expect(service.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Ausente');
    expect(comp.formColor).toBe('#ff0000');
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the call reason in edit mode', async () => {
    const { comp, service, navigate } = await setup('5');
    comp.formName = 'Ausente definitivo';

    comp.save();

    expect(service.update).toHaveBeenCalledWith(5, 'Ausente definitivo', '#ff0000', true);
    expect(navigate).toHaveBeenCalledWith(['/admin/call-reasons']);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, service, toast } = await setup();
    service.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName = 'X';

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
