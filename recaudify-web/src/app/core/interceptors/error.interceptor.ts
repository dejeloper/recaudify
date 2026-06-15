import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { catchError, throwError } from 'rxjs';
import { ApiError } from '../models/api-error';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  return next(req).pipe(
    catchError((err: HttpErrorResponse) => {
      const apiError: ApiError = {
        message: err.error?.message ?? 'Error inesperado. Intente nuevamente.',
        statusCode: err.error?.statusCode ?? err.status,
        errors: err.error?.data ?? undefined,
      };
      return throwError(() => apiError);
    }),
  );
};
