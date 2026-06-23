import { inject, Injectable } from '@angular/core';
import { User } from '@core/models/user';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class UsersService {
  private readonly api = inject(ApiService);

  getAll() {
    return this.api.get<User[]>('users');
  }

  getById(id: number) {
    return this.api.get<User>('users', String(id));
  }
}
