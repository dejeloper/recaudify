import { DecimalPipe } from '@angular/common';
import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Rate } from '@core/interfaces/rate.interface';
import { RatesService } from '@core/services/rates.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-rates',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner, DecimalPipe],
  templateUrl: './rates.html',
})
export class Rates implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(RatesService);

  protected readonly rates = this.service.items;
  protected readonly trashed = this.service.trashed;
  protected readonly loading = this.service.loading;
  protected readonly loadingTrashed = this.service.loadingTrashed;
  protected readonly showTrashed = this.service.showTrashed;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  ngOnInit() {
    this.service.load();
  }

  protected toggleTrashed() {
    this.service.toggleTrashed();
  }

  protected delete(rate: Rate) {
    if (!confirm(`¿Eliminar la tarifa "${rate.name}"?`)) return;
    this.deletingId.set(rate.id);
    this.service
      .remove(rate)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(rate: Rate) {
    this.restoringId.set(rate.id);
    this.service
      .restoreItem(rate)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
