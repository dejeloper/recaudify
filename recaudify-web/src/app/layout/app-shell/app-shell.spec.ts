import { provideZonelessChangeDetection, signal, WritableSignal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { NavGroup, NavItem } from '@core/interfaces/nav.interface';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { ShiftStatusService } from '@core/services/shift-status.service';
import { AppShell } from './app-shell';

interface AppShellHarness {
  isAdmin: () => boolean;
  isItemVisible: (item: NavItem) => boolean;
  hasVisibleItems: (group: NavGroup) => boolean;
  sidebarOpen: WritableSignal<boolean>;
  toggleSidebar: () => void;
  closeSidebar: () => void;
  userMenuOpen: WritableSignal<boolean>;
  toggleUserMenu: () => void;
  closeUserMenu: () => void;
  logout: () => void;
}

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: 'Juan',
    username: 'juan',
    email: null,
    active: true,
    roles: [],
    permissions: [],
    ...overrides,
  } as User;
}

async function setup(user: User | null, permissions: string[] = []) {
  const auth = {
    currentUser: signal(user),
    hasPermission: (p: string) => permissions.includes(p),
    shiftCountdownEnabled: signal(false),
    logout: vi.fn().mockReturnValue(of(undefined)),
  };
  const shiftStatus = { visibleShift: signal(null), countdownMinutes: signal(0) };

  await TestBed.configureTestingModule({
    imports: [AppShell],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
      { provide: ShiftStatusService, useValue: shiftStatus },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(AppShell);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as unknown as AppShellHarness, auth };
}

describe('AppShell', () => {
  it('isAdmin is true for the administrador role', async () => {
    const { comp } = await setup(makeUser({ roles: ['administrador'] }));
    expect(comp.isAdmin()).toBe(true);
  });

  it('isAdmin is true for the superadmin role', async () => {
    const { comp } = await setup(makeUser({ roles: ['superadmin'] }));
    expect(comp.isAdmin()).toBe(true);
  });

  it('isAdmin is false for other roles', async () => {
    const { comp } = await setup(makeUser({ roles: ['cobrador'] }));
    expect(comp.isAdmin()).toBe(false);
  });

  it('isItemVisible allows items without a permission requirement', async () => {
    const { comp } = await setup(makeUser());

    expect(comp.isItemVisible({ label: 'Clientes', icons: [], route: '#' })).toBe(true);
  });

  it('isItemVisible respects hasPermission for items with a permission', async () => {
    const { comp } = await setup(makeUser(), ['users.view']);

    expect(
      comp.isItemVisible({ label: 'Usuarios', icons: [], route: '/admin/users', permission: 'users.view' }),
    ).toBe(true);
    expect(
      comp.isItemVisible({ label: 'Roles', icons: [], route: '/admin/roles', permission: 'roles.view' }),
    ).toBe(false);
  });

  it('hasVisibleItems is true if at least one item in the group is visible', async () => {
    const { comp } = await setup(makeUser(), ['users.view']);

    const group = {
      key: 'users',
      label: 'Usuarios',
      items: [
        { label: 'Usuarios', icons: [], route: '/admin/users', permission: 'users.view' },
        { label: 'Roles', icons: [], route: '/admin/roles', permission: 'roles.view' },
      ],
    };

    expect(comp.hasVisibleItems(group)).toBe(true);
  });

  it('hasVisibleItems is false when no item in the group is visible', async () => {
    const { comp } = await setup(makeUser(), []);

    const group = {
      key: 'users',
      label: 'Usuarios',
      items: [{ label: 'Usuarios', icons: [], route: '/admin/users', permission: 'users.view' }],
    };

    expect(comp.hasVisibleItems(group)).toBe(false);
  });

  it('toggleSidebar and closeSidebar control sidebarOpen', async () => {
    const { comp } = await setup(makeUser());

    expect(comp.sidebarOpen()).toBe(false);
    comp.toggleSidebar();
    expect(comp.sidebarOpen()).toBe(true);
    comp.toggleSidebar();
    expect(comp.sidebarOpen()).toBe(false);
    comp.toggleSidebar();
    comp.closeSidebar();
    expect(comp.sidebarOpen()).toBe(false);
  });

  it('toggleUserMenu and closeUserMenu control userMenuOpen', async () => {
    const { comp } = await setup(makeUser());

    expect(comp.userMenuOpen()).toBe(false);
    comp.toggleUserMenu();
    expect(comp.userMenuOpen()).toBe(true);
    comp.closeUserMenu();
    expect(comp.userMenuOpen()).toBe(false);
  });

  it('logout delegates to AuthService.logout()', async () => {
    const { comp, auth } = await setup(makeUser());

    comp.logout();

    expect(auth.logout).toHaveBeenCalled();
  });
});
