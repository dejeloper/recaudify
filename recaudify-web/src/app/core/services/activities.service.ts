import { inject, Injectable, signal } from '@angular/core';
import { Activity, ActivityFilters } from '@core/interfaces/activity.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class ActivitiesService {
  private readonly api = inject(ApiService);

  readonly items = signal<Activity[]>([]);
  readonly loading = signal(false);

  load(filters: ActivityFilters = {}): void {
    this.loading.set(true);
    this.getAll(filters).subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  getAll(filters: ActivityFilters = {}) {
    const params: Record<string, string | number> = {};
    if (filters.model) params['model'] = filters.model;
    if (filters.subject_id != null) params['subject_id'] = filters.subject_id;
    if (filters.causer_id != null) params['causer_id'] = filters.causer_id;
    return this.api.get<Activity[]>('activities', undefined, params);
  }
}
