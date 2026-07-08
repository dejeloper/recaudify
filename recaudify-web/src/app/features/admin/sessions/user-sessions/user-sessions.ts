import { Component, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { DatePipe } from '@angular/common';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { User } from '@core/interfaces/user.interface';
import { UserSession } from '@core/interfaces/user-session.interface';
import { ToastService } from '@core/services/toast.service';
import { UserSessionsService } from '@core/services/user-sessions.service';
import { UsersService } from '@core/services/users.service';

@Component({
  selector: 'app-user-sessions',
  imports: [RouterLink, DatePipe, BtnDirective, Spinner],
  templateUrl: './user-sessions.html',
})
export class UserSessions implements OnInit {
  readonly userId = input<string>('');

  private readonly usersService = inject(UsersService);
  private readonly sessionsService = inject(UserSessionsService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly user = signal<User | null>(null);
  protected readonly sessions = this.sessionsService.sessions;
  protected readonly loading = this.sessionsService.loading;

  ngOnInit() {
    const id = Number(this.userId());
    this.usersService
      .getById(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({ next: (user) => this.user.set(user) });
    this.load();
  }

  private load(): void {
    this.sessionsService
      .loadForUser(Number(this.userId()))
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe();
  }

  protected revoke(session: UserSession): void {
    if (!confirm('¿Revocar esta sesión?')) return;

    this.sessionsService
      .revoke(session.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Sesión revocada correctamente.');
          this.load();
        },
        error: () => this.toast.error('No se pudo revocar la sesión.'),
      });
  }
}
