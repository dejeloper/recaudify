import {inject} from '@angular/core';
import {CanActivateFn, Router} from '@angular/router';
import {AuthService} from '@core/services/auth.service';

export const permissionGuard =
  (permission: string): CanActivateFn =>
    () => {
      const auth = inject(AuthService);
      const router = inject(Router);

      if (auth.hasPermission(permission)) return true;

      return router.createUrlTree(['/dashboard']);
    };
