import { Component, computed, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Role } from '@core/interfaces/role.interface';
import { RolesService } from '@core/services/roles.service';
import { createDebouncedSearch } from '@core/utils/debounced-search';
import { computePageNumbers } from '@core/utils/pagination-pages';
import { finalize } from 'rxjs';

@Component({
  selector: 'app-roles',
  imports: [RouterLink, BtnDirective, TableDirective, Spinner],
  templateUrl: './roles.html',
})
export class Roles implements OnInit {
  private readonly destroyRef = inject(DestroyRef);
  protected readonly service = inject(RolesService);

  protected readonly roles = this.service.items;
  protected readonly meta = this.service.meta;
  protected readonly trashed = this.service.trashed;
  protected readonly loading = this.service.loading;
  protected readonly loadingTrashed = this.service.loadingTrashed;
  protected readonly showTrashed = this.service.showTrashed;
  protected readonly deletingId = signal<number | null>(null);
  protected readonly restoringId = signal<number | null>(null);
  protected readonly searchTerm = signal('');

  private readonly emitSearch = createDebouncedSearch(this.destroyRef, (term) =>
    this.service.load(term || undefined),
  );

  ngOnInit() {
    this.service.load();
  }

  protected onSearch(term: string) {
    this.searchTerm.set(term);
    this.emitSearch(term);
  }

  protected readonly pageNumbers = computed(() => computePageNumbers(this.meta()));

  protected goToPage(page: number | '...') {
    if (page === '...') return;
    this.service.goToPage(page);
  }

  protected toggleTrashed() {
    this.service.toggleTrashed();
  }

  protected delete(role: Role) {
    if (!confirm(`¿Eliminar el rol "${role.name}"?`)) return;
    this.deletingId.set(role.id);
    this.service
      .remove(role)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }

  protected restore(role: Role) {
    this.restoringId.set(role.id);
    this.service
      .restoreItem(role)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.restoringId.set(null)),
      )
      .subscribe();
  }
}
