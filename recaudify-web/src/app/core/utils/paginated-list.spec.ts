import { of } from 'rxjs';
import { Paginated } from '@core/interfaces/pagination.interface';
import { PaginatedList } from './paginated-list';

function page<T>(items: T[], meta: Partial<Paginated<T>['meta']> = {}): Paginated<T> {
  return {
    items,
    meta: { total: items.length, page: 1, perPage: 10, lastPage: 1, ...meta },
  };
}

describe('PaginatedList', () => {
  it('load populates items and meta, and clears loading', () => {
    const fetch = vi.fn().mockReturnValue(of(page(['a'])));
    const list = new PaginatedList<string>(fetch, 10);

    list.load();

    expect(fetch).toHaveBeenCalledWith(1, 10, undefined);
    expect(list.items()).toEqual(['a']);
    expect(list.loading()).toBe(false);
  });

  it('loadMore appends the next page and advances meta', () => {
    const fetch = vi
      .fn()
      .mockReturnValueOnce(of(page(['a'], { page: 1, lastPage: 2 })))
      .mockReturnValueOnce(of(page(['b'], { page: 2, lastPage: 2 })));
    const list = new PaginatedList<string>(fetch, 10);

    list.load();
    list.loadMore();

    expect(fetch).toHaveBeenLastCalledWith(2, 10, undefined);
    expect(list.items()).toEqual(['a', 'b']);
    expect(list.meta()?.page).toBe(2);
    expect(list.loadingMore()).toBe(false);
  });

  it('loadMore does nothing on the last page', () => {
    const fetch = vi.fn().mockReturnValue(of(page(['a'], { page: 1, lastPage: 1 })));
    const list = new PaginatedList<string>(fetch, 10);

    list.load();
    list.loadMore();

    expect(fetch).toHaveBeenCalledTimes(1);
  });

  it('goToPage replaces the items instead of appending', () => {
    const fetch = vi
      .fn()
      .mockReturnValueOnce(of(page(['a'], { page: 1, lastPage: 3 })))
      .mockReturnValueOnce(of(page(['c'], { page: 3, lastPage: 3 })));
    const list = new PaginatedList<string>(fetch, 10);

    list.load();
    list.goToPage(3);

    expect(list.items()).toEqual(['c']);
    expect(list.meta()?.page).toBe(3);
  });

  it('goToPage ignores out-of-range pages', () => {
    const fetch = vi.fn().mockReturnValue(of(page(['a'], { page: 1, lastPage: 2 })));
    const list = new PaginatedList<string>(fetch, 10);

    list.load();
    list.goToPage(0);
    list.goToPage(99);

    expect(fetch).toHaveBeenCalledTimes(1);
  });

  it('hasMore reflects whether there is a next page', () => {
    const fetch = vi.fn().mockReturnValue(of(page(['a'], { page: 1, lastPage: 2 })));
    const list = new PaginatedList<string>(fetch, 10);

    expect(list.hasMore()).toBe(false);
    list.load();
    expect(list.hasMore()).toBe(true);
  });

  it('passes filters through to fetchPage', () => {
    const fetch = vi.fn().mockReturnValue(of(page(['a'])));
    const list = new PaginatedList<string, { term: string }>(fetch, 10);

    list.load({ term: 'busqueda' });

    expect(fetch).toHaveBeenCalledWith(1, 10, { term: 'busqueda' });
  });
});
