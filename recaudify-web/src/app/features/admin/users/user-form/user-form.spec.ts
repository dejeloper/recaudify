import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { RolesService } from '@core/services/roles.service';
import { ToastService } from '@core/services/toast.service';
import { UsersService } from '@core/services/users.service';
import { UserForm } from './user-form';

const roles = [{ id: 1, name: 'supervisor', guard_name: 'api', permissions: [] }];

async function setup(id?: string) {
  const usersService = {
    getById: vi.fn().mockReturnValue(
      of({
        id: 5,
        name: 'Juan Pérez',
        username: 'juan.perez',
        email: 'juan@empresa.com',
        active: true,
        roles: ['supervisor'],
        permissions: [],
      }),
    ),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const rolesService = { getAll: vi.fn().mockReturnValue(of(roles)) };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [UserForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: UsersService, useValue: usersService },
      { provide: RolesService, useValue: rolesService },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(UserForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return {
    fixture,
    comp: fixture.componentInstance as any,
    usersService,
    rolesService,
    toast,
    navigate,
  };
}

describe('UserForm', () => {
  it('loads roles on init', async () => {
    const { comp, rolesService } = await setup();
    expect(rolesService.getAll).toHaveBeenCalled();
    expect(comp.roles()).toEqual(roles);
  });

  it('creates a user and navigates back on save', async () => {
    const { comp, usersService, toast, navigate } = await setup();
    comp.formName = 'Ana Gómez';
    comp.formUsername = 'ana.gomez';
    comp.formEmail = 'ana@empresa.com';
    comp.formRole = 'supervisor';
    comp.formPassword = 'secret123';
    comp.formPasswordConfirmation = 'secret123';

    comp.save();

    expect(usersService.create).toHaveBeenCalledWith({
      name: 'Ana Gómez',
      username: 'ana.gomez',
      email: 'ana@empresa.com',
      role: 'supervisor',
      password: 'secret123',
      password_confirmation: 'secret123',
    });
    expect(navigate).toHaveBeenCalledWith(['/admin/users']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when name or username is missing', async () => {
    const { comp, usersService } = await setup();
    comp.formName = '';
    comp.formUsername = 'ana.gomez';
    comp.formPassword = 'secret123';

    comp.save();

    expect(usersService.create).not.toHaveBeenCalled();
  });

  it('does not save a new user without a password', async () => {
    const { comp, usersService } = await setup();
    comp.formName = 'Ana Gómez';
    comp.formUsername = 'ana.gomez';
    comp.formPassword = '';

    comp.save();

    expect(usersService.create).not.toHaveBeenCalled();
  });

  it('loads the user in edit mode', async () => {
    const { comp, usersService } = await setup('5');

    expect(usersService.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Juan Pérez');
    expect(comp.formUsername).toBe('juan.perez');
    expect(comp.formRole).toBe('supervisor');
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the user in edit mode without changing the password', async () => {
    const { comp, usersService, navigate } = await setup('5');
    comp.formName = 'Juan P. Actualizado';

    comp.save();

    expect(usersService.update).toHaveBeenCalledWith(5, {
      name: 'Juan P. Actualizado',
      username: 'juan.perez',
      email: 'juan@empresa.com',
      role: 'supervisor',
    });
    expect(navigate).toHaveBeenCalledWith(['/admin/users']);
  });

  it('updates the user including a new password when provided', async () => {
    const { comp, usersService } = await setup('5');
    comp.formPassword = 'newpass123';
    comp.formPasswordConfirmation = 'newpass123';

    comp.save();

    expect(usersService.update).toHaveBeenCalledWith(5, {
      name: 'Juan Pérez',
      username: 'juan.perez',
      email: 'juan@empresa.com',
      role: 'supervisor',
      password: 'newpass123',
      password_confirmation: 'newpass123',
    });
  });

  it('shows the error message when saving fails', async () => {
    const { comp, usersService, toast } = await setup();
    usersService.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName = 'X';
    comp.formUsername = 'x';
    comp.formPassword = 'secret123';

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
