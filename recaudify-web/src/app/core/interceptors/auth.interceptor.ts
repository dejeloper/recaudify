import { HttpInterceptorFn } from '@angular/common/http';

// Cookie HTTP-only enviada automáticamente por el navegador con withCredentials: true.
// Este interceptor ya no inyecta el header Authorization.
export const authInterceptor: HttpInterceptorFn = (req, next) => next(req);
