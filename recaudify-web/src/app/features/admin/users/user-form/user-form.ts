import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/models/api-error';
import { Role } from '@core/models/role';
import { RolesService } from '@core/services/roles.service';
import { ToastService } from '@core/services/toast.service';
import { UsersService } from '@core/services/users.service';

@Component({
  selector: 'app-user-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './user-form.html',
})
export class UserForm implements OnInit {
  private readonly usersService = inject(UsersService);
  private readonly rolesService = inject(RolesService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');
  protected readonly roles = signal<Role[]>([]);

  protected formName = '';
  protected formUsername = '';
  protected formEmail = '';
  protected formPassword = '';
  protected formPasswordConfirmation = '';
  protected formRole = '';

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    this.rolesService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (roles) => this.roles.set(roles),
      });

    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.usersService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (user) => {
          this.formName = user.name;
          this.formUsername = user.username;
          this.formEmail = user.email ?? '';
          this.formRole = user.roles[0] ?? '';
          this.loading.set(false);
        },
        error: () => {
          this.error.set('No se pudo cargar el usuario.');
          this.loading.set(false);
        },
      });
  }

  protected save() {
    if (!this.formName.trim() || !this.formUsername.trim()) return;
    if (!this.isEdit() && !this.formPassword) return;

    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const payload = {
      name: this.formName.trim(),
      username: this.formUsername.trim(),
      email: this.formEmail.trim() || null,
      role: this.formRole || null,
      ...(this.formPassword
        ? {
            password: this.formPassword,
            password_confirmation: this.formPasswordConfirmation,
          }
        : {}),
    };

    const req$ = id
      ? this.usersService.update(+id, payload)
      : this.usersService.create({
          ...payload,
          password: this.formPassword,
          password_confirmation: this.formPasswordConfirmation,
        });

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Usuario actualizado.' : 'Usuario creado.');
        this.router.navigate(['/admin/users']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el usuario.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
