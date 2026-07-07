import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { MenuItem } from '@core/interfaces/nav.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

export interface MenuItemPayload {
  parent_id: number | null;
  label: string;
  icons: string[] | null;
  route: string | null;
  permission: string | null;
  order: number;
  is_active: boolean;
}

@Injectable({ providedIn: 'root' })
export class MenuItemsService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<MenuItem[]>([]);
  readonly trashed = signal<MenuItem[]>([]);
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

  remove(item: MenuItem) {
    return this.delete(item.id).pipe(
      tap(() => {
        const removed = this.items().find((i) => i.id === item.id)!;
        this.items.update((list) => list.filter((i) => i.id !== item.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Ítem "${item.label}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el ítem.');
        return EMPTY;
      }),
    );
  }

  restoreItem(item: MenuItem) {
    return this.restore(item.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((i) => i.id !== item.id));
        this.items.update((list) => [...list, item].sort((a, b) => a.order - b.order));
        this.toast.success(`Ítem "${item.label}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el ítem.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<MenuItem[]>('menu-items');
  }
  getById(id: number) {
    return this.api.get<MenuItem>('menu-items', String(id));
  }
  create(payload: MenuItemPayload) {
    return this.api.post<MenuItem>('menu-items', undefined, { ...payload });
  }
  update(id: number, payload: Partial<MenuItemPayload>) {
    return this.api.put<MenuItem>('menu-items', String(id), { ...payload });
  }
  delete(id: number) {
    return this.api.delete('menu-items', String(id));
  }
  getTrashed() {
    return this.api.get<MenuItem[]>('menu-items', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('menu-items', `${id}/restore`);
  }
}
