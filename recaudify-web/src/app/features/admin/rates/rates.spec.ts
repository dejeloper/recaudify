import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Rate } from '@core/interfaces/rate.interface';
import { RatesService } from '@core/services/rates.service';
import { Rates } from './rates';

const sample: Rate = {
  id: 1,
  name: 'Tarifa Basica',
  product_id: 1,
  product: null,
  value: 1000,
  installments: 3,
  installment_value: 350,
  discount: 0,
  active: true,
};

async function setup() {
  const service = {
    items: signal<Rate[]>([]),
    trashed: signal<Rate[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    load: vi.fn(),
    toggleTrashed: vi.fn(),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [Rates],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: RatesService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Rates);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('Rates (list)', () => {
  it('loads rates on init', async () => {
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
