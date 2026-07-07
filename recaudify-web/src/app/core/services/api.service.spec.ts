import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { ApiService } from '@core/services/api.service';
import { ApiError } from '@core/interfaces/api-error.interface';

function setup() {
  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      provideHttpClient(),
      provideHttpClientTesting(),
      ApiService,
    ],
  });

  return {
    api: TestBed.inject(ApiService),
    controller: TestBed.inject(HttpTestingController),
  };
}

describe('ApiService', () => {
  it('unwraps response.data from the standard envelope', () => {
    const { api, controller } = setup();
    let result: unknown;
    api.get('products').subscribe((r) => (result = r));

    controller
      .expectOne((r) => r.url.endsWith('/products'))
      .flush({ success: true, message: 'ok', statusCode: 200, data: [{ id: 1 }] });

    expect(result).toEqual([{ id: 1 }]);
    controller.verify();
  });

  it('sanitizes unsafe keys from the body (incl. nested)', () => {
    const { api, controller } = setup();
    // JSON.parse crea claves propias "__proto__"/"constructor" (un literal no lo haría).
    const body = JSON.parse('{"name":"x","__proto__":1,"nested":{"constructor":2,"ok":3}}');

    api.post('products', undefined, body).subscribe();
    const req = controller.expectOne((r) => r.url.endsWith('/products'));

    const sent = req.request.body as Record<string, unknown>;
    expect(Object.prototype.hasOwnProperty.call(sent, '__proto__')).toBe(false);
    expect(sent['name']).toBe('x');
    const nested = sent['nested'] as Record<string, unknown>;
    expect(Object.prototype.hasOwnProperty.call(nested, 'constructor')).toBe(false);
    expect(nested['ok']).toBe(3);

    req.flush({ data: null });
    controller.verify();
  });

  it('builds query params skipping null/undefined values', () => {
    const { api, controller } = setup();
    api.get('products', undefined, { a: 1, b: false }).subscribe();

    const req = controller.expectOne((r) => r.url.endsWith('/products') && r.params.has('a'));
    expect(req.request.params.get('a')).toBe('1');
    expect(req.request.params.get('b')).toBe('false');

    req.flush({ data: [] });
    controller.verify();
  });

  it('maps an error response to ApiError', () => {
    const { api, controller } = setup();
    let error!: ApiError;
    api.post('users', undefined, {}).subscribe({ error: (e) => (error = e) });

    controller
      .expectOne((r) => r.url.endsWith('/users'))
      .flush(
        { message: 'Error de validación.', errors: { username: ['Ya existe.'] } },
        { status: 422, statusText: 'Unprocessable Entity' },
      );

    expect(error.statusCode).toBe(422);
    expect(error.message).toBe('Error de validación.');
    expect(error.errors?.['username']).toContain('Ya existe.');
    controller.verify();
  });

  it('getPaginated returns the { items, meta } payload', () => {
    const { api, controller } = setup();
    let result: { items: unknown[]; meta: unknown } | undefined;
    api.getPaginated('activities').subscribe((r) => (result = r));

    controller
      .expectOne((r) => r.url.endsWith('/activities'))
      .flush({
        data: { items: [{ id: 1 }], meta: { total: 1, page: 1, perPage: 25, lastPage: 1 } },
      });

    expect(result?.items).toEqual([{ id: 1 }]);
    expect(result?.meta).toEqual({ total: 1, page: 1, perPage: 25, lastPage: 1 });
    controller.verify();
  });
});
