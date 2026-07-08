import { inject, Injectable } from '@angular/core';
import { LoginAudit, LoginAuditFilters } from '@core/interfaces/login-audit.interface';
import { ApiService } from '@core/services/api.service';
import { PaginatedList } from '@core/utils/paginated-list';

@Injectable({ providedIn: 'root' })
export class LoginAuditsService {
  private readonly api = inject(ApiService);

  private readonly list = new PaginatedList<LoginAudit, LoginAuditFilters>(
    (page, perPage, filters) => {
      const params: Record<string, string | number> = { page, per_page: perPage };
      if (filters?.status) params['status'] = filters.status;
      if (filters?.user_id != null) params['user_id'] = filters.user_id;
      return this.api.getPaginated<LoginAudit>('login-audits', undefined, params);
    },
    25,
  );

  readonly items = this.list.items;
  readonly meta = this.list.meta;
  readonly loading = this.list.loading;
  readonly loadingMore = this.list.loadingMore;

  load(filters: LoginAuditFilters = {}): void {
    this.list.load(filters);
  }

  loadMore(): void {
    this.list.loadMore();
  }

  readonly hasMore = (): boolean => this.list.hasMore();
}
