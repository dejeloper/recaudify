import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { ToastContainer } from '@core/components/toast/toast';
import { ToastService } from '@core/services/toast.service';

function setup() {
  TestBed.configureTestingModule({ providers: [provideZonelessChangeDetection()] });
  const fixture = TestBed.createComponent(ToastContainer);
  const toast = TestBed.inject(ToastService);
  fixture.detectChanges();
  return { fixture, toast, el: fixture.nativeElement as HTMLElement };
}

describe('ToastContainer', () => {
  it('renders a toast message from the service', () => {
    const { fixture, toast, el } = setup();
    toast.success('Guardado correctamente.', 0);
    fixture.detectChanges();

    expect(el.textContent).toContain('Guardado correctamente.');
  });

  it('renders one card per toast', () => {
    const { fixture, toast, el } = setup();
    toast.success('uno', 0);
    toast.error('dos', 0);
    fixture.detectChanges();

    expect(el.querySelectorAll('p.flex-1')).toHaveLength(2);
  });

  it('dismisses a toast when the close button is clicked', () => {
    const { fixture, toast, el } = setup();
    toast.info('cerrable', 0);
    fixture.detectChanges();

    (el.querySelector('button') as HTMLButtonElement).click();
    fixture.detectChanges();

    expect(toast.toasts()).toHaveLength(0);
    expect(el.querySelector('p.flex-1')).toBeNull();
  });
});
