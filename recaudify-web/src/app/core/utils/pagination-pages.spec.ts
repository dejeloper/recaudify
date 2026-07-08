import { PaginationMeta } from '@core/interfaces/pagination.interface';
import { computePageNumbers } from './pagination-pages';

function meta(page: number, lastPage: number): PaginationMeta {
  return { total: lastPage * 10, page, perPage: 10, lastPage };
}

describe('computePageNumbers', () => {
  it('returns an empty array when there is no meta', () => {
    expect(computePageNumbers(null)).toEqual([]);
  });

  it('returns an empty array with a single page', () => {
    expect(computePageNumbers(meta(1, 1))).toEqual([]);
  });

  it('returns all pages when there are 7 or fewer', () => {
    expect(computePageNumbers(meta(3, 5))).toEqual([1, 2, 3, 4, 5]);
  });

  it('windows around the current page with ellipsis when there are many pages', () => {
    expect(computePageNumbers(meta(10, 20))).toEqual([1, 2, '...', 9, 10, 11, '...', 19, 20]);
  });

  it('avoids a redundant ellipsis near the edges', () => {
    expect(computePageNumbers(meta(1, 20))).toEqual([1, 2, '...', 19, 20]);
    expect(computePageNumbers(meta(20, 20))).toEqual([1, 2, '...', 19, 20]);
  });
});
