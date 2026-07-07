import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { MenuItem } from '@core/interfaces/nav.interface';
import { Permission } from '@core/interfaces/permission.interface';
import { MenuItemsService } from '@core/services/menu-items.service';
import { PermissionsService } from '@core/services/permissions.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-menu-item-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './menu-item-form.html',
})
export class MenuItemForm implements OnInit {
  private readonly menuItemsService = inject(MenuItemsService);
  private readonly permissionsService = inject(PermissionsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formLabel = '';
  protected formParentId = '';
  protected formRoute = '';
  protected formIcons = '';
  protected formPermission = '';
  protected formOrder = 0;
  protected formIsActive = true;

  protected readonly allPermissions = signal<Permission[]>([]);
  protected readonly allItems = signal<MenuItem[]>([]);

  protected readonly isEdit = computed(() => !!this.id());

  /** Ítems que aún pueden aceptar un hijo: los de profundidad 0 o 1 (máx. 3 niveles). */
  protected readonly availableParents = computed(() =>
    this.allItems().filter((item) => item.id !== +(this.id() ?? 0) && this.depth(item) <= 1),
  );

  ngOnInit() {
    this.permissionsService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({ next: (perms) => this.allPermissions.set(perms) });

    this.menuItemsService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (items) => {
          this.allItems.set(items);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });

    const id = this.id();
    if (!id) return;

    this.loading.set(true);
    this.menuItemsService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (item) => {
          this.formLabel = item.label;
          this.formParentId = item.parent_id !== null ? String(item.parent_id) : '';
          this.formRoute = item.route ?? '';
          this.formIcons = (item.icons ?? []).join('\n');
          this.formPermission = item.permission ?? '';
          this.formOrder = item.order;
          this.formIsActive = item.is_active;
        },
        error: () => {
          this.toast.error('No se pudo cargar el ítem de menú.');
          this.router.navigate(['/admin/menu-items']);
        },
      });
  }

  private depth(item: MenuItem): number {
    let depth = 0;
    let current: MenuItem | undefined = item;
    while (current?.parent_id !== null && current?.parent_id !== undefined) {
      depth++;
      current = this.allItems().find((i) => i.id === current!.parent_id);
    }
    return depth;
  }

  protected save() {
    if (!this.formLabel.trim()) return;

    this.saving.set(true);
    this.error.set('');

    const payload = {
      parent_id: this.formParentId ? +this.formParentId : null,
      label: this.formLabel.trim(),
      icons: this.formIcons.trim()
        ? this.formIcons
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean)
        : null,
      route: this.formRoute.trim() || null,
      permission: this.formPermission || null,
      order: this.formOrder,
      is_active: this.formIsActive,
    };

    const id = this.id();
    const req$ = id
      ? this.menuItemsService.update(+id, payload)
      : this.menuItemsService.create(payload);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Ítem de menú actualizado.' : 'Ítem de menú creado.');
        this.router.navigate(['/admin/menu-items']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el ítem de menú.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
