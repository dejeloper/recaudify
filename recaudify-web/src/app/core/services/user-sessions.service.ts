import { inject, Injectable, signal } from '@angular/core';
import { SessionFilters, UserSession } from '@core/interfaces/user-session.interface';
import { PaginationMeta } from '@core/interfaces/pagination.interface';
import { ApiService } from '@core/services/api.service';
import { tap } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class UserSessionsService {
  private readonly api = inject(ApiService);
  private static readonly PER_PAGE = 25;

  readonly sessions = signal<UserSession[]>([]);
  readonly loading = signal(false);

  readonly items = signal<UserSession[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loadingMore = signal(false);

  private filters: SessionFilters = {};

  loadMine() {
    this.loading.set(true);
    return this.api.get<UserSession[]>('auth', 'sessions').pipe(
      tap({
        next: (sessions) => {
          this.sessions.set(sessions);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      }),
    );
  }

  revokeMine(id: number) {
    return this.api.post<void>('auth', `sessions/${id}/revoke`);
  }

  revokeAllMine() {
    return this.api.post<void>('auth', 'sessions/revoke-all');
  }

  loadAll(filters: SessionFilters = {}): void {
    this.filters = filters;
    this.loading.set(true);
    this.fetch(1).subscribe({
      next: (page) => {
        this.items.set(page.items);
        this.meta.set(page.meta);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  loadMore(): void {
    const meta = this.meta();
    if (!meta || meta.page >= meta.lastPage || this.loadingMore()) return;

    this.loadingMore.set(true);
    this.fetch(meta.page + 1).subscribe({
      next: (page) => {
        this.items.update((list) => [...list, ...page.items]);
        this.meta.set(page.meta);
        this.loadingMore.set(false);
      },
      error: () => this.loadingMore.set(false),
    });
  }

  readonly hasMore = () => {
    const meta = this.meta();
    return !!meta && meta.page < meta.lastPage;
  };

  revoke(id: number) {
    return this.api.post<void>('sessions', `${id}/revoke`);
  }

  revokeAllGlobal() {
    return this.api.post<void>('sessions', 'revoke-all');
  }

  private fetch(page: number) {
    const params: Record<string, string | number> = {
      page,
      per_page: UserSessionsService.PER_PAGE,
    };
    if (this.filters.user_id != null) params['user_id'] = this.filters.user_id;
    if (this.filters.device_type) params['device_type'] = this.filters.device_type;
    if (this.filters.ip_address) params['ip_address'] = this.filters.ip_address;
    return this.api.getPaginated<UserSession>('sessions', undefined, params);
  }
}
