import { inject, Injectable } from '@angular/core';
import { Schedule } from '@core/interfaces/schedule.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class SchedulesService {
  private readonly api = inject(ApiService);

  getByUser(userId: number) {
    return this.api.request<Schedule[]>({
      controller: `users/${userId}/schedules`,
      method: 'GET',
    });
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
