import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Product } from '@core/interfaces/product.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class ProductsService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Product[]>([]);
  readonly trashed = signal<Product[]>([]);
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

  remove(product: Product) {
    return this.delete(product.id).pipe(
      tap(() => {
        const removed = this.items().find((p) => p.id === product.id)!;
        this.items.update((list) => list.filter((p) => p.id !== product.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Producto "${product.name}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el producto.');
        return EMPTY;
      }),
    );
  }

  restoreItem(product: Product) {
    return this.restore(product.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((p) => p.id !== product.id));
        this.items.update((list) =>
          [...list, product].sort((a, b) => a.name.localeCompare(b.name)),
        );
        this.toast.success(`Producto "${product.name}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el producto.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<Product[]>('products');
  }
  getById(id: number) {
    return this.api.get<Product>('products', String(id));
  }
  create(name: string, value: number, active: boolean) {
    return this.api.post<Product>('products', undefined, { name, value, active });
  }
  update(id: number, name: string, value: number, active: boolean) {
    return this.api.put<Product>('products', String(id), { name, value, active });
  }
  delete(id: number) {
    return this.api.delete('products', String(id));
  }
  getTrashed() {
    return this.api.get<Product[]>('products', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('products', `${id}/restore`);
  }
}
