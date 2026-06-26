import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { LoginAudit } from '@core/interfaces/login-audit.interface';
import { LoginAuditsService } from '@core/services/login-audits.service';
import { AccessLog } from './access-log';

async function setup() {
  const service = {
    items: signal([]),
    meta: signal(null),
    loading: signal(false),
    loadingMore: signal(false),
    load: vi.fn(),
    loadMore: vi.fn(),
    hasMore: vi.fn().mockReturnValue(false),
  };

  await TestBed.configureTestingModule({
    imports: [AccessLog],
    providers: [
      provideZonelessChangeDetection(),
      { provide: LoginAuditsService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(AccessLog);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

function audit(geo: LoginAudit['geolocation']): LoginAudit {
  return {
    id: 1,
    username: 'juan',
    user: null,
    status: 'success',
    reason: null,
    ip_address: null,
    os: null,
    device_type: null,
    geolocation: geo,
    logged_at: '2026-06-25T10:00:00',
  };
}

describe('AccessLog', () => {
  it('loads on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalled();
  });

  it('setStatus reloads with the chosen filter', async () => {
    const { comp, service } = await setup();

    comp.setStatus('failed');
    expect(comp.status()).toBe('failed');
    expect(service.load).toHaveBeenLastCalledWith({ status: 'failed' });

    comp.setStatus('all');
    expect(service.load).toHaveBeenLastCalledWith({});
  });

  it('setStatus is a no-op when the status is unchanged', async () => {
    const { comp, service } = await setup();
    service.load.mockClear();

    comp.setStatus('all'); // ya es 'all'

    expect(service.load).not.toHaveBeenCalled();
  });

  it('reasonLabel translates known reasons', async () => {
    const { comp } = await setup();
    expect(comp.reasonLabel('invalid_credentials')).toBe('Credenciales incorrectas');
    expect(comp.reasonLabel('inactive')).toBe('Usuario inactivo');
    expect(comp.reasonLabel(null)).toBe('');
  });

  it('mapsUrl builds a maps link only when geolocation exists', async () => {
    const { comp } = await setup();
    expect(comp.mapsUrl(audit(null))).toBeNull();
    expect(comp.mapsUrl(audit({ latitude: 4.7, longitude: -74, accuracy: 10 }))).toContain(
      '4.7,-74',
    );
  });
});
