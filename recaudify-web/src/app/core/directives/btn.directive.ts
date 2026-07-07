import { Directive, effect, ElementRef, inject, input, Renderer2 } from '@angular/core';
import { BtnVariant } from '@core/interfaces/btn.interface';

export type { BtnVariant };

const VARIANT_CLASS: Record<BtnVariant, string> = {
  primary: 'btn-primary',
  secondary: 'btn-secondary',
  'table-edit': 'btn-table-edit',
  'table-danger': 'btn-table-danger',
  'table-restore': 'btn-table-restore',
  'table-neutral': 'btn-table-neutral',
  'inline-save': 'btn-inline-save',
  'inline-cancel': 'btn-inline-cancel',
};

@Directive({
  selector: '[appBtn]',
  standalone: true,
})
export class BtnDirective {
  readonly appBtn = input.required<BtnVariant>();

  private readonly el = inject(ElementRef);
  private readonly renderer = inject(Renderer2);
  private currentClass: string | null = null;

  constructor() {
    effect(() => {
      if (this.currentClass) {
        this.renderer.removeClass(this.el.nativeElement, this.currentClass);
      }
      const cls = VARIANT_CLASS[this.appBtn()];
      this.renderer.addClass(this.el.nativeElement, cls);
      this.currentClass = cls;
    });
  }
}
