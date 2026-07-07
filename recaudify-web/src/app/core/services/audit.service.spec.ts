import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { AuditService } from '@core/services/audit.service';

function setup() {
  TestBed.configureTestingModule({
    providers: [provideZonelessChangeDetection(), AuditService],
  });

  return { service: TestBed.inject(AuditService) };
}

function withUserAgent(ua: string, fn: () => void): void {
  const original = navigator.userAgent;
  Object.defineProperty(navigator, 'userAgent', { value: ua, configurable: true });
  try {
    fn();
  } finally {
    Object.defineProperty(navigator, 'userAgent', { value: original, configurable: true });
  }
}

describe('AuditService', () => {
  it('captureLogin logs an audit entry without throwing', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    service.captureLogin(1, '127.0.0.1', null);

    expect(logSpy).toHaveBeenCalledWith('[Audit] Login:', expect.objectContaining({ user_id: 1 }));
    logSpy.mockRestore();
  });

  it('captureLogin includes geolocation when coords are provided', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});
    const coords = { latitude: 10, longitude: 20, accuracy: 5 } as GeolocationCoordinates;

    service.captureLogin(2, null, coords);

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({
        geolocation: { latitude: 10, longitude: 20, accuracy: 5 },
      }),
    );
    logSpy.mockRestore();
  });

  it('parseOs detects Windows from the user agent', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ os: { name: 'Windows', version: '10.0' } }),
    );
    logSpy.mockRestore();
  });

  it('parseOs detects Android and replaces underscores with dots', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('Mozilla/5.0 (Linux; Android 13; Pixel 7)', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ os: { name: 'Android', version: '13' } }),
    );
    logSpy.mockRestore();
  });

  it('parseOs falls back to Unknown for unrecognized user agents', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('SomeWeirdBot/1.0', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ os: { name: 'Unknown', version: '' } }),
    );
    logSpy.mockRestore();
  });

  it('getDeviceType identifies a tablet user agent', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('Mozilla/5.0 (iPad; CPU OS 16_0 like Mac OS X)', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ device_type: 'tablet' }),
    );
    logSpy.mockRestore();
  });

  it('getDeviceType identifies a mobile user agent', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ device_type: 'mobile' }),
    );
    logSpy.mockRestore();
  });

  it('getDeviceType defaults to desktop', () => {
    const { service } = setup();
    const logSpy = vi.spyOn(console, 'log').mockImplementation(() => {});

    withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64)', () => {
      service.captureLogin(1, null, null);
    });

    expect(logSpy).toHaveBeenCalledWith(
      '[Audit] Login:',
      expect.objectContaining({ device_type: 'desktop' }),
    );
    logSpy.mockRestore();
  });
});
