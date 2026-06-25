import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { CallReason } from '@core/interfaces/call-reason.interface';
import { CallReasonsService } from '@core/services/call-reasons.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-call-reasons',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './call-reasons.html',
})
export class CallReasons implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(CallReasonsService);

  protected readonly reasons = this.service.items;
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

  protected delete(reason: CallReason) {
    if (!confirm(`¿Eliminar el motivo "${reason.name}"?`)) return;
    this.deletingId.set(reason.id);
    this.service
      .remove(reason)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(reason: CallReason) {
    this.restoringId.set(reason.id);
    this.service
      .restoreItem(reason)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
