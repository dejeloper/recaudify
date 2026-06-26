import { inject, Injectable, signal } from '@angular/core';
import { LoginAudit, LoginAuditFilters } from '@core/interfaces/login-audit.interface';
import { PaginationMeta } from '@core/interfaces/pagination.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class LoginAuditsService {
  private readonly api = inject(ApiService);
  private static readonly PER_PAGE = 25;

  readonly items = signal<LoginAudit[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loading = signal(false);
  readonly loadingMore = signal(false);

  private filters: LoginAuditFilters = {};

  load(filters: LoginAuditFilters = {}): void {
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

  private fetch(page: number) {
    const params: Record<string, string | number> = {
      page,
      per_page: LoginAuditsService.PER_PAGE,
    };
    if (this.filters.status) params['status'] = this.filters.status;
    if (this.filters.user_id != null) params['user_id'] = this.filters.user_id;
    return this.api.getPaginated<LoginAudit>('login-audits', undefined, params);
  }
}
