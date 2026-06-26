import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { User } from '@core/interfaces/user.interface';
import { ApiService } from '@core/services/api.service';
import { AuthService } from '@core/services/auth.service';
import { GeolocationService } from '@core/services/geolocation.service';

function makeUser(overrides: Partial<User> = {}): User {
  return {
    id: 1,
    name: 'Juan',
    username: 'juan',
    email: null,
    roles: [],
    permissions: [],
    geolocalization_login_enabled: false,
    ...overrides,
  } as User;
}

function setup() {
  const api = { post: vi.fn(), get: vi.fn() };
  const router = { navigate: vi.fn() };
  const geo = { request: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      AuthService,
      { provide: ApiService, useValue: api },
      { provide: Router, useValue: router },
      { provide: GeolocationService, useValue: geo },
    ],
  });

  return { service: TestBed.inject(AuthService), api, router, geo };
}

describe('AuthService', () => {
  describe('initial state', () => {
    it('is not authenticated without a current user', () => {
      const { service } = setup();
      expect(service.isAuthenticated()).toBe(false);
      expect(service.currentUser()).toBeNull();
    });
  });

  describe('login', () => {
    it('sets currentUser on success (geo disabled)', () => {
      const { service, api } = setup();
      api.post.mockReturnValue(of({}));
      api.get.mockReturnValue(of(makeUser()));

      service.login('admin', 'password').subscribe();

      expect(service.isAuthenticated()).toBe(true);
      expect(service.currentUser()?.username).toBe('juan');
    });

    it('normalizes username to lowercase before sending', () => {
      const { service, api } = setup();
      api.post.mockReturnValue(of({}));
      api.get.mockReturnValue(of(makeUser()));

      service.login('ADMIN', 'password').subscribe();

      expect(api.post).toHaveBeenCalledWith('auth', 'login', {
        username: 'admin',
        password: 'password',
      });
    });

    it('sends geolocation to the backend when geo is enabled and granted', () => {
      const { service, api, geo } = setup();
      api.post.mockReturnValue(of({}));
      api.get.mockReturnValue(of(makeUser({ geolocalization_login_enabled: true })));
      geo.request.mockReturnValue(of({ latitude: 4.7, longitude: -74, accuracy: 10 }));

      service.login('admin', 'password').subscribe();

      expect(api.post).toHaveBeenCalledWith('auth', 'login/location', {
        latitude: 4.7,
        longitude: -74,
        accuracy: 10,
      });
    });

    it('logs out and errors when geolocation is denied', () => {
      const { service, api, geo } = setup();
      api.post.mockReturnValue(of({}));
      api.get.mockReturnValue(of(makeUser({ geolocalization_login_enabled: true })));
      geo.request.mockReturnValue(throwError(() => new Error('denied')));

      const onError = vi.fn();
      service.login('admin', 'password').subscribe({ error: onError });

      expect(onError).toHaveBeenCalled();
      expect(service.currentUser()).toBeNull();
      expect(api.post).toHaveBeenCalledWith('auth', 'logout');
    });
  });

  describe('logout', () => {
    it('clears the user and navigates to /login', () => {
      const { service, api, router } = setup();
      service.currentUser.set(makeUser());
      api.post.mockReturnValue(of(undefined));

      service.logout().subscribe();

      expect(service.isAuthenticated()).toBe(false);
      expect(router.navigate).toHaveBeenCalledWith(['/login']);
    });
  });

  describe('me', () => {
    it('sets currentUser on success', () => {
      const { service, api } = setup();
      const user = makeUser();
      api.get.mockReturnValue(of(user));

      service.me().subscribe();

      expect(service.currentUser()).toEqual(user);
    });
  });

  describe('checkAuth', () => {
    it('sets currentUser when /me succeeds', () => {
      const { service, api } = setup();
      api.get.mockReturnValue(of(makeUser()));

      service.checkAuth().subscribe();

      expect(service.isAuthenticated()).toBe(true);
    });

    it('refreshes and retries /me on 401', () => {
      const { service, api } = setup();
      api.get
        .mockReturnValueOnce(throwError(() => ({ statusCode: 401 })))
        .mockReturnValueOnce(of(makeUser()));
      api.post.mockReturnValue(of({}));

      service.checkAuth().subscribe();

      expect(api.post).toHaveBeenCalledWith('auth', 'refresh');
      expect(service.isAuthenticated()).toBe(true);
    });

    it('clears the user when refresh also fails', () => {
      const { service, api } = setup();
      api.get
        .mockReturnValueOnce(throwError(() => ({ statusCode: 401 })))
        .mockReturnValueOnce(throwError(() => ({ statusCode: 401 })));
      api.post.mockReturnValue(of({}));

      service.checkAuth().subscribe();

      expect(service.currentUser()).toBeNull();
    });
  });

  describe('permissions and shift computeds', () => {
    it('hasPermission reflects the user permissions', () => {
      const { service } = setup();
      service.currentUser.set(makeUser({ permissions: ['clientes.ver'] }));

      expect(service.hasPermission('clientes.ver')).toBe(true);
      expect(service.hasPermission('clientes.crear')).toBe(false);
    });

    it('exposes shift flags from the current user', () => {
      const { service } = setup();
      service.currentUser.set(
        makeUser({ shift_status_enabled: true, shift_countdown_enabled: false }),
      );

      expect(service.shiftStatusEnabled()).toBe(true);
      expect(service.shiftCountdownEnabled()).toBe(false);
    });

    it('defaults geolocalizationLoginEnabled to true without a user', () => {
      const { service } = setup();
      expect(service.geolocalizationLoginEnabled()).toBe(true);
    });
  });
});
