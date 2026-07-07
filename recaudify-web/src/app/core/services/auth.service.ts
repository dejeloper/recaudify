import { computed, inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import {
  catchError,
  finalize,
  from,
  map,
  Observable,
  of,
  shareReplay,
  switchMap,
  tap,
  throwError,
} from 'rxjs';
import { lower } from '@core/utils/text';
import { ApiError } from '@core/interfaces/api-error.interface';
import { LoginResponse } from '@core/interfaces/auth.interface';
import { CurrentShift, User } from '@core/interfaces/user.interface';
import { ApiService } from '@core/services/api.service';
import { ConfigService } from '@core/services/config.service';
import { GeolocationService } from '@core/services/geolocation.service';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly geolocation = inject(GeolocationService);
  private readonly config = inject(ConfigService);

  readonly currentUser = signal<User | null>(null);
  readonly isAuthenticated = computed(() => this.currentUser() !== null);

  readonly currentShift = computed<CurrentShift | null>(
    () => this.currentUser()?.current_shift ?? null,
  );
  readonly shiftStatusEnabled = computed(() => this.currentUser()?.shift_status_enabled ?? false);
  readonly shiftCountdownEnabled = computed(
    () => this.currentUser()?.shift_countdown_enabled ?? false,
  );
  readonly geolocalizationLoginEnabled = computed(
    () => this.currentUser()?.geolocalization_login_enabled ?? true,
  );
  readonly passwordExpired = computed(() => this.currentUser()?.password_expired ?? false);

  hasPermission(permission: string): boolean {
    return this.currentUser()?.permissions.includes(permission) ?? false;
  }

  private refreshRequest$: Observable<unknown> | null = null;

  checkAuth() {
    return this.api.get<User>('auth', 'me').pipe(
      tap((user) => this.currentUser.set(user)),
      catchError((err: ApiError) => {
        if (err.statusCode === 401) {
          return this.api.post('auth', 'refresh').pipe(
            switchMap(() => this.api.get<User>('auth', 'me')),
            tap((user) => this.currentUser.set(user as User)),
            catchError(() => {
              this.currentUser.set(null);
              return of(null);
            }),
          );
        }
        this.currentUser.set(null);
        return of(null);
      }),
    );
  }

  login(username: string, password: string) {
    return this.config.getLoginConfig().pipe(
      switchMap((cfg) =>
        cfg.geolocalization_login
          ? from(this.geolocation.getPermissionState())
          : of<PermissionState>('denied'),
      ),
      switchMap((state) =>
        state === 'granted'
          ? this.geolocation.request().pipe(catchError(() => of(null)))
          : of(null),
      ),
      switchMap((coords: GeolocationCoordinates | null) => {
        const data: Record<string, unknown> = { username: lower(username), password };
        if (coords) {
          data['latitude'] = coords.latitude;
          data['longitude'] = coords.longitude;
          data['accuracy'] = coords.accuracy;
        }
        return this.api.post<LoginResponse>('auth', 'login', data).pipe(
          tap((res) => this.currentUser.set(res.user)),
          switchMap((res) => {
            const user = res.user;
            if (!this.geolocalizationLoginEnabled()) return of(user);
            if (coords) return of(user);
            return this.geolocation.request().pipe(
              switchMap((newCoords) => this.sendLoginLocation(newCoords).pipe(map(() => user))),
              catchError(() =>
                this.api.post('auth', 'logout').pipe(
                  tap(() => this.currentUser.set(null)),
                  switchMap(() =>
                    throwError(
                      () => new Error('Se requiere permiso de ubicación para usar la aplicación.'),
                    ),
                  ),
                ),
              ),
            );
          }),
        );
      }),
    );
  }

  changePassword(currentPassword: string, password: string, passwordConfirmation: string) {
    return this.api
      .post('auth', 'change-password', {
        current_password: currentPassword,
        password,
        password_confirmation: passwordConfirmation,
      })
      .pipe(
        tap(() => {
          const user = this.currentUser();
          if (user) this.currentUser.set({ ...user, password_expired: false });
        }),
      );
  }

  private sendLoginLocation(coords: GeolocationCoordinates) {
    return this.api
      .post('auth', 'login/location', {
        latitude: coords.latitude,
        longitude: coords.longitude,
        accuracy: coords.accuracy,
      })
      .pipe(catchError(() => of(null)));
  }

  me() {
    return this.api.get<User>('auth', 'me').pipe(tap((user) => this.currentUser.set(user)));
  }

  refresh() {
    if (!this.refreshRequest$) {
      this.refreshRequest$ = this.api.post('auth', 'refresh').pipe(
        shareReplay(1),
        finalize(() => (this.refreshRequest$ = null)),
      );
    }
    return this.refreshRequest$;
  }

  clearSession() {
    this.currentUser.set(null);
  }

  expireSession() {
    this.currentUser.set(null);
    this.api.post('auth', 'logout').subscribe();
  }

  logout() {
    return this.api.post('auth', 'logout').pipe(
      tap(() => {
        this.currentUser.set(null);
        this.router.navigate(['/login']);
      }),
    );
  }
}
