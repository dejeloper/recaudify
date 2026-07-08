import { PaginationMeta } from '@core/interfaces/pagination.interface';

export type PageToken = number | '...';

/**
 * Botones de página a mostrar para una paginación numerada: todas si hay pocas, o una
 * ventana alrededor de la actual (primera, última, vecinas) con `'...'` en los huecos.
 */
export function computePageNumbers(meta: PaginationMeta | null): PageToken[] {
  if (!meta || meta.lastPage <= 1) return [];

  const { page: current, lastPage: last } = meta;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);

  const pages = new Set<number>([1, 2, last - 1, last, current - 1, current, current + 1]);
  const sorted = [...pages].filter((p) => p >= 1 && p <= last).sort((a, b) => a - b);

  const result: PageToken[] = [];
  for (let i = 0; i < sorted.length; i++) {
    if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('...');
    result.push(sorted[i]);
  }
  return result;
}
