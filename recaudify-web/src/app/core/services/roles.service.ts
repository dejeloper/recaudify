import { inject, Injectable } from '@angular/core';
import { Role } from '@core/models/role';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class RolesService {
  private readonly api = inject(ApiService);

  getAll() {
    return this.api.get<Role[]>('roles');
  }

  getById(id: number) {
    return this.api.get<Role>('roles', String(id));
  }

  create(name: string, permissions: string[]) {
    return this.api.post<Role>('roles', undefined, { name, permissions });
  }

  update(id: number, name: string, permissions: string[]) {
    return this.api.put<Role>('roles', String(id), { name, permissions });
  }

  delete(id: number) {
    return this.api.delete('roles', String(id));
  }
}
