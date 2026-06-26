import { provideZonelessChangeDetection, signal } from '@angular/core';
import { TestBed } from '@angular/core/testing';
import { CurrentShift } from '@core/interfaces/user.interface';
import { AuthService } from '@core/services/auth.service';
import { ShiftStatusService } from '@core/services/shift-status.service';

function shift(overrides: Partial<CurrentShift> = {}): CurrentShift {
  return {
    is_within_schedule: true,
    show_status: true,
    day_of_week: 1,
    start_time: '08:00',
    end_time: '17:00',
    remaining_minutes: 30,
    ...overrides,
  } as CurrentShift;
}

function setup(enabled: boolean, current: CurrentShift | null) {
  const auth = { shiftStatusEnabled: signal(enabled), currentShift: signal(current) };

  TestBed.configureTestingModule({
    providers: [
      provideZonelessChangeDetection(),
      ShiftStatusService,
      { provide: AuthService, useValue: auth },
    ],
  });

  return TestBed.inject(ShiftStatusService);
}

describe('ShiftStatusService', () => {
  it('visibleShift is null when the flag is disabled', () => {
    const service = setup(false, shift());
    expect(service.visibleShift()).toBeNull();
  });

  it('visibleShift is null when not within schedule', () => {
    const service = setup(true, shift({ is_within_schedule: false }));
    expect(service.visibleShift()).toBeNull();
  });

  it('visibleShift is null when show_status is false', () => {
    const service = setup(true, shift({ show_status: false }));
    expect(service.visibleShift()).toBeNull();
  });

  it('visibleShift returns the shift when enabled, within schedule and visible', () => {
    const service = setup(true, shift());
    expect(service.visibleShift()).not.toBeNull();
  });

  it('countdownMinutes equals remaining_minutes initially', () => {
    const service = setup(true, shift({ remaining_minutes: 45 }));
    expect(service.countdownMinutes()).toBe(45);
  });

  it('countdownMinutes is null without a visible shift', () => {
    const service = setup(false, shift());
    expect(service.countdownMinutes()).toBeNull();
  });
});
