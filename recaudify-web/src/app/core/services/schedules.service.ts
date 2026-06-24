import { inject, Injectable, signal } from '@angular/core';
import { EMPTY, catchError, tap } from 'rxjs';
import { Schedule } from '@core/interfaces/schedule.interface';
import { ApiService } from '@core/services/api.service';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';

@Injectable({ providedIn: 'root' })
export class SchedulesService {
  private readonly api = inject(ApiService);
  private readonly toast = inject(ToastService);
  private readonly parametersService = inject(ParametersService);

  readonly items = signal<Schedule[]>([]);
  readonly loading = signal(false);
  readonly showStatusEnabled = signal(false);

  loadForUser(userId: number): void {
    this.loading.set(true);
    this.items.set([]);
    this.getByUser(userId).subscribe({
      next: (list) => {
        this.items.set(list);
        this.loading.set(false);
      },
      error: () => this.loading.set(false),
    });
  }

  loadShiftStatusFlag(): void {
    this.parametersService.getFlag('shift-status').subscribe({
      next: (enabled) => this.showStatusEnabled.set(enabled),
    });
  }

  formatTime(time: string): string {
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${String(m).padStart(2, '0')} ${period}`;
  }

  getForDay(dayId: number): Schedule[] {
    return this.items().filter((s) => s.day_of_week === dayId);
  }

  addEntry(
    userId: number,
    data: { day_of_week: number; start_time: string; end_time: string; show_status: boolean },
  ) {
    return this.create(userId, data).pipe(
      tap((created) => {
        this.items.update((list) => [...list, created]);
        this.toast.success('Horario agregado.');
      }),
      catchError(() => {
        this.toast.error('No se pudo agregar el horario.');
        return EMPTY;
      }),
    );
  }

  updateEntry(id: number, data: { start_time: string; end_time: string; show_status: boolean }) {
    return this.update(id, data).pipe(
      tap((updated) => {
        this.items.update((list) => list.map((s) => (s.id === updated.id ? updated : s)));
        this.toast.success('Horario actualizado.');
      }),
      catchError(() => {
        this.toast.error('No se pudo actualizar el horario.');
        return EMPTY;
      }),
    );
  }

  removeEntry(entry: Schedule) {
    return this.delete(entry.id).pipe(
      tap(() => {
        this.items.update((list) => list.filter((s) => s.id !== entry.id));
        this.toast.success('Horario eliminado.');
      }),
      catchError(() => {
        this.toast.error('No se pudo eliminar el horario.');
        return EMPTY;
      }),
    );
  }

  getByUser(userId: number) {
    return this.api.request<Schedule[]>({ controller: `users/${userId}/schedules`, method: 'GET' });
  }

  create(
    userId: number,
    data: { day_of_week: number; start_time: string; end_time: string; show_status?: boolean },
  ) {
    return this.api.request<Schedule>({
      controller: `users/${userId}/schedules`,
      method: 'POST',
      body: data,
    });
  }

  update(id: number, data: { start_time: string; end_time: string; show_status?: boolean }) {
    return this.api.request<Schedule>({
      controller: 'schedules',
      action: String(id),
      method: 'PUT',
      body: data,
    });
  }

  delete(id: number) {
    return this.api.request<void>({
      controller: 'schedules',
      action: String(id),
      method: 'DELETE',
    });
  }
}
