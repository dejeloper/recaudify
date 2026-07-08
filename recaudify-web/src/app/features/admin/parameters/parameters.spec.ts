import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Parameter } from '@core/interfaces/parameter.interface';
import { ParametersService } from '@core/services/parameters.service';
import { Parameters } from './parameters';

const sample: Parameter = {
  id: 1,
  type: 'application',
  type_label: 'Aplicación',
  key: 'app_name',
  value: 'Recaudify',
  typed_value: 'Recaudify',
  cast: 'string',
  description: null,
  is_editable: true,
  updated_at: '2026-01-01',
};

async function setup() {
  const service = {
    items: signal<Parameter[]>([]),
    meta: signal(null),
    trashed: signal<Parameter[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    load: vi.fn(),
    goToPage: vi.fn(),
    toggleTrashed: vi.fn(),
    getConfigValue: vi.fn().mockReturnValue(of(null)),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [Parameters],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: ParametersService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Parameters);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('Parameters (list)', () => {
  it('loads parameters and available types on init', async () => {
    const { service } = await setup();
    expect(service.load).toHaveBeenCalledWith();
    expect(service.getConfigValue).toHaveBeenCalledWith('parameter_types');
  });

  it('sets availableTypes when the config value resolves', async () => {
    const service = {
      items: signal<Parameter[]>([]),
      meta: signal(null),
      trashed: signal<Parameter[]>([]),
      loading: signal(false),
      loadingMore: signal(false),
      loadingTrashed: signal(false),
      showTrashed: signal(false),
      load: vi.fn(),
      loadMore: vi.fn(),
      hasMore: vi.fn().mockReturnValue(false),
      toggleTrashed: vi.fn(),
      getConfigValue: vi.fn().mockReturnValue(of(['application', 'security'])),
      remove: vi.fn().mockReturnValue(of(undefined)),
      restoreItem: vi.fn().mockReturnValue(of(undefined)),
    };

    await TestBed.configureTestingModule({
      imports: [Parameters],
      providers: [
        provideZonelessChangeDetection(),
        provideRouter([]),
        { provide: ParametersService, useValue: service },
      ],
    }).compileComponents();

    const fixture = TestBed.createComponent(Parameters);
    fixture.detectChanges();
    const comp = fixture.componentInstance as any;

    expect(comp.availableTypes()).toEqual(['application', 'security']);
  });

  it('filterByType sets the selected type and reloads', async () => {
    const { comp, service } = await setup();
    comp.filterByType('security');
    expect(comp.selectedType()).toBe('security');
    expect(service.load).toHaveBeenCalledWith('security', undefined);
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
