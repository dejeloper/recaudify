import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

export const superadminGuard: CanActivateFn = () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  const roles = auth.currentUser()?.roles ?? [];
  if (roles.includes('superadmin')) return true;

  return router.createUrlTree(['/dashboard']);
};
