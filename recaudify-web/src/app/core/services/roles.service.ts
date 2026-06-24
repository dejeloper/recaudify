import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Role } from '@core/interfaces/role.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class RolesService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Role[]>([]);
  readonly trashed = signal<Role[]>([]);
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
