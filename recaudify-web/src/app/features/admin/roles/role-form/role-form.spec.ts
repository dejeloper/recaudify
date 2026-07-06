import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { PermissionsService } from '@core/services/permissions.service';
import { RolesService } from '@core/services/roles.service';
import { ToastService } from '@core/services/toast.service';
import { RoleForm } from './role-form';

const perms = [
  { id: 1, name: 'clientes.crear', guard_name: 'api' },
  { id: 2, name: 'clientes.editar', guard_name: 'api' },
];

async function setup(id?: string) {
  const rolesService = {
    getById: vi.fn().mockReturnValue(
      of({
        id: 5,
        name: 'Supervisor',
        guard_name: 'api',
        permissions: [{ id: 1, name: 'clientes.crear', guard_name: 'api' }],
      }),
    ),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const permissionsService = {
    getAll: vi.fn().mockReturnValue(of(perms)),
    groupByModuleNames: vi.fn().mockReturnValue([{ module: 'clientes', perms: ['clientes.crear', 'clientes.editar'] }]),
    actionLabel: vi.fn((name: string) => name.split('.')[1] ?? name),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [RoleForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: RolesService, useValue: rolesService },
      { provide: PermissionsService, useValue: permissionsService },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(RoleForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, rolesService, permissionsService, toast, navigate };
}

describe('RoleForm', () => {
  it('loads permissions on init', async () => {
    const { comp, permissionsService } = await setup();
    expect(permissionsService.getAll).toHaveBeenCalled();
    expect(comp.allPermissions()).toEqual(perms);
  });

  it('creates a role and navigates back on save', async () => {
    const { comp, rolesService, toast, navigate } = await setup();
    comp.formName = 'Nuevo Rol';
    comp.toggle('clientes.crear');

    comp.save();

    expect(rolesService.create).toHaveBeenCalledWith('Nuevo Rol', ['clientes.crear']);
    expect(navigate).toHaveBeenCalledWith(['/admin/roles']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when the name is empty', async () => {
    const { comp, rolesService } = await setup();
    comp.formName = '   ';

    comp.save();

    expect(rolesService.create).not.toHaveBeenCalled();
  });

  it('loads the role in edit mode', async () => {
    const { comp, rolesService } = await setup('5');

    expect(rolesService.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Supervisor');
    expect(comp.isSelected('clientes.crear')).toBe(true);
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the role in edit mode', async () => {
    const { comp, rolesService, navigate } = await setup('5');
    comp.formName = 'Supervisor Senior';

    comp.save();

    expect(rolesService.update).toHaveBeenCalledWith(5, 'Supervisor Senior', ['clientes.crear']);
    expect(navigate).toHaveBeenCalledWith(['/admin/roles']);
  });

  it('toggle adds and removes permissions from the selection', async () => {
    const { comp } = await setup();
    comp.toggle('clientes.crear');
    expect(comp.isSelected('clientes.crear')).toBe(true);
    comp.toggle('clientes.crear');
    expect(comp.isSelected('clientes.crear')).toBe(false);
  });

  it('toggleAll checks or unchecks a whole group', async () => {
    const { comp } = await setup();
    comp.toggleAll(['clientes.crear', 'clientes.editar'], true);
    expect(comp.allChecked(['clientes.crear', 'clientes.editar'])).toBe(true);

    comp.toggleAll(['clientes.crear', 'clientes.editar'], false);
    expect(comp.allChecked(['clientes.crear', 'clientes.editar'])).toBe(false);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, rolesService, toast } = await setup();
    rolesService.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName = 'X';

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
