import { Component, computed, DestroyRef, inject, input, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { Schedule } from '@core/interfaces/schedule.interface';
import { User } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { SchedulesService } from '@core/services/schedules.service';
import { UsersService } from '@core/services/users.service';
import { finalize } from 'rxjs';

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
  private readonly authService = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly days = DAYS;
  protected readonly user = signal<User | null>(this.usersService.items()[0] ?? null);
  protected readonly schedules = this.schedulesService.items;
  protected readonly loading = this.schedulesService.loading;
  protected readonly showStatusEnabled = this.schedulesService.showStatusEnabled;

  protected readonly canCreate = computed(() => this.authService.hasPermission('schedules.create'));
  protected readonly canEdit = computed(() => this.authService.hasPermission('schedules.edit'));
  protected readonly canDelete = computed(() => this.authService.hasPermission('schedules.delete'));

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
    this.schedulesService.loadForUser(id);
    this.schedulesService.loadShiftStatusFlag();
  }

  protected formatTime(time: string) {
    return this.schedulesService.formatTime(time);
  }

  protected schedulesForDay(dayId: number) {
    return this.schedulesService.getForDay(dayId);
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
      .addEntry(Number(this.userId()), {
        day_of_week: dayId,
        start_time: this.addStart,
        end_time: this.addEnd,
        show_status: this.showStatusEnabled() && this.addShowStatus,
      })
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.savingAdd.set(false)),
      )
      .subscribe({ next: () => this.addingDay.set(null) });
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
      .updateEntry(id, {
        start_time: this.editStart,
        end_time: this.editEnd,
        show_status: this.showStatusEnabled() && this.editShowStatus,
      })
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.savingEdit.set(false)),
      )
      .subscribe({ next: () => this.editingId.set(null) });
  }

  protected deleteEntry(entry: Schedule) {
    const label = `${this.formatTime(entry.start_time)}–${this.formatTime(entry.end_time)} del ${entry.day_name}`;
    if (!confirm(`¿Eliminar el horario ${label}?`)) return;
    this.deletingId.set(entry.id);
    this.schedulesService
      .removeEntry(entry)
      .pipe(
        takeUntilDestroyed(this.destroyRef),
        finalize(() => this.deletingId.set(null)),
      )
      .subscribe();
  }
}
