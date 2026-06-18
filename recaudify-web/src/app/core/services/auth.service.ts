import {computed, inject, Injectable, signal} from '@angular/core';
import {Router} from '@angular/router';
import {catchError, finalize, Observable, of, shareReplay, switchMap, tap} from 'rxjs';
import {lower} from '@core/utils/text';
import {ApiError} from '@core/models/api-error';
import {User} from '@core/models/user';
import {ApiService} from '@core/services/api.service';

@Injectable({providedIn: 'root'})
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  readonly currentUser = signal<User | null>(null);
  readonly isAuthenticated = computed(() => this.currentUser() !== null);

  private refreshRequest$: Observable<unknown> | null = null;

  checkAuth() {
    return this.api.get<User>('auth', 'me').pipe(
      tap(user => this.currentUser.set(user)),
      catchError((err: ApiError) => {
        if (err.statusCode === 401) {
          return this.api.post('auth', 'refresh').pipe(
            switchMap(() => this.api.get<User>('auth', 'me')),
            tap(user => this.currentUser.set(user as User)),
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
    const data = {username: lower(username), password};
    return this.api.post('auth', 'login', data).pipe(
      switchMap(() => this.me()),
    );
  }

  me() {
    return this.api.get<User>('auth', 'me').pipe(
      tap(user => this.currentUser.set(user)),
    );
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

  logout() {
    return this.api.post('auth', 'logout').pipe(
      tap(() => {
        this.currentUser.set(null);
        this.router.navigate(['/login']);
      }),
    );
  }
}
