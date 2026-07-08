import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { ToastService } from '@core/services/toast.service';
import { UsersService } from '@core/services/users.service';
import { Users } from './users';

const sample: User = {
  id: 1,
  name: 'Juan Perez',
  username: 'jperez',
  email: 'juan@example.com',
  active: true,
  roles: ['admin'],
  permissions: [],
};

async function setup(permissions: string[] = []) {
  const service = {
    items: signal<User[]>([]),
    disabled: signal<User[]>([]),
    loading: signal(false),
    loadingDisabled: signal(false),
    showDisabled: signal(false),
    load: vi.fn(),
    search: vi.fn(),
    toggleDisabled: vi.fn(),
    roleLabel: vi.fn((user: User) => user.roles[0] ?? '—'),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
    resetPassword: vi.fn().mockReturnValue(of({ password: 'secret123' })),
  };

  const authService = {
    hasPermission: vi.fn((permission: string) => permissions.includes(permission)),
  };

  const toast = {
    success: vi.fn(),
    error: vi.fn(),
  };

  await TestBed.configureTestingModule({
    imports: [Users],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: UsersService, useValue: service },
      { provide: AuthService, useValue: authService },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Users);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service, authService, toast };
}

describe('Users (list)', () => {
  it('loads users on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalled();
  });

  it('onSearch debounces and delegates search to the service', async () => {
    vi.useFakeTimers();
    const { comp, service } = await setup();

    comp.onSearch('juan');
    expect(comp.searchTerm()).toBe('juan');
    expect(service.search).not.toHaveBeenCalled();

    vi.advanceTimersByTime(500);
    expect(service.search).toHaveBeenCalledWith('juan');
    vi.useRealTimers();
  });

  it('toggleDisabled delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.toggleDisabled();
    expect(service.toggleDisabled).toHaveBeenCalled();
  });

  it('roleLabel delegates to the service', async () => {
    const { comp, service } = await setup();
    expect(comp.roleLabel(sample)).toBe('admin');
    expect(service.roleLabel).toHaveBeenCalledWith(sample);
  });

  it('delete calls remove when confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(true);

    comp.delete(sample);

    expect(service.remove).toHaveBeenCalledWith(sample);
  });

  it('delete does nothing when not confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    comp.delete(sample);

    expect(service.remove).not.toHaveBeenCalled();
  });

  it('restore delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.restore(sample);
    expect(service.restoreItem).toHaveBeenCalledWith(sample);
  });

  it('resetPassword sets the generated password and shows a toast when confirmed', async () => {
    const { comp, service, toast } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(true);

    comp.resetPassword(sample);

    expect(service.resetPassword).toHaveBeenCalledWith(sample.id);
    expect(comp.generatedPassword()).toEqual({ user: sample, password: 'secret123' });
    expect(toast.success).toHaveBeenCalled();
  });

  it('resetPassword does nothing when not confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    comp.resetPassword(sample);

    expect(service.resetPassword).not.toHaveBeenCalled();
  });

  it('closePasswordModal clears the generated password', async () => {
    const { comp } = await setup();
    comp.generatedPassword.set({ user: sample, password: 'secret123' });

    comp.closePasswordModal();

    expect(comp.generatedPassword()).toBeNull();
  });

  it('computes permission flags from AuthService', async () => {
    const { comp } = await setup([
      'users.create',
      'users.edit',
      'users.deactivate',
      'users.restore',
      'users.reset-password',
    ]);

    expect(comp.canCreate()).toBe(true);
    expect(comp.canEdit()).toBe(true);
    expect(comp.canDelete()).toBe(true);
    expect(comp.canRestore()).toBe(true);
    expect(comp.canResetPassword()).toBe(true);
  });

  it('permission flags are false when the user lacks permissions', async () => {
    const { comp } = await setup([]);

    expect(comp.canCreate()).toBe(false);
    expect(comp.canEdit()).toBe(false);
    expect(comp.canDelete()).toBe(false);
    expect(comp.canRestore()).toBe(false);
    expect(comp.canResetPassword()).toBe(false);
  });
});
