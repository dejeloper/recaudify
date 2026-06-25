import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { CallReason } from '@core/interfaces/call-reason.interface';
import { ApiService } from '@core/services/api.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class CallReasonsService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);

  readonly items = signal<CallReason[]>([]);
  readonly trashed = signal<CallReason[]>([]);
  readonly loading = signal(false);
  readonly loadingTrashed = signal(false);
  readonly showTrashed = signal(false);

  load(): void {
    this.loading.set(true);
    this.showTrashed.set(false);
    this.trashed.set([]);
    this.getAll().subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  toggleTrashed(): void {
    const next = !this.showTrashed();
    this.showTrashed.set(next);
    if (next && this.trashed().length === 0) {
      this.loadingTrashed.set(true);
      this.getTrashed().subscribe({
        next: (list) => {
          this.trashed.set(list);
          this.loadingTrashed.set(false);
        },
        error: () => this.loadingTrashed.set(false),
      });
    }
  }

  remove(reason: CallReason) {
    return this.delete(reason.id).pipe(
      tap(() => {
        const removed = this.items().find((r) => r.id === reason.id)!;
        this.items.update((list) => list.filter((r) => r.id !== reason.id));
        this.trashed.update((list) => [removed, ...list]);
        this.toast.success(`Motivo "${reason.name}" eliminado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el motivo.');
        return EMPTY;
      }),
    );
  }

  restoreItem(reason: CallReason) {
    return this.restore(reason.id).pipe(
      tap(() => {
        this.trashed.update((list) => list.filter((r) => r.id !== reason.id));
        this.items.update((list) => [...list, reason].sort((a, b) => a.name.localeCompare(b.name)));
        this.toast.success(`Motivo "${reason.name}" restaurado.`);
      }),
      catchError(() => {
        this.toast.error('No se pudo restaurar el motivo.');
        return EMPTY;
      }),
    );
  }

  getAll() {
    return this.api.get<CallReason[]>('call-reasons');
  }
  getById(id: number) {
    return this.api.get<CallReason>('call-reasons', String(id));
  }
  create(name: string, color: string | null, active: boolean) {
    return this.api.post<CallReason>('call-reasons', undefined, { name, color, active });
  }
  update(id: number, name: string, color: string | null, active: boolean) {
    return this.api.put<CallReason>('call-reasons', String(id), { name, color, active });
  }
  delete(id: number) {
    return this.api.delete('call-reasons', String(id));
  }
  getTrashed() {
    return this.api.get<CallReason[]>('call-reasons', 'trashed');
  }
  restore(id: number) {
    return this.api.post<void>('call-reasons', `${id}/restore`);
  }
}
