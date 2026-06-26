import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Seller } from '@core/interfaces/seller.interface';
import { SellersService } from '@core/services/sellers.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-sellers',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './sellers.html',
})
export class Sellers implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(SellersService);

  protected readonly sellers = this.service.items;
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

  protected delete(seller: Seller) {
    if (!confirm(`¿Eliminar el vendedor "${seller.name}"?`)) return;
    this.deletingId.set(seller.id);
    this.service
      .remove(seller)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(seller: Seller) {
    this.restoringId.set(seller.id);
    this.service
      .restoreItem(seller)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
