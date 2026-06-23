import { Component, computed, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive],
  templateUrl: './app-shell.html',
})
export class AppShell {
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly currentUser = this.authService.currentUser;
  protected readonly isAdmin = computed(() =>
    this.currentUser()?.roles.includes('administrador') ?? false
  );

  protected hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

  protected readonly sidebarOpen = signal(false);
  protected readonly userMenuOpen = signal(false);

  protected toggleSidebar() {
    this.sidebarOpen.update((v: boolean) => !v);
  }

  protected closeSidebar() {
    this.sidebarOpen.set(false);
  }

  protected toggleUserMenu() {
    this.userMenuOpen.update((v: boolean) => !v);
  }

  protected closeUserMenu() {
    this.userMenuOpen.set(false);
  }

  protected logout() {
    this.authService.logout().pipe(takeUntilDestroyed(this.destroyRef)).subscribe();
  }
}
