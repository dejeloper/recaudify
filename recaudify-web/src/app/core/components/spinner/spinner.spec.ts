import { Component, provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { Spinner } from '@core/components/spinner/spinner';

@Component({
  imports: [Spinner],
  template: `<app-spinner [show]="show()" [label]="label()" />`,
})
class Host {
  show = signal(false);
  label = signal('');
}

function setup() {
  TestBed.configureTestingModule({ providers: [provideZonelessChangeDetection()] });
  const fixture = TestBed.createComponent(Host);
  fixture.detectChanges();
  return { fixture, el: fixture.nativeElement as HTMLElement };
}

describe('Spinner', () => {
  it('renders nothing when show is false', () => {
    const { el } = setup();
    expect(el.querySelector('svg')).toBeNull();
  });

  it('renders the spinner when show is true', () => {
    const { fixture, el } = setup();
    fixture.componentInstance.show.set(true);
    fixture.detectChanges();

    expect(el.querySelector('svg.animate-spin')).not.toBeNull();
  });

  it('renders the label when provided', () => {
    const { fixture, el } = setup();
    fixture.componentInstance.show.set(true);
    fixture.componentInstance.label.set('Cargando...');
    fixture.detectChanges();

    expect(el.textContent).toContain('Cargando...');
  });
});
