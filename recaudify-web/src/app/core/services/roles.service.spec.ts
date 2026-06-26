import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { Role } from '@core/interfaces/role.interface';
import { ApiService } from '@core/services/api.service';
import { RolesService } from '@core/services/roles.service';
import { ToastService } from '@core/services/toast.service';

function role(id: number, name: string): Role {
  return { id, name, guard_name: 'api', permissions: [] } as Role;
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      RolesService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(RolesService), api, toast };
}

describe('RolesService', () => {
  it('load populates items', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([role(1, 'cobrador')]));
    service.load();
    expect(service.items()).toHaveLength(1);
  });

  it('remove moves the role to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([role(1, 'cobrador')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(role(1, 'cobrador')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('restoreItem moves the role back', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(role(2, 'gestor')).subscribe();

    expect(service.items().some((r) => r.id === 2)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts name and permissions', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(role(3, 'nuevo')));

    service.create('nuevo', ['clientes.ver']).subscribe();

    expect(api.post).toHaveBeenCalledWith('roles', undefined, {
      name: 'nuevo',
      permissions: ['clientes.ver'],
    });
  });
});
