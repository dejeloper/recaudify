import { inject, Injectable } from '@angular/core';
import { Permission } from '@core/models/permission';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class PermissionsService {
  private readonly api = inject(ApiService);

  getAll() {
    return this.api.get<Permission[]>('permissions');
  }

  getById(id: number) {
    return this.api.get<Permission>('permissions', String(id));
  }

  create(name: string) {
    return this.api.post<Permission>('permissions', undefined, { name });
  }

  update(id: number, name: string) {
    return this.api.put<Permission>('permissions', String(id), { name });
  }

  delete(id: number) {
    return this.api.delete('permissions', String(id));
  }

  getTrashed() {
    return this.api.get<Permission[]>('permissions', 'trashed');
  }

  restore(id: number) {
    return this.api.post<void>('permissions', `${id}/restore`);
  }
}
