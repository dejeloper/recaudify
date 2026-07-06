import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { Rate, RateInput } from '@core/interfaces/rate.interface';
import { ApiService } from '@core/services/api.service';
import { RatesService } from '@core/services/rates.service';
import { ToastService } from '@core/services/toast.service';

function rate(id: number, name: string): Rate {
  return {
    id,
    name,
    product_id: 1,
    product: null,
    value: 10000,
    installments: 12,
    installment_value: 850,
    discount: 0,
    active: true,
  };
}

function rateInput(overrides: Partial<RateInput> = {}): RateInput {
  return {
    name: 'Plan Básico',
    product_id: 1,
    value: 10000,
    installments: 12,
    installment_value: 850,
    discount: 0,
    active: true,
    ...overrides,
  };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      RatesService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(RatesService), api, toast };
}

describe('RatesService', () => {
  it('load populates items and clears loading', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([rate(1, 'Plan Básico')]));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.loading()).toBe(false);
  });

  it('toggleTrashed fetches trashed once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([]));
    service.load();
    api.get.mockReturnValueOnce(of([rate(2, 'Eliminada')]));

    service.toggleTrashed();

    expect(service.showTrashed()).toBe(true);
    expect(service.trashed()).toHaveLength(1);
  });

  it('remove optimistically moves the item to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([rate(1, 'Plan Básico')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(rate(1, 'Plan Básico')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('remove keeps state and toasts error on failure', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([rate(1, 'Plan Básico')]));
    service.load();
    api.delete.mockReturnValue(throwError(() => new Error('fail')));

    service.remove(rate(1, 'Plan Básico')).subscribe();

    expect(service.items()).toHaveLength(1);
    expect(toast.error).toHaveBeenCalled();
  });

  it('restoreItem moves the item back to the list', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(rate(5, 'Restaurable')).subscribe();

    expect(service.items().some((r) => r.id === 5)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts the rate input payload', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(rate(9, 'Nueva')));

    service.create(rateInput()).subscribe();

    expect(api.post).toHaveBeenCalledWith('rates', undefined, rateInput());
  });

  it('update puts the rate input payload', () => {
    const { service, api } = setup();
    api.put.mockReturnValue(of(rate(9, 'Actualizada')));

    service.update(9, rateInput({ name: 'Actualizada' })).subscribe();

    expect(api.put).toHaveBeenCalledWith('rates', '9', rateInput({ name: 'Actualizada' }));
  });

  it('delete calls the api with the id', () => {
    const { service, api } = setup();
    api.delete.mockReturnValue(of(undefined));

    service.delete(3).subscribe();

    expect(api.delete).toHaveBeenCalledWith('rates', '3');
  });

  it('restore posts to the restore action', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restore(4).subscribe();

    expect(api.post).toHaveBeenCalledWith('rates', '4/restore');
  });
});
