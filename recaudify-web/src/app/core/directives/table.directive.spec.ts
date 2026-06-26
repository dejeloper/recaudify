import { Component, provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { TableDirective, TableVariant } from '@core/directives/table.directive';

@Component({
  imports: [TableDirective],
  template: `<div [appTable]="variant()"></div>`,
})
class Host {
  variant = signal<TableVariant | ''>('');
}

function setup() {
  TestBed.configureTestingModule({ providers: [provideZonelessChangeDetection()] });
  const fixture = TestBed.createComponent(Host);
  fixture.detectChanges();
  const div = fixture.nativeElement.querySelector('div') as HTMLDivElement;
  return { fixture, div };
}

describe('TableDirective', () => {
  it('applies the default class when no variant is set', () => {
    const { div } = setup();
    expect(div.classList.contains('data-table')).toBe(true);
  });

  it('applies the trashed class for the trashed variant', () => {
    const { fixture, div } = setup();

    fixture.componentInstance.variant.set('trashed');
    fixture.detectChanges();

    expect(div.classList.contains('data-table-trashed')).toBe(true);
    expect(div.classList.contains('data-table')).toBe(false);
  });
});
