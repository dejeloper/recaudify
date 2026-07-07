import { Directive, effect, ElementRef, inject, input, Renderer2 } from '@angular/core';
import { TableVariant } from '@core/interfaces/table.interface';

export type { TableVariant };

const VARIANT_CLASS: Record<TableVariant, string> = {
  default: 'data-table',
  trashed: 'data-table-trashed',
};

@Directive({
  selector: '[appTable]',
  standalone: true,
})
export class TableDirective {
  readonly appTable = input<TableVariant | ''>('');

  private readonly el = inject(ElementRef);
  private readonly renderer = inject(Renderer2);
  private currentClass: string | null = null;

  constructor() {
    effect(() => {
      if (this.currentClass) {
        this.renderer.removeClass(this.el.nativeElement, this.currentClass);
      }
      const variant = (this.appTable() || 'default') as TableVariant;
      const cls = VARIANT_CLASS[variant];
      this.renderer.addClass(this.el.nativeElement, cls);
      this.currentClass = cls;
    });
  }
}
