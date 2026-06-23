import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Schedule } from '@core/models/schedule';
import { User } from '@core/models/user';
import { AuthService } from '@core/services/auth.service';
import { ParametersService } from '@core/services/parameters.service';
import { SchedulesService } from '@core/services/schedules.service';
import { ToastService } from '@core/services/toast.service';
import { UsersService } from '@core/services/users.service';

const DAYS = [
  { id: 1, name: 'Lunes' },
  { id: 2, name: 'Martes' },
  { id: 3, name: 'Miércoles' },
  { id: 4, name: 'Jueves' },
  { id: 5, name: 'Viernes' },
  { id: 6, name: 'Sábado' },
  { id: 0, name: 'Domingo' },
];

@Component({
  selector: 'app-user-schedules',
  imports: [RouterLink, FormsModule, BtnDirective, Spinner],
  templateUrl: './user-schedules.html',
})
export class UserSchedules implements OnInit {
  readonly userId = input<string>('');

  private readonly usersService = inject(UsersService);
  private readonly schedulesService = inject(SchedulesService);
  private readonly parametersService = inject(ParametersService);
  private readonly authService = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly days = DAYS;
  protected readonly user = signal<User | null>(null);
  protected readonly schedules = signal<Schedule[]>([]);
  protected readonly loading = signal(true);
  protected readonly showStatusEnabled = signal(false);

  protected readonly canCreate = computed(() => this.authService.hasPermission('horarios.crear'));
  protected readonly canEdit = computed(() => this.authService.hasPermission('horarios.editar'));
  protected readonly canDelete = computed(() =>
    this.authService.hasPermission('horarios.eliminar'),
  );

  protected readonly addingDay = signal<number | null>(null);
  protected readonly editingId = signal<number | null>(null);
  protected readonly savingAdd = signal(false);
  protected readonly savingEdit = signal(false);
  protected readonly deletingId = signal<number | null>(null);

  protected addStart = '';
  protected addEnd = '';
  protected addShowStatus = true;
  protected editStart = '';
  protected editEnd = '';
  protected editShowStatus = true;

  ngOnInit() {
    const id = Number(this.userId());
    this.usersService
      .getById(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({ next: (user) => this.user.set(user) });
    this.loadSchedules(id);
    this.parametersService
      .getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (params) => {
          const param = params.find((p) => p.key === 'shift-status');
          this.showStatusEnabled.set(param?.value === 'true');
        },
      });
  }

  protected formatTime(time: string): string {
    const [h, m] = time.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour = h % 12 || 12;
    return `${hour}:${String(m).padStart(2, '0')} ${period}`;
  }

  protected schedulesForDay(dayId: number): Schedule[] {
    return this.schedules().filter((s) => s.day_of_week === dayId);
  }

  private loadSchedules(userId: number) {
    this.loading.set(true);
    this.schedulesService
      .getByUser(userId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (list) => {
          this.schedules.set(list);
          this.loading.set(false);
        },
        error: () => this.loading.set(false),
      });
  }

  protected openAdd(dayId: number) {
    this.addingDay.set(dayId);
    this.addStart = '';
    this.addEnd = '';
    this.addShowStatus = true;
    this.editingId.set(null);
  }

  protected cancelAdd() {
    this.addingDay.set(null);
  }

  protected saveAdd() {
    const dayId = this.addingDay();
    if (dayId === null || !this.addStart || !this.addEnd) return;
    this.savingAdd.set(true);
    this.schedulesService
      .create(Number(this.userId()), {
        day_of_week: dayId,
        start_time: this.addStart,
        end_time: this.addEnd,
        show_status: this.showStatusEnabled() && this.addShowStatus,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (created) => {
          this.schedules.update((list) => [...list, created]);
          this.addingDay.set(null);
          this.savingAdd.set(false);
          this.toast.success('Horario agregado.');
        },
        error: () => {
          this.savingAdd.set(false);
          this.toast.error('No se pudo agregar el horario.');
        },
      });
  }

  protected openEdit(entry: Schedule) {
    this.editingId.set(entry.id);
    this.editStart = entry.start_time;
    this.editEnd = entry.end_time;
    this.editShowStatus = entry.show_status;
    this.addingDay.set(null);
  }

  protected cancelEdit() {
    this.editingId.set(null);
  }

  protected saveEdit() {
    const id = this.editingId();
    if (!id || !this.editStart || !this.editEnd) return;
    this.savingEdit.set(true);
    this.schedulesService
      .update(id, {
        start_time: this.editStart,
        end_time: this.editEnd,
        show_status: this.showStatusEnabled() && this.editShowStatus,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (updated) => {
          this.schedules.update((list) => list.map((s) => (s.id === updated.id ? updated : s)));
          this.editingId.set(null);
          this.savingEdit.set(false);
          this.toast.success('Horario actualizado.');
        },
        error: () => {
          this.savingEdit.set(false);
          this.toast.error('No se pudo actualizar el horario.');
        },
      });
  }

  protected deleteEntry(entry: Schedule) {
    if (
      !confirm(
        `¿Eliminar el horario ${this.formatTime(entry.start_time)}–${this.formatTime(entry.end_time)} del ${entry.day_name}?`,
      )
    )
      return;
    this.deletingId.set(entry.id);
    this.schedulesService
      .delete(entry.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.schedules.update((list) => list.filter((s) => s.id !== entry.id));
          this.deletingId.set(null);
          this.toast.success('Horario eliminado.');
        },
        error: () => {
          this.deletingId.set(null);
          this.toast.error('No se pudo eliminar el horario.');
        },
      });
  }
}
