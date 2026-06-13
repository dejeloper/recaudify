import { HttpClient } from '@angular/common/http';
import { inject, Injectable, signal } from '@angular/core';
import { Router } from '@angular/router';
import { tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { User } from '../models/user';

const TOKEN_KEY = 'auth_token';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private http = inject(HttpClient);
  private router = inject(Router);
  private base = `${environment.apiUrl}/auth`;

  currentUser = signal<User | null>(null);

  get token(): string | null {
    return localStorage.getItem(TOKEN_KEY);
  }

  get isAuthenticated(): boolean {
    return !!this.token;
  }

  login(email: string, password: string) {
    return this.http.post<{ token: string }>(`${this.base}/login`, { email, password }).pipe(
      tap(({ token }) => localStorage.setItem(TOKEN_KEY, token)),
    );
  }

  register(name: string, email: string, password: string, password_confirmation: string) {
    return this.http
      .post<{ token: string }>(`${this.base}/register`, { name, email, password, password_confirmation })
      .pipe(tap(({ token }) => localStorage.setItem(TOKEN_KEY, token)));
  }

  me() {
    return this.http.get<User>(`${this.base}/me`).pipe(
      tap((user) => this.currentUser.set(user)),
    );
  }

  logout() {
    return this.http.post(`${this.base}/logout`, {}).pipe(
      tap(() => {
        localStorage.removeItem(TOKEN_KEY);
        this.currentUser.set(null);
        this.router.navigate(['/login']);
      }),
    );
  }
}
