import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { LoginConfig } from '@core/interfaces/login-config.interface';
import { ApiService } from '@core/services/api.service';
import { ConfigService } from '@core/services/config.service';

function loginConfig(overrides: Partial<LoginConfig> = {}): LoginConfig {
  return { geolocalization_login: false, login_field: 'username', ...overrides };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      ConfigService,
      { provide: ApiService, useValue: api },
    ],
  });

  return { service: TestBed.inject(ConfigService), api };
}

describe('ConfigService', () => {
  it('getLoginConfig fetches from the api', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of(loginConfig()));

    let result: LoginConfig | undefined;
    service.getLoginConfig().subscribe((c) => (result = c));

    expect(api.get).toHaveBeenCalledWith('auth', 'config');
    expect(result).toEqual(loginConfig());
  });

  it('getLoginConfig caches the result and does not call the api again', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of(loginConfig({ login_field: 'email' })));

    service.getLoginConfig().subscribe();
    service.getLoginConfig().subscribe();

    expect(api.get).toHaveBeenCalledTimes(1);
  });

  it('getLoginConfig returns the cached value on subsequent calls', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of(loginConfig({ login_field: 'email' })));

    let first: LoginConfig | undefined;
    let second: LoginConfig | undefined;
    service.getLoginConfig().subscribe((c) => (first = c));
    service.getLoginConfig().subscribe((c) => (second = c));

    expect(first).toEqual(second);
  });
});
