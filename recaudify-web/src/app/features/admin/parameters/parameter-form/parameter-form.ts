import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/models/api-error';
import { ParametersService } from '@core/services/parameters.service';

@Component({
  selector: 'app-parameter-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './parameter-form.html',
})
export class ParameterForm implements OnInit {
  private readonly parametersService = inject(ParametersService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formKey = '';
  protected formValue = '';
  protected formDescription = '';

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.parametersService.getById(+id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: param => {
        this.formKey = param.key;
        this.formValue = param.value;
        this.formDescription = param.description ?? '';
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
      ? this.parametersService.update(+id, key, value, description)
      : this.parametersService.create(key, value, description);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => this.router.navigate(['/admin/parameters']),
      error: (err: ApiError) => {
        this.error.set(err.message ?? 'Error al guardar el parámetro.');
        this.saving.set(false);
      },
    });
  }
}
