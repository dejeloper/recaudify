import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, map, tap } from 'rxjs';
import { Parameter } from '@core/interfaces/parameter.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class ParametersService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Parameter[]>([]);
  readonly trashed = signal<Parameter[]>([]);
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

  remove(parameter: Parameter) {
    return this.delete(parameter.id).pipe(
      tap(() => {
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

  getAll() {
    return this.api.get<Parameter[]>('parameters');
  }
  getById(id: number) {
    return this.api.get<Parameter>('parameters', String(id));
  }
  create(key: string, value: string, description: string | null) {
    return this.api.post<Parameter>('parameters', undefined, { key, value, description });
  }
  update(id: number, key: string, value: string, description: string | null) {
    return this.api.put<Parameter>('parameters', String(id), { key, value, description });
  }
  delete(id: number) {
    return this.api.delete('parameters', String(id));
  }
  getTrashed() {
    return this.api.get<Parameter[]>('parameters', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('parameters', `${id}/restore`);
  }
}
