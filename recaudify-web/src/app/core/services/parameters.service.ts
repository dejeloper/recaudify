import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, map, Observable, shareReplay, tap } from 'rxjs';
import { Parameter, ParameterFilters } from '@core/interfaces/parameter.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { PaginatedList } from '@core/utils/paginated-list';

@Injectable({ providedIn: 'root' })
export class ParametersService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);
  private readonly cache = new Map<string, Observable<Parameter[]>>();

  private readonly list = new PaginatedList<Parameter, ParameterFilters>(
    (page, perPage, filters) => {
      const params: Record<string, string | number> = { page, per_page: perPage };
      if (filters?.type) params['type'] = filters.type;
      if (filters?.search) params['search'] = filters.search;
      return this.api.getPaginated<Parameter>('parameters', undefined, params);
    },
    10,
  );

  readonly items = this.list.items;
  readonly meta = this.list.meta;
  readonly loading = this.list.loading;
  readonly trashed = signal<Parameter[]>([]);
  readonly loadingTrashed = signal(false);
  readonly showTrashed = signal(false);

  load(type?: string, search?: string): void {
    this.showTrashed.set(false);
    this.trashed.set([]);
    this.list.load({ type, search });
  }

  goToPage(page: number): void {
    this.list.goToPage(page);
  }

  toggleTrashed(): void {
    const next = !this.showTrashed();
    this.showTrashed.set(next);
    if (next && this.trashed().length === 0) {
      this.loadingTrashed.set(true);
      this.getTrashed().subscribe({
        next: (list) => {
          this.trashed.set(list);
          this.loadingTrashed.set(false);
        },
        error: () => this.loadingTrashed.set(false),
      });
    }
  }

  private clearCache(): void {
    this.cache.clear();
  }

  remove(parameter: Parameter) {
    return this.delete(parameter.id).pipe(
      tap(() => {
        this.clearCache();
        const removed = this.items().find((p) => p.id === parameter.id)!;
        this.items.update((list) => list.filter((p) => p.id !== parameter.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Parámetro "${parameter.key}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el parámetro.');
        return EMPTY;
      }),
    );
  }

  restoreItem(parameter: Parameter) {
    return this.restore(parameter.id).pipe(
      tap(() => {
        this.clearCache();
        this.trashed.update((list) => list.filter((p) => p.id !== parameter.id));
        this.items.update((list) =>
          [...list, parameter].sort((a, b) => a.key.localeCompare(b.key)),
        );
        this.toast.success(`Parámetro "${parameter.key}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el parámetro.');
        return EMPTY;
      }),
    );
  }

  getFlag(key: string) {
    return this.getAll().pipe(map((params) => params.find((p) => p.key === key)?.value === 'true'));
  }

  getConfigValue<T>(key: string) {
    return this.getAll('configuration').pipe(
      map((params) => {
        const param = params.find((p) => p.key === key);
        return param ? (param.typed_value as T) : null;
      }),
    );
  }

  getAll(type?: string): Observable<Parameter[]> {
    const key = type ?? '__all__';
    if (!this.cache.has(key)) {
      const params = type ? { type } : undefined;
      this.cache.set(
        key,
        this.api.get<Parameter[]>('parameters', undefined, params).pipe(shareReplay(1)),
      );
    }
    return this.cache.get(key)!;
  }

  getById(id: number) {
    return this.api.get<Parameter>('parameters', String(id));
  }

  getTrashed() {
    return this.api.get<Parameter[]>('parameters', 'trashed');
  }

  create(key: string, value: string, description: string | null, type: string, cast = 'string') {
    return this.api
      .post<Parameter>('parameters', undefined, {
        key,
        value,
        description,
        type,
        cast,
      })
      .pipe(tap(() => this.clearCache()));
  }

  update(id: number, value: string) {
    return this.api
      .put<Parameter>('parameters', String(id), { value })
      .pipe(tap(() => this.clearCache()));
  }

  delete(id: number) {
    return this.api.delete('parameters', String(id));
  }

  restore(id: number) {
    return this.api.post<void>('parameters', `${id}/restore`);
  }
}
