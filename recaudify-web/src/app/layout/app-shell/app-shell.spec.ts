import { provideZonelessChangeDetection, signal, WritableSignal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { MenuItem } from '@core/interfaces/nav.interface';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { MenuService } from '@core/services/menu.service';
import { ShiftStatusService } from '@core/services/shift-status.service';
import { AppShell } from './app-shell';

interface AppShellHarness {
  isItemVisible: (item: MenuItem) => boolean;
  hasVisibleItems: (group: MenuItem) => boolean;
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

function makeItem(overrides: Partial<MenuItem> = {}): MenuItem {
  return {
    id: 1,
    parent_id: null,
    label: 'Item',
    icons: [],
    route: null,
    permission: null,
    order: 0,
    is_active: true,
    ...overrides,
  };
}

async function setup(user: User | null, permissions: string[] = []) {
  const auth = {
    currentUser: signal(user),
    hasPermission: (p: string) => permissions.includes(p),
    shiftCountdownEnabled: signal(false),
    logout: vi.fn().mockReturnValue(of(undefined)),
  };
  const shiftStatus = { visibleShift: signal(null), countdownMinutes: signal(0) };
  const menuService = { menuTree: signal<MenuItem[]>([]), load: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [AppShell],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: AuthService, useValue: auth },
      { provide: ShiftStatusService, useValue: shiftStatus },
      { provide: MenuService, useValue: menuService },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(AppShell);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as unknown as AppShellHarness, auth, menuService };
}

describe('AppShell', () => {
  it('isItemVisible allows items without a permission requirement', async () => {
    const { comp } = await setup(makeUser());

    expect(comp.isItemVisible(makeItem({ label: 'Clientes', route: null }))).toBe(true);
  });

  it('isItemVisible respects hasPermission for items with a permission', async () => {
    const { comp } = await setup(makeUser(), ['users.view']);

    expect(
      comp.isItemVisible(
        makeItem({ label: 'Usuarios', route: '/admin/users', permission: 'users.view' }),
      ),
    ).toBe(true);
    expect(
      comp.isItemVisible(
        makeItem({ label: 'Roles', route: '/admin/roles', permission: 'roles.view' }),
      ),
    ).toBe(false);
  });

  it('isItemVisible for a header item (no route) checks its children', async () => {
    const { comp } = await setup(makeUser(), ['users.view']);

    const header = makeItem({
      label: 'Usuarios',
      route: null,
      children: [makeItem({ id: 2, route: '/admin/users', permission: 'users.view' })],
    });

    expect(comp.isItemVisible(header)).toBe(true);
  });

  it('hasVisibleItems is true if at least one child is visible', async () => {
    const { comp } = await setup(makeUser(), ['users.view']);

    const group = makeItem({
      label: 'Usuarios',
      children: [
        makeItem({ id: 2, label: 'Usuarios', route: '/admin/users', permission: 'users.view' }),
        makeItem({ id: 3, label: 'Roles', route: '/admin/roles', permission: 'roles.view' }),
      ],
    });

    expect(comp.hasVisibleItems(group)).toBe(true);
  });

  it('hasVisibleItems is false when no child is visible', async () => {
    const { comp } = await setup(makeUser(), []);

    const group = makeItem({
      label: 'Usuarios',
      children: [
        makeItem({ id: 2, label: 'Usuarios', route: '/admin/users', permission: 'users.view' }),
      ],
    });

    expect(comp.hasVisibleItems(group)).toBe(false);
  });

  it('loads the menu tree on init', async () => {
    const { menuService } = await setup(makeUser());

    expect(menuService.load).toHaveBeenCalled();
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
