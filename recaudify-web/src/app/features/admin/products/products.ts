import { DecimalPipe } from '@angular/common';
import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Product } from '@core/interfaces/product.interface';
import { ProductsService } from '@core/services/products.service';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-products',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner, DecimalPipe],
  templateUrl: './products.html',
})
export class Products implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(ProductsService);

  protected readonly products = this.service.items;
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

  protected delete(product: Product) {
    if (!confirm(`¿Eliminar el producto "${product.name}"?`)) return;
    this.deletingId.set(product.id);
    this.service
      .remove(product)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(product: Product) {
    this.restoringId.set(product.id);
    this.service
      .restoreItem(product)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
