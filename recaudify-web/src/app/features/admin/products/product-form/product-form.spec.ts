import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter, Router } from '@angular/router';
import { of, throwError } from 'rxjs';
import { ProductsService } from '@core/services/products.service';
import { ToastService } from '@core/services/toast.service';
import { ProductForm } from './product-form';

async function setup(id?: string) {
  const service = {
    getById: vi.fn().mockReturnValue(of({ id: 5, name: 'Biblia', value: 1000, active: true })),
    create: vi.fn().mockReturnValue(of({})),
    update: vi.fn().mockReturnValue(of({})),
  };
  const toast = { success: vi.fn(), error: vi.fn() };

  await TestBed.configureTestingModule({
    imports: [ProductForm],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: ProductsService, useValue: service },
      { provide: ToastService, useValue: toast },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(ProductForm);
  if (id) fixture.componentRef.setInput('id', id);
  fixture.detectChanges();

  const router = TestBed.inject(Router);
  const navigate = vi.spyOn(router, 'navigate').mockResolvedValue(true);

  return { fixture, comp: fixture.componentInstance as any, service, toast, navigate };
}

describe('ProductForm', () => {
  it('creates a product and navigates back on save', async () => {
    const { comp, service, toast, navigate } = await setup();
    comp.formName = 'Atril';
    comp.formValue = 5000;
    comp.formActive = true;

    comp.save();

    expect(service.create).toHaveBeenCalledWith('Atril', 5000, true);
    expect(navigate).toHaveBeenCalledWith(['/admin/products']);
    expect(toast.success).toHaveBeenCalled();
  });

  it('does not save when the form is invalid', async () => {
    const { comp, service } = await setup();
    comp.formName = '';
    comp.formValue = null;

    comp.save();

    expect(service.create).not.toHaveBeenCalled();
  });

  it('loads the product in edit mode', async () => {
    const { comp, service } = await setup('5');

    expect(service.getById).toHaveBeenCalledWith(5);
    expect(comp.formName).toBe('Biblia');
    expect(comp.formValue).toBe(1000);
    expect(comp.isEdit()).toBe(true);
  });

  it('updates the product in edit mode', async () => {
    const { comp, service, navigate } = await setup('5');
    comp.formName = 'Biblia Grande';
    comp.formValue = 1200;

    comp.save();

    expect(service.update).toHaveBeenCalledWith(5, 'Biblia Grande', 1200, true);
    expect(navigate).toHaveBeenCalledWith(['/admin/products']);
  });

  it('shows the error message when saving fails', async () => {
    const { comp, service, toast } = await setup();
    service.create.mockReturnValue(throwError(() => ({ message: 'Error al guardar.' })));
    comp.formName = 'X';
    comp.formValue = 1;

    comp.save();

    expect(comp.error()).toBe('Error al guardar.');
    expect(toast.error).toHaveBeenCalled();
  });
});
