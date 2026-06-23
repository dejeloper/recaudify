import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { Permission } from '@core/models/permission';
import { PermissionsService } from '@core/services/permissions.service';

@Component({
  selector: 'app-permissions',
  imports: [RouterLink],
  templateUrl: './permissions.html',
})
export class Permissions implements OnInit {
  private readonly permissionsService = inject(PermissionsService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly permissions = signal<Permission[]>([]);
  protected readonly loading = signal(true);
  protected readonly deletingId = signal<number | null>(null);

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

  ngOnInit() {
    this.permissionsService.getAll().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: perms => { this.permissions.set(perms); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  protected delete(permission: Permission) {
    if (!confirm(`¿Eliminar el permiso "${permission.name}"? Esta acción no se puede deshacer.`)) return;
    this.deletingId.set(permission.id);
    this.permissionsService.delete(permission.id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.permissions.update(list => list.filter(p => p.id !== permission.id));
        this.deletingId.set(null);
      },
      error: () => this.deletingId.set(null),
    });
  }

  protected actionLabel(name: string) {
    return name.split('.')[1] ?? name;
  }
}
