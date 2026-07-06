import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';
import { ParameterForm } from './parameter-form';

async function setup(id?: string) {
  const service = {
    getConfigValue: vi.fn((key: string) =>
      of(key === 'parameter_types' ? ['configuration', 'business'] : ['string', 'integer']),
    ),
    getById: vi.fn().mockReturnValue(
      of({
        id: 5,
        key: 'tasa_interes',
        value: '2.5',
        description: 'Tasa de interés',
        type: 'business',
        type_label: 'Negocio',
        cast: 'string',
        typed_value: '2.5',
        is_editable: true,
        updated_at: '2024-01-01',
      }),
    ),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [ParameterForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: ParametersService, useValue: service },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(ParameterForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, service, toast, navigate };
}

describe('ParameterForm', () => {
  it('loads the available types and casts on init', async () => {
    const { comp } = await setup();
    expect(comp.availableTypes()).toEqual(['configuration', 'business']);
    expect(comp.availableCasts()).toEqual(['string', 'integer']);
  });

  it('creates a parameter and navigates back on save', async () => {
    const { comp, service, toast, navigate } = await setup();
    comp.formKey = 'nueva_clave';
    comp.formValue = '10';
    comp.formDescription = 'Una descripción';
    comp.formType = 'business';
    comp.formCast = 'integer';

    comp.save();

    expect(service.create).toHaveBeenCalledWith(
      'nueva_clave',
      '10',
      'Una descripción',
      'business',
      'integer',
    );
    expect(navigate).toHaveBeenCalledWith(['/admin/parameters']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when key or value is empty', async () => {
    const { comp, service } = await setup();
    comp.formKey = '';
    comp.formValue = '10';

    comp.save();

    expect(service.create).not.toHaveBeenCalled();
  });

  it('loads the parameter in edit mode', async () => {
    const { comp, service } = await setup('5');

    expect(service.getById).toHaveBeenCalledWith(5);
    expect(comp.formKey).toBe('tasa_interes');
    expect(comp.formValue).toBe('2.5');
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the parameter in edit mode using only the value', async () => {
    const { comp, service, navigate } = await setup('5');
    comp.formValue = '3.0';

    comp.save();

    expect(service.update).toHaveBeenCalledWith(5, '3.0');
    expect(navigate).toHaveBeenCalledWith(['/admin/parameters']);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, service, toast } = await setup();
    service.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formKey = 'x';
    comp.formValue = 'y';

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
