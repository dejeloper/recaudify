import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { Parameter } from '@core/interfaces/parameter.interface';
import { ApiService } from '@core/services/api.service';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';

function param(id: number, key: string, value: string): Parameter {
  return {
    id,
    key,
    value,
    typed_value: value,
    cast: 'string',
    description: null,
    type: 'configuration',
    type_label: 'Configuración',
    is_editable: true,
    updated_at: '2026-01-01T00:00:00Z',
  };
}

function setup() {
  const api = { get: vi.fn(), post: vi.fn(), put: vi.fn(), delete: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      ParametersService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
    ],
  });

  return { service: TestBed.inject(ParametersService), api, toast };
}

describe('ParametersService', () => {
  it("getFlag returns true when the parameter value is 'true'", () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([param(1, 'shift-status', 'true')]));

    let flag: boolean | undefined;
    service.getFlag('shift-status').subscribe((v) => (flag = v));

    expect(flag).toBe(true);
  });

  it('getFlag returns false for non-true or missing values', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([param(1, 'shift-status', 'false')]));

    let flag: boolean | undefined;
    service.getFlag('shift-status').subscribe((v) => (flag = v));
    expect(flag).toBe(false);

    let missing: boolean | undefined;
    service.getFlag('inexistente').subscribe((v) => (missing = v));
    expect(missing).toBe(false);
  });

  it('load populates items', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([param(1, 'k', 'v')]));
    service.load();
    expect(service.items()).toHaveLength(1);
  });

  it('load with type passes query param', () => {
    const { service, api } = setup();
    api.get.mockReturnValue(of([]));
    service.load('business');
    expect(api.get).toHaveBeenCalledWith('parameters', undefined, { type: 'business' });
  });

  it('remove moves the parameter to trashed', () => {
    const { service, api, toast } = setup();
    api.get.mockReturnValue(of([param(1, 'k', 'v')]));
    service.load();
    api.delete.mockReturnValue(of(undefined));

    service.remove(param(1, 'k', 'v')).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(service.trashed()).toHaveLength(1);
    expect(toast.success).toHaveBeenCalled();
  });

  it('create posts the parameter payload with cast', () => {
    const { service, api } = setup();
    api.post.mockReturnValue(of(param(2, 'dias_mora', '45')));

    service.create('dias_mora', '45', 'Días de mora', 'business').subscribe();

    expect(api.post).toHaveBeenCalledWith('parameters', undefined, {
      key: 'dias_mora',
      value: '45',
      description: 'Días de mora',
      type: 'business',
      cast: 'string',
    });
  });
});
