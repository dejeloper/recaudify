import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Seller } from '@core/interfaces/seller.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class SellersService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Seller[]>([]);
  readonly trashed = signal<Seller[]>([]);
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

  remove(seller: Seller) {
    return this.delete(seller.id).pipe(
      tap(() => {
        const removed = this.items().find((s) => s.id === seller.id)!;
        this.items.update((list) => list.filter((s) => s.id !== seller.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Vendedor "${seller.name}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el vendedor.');
        return EMPTY;
      }),
    );
  }

  restoreItem(seller: Seller) {
    return this.restore(seller.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((s) => s.id !== seller.id));
        this.items.update((list) => [...list, seller].sort((a, b) => a.name.localeCompare(b.name)));
        this.toast.success(`Vendedor "${seller.name}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el vendedor.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<Seller[]>('sellers');
  }
  getById(id: number) {
    return this.api.get<Seller>('sellers', String(id));
  }
  create(name: string, username: string | null, active: boolean) {
    return this.api.post<Seller>('sellers', undefined, { name, username, active });
  }
  update(id: number, name: string, username: string | null, active: boolean) {
    return this.api.put<Seller>('sellers', String(id), { name, username, active });
  }
  delete(id: number) {
    return this.api.delete('sellers', String(id));
  }
  getTrashed() {
    return this.api.get<Seller[]>('sellers', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('sellers', `${id}/restore`);
  }
}
