import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { UsersService } from '@core/services/users.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-users',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './users.html',
})
export class Users implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  private readonly authService = inject(AuthService);
  protected readonly service = inject(UsersService);

  protected readonly users = this.service.items;
  protected readonly disabled = this.service.disabled;
  protected readonly loading = this.service.loading;
  protected readonly loadingDisabled = this.service.loadingDisabled;
  protected readonly showDisabled = this.service.showDisabled;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  protected readonly canCreate = computed(() => this.authService.hasPermission('usuarios.crear'));
  protected readonly canEdit = computed(() => this.authService.hasPermission('usuarios.editar'));
  protected readonly canDelete = computed(() =>
    this.authService.hasPermission('usuarios.desactivar'),
  );
  protected readonly canRestore = computed(() =>
    this.authService.hasPermission('usuarios.restaurar'),
  );

  ngOnInit() {
    this.service.load();
  }

  protected toggleDisabled() {
    this.service.toggleDisabled();
  }

  protected roleLabel(user: User) {
    return this.service.roleLabel(user);
  }

  protected delete(user: User) {
    if (!confirm(`¿Desactivar al usuario "${user.name}"?`)) return;
    this.deletingId.set(user.id);
    this.service
      .remove(user)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(user: User) {
    this.restoringId.set(user.id);
    this.service
      .restoreItem(user)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
