import { DecimalPipe } from '@angular/common';
import { Component, computed, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { ShiftStatusService } from '@core/services/shift-status.service';
import { NAV_GROUPS, NavGroup, NavItem } from './nav-groups';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, DecimalPipe],
  templateUrl: './app-shell.html',
})
export class AppShell {
  private readonly authService = inject(AuthService);
  private readonly shiftStatus = inject(ShiftStatusService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly navGroups = NAV_GROUPS;
  protected readonly currentUser = this.authService.currentUser;
  protected readonly isAdmin = computed(() => {
    const roles = this.currentUser()?.roles ?? [];
    return roles.includes('administrador') || roles.includes('superadmin');
  });

  protected readonly sidebarOpen = signal(false);
  protected readonly userMenuOpen = signal(false);
  protected readonly openSection = signal<string>('users');

  protected readonly shiftCountdownEnabled = this.authService.shiftCountdownEnabled;
  protected readonly visibleShift = this.shiftStatus.visibleShift;
  protected readonly countdownMinutes = this.shiftStatus.countdownMinutes;

  protected hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

  protected isItemVisible(item: NavItem): boolean {
    return !item.permission || this.hasPermission(item.permission);
  }

  protected hasVisibleItems(group: NavGroup): boolean {
    return group.items.some((item) => this.isItemVisible(item));
  }

  protected toggleSidebar() {
    this.sidebarOpen.update((v) => !v);
  }

  protected closeSidebar() {
    this.sidebarOpen.set(false);
  }

  protected toggleUserMenu() {
    this.userMenuOpen.update((v) => !v);
  }

  protected closeUserMenu() {
    this.userMenuOpen.set(false);
  }

  protected logout() {
    this.authService.logout().pipe(takeUntilDestroyed(this.destroyRef)).subscribe();
  }
}
