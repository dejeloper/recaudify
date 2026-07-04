import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { Permission } from '@core/interfaces/permission.interface';
import { PermissionsService } from '@core/services/permissions.service';
import { RolesService } from '@core/services/roles.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-role-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './role-form.html',
})
export class RoleForm implements OnInit {
  private readonly rolesService = inject(RolesService);
  private readonly permissionsService = inject(PermissionsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formName = '';
  protected readonly allPermissions = signal<Permission[]>([]);
  protected readonly selected = signal<Set<string>>(new Set());

  protected readonly isEdit = computed(() => !!this.id());

  protected readonly grouped = computed(() =>
    this.permissionsService.groupByModuleNames(this.allPermissions()),
  );

  ngOnInit() {
    this.permissionsService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (perms) => {
          this.allPermissions.set(perms);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });

    const id = this.id();
    if (!id) return;

    this.loading.set(true);
    this.rolesService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (role) => {
          this.formName = role.name;
          this.selected.set(new Set(role.permissions.map((p) => p.name)));
        },
        error: () => {
          this.toast.error('No se pudo cargar el rol.');
          this.router.navigate(['/admin/roles']);
        },
      });
  }

  protected toggle(permName: string) {
    const s = new Set(this.selected());
    if (s.has(permName)) {
      s.delete(permName);
    } else {
      s.add(permName);
    }
    this.selected.set(s);
  }

  protected isSelected(permName: string) {
    return this.selected().has(permName);
  }

  protected toggleAll(perms: string[], checked: boolean) {
    const s = new Set(this.selected());
    perms.forEach((p) => (checked ? s.add(p) : s.delete(p)));
    this.selected.set(s);
  }

  protected allChecked(perms: string[]) {
    return perms.every((p) => this.selected().has(p));
  }

  protected actionLabel(perm: string) {
    return this.permissionsService.actionLabel(perm);
  }

  protected save() {
    if (!this.formName.trim()) return;

    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const name = this.formName.trim();
    const permissions = [...this.selected()];

    const req$ = id
      ? this.rolesService.update(+id, name, permissions)
      : this.rolesService.create(name, permissions);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Rol actualizado.' : 'Rol creado.');
        this.router.navigate(['/admin/roles']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el rol.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
