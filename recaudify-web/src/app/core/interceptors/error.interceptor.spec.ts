import { HttpClient, provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { provideZonelessChangeDetection } from '@angular/core';
import { errorInterceptor } from '@core/interceptors/error.interceptor';
import type { ApiError } from '@core/models/api-error';

describe('errorInterceptor', () => {
  let http: HttpClient;
  let controller: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideZonelessChangeDetection(),
        provideHttpClient(withInterceptors([errorInterceptor])),
        provideHttpClientTesting(),
      ],
    });

    http = TestBed.inject(HttpClient);
    controller = TestBed.inject(HttpTestingController);
  });

  afterEach(() => controller.verify());

  it('maps server error body to ApiError', () => {
    let error!: ApiError;
    http.get('/test').subscribe({ error: (e) => (error = e) });

    controller.expectOne('/test').flush(
      { message: 'No autorizado.', statusCode: 401 },
      { status: 401, statusText: 'Unauthorized' },
    );

    expect(error.message).toBe('No autorizado.');
    expect(error.statusCode).toBe(401);
    expect(error.errors).toBeUndefined();
  });

  it('maps validation errors to ApiError.errors', () => {
    let error!: ApiError;
    http.post('/test', {}).subscribe({ error: (e) => (error = e) });

    controller.expectOne('/test').flush(
      {
        message: 'Error de validación.',
        statusCode: 422,
        data: { username: ['El usuario ya existe.'] },
      },
      { status: 422, statusText: 'Unprocessable Entity' },
    );

    expect(error.message).toBe('Error de validación.');
    expect(error.statusCode).toBe(422);
    expect(error.errors?.['username']).toContain('El usuario ya existe.');
  });

  it('falls back to generic message when body is empty', () => {
    let error!: ApiError;
    http.get('/test').subscribe({ error: (e) => (error = e) });

    controller.expectOne('/test').flush(null, { status: 500, statusText: 'Internal Server Error' });

    expect(error.message).toBe('Error inesperado. Intente nuevamente.');
    expect(error.statusCode).toBe(500);
  });
});
