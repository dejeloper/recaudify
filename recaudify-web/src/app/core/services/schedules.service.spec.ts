import { provideZonelessChangeDetection } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { Schedule } from '@core/interfaces/schedule.interface';
import { ApiService } from '@core/services/api.service';
import { ParametersService } from '@core/services/parameters.service';
import { SchedulesService } from '@core/services/schedules.service';
import { ToastService } from '@core/services/toast.service';

function schedule(id: number, day: number): Schedule {
  return {
    id,
    user_id: 1,
    day_of_week: day,
    day_name: 'Lunes',
    start_time: '08:00',
    end_time: '17:00',
    show_status: true,
  } as Schedule;
}

function setup() {
  const api = { request: vi.fn() };
  const toast = { success: vi.fn(), error: vi.fn() };
  const parameters = { getFlag: vi.fn() };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      SchedulesService,
      { provide: ApiService, useValue: api },
      { provide: ToastService, useValue: toast },
      { provide: ParametersService, useValue: parameters },
    ],
  });

  return { service: TestBed.inject(SchedulesService), api, toast, parameters };
}

describe('SchedulesService', () => {
  it('formatTime converts 24h to 12h with period', () => {
    const { service } = setup();
    expect(service.formatTime('08:00')).toBe('8:00 AM');
    expect(service.formatTime('13:30')).toBe('1:30 PM');
    expect(service.formatTime('00:15')).toBe('12:15 AM');
    expect(service.formatTime('12:00')).toBe('12:00 PM');
  });

  it('loadForUser populates items and getForDay filters by day', () => {
    const { service, api } = setup();
    api.request.mockReturnValue(of([schedule(1, 1), schedule(2, 3)]));

    service.loadForUser(1);

    expect(service.items()).toHaveLength(2);
    expect(service.getForDay(3).map((s) => s.id)).toEqual([2]);
  });

  it('addEntry appends the created schedule and toasts', () => {
    const { service, api, toast } = setup();
    api.request.mockReturnValue(of(schedule(5, 2)));

    service
      .addEntry(1, { day_of_week: 2, start_time: '08:00', end_time: '17:00', show_status: true })
      .subscribe();

    expect(service.items().some((s) => s.id === 5)).toBe(true);
    expect(toast.success).toHaveBeenCalled();
  });

  it('removeEntry removes the schedule and toasts', () => {
    const { service, api, toast } = setup();
    api.request.mockReturnValueOnce(of([schedule(1, 1)])); // loadForUser
    service.loadForUser(1);
    api.request.mockReturnValueOnce(of(undefined)); // delete

    service.removeEntry(schedule(1, 1)).subscribe();

    expect(service.items()).toHaveLength(0);
    expect(toast.success).toHaveBeenCalled();
  });

  it('loadShiftStatusFlag reads the flag from ParametersService', () => {
    const { service, parameters } = setup();
    parameters.getFlag.mockReturnValue(of(true));

    service.loadShiftStatusFlag();

    expect(parameters.getFlag).toHaveBeenCalledWith('shift-status');
    expect(service.showStatusEnabled()).toBe(true);
  });
});
