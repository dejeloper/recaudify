import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { provideRouter } from '@angular/router';
import { of } from 'rxjs';
import { Schedule } from '@core/interfaces/schedule.interface';
import { AuthService } from '@core/services/auth.service';
import { SchedulesService } from '@core/services/schedules.service';
import { UsersService } from '@core/services/users.service';
import { UserSchedules } from './user-schedules';

const entry: Schedule = {
  id: 7,
  user_id: 5,
  day_of_week: 1,
  day_name: 'Lunes',
  start_time: '08:00',
  end_time: '17:00',
  show_status: true,
} as Schedule;

async function setup(
  permissions: string[] = ['horarios.crear', 'horarios.editar', 'horarios.eliminar'],
) {
  const usersService = {
    items: signal([]),
    getById: vi.fn().mockReturnValue(of({ id: 5, name: 'Ana' })),
  };
  const schedulesService = {
    items: signal<Schedule[]>([]),
    loading: signal(false),
    showStatusEnabled: signal(true),
    loadForUser: vi.fn(),
    loadShiftStatusFlag: vi.fn(),
    getForDay: vi.fn().mockReturnValue([]),
    formatTime: vi.fn((t: string) => t),
    addEntry: vi.fn().mockReturnValue(of({})),
    updateEntry: vi.fn().mockReturnValue(of({})),
    removeEntry: vi.fn().mockReturnValue(of(undefined)),
  };
  const authService = { hasPermission: (p: string) => permissions.includes(p) };

  await TestBed.configureTestingModule({
    imports: [UserSchedules],
    providers: [
      provideZonelessChangeDetection(),
      provideRouter([]),
      { provide: UsersService, useValue: usersService },
      { provide: SchedulesService, useValue: schedulesService },
      { provide: AuthService, useValue: authService },
    ],
  }).compileComponents();

  const fixture = TestBed.createComponent(UserSchedules);
  fixture.componentRef.setInput('userId', '5');
  fixture.detectChanges();

  return { comp: fixture.componentInstance as any, usersService, schedulesService };
}

describe('UserSchedules', () => {
  it('loads the user and schedules on init', async () => {
    const { usersService, schedulesService } = await setup();
    expect(usersService.getById).toHaveBeenCalledWith(5);
    expect(schedulesService.loadForUser).toHaveBeenCalledWith(5);
    expect(schedulesService.loadShiftStatusFlag).toHaveBeenCalled();
  });

  it('exposes permission flags', async () => {
    const { comp } = await setup(['horarios.crear']);
    expect(comp.canCreate()).toBe(true);
    expect(comp.canEdit()).toBe(false);
    expect(comp.canDelete()).toBe(false);
  });

  it('saveAdd sends the new entry when the form is complete', async () => {
    const { comp, schedulesService } = await setup();
    comp.openAdd(2);
    comp.addStart = '08:00';
    comp.addEnd = '17:00';

    comp.saveAdd();

    expect(schedulesService.addEntry).toHaveBeenCalledWith(5, {
      day_of_week: 2,
      start_time: '08:00',
      end_time: '17:00',
      show_status: true,
    });
  });

  it('saveAdd does nothing without start/end times', async () => {
    const { comp, schedulesService } = await setup();
    comp.openAdd(2);

    comp.saveAdd();

    expect(schedulesService.addEntry).not.toHaveBeenCalled();
  });

  it('openEdit populates the edit form and saveEdit updates', async () => {
    const { comp, schedulesService } = await setup();
    comp.openEdit(entry);
    expect(comp.editStart).toBe('08:00');

    comp.saveEdit();

    expect(schedulesService.updateEntry).toHaveBeenCalledWith(7, {
      start_time: '08:00',
      end_time: '17:00',
      show_status: true,
    });
  });

  it('deleteEntry removes when confirmed', async () => {
    const { comp, schedulesService } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(true);

    comp.deleteEntry(entry);

    expect(schedulesService.removeEntry).toHaveBeenCalledWith(entry);
  });

  it('deleteEntry is cancelled when not confirmed', async () => {
    const { comp, schedulesService } = await setup();
    vi.spyOn(window, 'confirm').mockReturnValue(false);

    comp.deleteEntry(entry);

    expect(schedulesService.removeEntry).not.toHaveBeenCalled();
  });
});
