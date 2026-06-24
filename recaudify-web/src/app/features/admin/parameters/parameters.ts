import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Parameter } from '@core/interfaces/parameter.interface';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-parameters',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './parameters.html',
})
export class Parameters implements OnInit {
  private readonly parametersService = inject(ParametersService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly parameters = signal<Parameter[]>([]);
  protected readonly trashed = signal<Parameter[]>([]);
  protected readonly loading = signal(true);
  protected readonly loadingTrashed = signal(false);
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);
  protected readonly showTrashed = signal(false);

  ngOnInit() {
    this.parametersService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (parameters) => {
          this.parameters.set(parameters);
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
      this.parametersService
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

  protected delete(parameter: Parameter) {
    if (!confirm(`¿Eliminar el parámetro "${parameter.key}"?`)) return;
    this.deletingId.set(parameter.id);
    this.parametersService
      .delete(parameter.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          const removed = this.parameters().find((p) => p.id === parameter.id)!;
          this.parameters.update((list) => list.filter((p) => p.id !== parameter.id));
          this.trashed.update((list) => [removed, ...list]);
          this.deletingId.set(null);
          this.toast.success(`Parámetro "${parameter.key}" eliminado.`);
        },
        error: () => {
          this.deletingId.set(null);
          this.toast.error('No se pudo eliminar el parámetro.');
        },
      });
  }

  protected restore(parameter: Parameter) {
    this.restoringId.set(parameter.id);
    this.parametersService
      .restore(parameter.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.trashed.update((list) => list.filter((p) => p.id !== parameter.id));
          this.parameters.update((list) =>
            [...list, parameter].sort((a, b) => a.key.localeCompare(b.key)),
          );
          this.restoringId.set(null);
          this.toast.success(`Parámetro "${parameter.key}" restaurado.`);
        },
        error: () => {
          this.restoringId.set(null);
          this.toast.error('No se pudo restaurar el parámetro.');
        },
      });
  }
}
