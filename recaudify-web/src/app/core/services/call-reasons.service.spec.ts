import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { CallReason } from '@core/interfaces/call-reason.interface';
import { ApiService } from '@core/services/api.service';
import { CallReasonsService } from '@core/services/call-reasons.service';
import { ToastService } from '@core/services/toast.service';

function reason(id: number, name: string): CallReason {
  return { id, name, color: '#ff0000', active: true };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      CallReasonsService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(CallReasonsService), api, toast };
}

describe('CallReasonsService', () => {
  it('load populates items and clears loading', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([reason(1, 'No contesta')]));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.loading()).toBe(false);
  });

  it('toggleTrashed fetches trashed once', () => {
    const { service, api } = setup();
    api.get.mockReturnValueOnce(of([]));
    service.load();
    api.get.mockReturnValueOnce(of([reason(2, 'Eliminado')]));

    service.toggleTrashed();

    expect(service.showTrashed()).toBe(true);
    expect(service.trashed()).toHaveLength(1);
  });

  it('remove optimistically moves the item to trashed and toasts', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([reason(1, 'No contesta')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(reason(1, 'No contesta')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('remove keeps state and toasts error on failure', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([reason(1, 'No contesta')]));
    service.load();
    api.delete.mockReturnValue(throwError(() => new Error('fail')));

    service.remove(reason(1, 'No contesta')).subscribe();

    expect(service.items()).toHaveLength(1);
    expect(toast.error).toHaveBeenCalled();
  });

  it('restoreItem moves the item back to the list', () => {
    const { service, api, toast } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restoreItem(reason(5, 'Restaurable')).subscribe();

    expect(service.items().some((r) => r.id === 5)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts the call reason payload', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(reason(9, 'Nuevo')));

    service.create('Nuevo', '#00ff00', true).subscribe();

    expect(api.post).toHaveBeenCalledWith('call-reasons', undefined, {
      name: 'Nuevo',
      color: '#00ff00',
      active: true,
    });
  });

  it('update puts the call reason payload', () => {
    const { service, api } = setup();
    api.put.mockReturnValue(of(reason(9, 'Actualizado')));

    service.update(9, 'Actualizado', null, false).subscribe();

    expect(api.put).toHaveBeenCalledWith('call-reasons', '9', {
      name: 'Actualizado',
      color: null,
      active: false,
    });
  });

  it('delete calls the api with the id', () => {
    const { service, api } = setup();
    api.delete.mockReturnValue(of(undefined));

    service.delete(3).subscribe();

    expect(api.delete).toHaveBeenCalledWith('call-reasons', '3');
  });

  it('restore posts to the restore action', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(undefined));

    service.restore(4).subscribe();

    expect(api.post).toHaveBeenCalledWith('call-reasons', '4/restore');
  });
});
