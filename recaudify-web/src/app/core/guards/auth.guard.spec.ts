import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import {
  ActivatedRouteSnapshot,
  provideRouter,
  Router,
  RouterStateSnapshot,
  UrlTree,
} from '@angular/router';
import { firstValueFrom, Observable } from 'rxjs';
import { authGuard, guestGuard } from '@core/guards/auth.guard';
import { AuthService } from '@core/services/auth.service';
import { GeolocationService } from '@core/services/geolocation.service';

const mockRoute = {} as ActivatedRouteSnapshot;
const mockState = {} as RouterStateSnapshot;

function setupGuard(authenticated: boolean, permission: PermissionState = 'granted') {
  const auth = {
    isAuthenticated: signal(authenticated),
    geolocalizationLoginEnabled: signal(true),
    expireSession: vi.fn(),
  };
  const geo = { getPermissionState: vi.fn().mockResolvedValue(permission) };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
      { provide: GeolocationService, useValue: geo },
    ],
  });

  return { router: TestBed.inject(Router), auth, geo };
}

describe('authGuard', () => {
  it('allows access when authenticated and geolocation is not denied', async () => {
    setupGuard(true, 'granted');
    const result = TestBed.runInInjectionContext(() => authGuard(mockRoute, mockState));

    expect(await firstValueFrom(result as Observable<boolean | UrlTree>)).toBe(true);
  });

  it('redirects to /login when not authenticated', () => {
    setupGuard(false);
    const result = TestBed.runInInjectionContext(() => authGuard(mockRoute, mockState));

    expect(result).toBeInstanceOf(UrlTree);
    expect((result as UrlTree).toString()).toBe('/login');
  });

  it('expires session and redirects when geolocation is denied', async () => {
    const { auth } = setupGuard(true, 'denied');
    const result = TestBed.runInInjectionContext(() => authGuard(mockRoute, mockState));

    const value = await firstValueFrom(result as Observable<boolean | UrlTree>);

    expect(auth.expireSession).toHaveBeenCalled();
    expect(value).toBeInstanceOf(UrlTree);
    expect((value as UrlTree).toString()).toBe('/login');
  });
});

describe('guestGuard', () => {
  it('allows access when not authenticated', () => {
    setupGuard(false);
    const result = TestBed.runInInjectionContext(() => guestGuard(mockRoute, mockState));

    expect(result).toBe(true);
  });

  it('redirects to /dashboard when already authenticated', () => {
    setupGuard(true);
    const result = TestBed.runInInjectionContext(() => guestGuard(mockRoute, mockState));

    expect(result).toBeInstanceOf(UrlTree);
    expect((result as UrlTree).toString()).toBe('/dashboard');
  });
});
