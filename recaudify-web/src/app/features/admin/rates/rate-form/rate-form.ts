import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { Product } from '@core/interfaces/product.interface';
import { RateInput } from '@core/interfaces/rate.interface';
import { ProductsService } from '@core/services/products.service';
import { RatesService } from '@core/services/rates.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-rate-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './rate-form.html',
})
export class RateForm implements OnInit {
  private readonly ratesService = inject(RatesService);
  private readonly productsService = inject(ProductsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');
  protected readonly products = signal<Product[]>([]);

  protected formName = '';
  protected formProductId: number | null = null;
  protected formValue: number | null = null;
  protected formInstallments: number | null = null;
  protected formInstallmentValue: number | null = null;
  protected formDiscount = 0;
  protected formActive = true;

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    this.productsService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (list) => this.products.set(list),
      });

    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.ratesService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (rate) => {
          this.formName = rate.name;
          this.formProductId = rate.product_id;
          this.formValue = rate.value;
          this.formInstallments = rate.installments;
          this.formInstallmentValue = rate.installment_value;
          this.formDiscount = rate.discount;
          this.formActive = rate.active;
          this.loading.set(false);
        },
        error: () => {
          this.toast.error('No se pudo cargar la tarifa.');
          this.router.navigate(['/admin/rates']);
        },
      });
  }

  protected isValid(): boolean {
    return (
      this.formName.trim().length > 0 &&
      this.formProductId !== null &&
      this.formValue !== null &&
      this.formValue >= 0 &&
      this.formInstallments !== null &&
      this.formInstallments >= 0 &&
      this.formInstallmentValue !== null &&
      this.formInstallmentValue >= 0
    );
  }

  protected save() {
    if (!this.isValid()) return;
    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const input: RateInput = {
      name: this.formName.trim(),
      product_id: this.formProductId!,
      value: this.formValue!,
      installments: this.formInstallments!,
      installment_value: this.formInstallmentValue!,
      discount: this.formDiscount ?? 0,
      active: this.formActive,
    };

    const req$ = id ? this.ratesService.update(+id, input) : this.ratesService.create(input);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Tarifa actualizada.' : 'Tarifa creada.');
        this.router.navigate(['/admin/rates']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar la tarifa.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
