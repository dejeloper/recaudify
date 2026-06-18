import {HttpErrorResponse, HttpInterceptorFn} from '@angular/common/http';
import {inject} from '@angular/core';
import {Router} from '@angular/router';
import {catchError, switchMap, throwError} from 'rxjs';
import {ApiError} from '@core/models/api-error';
import {AuthService} from '@core/services/auth.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      const isAuthPath = req.url.includes('/auth/');

      if (err.status === 401 && !isAuthPath) {
        return auth.refresh().pipe(
          switchMap(() => next(req)),
          catchError(() => {
            auth.clearSession();
            router.navigate(['/login']);
            return throwError(() => ({
              message: 'Sesión expirada. Inicie sesión nuevamente.',
              statusCode: 401,
            } as ApiError));
          }),
        );
      }

      const apiError: ApiError = {
        message: err.error?.message ?? 'Error inesperado. Intente nuevamente.',
        statusCode: err.error?.statusCode ?? err.status,
        errors: err.error?.data ?? undefined,
      };
      return throwError(() => apiError);
    }),
  );
};
