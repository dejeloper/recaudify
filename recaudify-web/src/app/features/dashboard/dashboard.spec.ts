import { provideZonelessChangeDetection, Signal, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { Dashboard } from './dashboard';

interface DashboardHarness {
  currentUser: Signal<User | null>;
}

function makeUser(): User {
  return {
    id: 1,
    name: 'Juan',
    username: 'juan',
    email: null,
    active: true,
    roles: [],
    permissions: [],
  } as User;
}

async function setup(currentUser: User | null) {
  const auth = { currentUser: signal(currentUser), me: vi.fn().mockReturnValue(of(makeUser())) };

  await TestBed.configureTestingModule({
    imports: [Dashboard],
    providers: [provideZonelessChangeDetection(), { provide: AuthService, useValue: auth }],
  }).compileComponents();

  const fixture = TestBed.createComponent(Dashboard);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as unknown as DashboardHarness, auth };
}

describe('Dashboard', () => {
  it('calls AuthService.me() on init when there is no current user', async () => {
    const { auth } = await setup(null);

    expect(auth.me).toHaveBeenCalled();
  });

  it('does not call AuthService.me() when a current user is already set', async () => {
    const { auth } = await setup(makeUser());

    expect(auth.me).not.toHaveBeenCalled();
  });

  it('exposes currentUser from AuthService', async () => {
    const user = makeUser();
    const { comp } = await setup(user);

    expect(comp.currentUser()).toEqual(user);
  });
});
