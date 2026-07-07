import { HttpClient, HttpErrorResponse, HttpHeaders, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { catchError, map, Observable, throwError } from 'rxjs';
import { environment } from '@env/environment';
import { ApiOptions, ApiResponse } from '@core/interfaces/api.interface';
import { ApiError } from '@core/interfaces/api-error.interface';
import { Paginated } from '@core/interfaces/pagination.interface';

/** Claves que pueden contaminar el prototipo (prototype pollution). */
const UNSAFE_KEYS = new Set(['__proto__', 'constructor', 'prototype']);

/** Objeto plano (`{}`), no instancias como Date, File, Blob, FormData, etc. */
function isPlainObject(value: unknown): value is Record<string, unknown> {
  if (value === null || typeof value !== 'object') return false;
  const proto = Object.getPrototypeOf(value);
  return proto === Object.prototype || proto === null;
}

/**
 * Elimina claves inseguras de forma recursiva (objetos y arrays anidados),
 * dejando intactos los valores no-planos (Date, File, etc.).
 */
function sanitize(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(sanitize);
  }
  if (isPlainObject(value)) {
    return Object.fromEntries(
      Object.entries(value)
        .filter(([key]) => !UNSAFE_KEYS.has(key))
        .map(([key, val]) => [key, sanitize(val)]),
    );
  }
  return value;
}

function toApiError(err: HttpErrorResponse): ApiError {
  const body = err.error as { message?: string; errors?: Record<string, string[]> } | null;

  return {
    statusCode: err.status,
    message: body?.message ?? err.message ?? 'Error desconocido',
    errors: body?.errors,
  };
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private readonly http = inject(HttpClient);

  request<T = unknown>({
    controller,
    action,
    method = 'GET',
    body,
    params,
    headers,
  }: ApiOptions): Observable<T> {
    return this.http
      .request<ApiResponse<T>>(method, this.buildUrl(controller, action), {
        headers: this.buildHeaders(headers),
        params: this.buildParams(params),
        body: body ? sanitize(body) : undefined,
        withCredentials: true,
      })
      .pipe(
        map((response) => response.data),
        catchError((err: HttpErrorResponse) => throwError(() => toApiError(err))),
      );
  }

  get<T = unknown>(
    controller: string,
    action?: string,
    params?: ApiOptions['params'],
  ): Observable<T> {
    return this.request<T>({ controller, action, method: 'GET', params });
  }

  /** GET tipado como `Paginated<T>` — para listados con `{ items, meta }`. */
  getPaginated<T = unknown>(
    controller: string,
    action?: string,
    params?: ApiOptions['params'],
  ): Observable<Paginated<T>> {
    return this.request<Paginated<T>>({ controller, action, method: 'GET', params });
  }

  post<T = unknown>(controller: string, action?: string, body?: ApiOptions['body']): Observable<T> {
    return this.request<T>({ controller, action, method: 'POST', body });
  }

  put<T = unknown>(controller: string, action?: string, body?: ApiOptions['body']): Observable<T> {
    return this.request<T>({ controller, action, method: 'PUT', body });
  }

  patch<T = unknown>(
    controller: string,
    action?: string,
    body?: ApiOptions['body'],
  ): Observable<T> {
    return this.request<T>({ controller, action, method: 'PATCH', body });
  }

  delete<T = unknown>(controller: string, action?: string): Observable<T> {
    return this.request<T>({ controller, action, method: 'DELETE' });
  }

  private buildUrl(controller: string, action?: string): string {
    return [environment.apiUrl, controller, action].filter(Boolean).join('/');
  }

  private buildHeaders(headers: ApiOptions['headers'] = {}): HttpHeaders {
    return new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...headers,
    });
  }

  private buildParams(params?: ApiOptions['params']): HttpParams {
    let httpParams = new HttpParams();
    if (!params) return httpParams;

    for (const [key, value] of Object.entries(params)) {
      if (!UNSAFE_KEYS.has(key) && value != null) {
        httpParams = httpParams.set(key, String(value));
      }
    }
    return httpParams;
  }
}
