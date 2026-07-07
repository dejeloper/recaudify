import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { AuthService } from '@core/services/auth.service';
import { ConfigService } from '@core/services/config.service';
import { Login } from './login';

async function setup() {
  const auth = { login: vi.fn(), passwordExpired: vi.fn().mockReturnValue(false) };
  const config = { getLoginConfig: vi.fn().mockReturnValue(of({ geolocalization_login: true })) };
  const router = { navigate: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [Login],
    providers: [
      provideZonelessChangeDetection(),
      { provide: AuthService, useValue: auth },
      { provide: ConfigService, useValue: config },
      { provide: Router, useValue: router },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Login);
  fixture.detectChanges();
  return { fixture, comp: fixture.componentInstance as any, auth, config, router };
}

describe('Login', () => {
  it('reads the geolocation flag from config on init', async () => {
    const { config, comp } = await setup();
    config.getLoginConfig.mockReturnValue(of({ geolocalization_login: false }));
    comp.ngOnInit();

    expect(comp.geoRequired()).toBe(false);
  });

  it('navigates to /dashboard on successful login', async () => {
    const { comp, auth, router } = await setup();
    auth.login.mockReturnValue(of({}));

    comp.submit();

    expect(auth.login).toHaveBeenCalledWith('admin', 'admin1234');
    expect(router.navigate).toHaveBeenCalledWith(['/dashboard']);
    expect(comp.loading()).toBe(false);
  });

  it('shows the error message on failed login', async () => {
    const { comp, auth } = await setup();
    auth.login.mockReturnValue(throwError(() => ({ message: 'Credenciales incorrectas.' })));

    comp.submit();

    expect(comp.error()).toBe('Credenciales incorrectas.');
    expect(comp.loading()).toBe(false);
  });
});
