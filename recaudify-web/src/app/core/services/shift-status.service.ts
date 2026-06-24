import { computed, inject, Injectable } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { interval } from 'rxjs';
import { AuthService } from '@core/services/auth.service';

@Injectable({ providedIn: 'root' })
export class ShiftStatusService {
  private readonly auth = inject(AuthService);

  private readonly _tick = toSignal(interval(60_000), { initialValue: 0 });

  readonly visibleShift = computed(() => {
    const enabled = this.auth.shiftStatusEnabled();
    const shift = this.auth.currentShift();
    if (!enabled || !shift || !shift.is_within_schedule || !shift.show_status) return null;
    return shift;
  });

  readonly countdownMinutes = computed(() => {
    const shift = this.visibleShift();
    const tick = this._tick();
    if (!shift || shift.remaining_minutes == null) return null;
    return Math.max(0, shift.remaining_minutes - tick);
  });
}
