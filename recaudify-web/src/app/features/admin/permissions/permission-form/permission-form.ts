import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { PermissionsService } from '@core/services/permissions.service';
import { ToastService } from '@core/services/toast.service';

const NAME_PATTERN = /^[a-z_]+\.[a-z_-]+$/;

@Component({
  selector: 'app-permission-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './permission-form.html',
})
export class PermissionForm implements OnInit {
  private readonly permissionsService = inject(PermissionsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(false);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formName = '';

  protected readonly isEdit = computed(() => !!this.id());
  protected readonly isValid = computed(() => NAME_PATTERN.test(this.formName.trim()));

  ngOnInit() {
    const id = this.id();
    if (!id) return;

    this.loading.set(true);
    this.permissionsService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (perm) => {
          this.formName = perm.name;
          this.loading.set(false);
        },
        error: () => {
          this.error.set('No se pudo cargar el permiso.');
          this.loading.set(false);
        },
      });
  }

  protected save() {
    const name = this.formName.trim();
    if (!NAME_PATTERN.test(name)) {
      this.error.set('Usa el formato modulo.accion (ej. clientes.crear).');
      return;
    }

    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const req$ = id
      ? this.permissionsService.update(+id, name)
      : this.permissionsService.create(name);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Permiso actualizado.' : 'Permiso creado.');
        this.router.navigate(['/admin/permissions']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el permiso.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
