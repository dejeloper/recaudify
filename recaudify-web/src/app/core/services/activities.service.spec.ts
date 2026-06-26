import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { Activity } from '@core/interfaces/activity.interface';
import { PaginationMeta } from '@core/interfaces/pagination.interface';
import { ActivitiesService } from '@core/services/activities.service';
import { ApiService } from '@core/services/api.service';

function activity(id: number): Activity {
  return {
    id,
    log_name: 'catalogos',
    event: 'created',
    description: 'creó',
    model: 'Product',
    model_label: 'producto',
    subject: { id, label: `P${id}` },
    causer: null,
    changes: [],
    created_at: '2026-06-25T10:00:00',
  };
}

function page(items: Activity[], meta: Partial<PaginationMeta>) {
  return { items, meta: { total: 0, page: 1, perPage: 25, lastPage: 1, ...meta } };
}

function setup() {
  const api = { getPaginated: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      ActivitiesService,
      { provide: ApiService, useValue: api },
    ],
  });

  return { service: TestBed.inject(ActivitiesService), api };
}

describe('ActivitiesService', () => {
  it('load sets items and meta from the first page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([activity(1)], { page: 1, lastPage: 2, total: 2 })));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.meta()?.page).toBe(1);
    expect(service.loading()).toBe(false);
  });

  it('hasMore reflects whether there are more pages', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([activity(1)], { page: 1, lastPage: 2 })));
    service.load();
    expect(service.hasMore()).toBe(true);

    api.getPaginated.mockReturnValue(of(page([activity(2)], { page: 2, lastPage: 2 })));
    service.loadMore();
    expect(service.hasMore()).toBe(false);
  });

  it('loadMore appends the next page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([activity(1)], { page: 1, lastPage: 2 })));
    service.load();

    api.getPaginated.mockReturnValue(of(page([activity(2)], { page: 2, lastPage: 2 })));
    service.loadMore();

    expect(service.items().map((a) => a.id)).toEqual([1, 2]);
    expect(service.meta()?.page).toBe(2);
  });

  it('loadMore does nothing when on the last page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([activity(1)], { page: 1, lastPage: 1 })));
    service.load();

    service.loadMore();

    expect(api.getPaginated).toHaveBeenCalledTimes(1); // no segunda llamada
  });
});
