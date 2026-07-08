import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import {
  Parameter,
  PARAMETER_TYPE_COLORS,
  PARAMETER_TYPE_LABELS,
  ParameterType,
} from '@core/interfaces/parameter.interface';
import { ParametersService } from '@core/services/parameters.service';
import { debounceTime, distinctUntilChanged, finalize, Subject } from 'rxjs';

@Component({
  selector: 'app-parameters',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './parameters.html',
})
export class Parameters implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(ParametersService);

  protected readonly parameters = this.service.items;
  protected readonly meta = this.service.meta;
  protected readonly trashed = this.service.trashed;
  protected readonly loading = this.service.loading;
  protected readonly loadingTrashed = this.service.loadingTrashed;
  protected readonly showTrashed = this.service.showTrashed;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);

  protected readonly typeLabels = PARAMETER_TYPE_LABELS;
  protected readonly typeColors = PARAMETER_TYPE_COLORS;
  protected readonly availableTypes = signal<ParameterType[]>([]);
  protected readonly selectedType = signal('');
  protected readonly searchTerm = signal('');

  private readonly search$ = new Subject<string>();

  ngOnInit() {
    this.service.load();
    this.service
      .getConfigValue<ParameterType[]>('parameter_types')
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((types) => {
        if (types) this.availableTypes.set(types);
      });
    this.search$
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((term) => this.service.load(this.selectedType() || undefined, term || undefined));
  }

  protected onSearch(term: string) {
    this.searchTerm.set(term);
    this.search$.next(term);
  }

  protected filterByType(type: string) {
    this.selectedType.set(type);
    this.service.load(type || undefined, this.searchTerm() || undefined);
  }

  protected readonly pageNumbers = computed<(number | '...')[]>(() => {
    const meta = this.meta();
    if (!meta || meta.lastPage <= 1) return [];

    const { page: current, lastPage: last } = meta;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);

    const pages = new Set<number>([1, 2, last - 1, last, current - 1, current, current + 1]);
    const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

    const result: (number | '...')[] = [];
    for (let i = 0; i < sorted.length; i++) {
      if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...');
      result.push(sorted[i]);
    }
    return result;
  });

  protected goToPage(page: number | '...') {
    if (page === '...') return;
    this.service.goToPage(page);
  }

  protected toggleTrashed() {
    this.service.toggleTrashed();
  }

  protected delete(parameter: Parameter) {
    if (!confirm(`¿Eliminar el parámetro "${parameter.key}"?`)) return;
    this.deletingId.set(parameter.id);
    this.service
      .remove(parameter)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(parameter: Parameter) {
    this.restoringId.set(parameter.id);
    this.service
      .restoreItem(parameter)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
