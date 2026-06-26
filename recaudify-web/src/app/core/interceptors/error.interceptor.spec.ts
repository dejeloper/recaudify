import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { errorInterceptor } from '@core/interceptors/error.interceptor';
import type { ApiError } from '@core/interfaces/api-error.interface';
import { AuthService } from '@core/services/auth.service';
import { ToastService } from '@core/services/toast.service';

function setup() {
  const auth = {
    refresh: vi.fn(),
    clearSession: vi.fn(),
    expireSession: vi.fn(),
    currentUser: () => ({ name: 'Juan' }),
  };
  const toast = { error: vi.fn() };
  const router = { navigate: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      provideHttpClient(withInterceptors([errorInterceptor])),
      provideHttpClientTesting(),
      { provide: AuthService, useValue: auth },
      { provide: ToastService, useValue: toast },
      { provide: Router, useValue: router },
    ],
  });

  return {
    http: TestBed.inject(HttpClient),
    controller: TestBed.inject(HttpTestingController),
    auth,
    toast,
    router,
  };
}

describe('errorInterceptor', () => {
  it('maps a server error body to ApiError on auth paths (no refresh)', () => {
    const { http, controller } = setup();
    let error!: ApiError;
    http.get('/api/auth/me').subscribe({ error: (e) => (error = e) });

    controller
      .expectOne('/api/auth/me')
      .flush(
        { message: 'No autorizado.', statusCode: 401 },
        { status: 401, statusText: 'Unauthorized' },
      );

    expect(error.message).toBe('No autorizado.');
    expect(error.statusCode).toBe(401);
    controller.verify();
  });

  it('refreshes and retries the request on 401 (non-auth path)', () => {
    const { http, controller, auth } = setup();
    auth.refresh.mockReturnValue(of({}));
    const next = vi.fn();
    http.get('/api/users').subscribe({ next });

    controller.expectOne('/api/users').flush(null, { status: 401, statusText: 'Unauthorized' });
    // Tras el refresh, el interceptor reintenta la petición original.
    controller.expectOne('/api/users').flush([{ id: 1 }]);

    expect(auth.refresh).toHaveBeenCalled();
    expect(next).toHaveBeenCalledWith([{ id: 1 }]);
    controller.verify();
  });

  it('clears session and redirects when refresh fails', () => {
    const { http, controller, auth, router } = setup();
    auth.refresh.mockReturnValue(throwError(() => new Error('expired')));
    let error!: ApiError;
    http.get('/api/users').subscribe({ error: (e) => (error = e) });

    controller.expectOne('/api/users').flush(null, { status: 401, statusText: 'Unauthorized' });

    expect(auth.clearSession).toHaveBeenCalled();
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
    expect(error.statusCode).toBe(401);
    expect(error.message).toBe('Sesión expirada. Inicie sesión nuevamente.');
    controller.verify();
  });

  it('handles 403 out-of-schedule: expires session, toasts and redirects', () => {
    const { http, controller, auth, toast, router } = setup();
    let error!: ApiError;
    http.get('/api/users').subscribe({ error: (e) => (error = e) });

    controller
      .expectOne('/api/users')
      .flush(
        { message: 'Acceso fuera del horario permitido.' },
        { status: 403, statusText: 'Forbidden' },
      );

    expect(auth.expireSession).toHaveBeenCalled();
    expect(toast.error).toHaveBeenCalled();
    expect(router.navigate).toHaveBeenCalledWith(['/login']);
    expect(error.statusCode).toBe(403);
    controller.verify();
  });

  it('maps validation errors to ApiError.errors', () => {
    const { http, controller } = setup();
    let error!: ApiError;
    http.post('/api/users', {}).subscribe({ error: (e) => (error = e) });

    controller.expectOne('/api/users').flush(
      {
        message: 'Error de validación.',
        statusCode: 422,
        data: { username: ['El usuario ya existe.'] },
      },
      { status: 422, statusText: 'Unprocessable Entity' },
    );

    expect(error.statusCode).toBe(422);
    expect(error.errors?.['username']).toContain('El usuario ya existe.');
    controller.verify();
  });

  it('falls back to a generic message when body is empty', () => {
    const { http, controller } = setup();
    let error!: ApiError;
    http.get('/api/users').subscribe({ error: (e) => (error = e) });

    controller
      .expectOne('/api/users')
      .flush(null, { status: 500, statusText: 'Internal Server Error' });

    expect(error.message).toBe('Error inesperado. Intente nuevamente.');
    expect(error.statusCode).toBe(500);
    controller.verify();
  });
});
