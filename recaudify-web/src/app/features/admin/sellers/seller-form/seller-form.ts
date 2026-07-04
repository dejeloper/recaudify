import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { SellersService } from '@core/services/sellers.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-seller-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './seller-form.html',
})
export class SellerForm implements OnInit {
  private readonly sellersService = inject(SellersService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formName = '';
  protected formUsername = '';
  protected formActive = true;

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.sellersService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (seller) => {
          this.formName = seller.name;
          this.formUsername = seller.username ?? '';
          this.formActive = seller.active;
          this.loading.set(false);
        },
        error: () => {
          this.toast.error('No se pudo cargar el vendedor.');
          this.router.navigate(['/admin/sellers']);
        },
      });
  }

  protected save() {
    if (!this.formName.trim()) return;
    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const name = this.formName.trim();
    const username = this.formUsername.trim() || null;
    const active = this.formActive;

    const req$ = id
      ? this.sellersService.update(+id, name, username, active)
      : this.sellersService.create(name, username, active);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Vendedor actualizado.' : 'Vendedor creado.');
        this.router.navigate(['/admin/sellers']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el vendedor.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
