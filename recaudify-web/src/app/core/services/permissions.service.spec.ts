import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { Permission } from '@core/interfaces/permission.interface';
import { ApiService } from '@core/services/api.service';
import { PermissionsService } from '@core/services/permissions.service';
import { ToastService } from '@core/services/toast.service';

function permission(id: number, name: string): Permission {
  return { id, name, guard_name: 'api' };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      PermissionsService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(PermissionsService), api, toast };
}

describe('PermissionsService', () => {
  it('load populates items', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([permission(1, 'clientes.ver')]));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.loading()).toBe(false);
  });

  it('toggleTrashed fetches trashed once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([]));
    service.load();
    api.get.mockReturnValueOnce(of([permission(2, 'clientes.crear')]));

    service.toggleTrashed();

    expect(service.showTrashed()).toBe(true);
    expect(service.trashed()).toHaveLength(1);
  });

  it('remove moves the permission to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([permission(1, 'clientes.ver')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(permission(1, 'clientes.ver')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('restoreItem moves the permission back', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(permission(3, 'clientes.editar')).subscribe();

    expect(service.items().some((p) => p.id === 3)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('grouped computes permissions grouped by module', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(
      of([permission(1, 'clientes.ver'), permission(2, 'ventas.crear'), permission(3, 'clientes.crear')]),
    );

    service.load();

    expect(service.grouped()).toEqual([
      { module: 'clientes', perms: [permission(1, 'clientes.ver'), permission(3, 'clientes.crear')] },
      { module: 'ventas', perms: [permission(2, 'ventas.crear')] },
    ]);
  });

  it('groupByModule groups permissions and sorts by module name', () => {
    const { service } = setup();

    const result = service.groupByModule([
      permission(1, 'ventas.ver'),
      permission(2, 'clientes.ver'),
    ]);

    expect(result.map((g) => g.module)).toEqual(['clientes', 'ventas']);
  });

  it('isValidName accepts a valid module.action pattern', () => {
    const { service } = setup();
    expect(service.isValidName('clientes.ver')).toBe(true);
  });

  it('isValidName rejects an invalid pattern', () => {
    const { service } = setup();
    expect(service.isValidName('ClientesVer')).toBe(false);
    expect(service.isValidName('clientes')).toBe(false);
  });

  it('actionLabel returns the action segment of the name', () => {
    const { service } = setup();
    expect(service.actionLabel('clientes.ver')).toBe('ver');
  });

  it('actionLabel returns the full name when there is no dot', () => {
    const { service } = setup();
    expect(service.actionLabel('clientes')).toBe('clientes');
  });

  it('create posts the permission name', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(permission(9, 'reportes.ver')));

    service.create('reportes.ver').subscribe();

    expect(api.post).toHaveBeenCalledWith('permissions', undefined, { name: 'reportes.ver' });
  });

  it('update puts the permission name', () => {
    const { service, api } = setup();
    api.put.mockReturnValue(of(permission(9, 'reportes.editar')));

    service.update(9, 'reportes.editar').subscribe();

    expect(api.put).toHaveBeenCalledWith('permissions', '9', { name: 'reportes.editar' });
  });
});
