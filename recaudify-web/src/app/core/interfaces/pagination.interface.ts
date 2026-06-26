export interface PaginationMeta {
  total: number;
  page: number;
  perPage: number;
  lastPage: number;
}

export interface Paginated<T> {
  items: T[];
  meta: PaginationMeta;
}
