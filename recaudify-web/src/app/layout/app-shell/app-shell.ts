import { DecimalPipe } from '@angular/common';
import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { MenuItem } from '@core/interfaces/nav.interface';
import { AuthService } from '@core/services/auth.service';
import { MenuService } from '@core/services/menu.service';
import { ShiftStatusService } from '@core/services/shift-status.service';

@Component({
  selector: 'app-shell',
  imports: [RouterOutlet, RouterLink, RouterLinkActive, DecimalPipe],
  templateUrl: './app-shell.html',
})
export class AppShell implements OnInit {
  private readonly authService = inject(AuthService);
  private readonly menuService = inject(MenuService);
  private readonly shiftStatus = inject(ShiftStatusService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly navGroups = this.menuService.menuTree;
  protected readonly currentUser = this.authService.currentUser;

  protected readonly sidebarOpen = signal(false);
  protected readonly userMenuOpen = signal(false);
  protected readonly groupOverrides = signal<ReadonlyMap<number, boolean>>(new Map());
  protected readonly itemOverrides = signal<ReadonlyMap<number, boolean>>(new Map());

  protected readonly shiftCountdownEnabled = this.authService.shiftCountdownEnabled;
  protected readonly visibleShift = this.shiftStatus.visibleShift;
  protected readonly countdownMinutes = this.shiftStatus.countdownMinutes;

  ngOnInit() {
    this.menuService.load();
  }

  protected hasPermission(permission: string): boolean {
    return this.authService.hasPermission(permission);
  }

  protected isItemVisible(item: MenuItem): boolean {
    const ownVisible = !item.permission || this.hasPermission(item.permission);
    if (!item.route) {
      return item.children?.some((child) => this.isItemVisible(child)) ?? ownVisible;
    }
    return ownVisible;
  }

  protected hasVisibleItems(group: MenuItem): boolean {
    return (group.children ?? []).some((item) => this.isItemVisible(item));
  }

  protected isGroupOpen(group: MenuItem, isFirst: boolean): boolean {
    return this.groupOverrides().get(group.id) ?? isFirst;
  }

  protected toggleGroup(group: MenuItem, isFirst: boolean) {
    this.groupOverrides.update((overrides) => {
      const next = new Map(overrides);
      next.set(group.id, !this.isGroupOpen(group, isFirst));
      return next;
    });
  }

  protected isItemOpen(item: MenuItem, isFirst: boolean): boolean {
    return this.itemOverrides().get(item.id) ?? isFirst;
  }

  protected toggleItem(item: MenuItem, isFirst: boolean) {
    this.itemOverrides.update((overrides) => {
      const next = new Map(overrides);
      next.set(item.id, !this.isItemOpen(item, isFirst));
      return next;
    });
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
