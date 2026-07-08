import { signal } from '@angular/core';
import { Observable } from 'rxjs';
import { Paginated, PaginationMeta } from '@core/interfaces/pagination.interface';

/**
 * Encapsula el patrón "paginar + buscar" repetido en los listados admin: primera carga,
 * "cargar más" (acumula) y salto a página puntual (reemplaza), todo contra un endpoint
 * que responde `Paginated<T>`. Cada service instancia una por listado, pasándole cómo
 * pedir una página dada — el resto (signals, estados de loading) lo resuelve esta clase.
 */
export class PaginatedList<T, F = void> {
  private readonly perPage: number;
  private filters: F | undefined;

  readonly items = signal<T[]>([]);
  readonly meta = signal<PaginationMeta | null>(null);
  readonly loading = signal(false);
  readonly loadingMore = signal(false);

  constructor(
    private readonly fetchPage: (
      page: number,
      perPage: number,
      filters?: F,
    ) => Observable<Paginated<T>>,
    perPage = 25,
  ) {
    this.perPage = perPage;
  }

  /** Carga la primera página (reemplaza el listado). */
  load(filters?: F): void {
    this.filters = filters;
    this.loading.set(true);
    this.fetchPage(1, this.perPage, filters).subscribe({
      next: (page) => {
        this.items.set(page.items);
        this.meta.set(page.meta);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  /** Carga la siguiente página y la agrega al listado (scroll infinito / "cargar más"). */
  loadMore(): void {
    const meta = this.meta();
    if (!meta || meta.page >= meta.lastPage || this.loadingMore()) return;

    this.loadingMore.set(true);
    this.fetchPage(meta.page + 1, this.perPage, this.filters).subscribe({
      next: (page) => {
        this.items.update((list) => [...list, ...page.items]);
        this.meta.set(page.meta);
        this.loadingMore.set(false);
      },
      error: () => this.loadingMore.set(false),
    });
  }

  /** Salta a una página puntual (reemplaza el listado — paginación numerada). */
  goToPage(page: number): void {
    const meta = this.meta();
    if (!meta || page < 1 || page > meta.lastPage || page === meta.page || this.loading()) return;

    this.loading.set(true);
    this.fetchPage(page, this.perPage, this.filters).subscribe({
      next: (result) => {
        this.items.set(result.items);
        this.meta.set(result.meta);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  readonly hasMore = (): boolean => {
    const meta = this.meta();
    return !!meta && meta.page < meta.lastPage;
  };
}
