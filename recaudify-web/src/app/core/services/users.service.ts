import { inject, Injectable } from '@angular/core';
import { User, UserPayload } from '@core/interfaces/user.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class UsersService {
  private readonly api = inject(ApiService);

  getAll() {
    return this.api.get<User[]>('users');
  }

  getDisabled() {
    return this.api.get<User[]>('users', 'disabled');
  }

  getById(id: number) {
    return this.api.get<User>('users', String(id));
  }

  create(payload: UserPayload) {
    return this.api.post<User>('users', undefined, payload);
  }

  update(id: number, payload: Partial<UserPayload>) {
    return this.api.put<User>('users', String(id), payload);
  }

  delete(id: number) {
    return this.api.delete('users', String(id));
  }

  restore(id: number) {
    return this.api.post<void>('users', `${id}/restore`);
  }
}
