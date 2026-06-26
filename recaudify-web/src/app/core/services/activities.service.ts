import { inject, Injectable, signal } from '@angular/core';
import { Activity, ActivityFilters } from '@core/interfaces/activity.interface';
import { PaginationMeta } from '@core/interfaces/pagination.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class ActivitiesService {
  private readonly api = inject(ApiService);
  private static readonly PER_PAGE = 25;

  readonly items = signal<Activity[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loading = signal(false);
  readonly loadingMore = signal(false);

  private filters: ActivityFilters = {};

  /** Carga la primera página (reemplaza el listado). */
  load(filters: ActivityFilters = {}): void {
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

  /** Carga la siguiente página y la agrega al listado. */
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
      per_page: ActivitiesService.PER_PAGE,
    };
    if (this.filters.model) params['model'] = this.filters.model;
    if (this.filters.subject_id != null) params['subject_id'] = this.filters.subject_id;
    if (this.filters.causer_id != null) params['causer_id'] = this.filters.causer_id;
    return this.api.getPaginated<Activity>('activities', undefined, params);
  }
}
