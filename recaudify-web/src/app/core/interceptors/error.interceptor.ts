import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, switchMap, throwError } from 'rxjs';
import { ApiError } from '@core/interfaces/api-error.interface';
import { AuthService } from '@core/services/auth.service';
import { ToastService } from '@core/services/toast.service';

const SCHEDULE_MESSAGES = [
  'Acceso fuera del horario permitido.',
  'No tiene horario de acceso asignado.',
];

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const toast = inject(ToastService);
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
            return throwError(
              () =>
                ({
                  message: 'Sesión expirada. Inicie sesión nuevamente.',
                  statusCode: 401,
                }) as ApiError,
            );
          }),
        );
      }

      if (err.status === 403 && SCHEDULE_MESSAGES.includes(err.error?.message)) {
        const name = auth.currentUser()?.name ?? 'usuario';
        auth.expireSession();
        toast.error(`Se acabó tu tiempo laboral, ${name}. Intenta ingresar en tu próximo horario.`);
        router.navigate(['/login']);
        return throwError(() => ({ message: err.error?.message, statusCode: 403 }) as ApiError);
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
