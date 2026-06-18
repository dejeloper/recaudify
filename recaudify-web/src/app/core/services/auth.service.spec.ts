import {provideZonelessChangeDetection} from '@angular/core';
import {TestBed} from '@angular/core/testing';
import {Router} from '@angular/router';
import {of} from 'rxjs';
import {ApiService} from '@core/services/api.service';
import {AuthService} from '@core/services/auth.service';

const TOKEN = 'fake-jwt-token';

function setup() {
  const apiMock = {post: vi.fn(), get: vi.fn()};
  const routerMock = {navigate: vi.fn()};

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      AuthService,
      {provide: ApiService, useValue: apiMock},
      {provide: Router, useValue: routerMock},
    ],
  });

  return {
    service: TestBed.inject(AuthService),
    api: apiMock,
    router: routerMock,
  };
}

describe('AuthService', () => {
  beforeEach(() => localStorage.clear());

  describe('initial state', () => {
    it('is not authenticated when no token in localStorage', () => {
      const {service} = setup();
      expect(service.isAuthenticated()).toBe(false);
      expect(service.token).toBeNull();
    });

    it('is authenticated when token exists in localStorage', () => {
      localStorage.setItem('auth_token', TOKEN);
      const {service} = setup();
      expect(service.isAuthenticated()).toBe(true);
      expect(service.token).toBe(TOKEN);
    });
  });

  describe('login', () => {
    it('stores token and sets isAuthenticated on success', () => {
      const {service, api} = setup();
      api.post.mockReturnValue(of({token: TOKEN}));

      service.login('admin', 'password').subscribe();

      expect(service.isAuthenticated()).toBe(true);
      expect(service.token).toBe(TOKEN);
      expect(localStorage.getItem('auth_token')).toBe(TOKEN);
    });

    it('normalizes username to lowercase before sending', () => {
      const {service, api} = setup();
      api.post.mockReturnValue(of({token: TOKEN}));

      service.login('ADMIN', 'password').subscribe();

      expect(api.post).toHaveBeenCalledWith('auth', 'login', {username: 'admin', password: 'password'});
    });
  });

  describe('logout', () => {
    it('clears token and navigates to /login', () => {
      localStorage.setItem('auth_token', TOKEN);
      const {service, api, router} = setup();
      api.post.mockReturnValue(of(undefined));

      service.logout().subscribe();

      expect(service.isAuthenticated()).toBe(false);
      expect(service.token).toBeNull();
      expect(localStorage.getItem('auth_token')).toBeNull();
      expect(router.navigate).toHaveBeenCalledWith(['/login']);
    });
  });

  describe('me', () => {
    it('sets currentUser on success', () => {
      const {service, api} = setup();
      const user = {id: 1, name: 'Juan', username: 'juan', email: null, roles: [], permissions: []};
      api.get.mockReturnValue(of(user));

      service.me().subscribe();

      expect(service.currentUser()).toEqual(user);
    });
  });
});
