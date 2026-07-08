import { DatePipe, TitleCasePipe } from '@angular/common';
import { Component, DestroyRef, inject, OnInit } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Activity } from '@core/interfaces/activity.interface';
import { ActivitiesService } from '@core/services/activities.service';
import { debounceTime, distinctUntilChanged, Subject } from 'rxjs';

const DEFAULT_USER_FILTER = 'sistema';

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
  imports: [Spinner, DatePipe, TitleCasePipe, BtnDirective],
  templateUrl: './activity.html',
})
export class ActivityFeed implements OnInit {
  protected readonly service = inject(ActivitiesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly activities = this.service.items;
  protected readonly meta = this.service.meta;
  protected readonly loading = this.service.loading;
  protected readonly loadingMore = this.service.loadingMore;

  protected readonly userFilter =
    this.route.snapshot.queryParamMap.get('user') ?? DEFAULT_USER_FILTER;

  private readonly userFilter$ = new Subject<string>();

  ngOnInit() {
    this.service.load({ user: this.userFilter });

    this.userFilter$
      .pipe(debounceTime(500), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((user) => {
        this.service.load({ user: user || DEFAULT_USER_FILTER });
        this.router.navigate([], {
          relativeTo: this.route,
          queryParams: { user: user || null },
          queryParamsHandling: 'merge',
          replaceUrl: true,
        });
      });
  }

  protected onUserFilterChange(user: string) {
    this.userFilter$.next(user);
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

  /** Trunca a 30 caracteres para mostrar en la lista; expone el texto completo como title. */
  protected changeValue(field: string, value: unknown): { text: string; title: string | null } {
    const full = this.formatValue(field, value);
    return full.length > 30
      ? { text: full.slice(0, 30) + '...', title: full }
      : { text: full, title: null };
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
        return { dot: 'bg-gray-500', badge: 'bg-gray-100 text-gray-700' };
      default:
        return { dot: 'bg-gray-400', badge: 'bg-gray-100 text-gray-600' };
    }
  }

  protected trackById(_: number, item: Activity) {
    return item.id;
  }
}
