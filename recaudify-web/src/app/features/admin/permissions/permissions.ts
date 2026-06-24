import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Permission } from '@core/interfaces/permission.interface';
import { PermissionsService } from '@core/services/permissions.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-permissions',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './permissions.html',
})
export class Permissions implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(PermissionsService);

  protected readonly loading = this.service.loading;
  protected readonly loadingTrashed = this.service.loadingTrashed;
  protected readonly showTrashed = this.service.showTrashed;
  protected readonly grouped = this.service.grouped;
  protected readonly groupedTrashed = this.service.groupedTrashed;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  ngOnInit() {
    this.service.load();
  }

  protected toggleTrashed() {
    this.service.toggleTrashed();
  }

  protected actionLabel(name: string) {
    return this.service.actionLabel(name);
  }

  protected delete(permission: Permission) {
    if (!confirm(`¿Eliminar el permiso "${permission.name}"?`)) return;
    this.deletingId.set(permission.id);
    this.service
      .remove(permission)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(permission: Permission) {
    this.restoringId.set(permission.id);
    this.service
      .restoreItem(permission)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
