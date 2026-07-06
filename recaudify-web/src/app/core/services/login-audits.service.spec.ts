import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { LoginAudit } from '@core/interfaces/login-audit.interface';
import { Paginated } from '@core/interfaces/pagination.interface';
import { ApiService } from '@core/services/api.service';
import { LoginAuditsService } from '@core/services/login-audits.service';

function audit(id: number): LoginAudit {
  return {
    id,
    username: 'jperez',
    user: { id: 1, name: 'Juan Perez' },
    status: 'success',
    reason: null,
    ip_address: '127.0.0.1',
    os: { name: 'Windows', version: '10.0' },
    device_type: 'desktop',
    geolocation: null,
    logged_at: '2026-01-01T00:00:00Z',
  };
}

function page(
  items: LoginAudit[],
  meta: Partial<Paginated<LoginAudit>['meta']> = {},
): Paginated<LoginAudit> {
  return {
    items,
    meta: { total: items.length, page: 1, perPage: 25, lastPage: 1, ...meta },
  };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn(), getPaginated: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      LoginAuditsService,
      { provide: ApiService, useValue: api },
    ],
  });

  return { service: TestBed.inject(LoginAuditsService), api };
}

describe('LoginAuditsService', () => {
  it('load populates items and meta, and clears loading', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([audit(1)])));

    service.load();

    expect(service.items()).toHaveLength(1);
    expect(service.meta()?.total).toBe(1);
    expect(service.loading()).toBe(false);
  });

  it('load requests page 1 with default per_page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([])));

    service.load();

    expect(api.getPaginated).toHaveBeenCalledWith('login-audits', undefined, {
      page: 1,
      per_page: 25,
    });
  });

  it('load includes status and user_id filters when provided', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([])));

    service.load({ status: 'failed', user_id: 7 });

    expect(api.getPaginated).toHaveBeenCalledWith('login-audits', undefined, {
      page: 1,
      per_page: 25,
      status: 'failed',
      user_id: 7,
    });
  });

  it('loadMore appends items and advances the page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([audit(1)], { page: 1, lastPage: 2 })));
    service.load();

    api.getPaginated.mockReturnValue(of(page([audit(2)], { page: 2, lastPage: 2 })));
    service.loadMore();

    expect(service.items()).toHaveLength(2);
    expect(service.meta()?.page).toBe(2);
    expect(service.loadingMore()).toBe(false);
  });

  it('loadMore does nothing when there is no next page', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([audit(1)], { page: 1, lastPage: 1 })));
    service.load();

    api.getPaginated.mockClear();
    service.loadMore();

    expect(api.getPaginated).not.toHaveBeenCalled();
  });

  it('hasMore reflects whether another page is available', () => {
    const { service, api } = setup();
    api.getPaginated.mockReturnValue(of(page([audit(1)], { page: 1, lastPage: 2 })));
    service.load();

    expect(service.hasMore()).toBe(true);
  });

  it('hasMore is false when there is no meta yet', () => {
    const { service } = setup();
    expect(service.hasMore()).toBe(false);
  });
});
