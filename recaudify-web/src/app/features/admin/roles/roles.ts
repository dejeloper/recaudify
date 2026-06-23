import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Role } from '@core/models/role';
import { RolesService } from '@core/services/roles.service';

@Component({
  selector: 'app-roles',
  imports: [RouterLink, BtnDirective, TableDirective],
  templateUrl: './roles.html',
})
export class Roles implements OnInit {
  private readonly rolesService = inject(RolesService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly roles = signal<Role[]>([]);
  protected readonly trashed = signal<Role[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingTrashed = signal(false);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);
  protected readonly showTrashed = signal(false);

  ngOnInit() {
    this.rolesService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (roles) => {
          this.roles.set(roles);
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
      this.rolesService
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

  protected delete(role: Role) {
    if (!confirm(`¿Eliminar el rol "${role.name}"?`)) return;
    this.deletingId.set(role.id);
    this.rolesService
      .delete(role.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          const removed = this.roles().find((r) => r.id === role.id)!;
          this.roles.update((list) => list.filter((r) => r.id !== role.id));
          this.trashed.update((list) => [removed, ...list]);
          this.deletingId.set(null);
        },
        error: () => this.deletingId.set(null),
      });
  }

  protected restore(role: Role) {
    this.restoringId.set(role.id);
    this.rolesService
      .restore(role.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.trashed.update((list) => list.filter((r) => r.id !== role.id));
          this.roles.update((list) => [...list, role].sort((a, b) => a.name.localeCompare(b.name)));
          this.restoringId.set(null);
        },
        error: () => this.restoringId.set(null),
      });
  }
}
