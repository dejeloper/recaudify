import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import {
  ActivatedRouteSnapshot,
  provideRouter,
  Router,
  RouterStateSnapshot,
  UrlTree,
} from '@angular/router';
import { permissionGuard } from '@core/guards/permission.guard';
import { AuthService } from '@core/services/auth.service';

const mockRoute = {} as ActivatedRouteSnapshot;
const mockState = {} as RouterStateSnapshot;

function setup(permissions: string[]) {
  const auth = { hasPermission: (p: string) => permissions.includes(p) };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
    ],
  });

  return { router: TestBed.inject(Router) };
}

describe('permissionGuard', () => {
  it('allows access when the user has the permission', () => {
    setup(['clientes.ver']);
    const guard = permissionGuard('clientes.ver');

    const result = TestBed.runInInjectionContext(() => guard(mockRoute, mockState));

    expect(result).toBe(true);
  });

  it('redirects to /dashboard when the permission is missing', () => {
    setup([]);
    const guard = permissionGuard('clientes.ver');

    const result = TestBed.runInInjectionContext(() => guard(mockRoute, mockState));

    expect(result).toBeInstanceOf(UrlTree);
    expect((result as UrlTree).toString()).toBe('/dashboard');
  });
});
