import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { Seller } from '@core/interfaces/seller.interface';
import { ApiService } from '@core/services/api.service';
import { SellersService } from '@core/services/sellers.service';
import { ToastService } from '@core/services/toast.service';

function seller(id: number, name: string): Seller {
  return { id, name, username: `user${id}`, active: true };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      SellersService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(SellersService), api, toast };
}

describe('SellersService', () => {
  it('load populates items and clears loading', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([seller(1, 'Carlos')]));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.loading()).toBe(false);
  });

  it('toggleTrashed fetches trashed once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([]));
    service.load();
    api.get.mockReturnValueOnce(of([seller(2, 'Eliminado')]));

    service.toggleTrashed();

    expect(service.showTrashed()).toBe(true);
    expect(service.trashed()).toHaveLength(1);
  });

  it('remove optimistically moves the item to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([seller(1, 'Carlos')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(seller(1, 'Carlos')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('remove keeps state and toasts error on failure', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([seller(1, 'Carlos')]));
    service.load();
    api.delete.mockReturnValue(throwError(() => new Error('fail')));

    service.remove(seller(1, 'Carlos')).subscribe();

    expect(service.items()).toHaveLength(1);
    expect(toast.error).toHaveBeenCalled();
  });

  it('restoreItem moves the item back to the list', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(seller(5, 'Restaurable')).subscribe();

    expect(service.items().some((s) => s.id === 5)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts the seller payload', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(seller(9, 'Nuevo')));

    service.create('Nuevo', 'nuevo1', true).subscribe();

    expect(api.post).toHaveBeenCalledWith('sellers', undefined, {
      name: 'Nuevo',
      username: 'nuevo1',
      active: true,
    });
  });

  it('update puts the seller payload', () => {
    const { service, api } = setup();
    api.put.mockReturnValue(of(seller(9, 'Actualizado')));

    service.update(9, 'Actualizado', null, false).subscribe();

    expect(api.put).toHaveBeenCalledWith('sellers', '9', {
      name: 'Actualizado',
      username: null,
      active: false,
    });
  });

  it('delete calls the api with the id', () => {
    const { service, api } = setup();
    api.delete.mockReturnValue(of(undefined));

    service.delete(3).subscribe();

    expect(api.delete).toHaveBeenCalledWith('sellers', '3');
  });

  it('restore posts to the restore action', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restore(4).subscribe();

    expect(api.post).toHaveBeenCalledWith('sellers', '4/restore');
  });
});
