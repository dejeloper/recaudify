import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Rate, RateInput } from '@core/interfaces/rate.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class RatesService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Rate[]>([]);
  readonly trashed = signal<Rate[]>([]);
  readonly loading = signal(false);
  readonly loadingTrashed = signal(false);
  readonly showTrashed = signal(false);

  load(): void {
    this.loading.set(true);
    this.showTrashed.set(false);
    this.trashed.set([]);
    this.getAll().subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
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

  remove(rate: Rate) {
    return this.delete(rate.id).pipe(
      tap(() => {
        const removed = this.items().find((r) => r.id === rate.id)!;
        this.items.update((list) => list.filter((r) => r.id !== rate.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Tarifa "${rate.name}" eliminada.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar la tarifa.');
        return EMPTY;
      }),
    );
  }

  restoreItem(rate: Rate) {
    return this.restore(rate.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((r) => r.id !== rate.id));
        this.items.update((list) => [...list, rate].sort((a, b) => a.name.localeCompare(b.name)));
        this.toast.success(`Tarifa "${rate.name}" restaurada.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar la tarifa.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<Rate[]>('rates');
  }
  getById(id: number) {
    return this.api.get<Rate>('rates', String(id));
  }
  create(input: RateInput) {
    return this.api.post<Rate>('rates', undefined, { ...input });
  }
  update(id: number, input: RateInput) {
    return this.api.put<Rate>('rates', String(id), { ...input });
  }
  delete(id: number) {
    return this.api.delete('rates', String(id));
  }
  getTrashed() {
    return this.api.get<Rate[]>('rates', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('rates', `${id}/restore`);
  }
}
