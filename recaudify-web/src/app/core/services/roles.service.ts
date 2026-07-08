import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Role, RoleFilters } from '@core/interfaces/role.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';
import { PaginatedList } from '@core/utils/paginated-list';

@Injectable({ providedIn: 'root' })
export class RolesService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  private readonly list = new PaginatedList<Role, RoleFilters>((page, perPage, filters) => {
    const params: Record<string, string | number> = { page, per_page: perPage };
    if (filters?.search) params['search'] = filters.search;
    return this.api.getPaginated<Role>('roles', undefined, params);
  }, 10);

  readonly items = this.list.items;
  readonly meta = this.list.meta;
  readonly loading = this.list.loading;
  readonly trashed = signal<Role[]>([]);
  readonly loadingTrashed = signal(false);
  readonly showTrashed = signal(false);

  load(search?: string): void {
    this.showTrashed.set(false);
    this.trashed.set([]);
    this.list.load({ search });
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

  remove(role: Role) {
    return this.delete(role.id).pipe(
      tap(() => {
        const removed = this.items().find((r) => r.id === role.id)!;
        this.items.update((list) => list.filter((r) => r.id !== role.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Rol "${role.name}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el rol.');
        return EMPTY;
      }),
    );
  }

  restoreItem(role: Role) {
    return this.restore(role.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((r) => r.id !== role.id));
        this.items.update((list) => [...list, role].sort((a, b) => a.name.localeCompare(b.name)));
        this.toast.success(`Rol "${role.name}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el rol.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<Role[]>('roles');
  }
  getById(id: number) {
    return this.api.get<Role>('roles', String(id));
  }
  create(name: string, permissions: string[]) {
    return this.api.post<Role>('roles', undefined, { name, permissions });
  }
  update(id: number, name: string, permissions: string[]) {
    return this.api.put<Role>('roles', String(id), { name, permissions });
  }
  delete(id: number) {
    return this.api.delete('roles', String(id));
  }
  getTrashed() {
    return this.api.get<Role[]>('roles', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('roles', `${id}/restore`);
  }
}
