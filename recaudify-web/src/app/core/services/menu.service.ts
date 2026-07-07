import { inject, Injectable, signal } from '@angular/core';
import { MenuItem } from '@core/interfaces/nav.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class MenuService {
  private readonly api = inject(ApiService);

  readonly menuTree = signal<MenuItem[]>([]);

  load(): void {
    this.api.get<MenuItem[]>('menu').subscribe((tree) => this.menuTree.set(tree));
  }
}
