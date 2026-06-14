import { computed, inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import { tap } from 'rxjs';
import { User } from '../models/user';
import { ApiService } from './api.service';

const TOKEN_KEY = 'auth_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly api = inject(ApiService);
  private readonly router = inject(Router);

  private readonly _token = signal<string | null>(localStorage.getItem(TOKEN_KEY));

  readonly currentUser = signal<User | null>(null);
  readonly isAuthenticated = computed(() => !!this._token());

  get token(): string | null {
    return this._token();
  }

  login(username: string, password: string) {
    return this.api.post<{ token: string }>('auth', 'login', { username, password }).pipe(
      tap(({ token }) => {
        localStorage.setItem(TOKEN_KEY, token);
        this._token.set(token);
      }),
    );
  }

  register(name: string, email: string, password: string, password_confirmation: string) {
    return this.api
      .post<{ token: string }>('auth', 'register', { name, email, password, password_confirmation })
      .pipe(tap(({ token }) => {
        localStorage.setItem(TOKEN_KEY, token);
        this._token.set(token);
      }));
  }

  me() {
    return this.api.get<User>('auth', 'me').pipe(
      tap((user) => this.currentUser.set(user)),
    );
  }

  logout() {
    return this.api.post('auth', 'logout').pipe(
      tap(() => {
        localStorage.removeItem(TOKEN_KEY);
        this._token.set(null);
        this.currentUser.set(null);
        this.router.navigate(['/login']);
      }),
    );
  }
}
