import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { Parameter } from '@core/models/parameter';
import { ParametersService } from '@core/services/parameters.service';

@Component({
  selector: 'app-parameters',
  imports: [RouterLink],
  templateUrl: './parameters.html',
})
export class Parameters implements OnInit {
  private readonly parametersService = inject(ParametersService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly parameters = signal<Parameter[]>([]);
  protected readonly loading = signal(true);
  protected readonly deletingId = signal<number | null>(null);

  ngOnInit() {
    this.parametersService.getAll().pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: parameters => { this.parameters.set(parameters); this.loading.set(false); },
      error: () => this.loading.set(false),
    });
  }

  protected delete(parameter: Parameter) {
    if (!confirm(`¿Eliminar el parámetro "${parameter.key}"? Esta acción no se puede deshacer.`)) return;
    this.deletingId.set(parameter.id);
    this.parametersService.delete(parameter.id).pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.parameters.update(list => list.filter(p => p.id !== parameter.id));
        this.deletingId.set(null);
      },
      error: () => this.deletingId.set(null),
    });
  }
}
