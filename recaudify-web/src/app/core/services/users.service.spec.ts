import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { User } from '@core/interfaces/user.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { UsersService } from '@core/services/users.service';

function user(id: number, name: string, roles: string[] = []): User {
  return {
    id,
    name,
    username: name.toLowerCase(),
    email: null,
    active: true,
    roles,
    permissions: [],
  } as User;
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      UsersService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(UsersService), api, toast };
}

describe('UsersService', () => {
  it('load populates items', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([user(1, 'Ana')]));
    service.load();
    expect(service.items()).toHaveLength(1);
  });

  it('toggleDisabled fetches disabled users once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([])); // load
    service.load();
    api.get.mockReturnValueOnce(of([user(2, 'Inactivo')])); // getDisabled

    service.toggleDisabled();

    expect(service.showDisabled()).toBe(true);
    expect(service.disabled()).toHaveLength(1);
  });

  it('remove moves the user to disabled and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([user(1, 'Ana')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(user(1, 'Ana')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.disabled()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('restoreItem reactivates the user', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(user(3, 'Beto')).subscribe();

    expect(service.items().some((u) => u.id === 3)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('roleLabel returns the first role or a dash', () => {
    const { service } = setup();
    expect(service.roleLabel(user(1, 'Ana', ['cobrador']))).toBe('cobrador');
    expect(service.roleLabel(user(2, 'Beto', []))).toBe('—');
  });

  it('create posts the payload', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(user(9, 'Nuevo')));
    const payload = {
      name: 'Nuevo',
      username: 'nuevo',
      email: null,
      password: 'secret1234',
      role: 'cobrador',
    };

    service.create(payload).subscribe();

    expect(api.post).toHaveBeenCalledWith('users', undefined, payload);
  });

  it('resetPassword posts to the reset-password endpoint and returns the new password', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of({ password: 'newpass123' }));

    let result: { password: string } | undefined;
    service.resetPassword(5).subscribe((res) => (result = res));

    expect(api.post).toHaveBeenCalledWith('users', '5/reset-password');
    expect(result).toEqual({ password: 'newpass123' });
  });

  it('remove toasts error and keeps state on failure', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([user(1, 'Ana')]));
    service.load();
    api.delete.mockReturnValue(throwError(() => new Error('x')));

    service.remove(user(1, 'Ana')).subscribe();

    expect(service.items()).toHaveLength(1);
    expect(toast.error).toHaveBeenCalled();
  });
});
