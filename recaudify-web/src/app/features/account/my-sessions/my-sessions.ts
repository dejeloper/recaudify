import { Component, DestroyRef, inject, OnInit } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { DatePipe } from '@angular/common';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { UserSession } from '@core/interfaces/user-session.interface';
import { ToastService } from '@core/services/toast.service';
import { UserSessionsService } from '@core/services/user-sessions.service';

@Component({
  selector: 'app-my-sessions',
  imports: [DatePipe, BtnDirective, Spinner],
  templateUrl: './my-sessions.html',
})
export class MySessions implements OnInit {
  private readonly sessionsService = inject(UserSessionsService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly sessions = this.sessionsService.sessions;
  protected readonly loading = this.sessionsService.loading;

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.sessionsService.loadMine().pipe(takeUntilDestroyed(this.destroyRef)).subscribe();
  }

  protected revoke(session: UserSession): void {
    if (!confirm('¿Cerrar esta sesión?')) return;

    this.sessionsService
      .revokeMine(session.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Sesión cerrada correctamente.');
          this.load();
        },
        error: () => this.toast.error('No se pudo cerrar la sesión.'),
      });
  }

  protected revokeAll(): void {
    if (!confirm('¿Cerrar todas las demás sesiones?')) return;

    this.sessionsService
      .revokeAllMine()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Sesiones cerradas correctamente.');
          this.load();
        },
        error: () => this.toast.error('No se pudieron cerrar las sesiones.'),
      });
  }
}
