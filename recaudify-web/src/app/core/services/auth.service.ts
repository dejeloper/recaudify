import {computed, inject, Injectable, signal} from '@angular/core';
import {Router} from '@angular/router';
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
import {lower} from '@core/utils/text';
import {ApiError} from '@core/interfaces/api-error.interface';
import {CurrentShift, User} from '@core/interfaces/user.interface';
import {ApiService} from '@core/services/api.service';
import {GeolocationService} from '@core/services/geolocation.service';

interface LoginResponse {
  token: string;
  token_type: string;
  expires_in: number;
  user: User;
}

@Injectable({providedIn: 'root'})
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);
  private readonly geolocation = inject(GeolocationService);

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
    return from(this.geolocation.getPermissionState()).pipe(
      switchMap((state) =>
        state === 'granted'
          ? this.geolocation.request().pipe(catchError(() => of(null)))
          : of(null),
      ),
      switchMap((coords: GeolocationCoordinates | null) => {
        const data: Record<string, unknown> = {username: lower(username), password};
        if (coords) {
          data['latitude'] = coords.latitude;
          data['longitude'] = coords.longitude;
          data['accuracy'] = coords.accuracy;
        }
        return this.api.post<LoginResponse>('auth', 'login', data).pipe(
          tap((res) => this.currentUser.set(res.user)),
          switchMap((res) => {
            const user = res.user;
            if (!(user.geolocalization_login_enabled ?? true)) return of(user);
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
