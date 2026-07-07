import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { MenuItem } from '@core/interfaces/nav.interface';
import { MenuItemsService } from '@core/services/menu-items.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-menu-items',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './menu-items.html',
})
export class MenuItems implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(MenuItemsService);

  protected readonly items = this.service.items;
  protected readonly trashed = this.service.trashed;
  protected readonly loading = this.service.loading;
  protected readonly loadingTrashed = this.service.loadingTrashed;
  protected readonly showTrashed = this.service.showTrashed;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  ngOnInit() {
    this.service.load();
  }

  protected parentLabel(item: MenuItem): string {
    if (item.parent_id === null) return '—';
    return this.items().find((i) => i.id === item.parent_id)?.label ?? `#${item.parent_id}`;
  }

  protected toggleTrashed() {
    this.service.toggleTrashed();
  }

  protected delete(item: MenuItem) {
    if (!confirm(`¿Eliminar el ítem de menú "${item.label}"?`)) return;
    this.deletingId.set(item.id);
    this.service
      .remove(item)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(item: MenuItem) {
    this.restoringId.set(item.id);
    this.service
      .restoreItem(item)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
