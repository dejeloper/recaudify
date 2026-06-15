import {computed, inject, Injectable, signal} from '@angular/core';
import {Router} from '@angular/router';
import {tap} from 'rxjs';
import { lower } from '@core/utils/text';
import { User } from '@core/models/user';
import { ApiService } from '@core/services/api.service';

const TOKEN_KEY = 'auth_token';

@Injectable({providedIn: 'root'})
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
    const data = { username: lower(username), password };

    return this.api.post<{ token: string }>('auth', 'login', data).pipe(
      tap(({ token }) => {
        localStorage.setItem(TOKEN_KEY, token);
        this._token.set(token);
      }),
    );
  }

  register(name: string, username: string, email: string, password: string, password_confirmation: string) {
    const data = { name: name.trim(), username, email: lower(email) || null, password, password_confirmation };

    return this.api
      .post<{ token: string }>('auth', 'register', data)
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
