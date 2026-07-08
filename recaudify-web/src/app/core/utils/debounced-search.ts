import { DestroyRef } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';

const DEFAULT_DELAY_MS = 500;

/**
 * Arma el `Subject` + `debounceTime` + `distinctUntilChanged` que se repetía a mano en cada
 * componente con un input de búsqueda. Se suscribe una sola vez (atada al `destroyRef` del
 * componente) y devuelve la función para emitir cada término tecleado.
 */
export function createDebouncedSearch(
  destroyRef: DestroyRef,
  onSearch: (term: string) => void,
  delayMs = DEFAULT_DELAY_MS,
): (term: string) => void {
  const search$ = new Subject<string>();

  search$
    .pipe(debounceTime(delayMs), distinctUntilChanged(), takeUntilDestroyed(destroyRef))
    .subscribe(onSearch);

  return (term: string) => search$.next(term);
}
