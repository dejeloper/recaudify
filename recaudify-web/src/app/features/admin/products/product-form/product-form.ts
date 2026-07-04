import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { ProductsService } from '@core/services/products.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-product-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './product-form.html',
})
export class ProductForm implements OnInit {
  private readonly productsService = inject(ProductsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formName = '';
  protected formValue: number | null = null;
  protected formActive = true;

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.productsService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (product) => {
          this.formName = product.name;
          this.formValue = product.value;
          this.formActive = product.active;
          this.loading.set(false);
        },
        error: () => {
          this.toast.error('No se pudo cargar el producto.');
          this.router.navigate(['/admin/products']);
        },
      });
  }

  protected save() {
    if (!this.formName.trim() || this.formValue === null || this.formValue < 0) return;
    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const name = this.formName.trim();
    const value = this.formValue;
    const active = this.formActive;

    const req$ = id
      ? this.productsService.update(+id, name, value, active)
      : this.productsService.create(name, value, active);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Producto actualizado.' : 'Producto creado.');
        this.router.navigate(['/admin/products']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el producto.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
