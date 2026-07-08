import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { DatePipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { SessionFilters, UserSession } from '@core/interfaces/user-session.interface';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { ToastService } from '@core/services/toast.service';
import { UserSessionsService } from '@core/services/user-sessions.service';
import { UsersService } from '@core/services/users.service';

@Component({
  selector: 'app-sessions',
  imports: [DatePipe, FormsModule, BtnDirective, Spinner],
  templateUrl: './sessions.html',
})
export class Sessions implements OnInit {
  private readonly usersService = inject(UsersService);
  private readonly sessionsService = inject(UserSessionsService);
  private readonly authService = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly users = signal<User[]>([]);
  protected readonly sessions = this.sessionsService.items;
  protected readonly meta = this.sessionsService.meta;
  protected readonly loading = this.sessionsService.loading;
  protected readonly loadingMore = this.sessionsService.loadingMore;

  protected readonly filterUserId = signal<number | null>(null);
  protected readonly filterDeviceType = signal<'' | 'mobile' | 'tablet' | 'desktop'>('');
  protected readonly filterIpAddress = signal('');

  ngOnInit() {
    this.usersService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((users) => this.users.set(users));

    this.load();
  }

  protected applyFilters(): void {
    this.load();
  }

  protected loadMore(): void {
    this.sessionsService.loadMore();
  }

  protected hasMore(): boolean {
    return this.sessionsService.hasMore();
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

  protected panic(): void {
    const confirmed = confirm(
      '¿Cerrar TODAS las sesiones de TODOS los usuarios, incluida la tuya? Deberás volver a iniciar sesión.',
    );
    if (!confirmed) return;

    this.sessionsService
      .revokeAllGlobal()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.authService.clearSession();
          this.router.navigate(['/login']);
          this.toast.success('Todas las sesiones fueron cerradas.');
        },
        error: () => this.toast.error('No se pudieron cerrar las sesiones.'),
      });
  }

  private load(): void {
    const filters: SessionFilters = {};
    if (this.filterUserId() != null) filters.user_id = this.filterUserId()!;
    if (this.filterDeviceType())
      filters.device_type = this.filterDeviceType() as 'mobile' | 'tablet' | 'desktop';
    if (this.filterIpAddress().trim()) filters.ip_address = this.filterIpAddress().trim();

    this.sessionsService.loadAll(filters);
  }
}
