import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { ProductsService } from '@core/services/products.service';
import { RatesService } from '@core/services/rates.service';
import { ToastService } from '@core/services/toast.service';
import { RateForm } from './rate-form';

const products = [{ id: 1, name: 'Biblia', value: 1000, active: true }];

async function setup(id?: string) {
  const ratesService = {
    getById: vi.fn().mockReturnValue(
      of({
        id: 5,
        name: 'Tarifa Basica',
        product_id: 1,
        product: null,
        value: 1000,
        installments: 3,
        installment_value: 350,
        discount: 5,
        active: true,
      }),
    ),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const productsService = { getAll: vi.fn().mockReturnValue(of(products)) };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [RateForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: RatesService, useValue: ratesService },
      { provide: ProductsService, useValue: productsService },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(RateForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, ratesService, productsService, toast, navigate };
}

describe('RateForm', () => {
  it('loads the available products on init', async () => {
    const { comp, productsService } = await setup();
    expect(productsService.getAll).toHaveBeenCalled();
    expect(comp.products()).toEqual(products);
  });

  it('creates a rate and navigates back on save', async () => {
    const { comp, ratesService, toast, navigate } = await setup();
    comp.formName = 'Tarifa Premium';
    comp.formProductId = 1;
    comp.formValue = 2000;
    comp.formInstallments = 4;
    comp.formInstallmentValue = 550;
    comp.formDiscount = 10;
    comp.formActive = true;

    comp.save();

    expect(ratesService.create).toHaveBeenCalledWith({
      name: 'Tarifa Premium',
      product_id: 1,
      value: 2000,
      installments: 4,
      installment_value: 550,
      discount: 10,
      active: true,
    });
    expect(navigate).toHaveBeenCalledWith(['/admin/rates']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when the form is invalid', async () => {
    const { comp, ratesService } = await setup();
    comp.formName = '';
    comp.formProductId = null;

    comp.save();

    expect(ratesService.create).not.toHaveBeenCalled();
  });

  it('does not save when a required numeric field is negative', async () => {
    const { comp, ratesService } = await setup();
    comp.formName = 'Tarifa';
    comp.formProductId = 1;
    comp.formValue = -100;
    comp.formInstallments = 3;
    comp.formInstallmentValue = 100;

    comp.save();

    expect(ratesService.create).not.toHaveBeenCalled();
  });

  it('loads the rate in edit mode', async () => {
    const { comp, ratesService } = await setup('5');

    expect(ratesService.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Tarifa Basica');
    expect(comp.formProductId).toBe(1);
    expect(comp.formInstallments).toBe(3);
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the rate in edit mode', async () => {
    const { comp, ratesService, navigate } = await setup('5');
    comp.formName = 'Tarifa Basica Actualizada';

    comp.save();

    expect(ratesService.update).toHaveBeenCalledWith(5, {
      name: 'Tarifa Basica Actualizada',
      product_id: 1,
      value: 1000,
      installments: 3,
      installment_value: 350,
      discount: 5,
      active: true,
    });
    expect(navigate).toHaveBeenCalledWith(['/admin/rates']);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, ratesService, toast } = await setup();
    ratesService.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName = 'X';
    comp.formProductId = 1;
    comp.formValue = 100;
    comp.formInstallments = 1;
    comp.formInstallmentValue = 100;

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
