import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Permission } from '@core/models/permission';
import { PermissionsService } from '@core/services/permissions.service';

@Component({
  selector: 'app-permissions',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './permissions.html',
})
export class Permissions implements OnInit {
  private readonly permissionsService = inject(PermissionsService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly permissions = signal<Permission[]>([]);
  protected readonly trashed = signal<Permission[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingTrashed = signal(false);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);
  protected readonly showTrashed = signal(false);

  protected readonly grouped = computed(() => {
    const groups = new Map<string, Permission[]>();
    for (const p of this.permissions()) {
      const module = p.name.split('.')[0];
      if (!groups.has(module)) groups.set(module, []);
      groups.get(module)!.push(p);
    }
    return [...groups.entries()]
      .map(([module, perms]) => ({ module, perms }))
      .sort((a, b) => a.module.localeCompare(b.module));
  });

  protected readonly groupedTrashed = computed(() => {
    const groups = new Map<string, Permission[]>();
    for (const p of this.trashed()) {
      const module = p.name.split('.')[0];
      if (!groups.has(module)) groups.set(module, []);
      groups.get(module)!.push(p);
    }
    return [...groups.entries()]
      .map(([module, perms]) => ({ module, perms }))
      .sort((a, b) => a.module.localeCompare(b.module));
  });

  ngOnInit() {
    this.permissionsService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (perms) => {
          this.permissions.set(perms);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  protected toggleTrashed() {
    const next = !this.showTrashed();
    this.showTrashed.set(next);
    if (next && this.trashed().length === 0) {
      this.loadingTrashed.set(true);
      this.permissionsService
        .getTrashed()
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (list) => {
            this.trashed.set(list);
            this.loadingTrashed.set(false);
          },
          error: () => this.loadingTrashed.set(false),
        });
    }
  }

  protected delete(permission: Permission) {
    if (!confirm(`¿Eliminar el permiso "${permission.name}"?`)) return;
    this.deletingId.set(permission.id);
    this.permissionsService
      .delete(permission.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          const removed = this.permissions().find((p) => p.id === permission.id)!;
          this.permissions.update((list) => list.filter((p) => p.id !== permission.id));
          this.trashed.update((list) => [removed, ...list]);
          this.deletingId.set(null);
        },
        error: () => this.deletingId.set(null),
      });
  }

  protected restore(permission: Permission) {
    this.restoringId.set(permission.id);
    this.permissionsService
      .restore(permission.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.trashed.update((list) => list.filter((p) => p.id !== permission.id));
          this.permissions.update((list) =>
            [...list, permission].sort((a, b) => a.name.localeCompare(b.name)),
          );
          this.restoringId.set(null);
        },
        error: () => this.restoringId.set(null),
      });
  }

  protected actionLabel(name: string) {
    return name.split('.')[1] ?? name;
  }
}
