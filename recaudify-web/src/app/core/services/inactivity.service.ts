import { inject, Injectable } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

const ACTIVITY_EVENTS = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart'] as const;

@Injectable({ providedIn: 'root' })
export class InactivityService {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  private timeoutId: ReturnType<typeof setTimeout> | null = null;
  private listening = false;
  private readonly onActivity = () => this.resetTimer();

  start(): void {
    if (this.listening) return;
    if (!this.timeoutMinutes()) return;

    this.listening = true;
    ACTIVITY_EVENTS.forEach((event) =>
      document.addEventListener(event, this.onActivity, { passive: true }),
    );
    this.resetTimer();
  }

  stop(): void {
    if (!this.listening) return;

    this.listening = false;
    ACTIVITY_EVENTS.forEach((event) => document.removeEventListener(event, this.onActivity));
    this.clearTimer();
  }

  private resetTimer(): void {
    this.clearTimer();

    const minutes = this.timeoutMinutes();
    if (!minutes) {
      this.stop();
      return;
    }

    this.timeoutId = setTimeout(() => this.expire(), minutes * 60_000);
  }

  private clearTimer(): void {
    if (this.timeoutId !== null) {
      clearTimeout(this.timeoutId);
      this.timeoutId = null;
    }
  }

  private timeoutMinutes(): number {
    return this.authService.currentUser()?.session_timeout_minutes ?? 0;
  }

  private expire(): void {
    this.stop();
    this.authService.expireSession();
    this.router.navigate(['/login']);
  }
}
