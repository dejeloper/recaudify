import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { ApiError } from '@core/interfaces/api-error.interface';
import { CallReasonsService } from '@core/services/call-reasons.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-call-reason-form',
  imports: [FormsModule, RouterLink, BtnDirective],
  templateUrl: './call-reason-form.html',
})
export class CallReasonForm implements OnInit {
  private readonly callReasonsService = inject(CallReasonsService);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly id = input<string>();

  protected readonly loading = signal(true);
  protected readonly saving = signal(false);
  protected readonly error = signal('');

  protected formName = '';
  protected formColor = '';
  protected formActive = true;

  protected readonly isEdit = computed(() => !!this.id());

  ngOnInit() {
    const id = this.id();
    if (!id) {
      this.loading.set(false);
      return;
    }

    this.callReasonsService
      .getById(+id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (reason) => {
          this.formName = reason.name;
          this.formColor = reason.color ?? '';
          this.formActive = reason.active;
          this.loading.set(false);
        },
        error: () => {
          this.error.set('No se pudo cargar el motivo.');
          this.loading.set(false);
        },
      });
  }

  protected save() {
    if (!this.formName.trim()) return;
    this.saving.set(true);
    this.error.set('');

    const id = this.id();
    const name = this.formName.trim();
    const color = this.formColor.trim() || null;
    const active = this.formActive;

    const req$ = id
      ? this.callReasonsService.update(+id, name, color, active)
      : this.callReasonsService.create(name, color, active);

    req$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.toast.success(this.isEdit() ? 'Motivo actualizado.' : 'Motivo creado.');
        this.router.navigate(['/admin/call-reasons']);
      },
      error: (err: ApiError) => {
        const msg = err.message ?? 'Error al guardar el motivo.';
        this.error.set(msg);
        this.toast.error(msg);
        this.saving.set(false);
      },
    });
  }
}
