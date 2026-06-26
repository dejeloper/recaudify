import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { Product } from '@core/interfaces/product.interface';
import { ApiService } from '@core/services/api.service';
import { ProductsService } from '@core/services/products.service';
import { ToastService } from '@core/services/toast.service';

function product(id: number, name: string): Product {
  return { id, name, value: 1000, active: true };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      ProductsService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(ProductsService), api, toast };
}

describe('ProductsService', () => {
  it('load populates items and clears loading', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([product(1, 'Biblia')]));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.loading()).toBe(false);
  });

  it('toggleTrashed fetches trashed once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([])); // load()
    service.load();
    api.get.mockReturnValueOnce(of([product(2, 'Eliminado')])); // getTrashed()

    service.toggleTrashed();

    expect(service.showTrashed()).toBe(true);
    expect(service.trashed()).toHaveLength(1);
  });

  it('remove optimistically moves the item to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([product(1, 'Biblia')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(product(1, 'Biblia')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('remove keeps state and toasts error on failure', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([product(1, 'Biblia')]));
    service.load();
    api.delete.mockReturnValue(throwError(() => new Error('fail')));

    service.remove(product(1, 'Biblia')).subscribe();

    expect(service.items()).toHaveLength(1);
    expect(toast.error).toHaveBeenCalled();
  });

  it('restoreItem moves the item back to the list', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(product(5, 'Restaurable')).subscribe();

    expect(service.items().some((p) => p.id === 5)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts the product payload', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(product(9, 'Nuevo')));

    service.create('Nuevo', 5000, true).subscribe();

    expect(api.post).toHaveBeenCalledWith('products', undefined, {
      name: 'Nuevo',
      value: 5000,
      active: true,
    });
  });
});
