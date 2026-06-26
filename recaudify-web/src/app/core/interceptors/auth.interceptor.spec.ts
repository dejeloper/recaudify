import { HttpRequest } from '@angular/common/http';
import { of } from 'rxjs';
import { authInterceptor } from '@core/interceptors/auth.interceptor';

describe('authInterceptor', () => {
  it('passes the request through unchanged (cookie-based auth)', () => {
    const req = new HttpRequest('GET', '/api/users');
    const next = vi.fn().mockReturnValue(of('response'));

    let result: unknown;
    authInterceptor(req, next).subscribe((r) => (result = r));

    expect(next).toHaveBeenCalledWith(req);
    expect(result).toBe('response');
  });
});
