import { DatePipe } from '@angular/common';
import { Component, inject, OnInit } from '@angular/core';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Activity } from '@core/interfaces/activity.interface';
import { ActivitiesService } from '@core/services/activities.service';

/** Etiquetas legibles de campos por modelo. */
const FIELD_LABELS: Record<string, Record<string, string>> = {
  Product: { name: 'Nombre', value: 'Valor', active: 'Activo' },
  Rate: {
    name: 'Nombre',
    product_id: 'Producto',
    value: 'Valor',
    installments: 'Cuotas',
    installment_value: 'Valor cuota',
    discount: 'Descuento',
    active: 'Activa',
  },
  Seller: { name: 'Nombre', username: 'Usuario', active: 'Activo' },
  CallReason: { name: 'Nombre', color: 'Color', active: 'Activo' },
};

const MONEY_FIELDS = new Set(['value', 'installment_value', 'discount']);

@Component({
  selector: 'app-activity',
  imports: [Spinner, DatePipe, BtnDirective],
  templateUrl: './activity.html',
})
export class ActivityFeed implements OnInit {
  protected readonly service = inject(ActivitiesService);

  protected readonly activities = this.service.items;
  protected readonly meta = this.service.meta;
  protected readonly loading = this.service.loading;
  protected readonly loadingMore = this.service.loadingMore;

  ngOnInit() {
    this.service.load();
  }

  protected loadMore() {
    this.service.loadMore();
  }

  protected hasMore() {
    return this.service.hasMore();
  }

  protected fieldLabel(model: string | null, field: string): string {
    return (model && FIELD_LABELS[model]?.[field]) || field;
  }

  protected formatValue(field: string, value: unknown): string {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'boolean') return value ? 'Sí' : 'No';
    if (MONEY_FIELDS.has(field) && !isNaN(Number(value))) {
      return '$ ' + Number(value).toLocaleString('es-CO');
    }
    return String(value);
  }

  /** Color del punto/etiqueta según el evento. */
  protected eventClasses(event: string): { dot: string; badge: string } {
    switch (event) {
      case 'created':
        return { dot: 'bg-green-500', badge: 'bg-green-50 text-green-700' };
      case 'updated':
        return { dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700' };
      case 'deleted':
        return { dot: 'bg-red-500', badge: 'bg-red-50 text-red-700' };
      case 'restored':
        return { dot: 'bg-blue-500', badge: 'bg-blue-50 text-blue-700' };
      default:
        return { dot: 'bg-gray-400', badge: 'bg-gray-100 text-gray-600' };
    }
  }

  protected trackById(_: number, item: Activity) {
    return item.id;
  }
}
