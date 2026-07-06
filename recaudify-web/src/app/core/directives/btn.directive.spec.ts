import { Component, provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { BtnDirective, BtnVariant } from '@core/directives/btn.directive';

@Component({
  imports: [BtnDirective],
  template: `<button [appBtn]="variant()">x</button>`,
})
class Host {
  variant = signal<BtnVariant>('primary');
}

function setup() {
  TestBed.configureTestingModule({ providers: [provideZonelessChangeDetection()] });
  const fixture = TestBed.createComponent(Host);
  fixture.detectChanges();
  const button = fixture.nativeElement.querySelector('button') as HTMLButtonElement;
  return { fixture, button };
}

describe('BtnDirective', () => {
  it('applies the variant class to the host element', () => {
    const { button } = setup();
    expect(button.classList.contains('btn-primary')).toBe(true);
  });

  it('swaps the class when the variant input changes', () => {
    const { fixture, button } = setup();

    fixture.componentInstance.variant.set('table-danger');
    fixture.detectChanges();

    expect(button.classList.contains('btn-primary')).toBe(false);
    expect(button.classList.contains('btn-table-danger')).toBe(true);
  });

  it('applies btn-table-neutral for the table-neutral variant', () => {
    const { fixture, button } = setup();

    fixture.componentInstance.variant.set('table-neutral');
    fixture.detectChanges();

    expect(button.classList.contains('btn-primary')).toBe(false);
    expect(button.classList.contains('btn-table-neutral')).toBe(true);
  });
});
