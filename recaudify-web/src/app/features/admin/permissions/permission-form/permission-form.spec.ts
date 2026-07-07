import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { PermissionsService } from '@core/services/permissions.service';
import { ToastService } from '@core/services/toast.service';
import { PermissionForm } from './permission-form';

async function setup(id?: string) {
  const service = {
    getById: vi.fn().mockReturnValue(of({ id: 5, name: 'clientes.crear', guard_name: 'api' })),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
    isValidName: vi.fn((name: string) => /^[a-z_]+\.[a-z_]+$/.test(name)),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [PermissionForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: PermissionsService, useValue: service },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(PermissionForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, service, toast, navigate };
}

describe('PermissionForm', () => {
  it('creates a permission and navigates back on save', async () => {
    const { comp, service, toast, navigate } = await setup();
    comp.formName.set('clientes.crear');

    comp.save();

    expect(service.create).toHaveBeenCalledWith('clientes.crear');
    expect(navigate).toHaveBeenCalledWith(['/admin/permissions']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when the name format is invalid', async () => {
    const { comp, service } = await setup();
    comp.formName.set('invalido');

    comp.save();

    expect(service.create).not.toHaveBeenCalled();
    expect(comp.error()).toBe('Usa el formato modulo.accion (ej. clientes.crear).');
  });

  it('loads the permission in edit mode', async () => {
    const { comp, service } = await setup('5');

    expect(service.getById).toHaveBeenCalledWith(5);
    expect(comp.formName()).toBe('clientes.crear');
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the permission in edit mode', async () => {
    const { comp, service, navigate } = await setup('5');
    comp.formName.set('clientes.editar');

    comp.save();

    expect(service.update).toHaveBeenCalledWith(5, 'clientes.editar');
    expect(navigate).toHaveBeenCalledWith(['/admin/permissions']);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, service, toast } = await setup();
    service.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName.set('clientes.crear');

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
