import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { SellersService } from '@core/services/sellers.service';
import { ToastService } from '@core/services/toast.service';
import { SellerForm } from './seller-form';

async function setup(id?: string) {
  const service = {
    getById: vi
      .fn()
      .mockReturnValue(of({ id: 5, name: 'Juan Vendedor', username: 'juan.v', active: true })),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [SellerForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: SellersService, useValue: service },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(SellerForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, service, toast, navigate };
}

describe('SellerForm', () => {
  it('creates a seller and navigates back on save', async () => {
    const { comp, service, toast, navigate } = await setup();
    comp.formName = 'Ana Vendedora';
    comp.formUsername = 'ana.v';
    comp.formActive = true;

    comp.save();

    expect(service.create).toHaveBeenCalledWith('Ana Vendedora', 'ana.v', true);
    expect(navigate).toHaveBeenCalledWith(['/admin/sellers']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('creates a seller with null username when empty', async () => {
    const { comp, service } = await setup();
    comp.formName = 'Ana Vendedora';
    comp.formUsername = '';

    comp.save();

    expect(service.create).toHaveBeenCalledWith('Ana Vendedora', null, true);
  });

  it('does not save when the form is invalid', async () => {
    const { comp, service } = await setup();
    comp.formName = '';

    comp.save();

    expect(service.create).not.toHaveBeenCalled();
  });

  it('loads the seller in edit mode', async () => {
    const { comp, service } = await setup('5');

    expect(service.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Juan Vendedor');
    expect(comp.formUsername).toBe('juan.v');
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the seller in edit mode', async () => {
    const { comp, service, navigate } = await setup('5');
    comp.formName = 'Juan V. Actualizado';

    comp.save();

    expect(service.update).toHaveBeenCalledWith(5, 'Juan V. Actualizado', 'juan.v', true);
    expect(navigate).toHaveBeenCalledWith(['/admin/sellers']);
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
