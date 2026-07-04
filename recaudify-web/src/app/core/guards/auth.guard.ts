import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { from, map } from 'rxjs';
import { AuthService } from '@core/services/auth.service';
import { GeolocationService } from '@core/services/geolocation.service';

export const authGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const geolocation = inject(GeolocationService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) return router.createUrlTree(['/login']);

  if (!auth.geolocalizationLoginEnabled()) return true;

  return from(geolocation.getPermissionState()).pipe(
    map((state) => {
      if (state === 'denied') {
        auth.expireSession();
        return router.createUrlTree(['/login']);
      }
      return true;
    }),
  );
};

export const guestGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) return true;

  return router.createUrlTree(['/dashboard']);
};
