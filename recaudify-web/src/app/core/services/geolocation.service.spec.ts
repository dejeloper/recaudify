import { firstValueFrom } from 'rxjs';
import { GeolocationService } from '@core/services/geolocation.service';

describe('GeolocationService', () => {
  let service: GeolocationService;

  beforeEach(() => {
    service = new GeolocationService();
  });

  afterEach(() => vi.unstubAllGlobals());

  it('emits the coordinates when geolocation succeeds', async () => {
    const coords = { latitude: 4.7, longitude: -74, accuracy: 10 };
    vi.stubGlobal('navigator', {
      geolocation: { getCurrentPosition: (ok: (p: unknown) => void) => ok({ coords }) },
    });

    await expect(firstValueFrom(service.request())).resolves.toEqual(coords);
  });

  it('errors GEOLOCATION_DENIED when the user denies', async () => {
    vi.stubGlobal('navigator', {
      geolocation: { getCurrentPosition: (_ok: unknown, err: () => void) => err() },
    });

    await expect(firstValueFrom(service.request())).rejects.toThrow('GEOLOCATION_DENIED');
  });

  it('errors GEOLOCATION_UNSUPPORTED when geolocation is missing', async () => {
    vi.stubGlobal('navigator', {});

    await expect(firstValueFrom(service.request())).rejects.toThrow('GEOLOCATION_UNSUPPORTED');
  });

  it('getPermissionState returns "prompt" when the permissions API is missing', async () => {
    vi.stubGlobal('navigator', {});

    await expect(service.getPermissionState()).resolves.toBe('prompt');
  });

  it('getPermissionState returns the queried state', async () => {
    vi.stubGlobal('navigator', {
      permissions: { query: vi.fn().mockResolvedValue({ state: 'granted' }) },
    });

    await expect(service.getPermissionState()).resolves.toBe('granted');
  });
});
