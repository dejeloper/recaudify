import { computed, inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Permission } from '@core/interfaces/permission.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

const NAME_PATTERN = /^[a-z_]+\.[a-z_-]+$/;

@Injectable({ providedIn: 'root' })
export class PermissionsService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<Permission[]>([]);
  readonly trashed = signal<Permission[]>([]);
  readonly loading = signal(false);
  readonly loadingTrashed = signal(false);
  readonly showTrashed = signal(false);

  readonly grouped = computed(() => this.groupByModule(this.items()));
  readonly groupedTrashed = computed(() => this.groupByModule(this.trashed()));

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

  remove(permission: Permission) {
    return this.delete(permission.id).pipe(
      tap(() => {
        const removed = this.items().find((p) => p.id === permission.id)!;
        this.items.update((list) => list.filter((p) => p.id !== permission.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Permiso "${permission.name}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el permiso.');
        return EMPTY;
      }),
    );
  }

  restoreItem(permission: Permission) {
    return this.restore(permission.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((p) => p.id !== permission.id));
        this.items.update((list) =>
          [...list, permission].sort((a, b) => a.name.localeCompare(b.name)),
        );
        this.toast.success(`Permiso "${permission.name}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el permiso.');
        return EMPTY;
      }),
    );
  }

  groupByModule(perms: Permission[]): { module: string; perms: Permission[] }[] {
    const groups = new Map<string, Permission[]>();
    for (const p of perms) {
      const module = p.name.split('.')[0];
      if (!groups.has(module)) groups.set(module, []);
      groups.get(module)!.push(p);
    }
    return [...groups.entries()]
      .map(([module, perms]) => ({ module, perms }))
      .sort((a, b) => a.module.localeCompare(b.module));
  }

  groupByModuleNames(perms: Permission[]): { module: string; perms: string[] }[] {
    const groups = new Map<string, string[]>();
    for (const p of perms) {
      const module = p.name.split('.')[0];
      if (!groups.has(module)) groups.set(module, []);
      groups.get(module)!.push(p.name);
    }
    return [...groups.entries()].map(([module, perms]) => ({ module, perms }));
  }

  isValidName(name: string): boolean {
    return NAME_PATTERN.test(name);
  }

  actionLabel(name: string): string {
    return name.split('.')[1] ?? name;
  }

  getAll() {
    return this.api.get<Permission[]>('permissions');
  }
  getById(id: number) {
    return this.api.get<Permission>('permissions', String(id));
  }
  create(name: string) {
    return this.api.post<Permission>('permissions', undefined, { name });
  }
  update(id: number, name: string) {
    return this.api.put<Permission>('permissions', String(id), { name });
  }
  delete(id: number) {
    return this.api.delete('permissions', String(id));
  }
  getTrashed() {
    return this.api.get<Permission[]>('permissions', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('permissions', `${id}/restore`);
  }
}
