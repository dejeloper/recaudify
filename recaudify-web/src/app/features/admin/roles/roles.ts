import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { Role } from '@core/models/role';
import { RolesService } from '@core/services/roles.service';

@Component({
  selector: 'app-roles',
  imports: [RouterLink],
  templateUrl: './roles.html',
})
export class Roles implements OnInit {
  private readonly rolesService = inject(RolesService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly roles = signal<Role[]>([]);
  protected readonly loading = signal(true);
  protected readonly deletingId = signal<number | null>(null);

  ngOnInit() {
    this.rolesService.getAll().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: roles => { this.roles.set(roles); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  protected delete(role: Role) {
    if (!confirm(`¿Eliminar el rol "${role.name}"? Esta acción no se puede deshacer.`)) return;
    this.deletingId.set(role.id);
    this.rolesService.delete(role.id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.roles.update(list => list.filter(r => r.id !== role.id));
        this.deletingId.set(null);
      },
      error: () => this.deletingId.set(null),
    });
  }
}
