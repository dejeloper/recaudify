import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { CallReason } from '@core/interfaces/call-reason.interface';
import { CallReasonsService } from '@core/services/call-reasons.service';
import { CallReasons } from './call-reasons';

const sample: CallReason = { id: 1, name: 'Ausente', color: '#ff0000', active: true };

async function setup() {
  const service = {
    items: signal<CallReason[]>([]),
    trashed: signal<CallReason[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    load: vi.fn(),
    toggleTrashed: vi.fn(),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [CallReasons],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: CallReasonsService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(CallReasons);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('CallReasons (list)', () => {
  it('loads call reasons on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalled();
  });

  it('toggleTrashed delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.toggleTrashed();
    expect(service.toggleTrashed).toHaveBeenCalled();
  });

  it('delete calls remove when confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(true);

    comp.delete(sample);

    expect(service.remove).toHaveBeenCalledWith(sample);
  });

  it('delete does nothing when not confirmed', async () => {
    const { comp, service } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    comp.delete(sample);

    expect(service.remove).not.toHaveBeenCalled();
  });

  it('restore delegates to the service', async () => {
    const { comp, service } = await setup();
    comp.restore(sample);
    expect(service.restoreItem).toHaveBeenCalledWith(sample);
  });
});
