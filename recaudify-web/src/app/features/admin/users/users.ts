import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { User } from '@core/models/user';
import { AuthService } from '@core/services/auth.service';
import { UsersService } from '@core/services/users.service';

@Component({
  selector: 'app-users',
  imports: [RouterLink, BtnDirective, TableDirective],
  templateUrl: './users.html',
})
export class Users implements OnInit {
  private readonly usersService = inject(UsersService);
  private readonly authService = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly users = signal<User[]>([]);
  protected readonly disabled = signal<User[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingDisabled = signal(false);
  protected readonly showDisabled = signal(false);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  protected readonly canCreate = computed(() => this.authService.hasPermission('usuarios.crear'));
  protected readonly canEdit = computed(() => this.authService.hasPermission('usuarios.editar'));
  protected readonly canDelete = computed(() => this.authService.hasPermission('usuarios.desactivar'));
  protected readonly canRestore = computed(() => this.authService.hasPermission('usuarios.restaurar'));

  ngOnInit() {
    this.usersService.getAll().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: users => { this.users.set(users); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  protected toggleDisabled() {
    const next = !this.showDisabled();
    this.showDisabled.set(next);
    if (next && this.disabled().length === 0) {
      this.loadingDisabled.set(true);
      this.usersService.getDisabled().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
        next: list => { this.disabled.set(list); this.loadingDisabled.set(false); },
        error: () => this.loadingDisabled.set(false),
      });
    }
  }

  protected delete(user: User) {
    if (!confirm(`¿Desactivar al usuario "${user.name}"?`)) return;
    this.deletingId.set(user.id);
    this.usersService.delete(user.id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        const removed = this.users().find(u => u.id === user.id)!;
        this.users.update(list => list.filter(u => u.id !== user.id));
        this.disabled.update(list => [removed, ...list]);
        this.deletingId.set(null);
      },
      error: () => this.deletingId.set(null),
    });
  }

  protected restore(user: User) {
    this.restoringId.set(user.id);
    this.usersService.restore(user.id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.disabled.update(list => list.filter(u => u.id !== user.id));
        this.users.update(list => [...list, user].sort((a, b) => a.name.localeCompare(b.name)));
        this.restoringId.set(null);
      },
      error: () => this.restoringId.set(null),
    });
  }

  protected roleLabel(user: User): string {
    return user.roles[0] ?? '—';
  }
}
