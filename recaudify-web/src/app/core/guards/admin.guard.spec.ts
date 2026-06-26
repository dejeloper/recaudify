import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import {
  ActivatedRouteSnapshot,
  provideRouter,
  Router,
  RouterStateSnapshot,
  UrlTree,
} from '@angular/router';
import { adminGuard } from '@core/guards/admin.guard';
import { AuthService } from '@core/services/auth.service';

const mockRoute = {} as ActivatedRouteSnapshot;
const mockState = {} as RouterStateSnapshot;

function setup(roles: string[]) {
  const auth = { currentUser: () => ({ roles }) };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
    ],
  });

  return { router: TestBed.inject(Router) };
}

describe('adminGuard', () => {
  it('allows access for administrador', () => {
    setup(['administrador']);
    const result = TestBed.runInInjectionContext(() => adminGuard(mockRoute, mockState));
    expect(result).toBe(true);
  });

  it('allows access for superadmin', () => {
    setup(['superadmin']);
    const result = TestBed.runInInjectionContext(() => adminGuard(mockRoute, mockState));
    expect(result).toBe(true);
  });

  it('redirects to /dashboard for non-admin roles', () => {
    setup(['cobrador']);
    const result = TestBed.runInInjectionContext(() => adminGuard(mockRoute, mockState));

    expect(result).toBeInstanceOf(UrlTree);
    expect((result as UrlTree).toString()).toBe('/dashboard');
  });
});
