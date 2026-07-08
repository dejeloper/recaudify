import { inject, Injectable, signal } from '@angular/core';
import { UserSession } from '@core/interfaces/user-session.interface';
import { ApiService } from '@core/services/api.service';
import { tap } from 'rxjs';

@Injectable({ providedIn: 'root' })
export class UserSessionsService {
  private readonly api = inject(ApiService);

  readonly sessions = signal<UserSession[]>([]);
  readonly loading = signal(false);

  loadMine() {
    this.loading.set(true);
    return this.api.get<UserSession[]>('auth', 'sessions').pipe(
      tap({
        next: (sessions) => {
          this.sessions.set(sessions);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      }),
    );
  }

  revokeMine(id: number) {
    return this.api.post<void>('auth', `sessions/${id}/revoke`);
  }

  revokeAllMine() {
    return this.api.post<void>('auth', 'sessions/revoke-all');
  }

  loadForUser(userId: number) {
    this.loading.set(true);
    return this.api.get<UserSession[]>('sessions', undefined, { user_id: userId }).pipe(
      tap({
        next: (sessions) => {
          this.sessions.set(sessions);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      }),
    );
  }

  revoke(id: number) {
    return this.api.post<void>('sessions', `${id}/revoke`);
  }
}
