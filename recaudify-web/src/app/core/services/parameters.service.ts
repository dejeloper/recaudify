import { inject, Injectable } from '@angular/core';
import { Parameter } from '@core/models/parameter';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class ParametersService {
  private readonly api = inject(ApiService);

  getAll() {
    return this.api.get<Parameter[]>('parameters');
  }

  getById(id: number) {
    return this.api.get<Parameter>('parameters', String(id));
  }

  create(key: string, value: string, description: string | null) {
    return this.api.post<Parameter>('parameters', undefined, { key, value, description });
  }

  update(id: number, key: string, value: string, description: string | null) {
    return this.api.put<Parameter>('parameters', String(id), { key, value, description });
  }

  delete(id: number) {
    return this.api.delete('parameters', String(id));
  }

  getTrashed() {
    return this.api.get<Parameter[]>('parameters', 'trashed');
  }

  restore(id: number) {
    return this.api.post<void>('parameters', `${id}/restore`);
  }
}
