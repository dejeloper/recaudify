import { inject, Injectable } from '@angular/core';
import { Activity, ActivityFilters } from '@core/interfaces/activity.interface';
import { ApiService } from '@core/services/api.service';
import { PaginatedList } from '@core/utils/paginated-list';

@Injectable({ providedIn: 'root' })
export class ActivitiesService {
  private readonly api = inject(ApiService);

  private readonly list = new PaginatedList<Activity, ActivityFilters>((page, perPage, filters) => {
    const params: Record<string, string | number> = { page, per_page: perPage };
    if (filters?.model) params['model'] = filters.model;
    if (filters?.subject_id != null) params['subject_id'] = filters.subject_id;
    if (filters?.causer_id != null) params['causer_id'] = filters.causer_id;
    if (filters?.user) params['user'] = filters.user;
    return this.api.getPaginated<Activity>('activities', undefined, params);
  }, 25);

  readonly items = this.list.items;
  readonly meta = this.list.meta;
  readonly loading = this.list.loading;
  readonly loadingMore = this.list.loadingMore;

  /** Carga la primera página (reemplaza el listado). */
  load(filters: ActivityFilters = {}): void {
    this.list.load(filters);
  }

  /** Carga la siguiente página y la agrega al listado. */
  loadMore(): void {
    this.list.loadMore();
  }

  readonly hasMore = (): boolean => this.list.hasMore();
}
