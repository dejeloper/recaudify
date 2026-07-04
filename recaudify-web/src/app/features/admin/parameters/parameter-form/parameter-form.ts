import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import {
  PARAMETER_TYPE_LABELS,
  PARAMETER_TYPES,
  ParameterType,
} from '@core/interfaces/parameter.interface';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';

const PARAMETER_CASTS = ['string', 'boolean', 'integer', 'float', 'json'] as const;

@Component({
  selector: 'app-parameter-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './parameter-form.html',
})
export class ParameterForm implements OnInit {
  private readonly parametersService = inject(ParametersService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected readonly types = PARAMETER_TYPES;
  protected readonly typeLabels = PARAMETER_TYPE_LABELS;
  protected readonly casts = PARAMETER_CASTS;

  protected formKey = '';
  protected formValue = '';
  protected formDescription = '';
  protected formType: ParameterType = 'configuration';
  protected formCast = 'string';

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.parametersService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (param) => {
          this.formKey = param.key;
          this.formValue = param.value;
          this.formDescription = param.description ?? '';
          this.formType = param.type;
          this.formCast = param.cast;
          this.loading.set(false);
        },
        error: () => {
          this.error.set('No se pudo cargar el parámetro.');
          this.loading.set(false);
        },
      });
  }

  protected save() {
    if (!this.formKey.trim() || !this.formValue.trim()) return;
    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const key = this.formKey.trim();
    const value = this.formValue.trim();
    const description = this.formDescription.trim() || null;

    const req$ = id
      ? this.parametersService.update(+id, value)
      : this.parametersService.create(key, value, description, this.formType, this.formCast);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Parámetro actualizado.' : 'Parámetro creado.');
        this.router.navigate(['/admin/parameters']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el parámetro.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
