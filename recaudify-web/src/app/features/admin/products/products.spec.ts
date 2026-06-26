import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Product } from '@core/interfaces/product.interface';
import { ProductsService } from '@core/services/products.service';
import { Products } from './products';

const sample: Product = { id: 1, name: 'Biblia', value: 1000, active: true };

async function setup() {
  const service = {
    items: signal<Product[]>([]),
    trashed: signal<Product[]>([]),
    loading: signal(false),
    loadingTrashed: signal(false),
    showTrashed: signal(false),
    load: vi.fn(),
    toggleTrashed: vi.fn(),
    remove: vi.fn().mockReturnValue(of(undefined)),
    restoreItem: vi.fn().mockReturnValue(of(undefined)),
  };

  await TestBed.configureTestingModule({
    imports: [Products],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: ProductsService, useValue: service },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(Products);
  fixture.detectChanges();
  return { comp: fixture.componentInstance as any, service };
}

describe('Products (list)', () => {
  it('loads products on init', async () => {
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
