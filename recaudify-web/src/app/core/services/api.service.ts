import { HttpClient, HttpHeaders, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { map, Observable } from 'rxjs';
import { environment } from '@env/environment';

interface ApiResponse<T> {
  success: boolean;
  message: string;
  statusCode: number;
  data: T;
}

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

interface ApiOptions {
  controller: string;
  action?: string;
  method?: HttpMethod;
  body?: Record<string, unknown>;
  params?: Record<string, string | number | boolean>;
  headers?: Record<string, string>;
}

const SAFE_KEYS = new Set(['__proto__', 'constructor', 'prototype']);

function sanitize(obj: Record<string, unknown>): Record<string, unknown> {
  return Object.fromEntries(Object.entries(obj).filter(([k]) => !SAFE_KEYS.has(k)));
}

@Injectable({ providedIn: 'root' })
export class ApiService {
  private http = inject(HttpClient);

  request<T = unknown>({
    controller,
    action,
    method = 'GET',
    body,
    params,
    headers = {},
  }: ApiOptions): Observable<T> {
    const url = [environment.apiUrl, controller, action].filter(Boolean).join('/');

    const secureHeaders = new HttpHeaders({
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...headers,
    });

    let httpParams = new HttpParams();
    if (params) {
      for (const [k, v] of Object.entries(params)) {
        if (!SAFE_KEYS.has(k)) httpParams = httpParams.set(k, String(v));
      }
    }

    const safeBody = body ? sanitize(body) : undefined;

    return this.http
      .request<ApiResponse<T>>(method, url, {
        headers: secureHeaders,
        params: httpParams,
        body: safeBody,
        withCredentials: true,
      })
      .pipe(map((response) => response.data));
  }

  get<T = unknown>(controller: string, action?: string, params?: ApiOptions['params']) {
    return this.request<T>({ controller, action, method: 'GET', params });
  }

  post<T = unknown>(controller: string, action?: string, body?: ApiOptions['body']) {
    return this.request<T>({ controller, action, method: 'POST', body });
  }

  put<T = unknown>(controller: string, action?: string, body?: ApiOptions['body']) {
    return this.request<T>({ controller, action, method: 'PUT', body });
  }

  patch<T = unknown>(controller: string, action?: string, body?: ApiOptions['body']) {
    return this.request<T>({ controller, action, method: 'PATCH', body });
  }

  delete<T = unknown>(controller: string, action?: string) {
    return this.request<T>({ controller, action, method: 'DELETE' });
  }
}
