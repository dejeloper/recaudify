import { inject, Injectable } from '@angular/core';
import { Observable, of, tap } from 'rxjs';
import { LoginConfig } from '@core/interfaces/login-config.interface';
import { ApiService } from '@core/services/api.service';

@Injectable({ providedIn: 'root' })
export class ConfigService {
  private readonly api = inject(ApiService);
  private loginConfig: LoginConfig | null = null;

  getLoginConfig(): Observable<LoginConfig> {
    if (this.loginConfig) return of(this.loginConfig);
    return this.api
      .get<LoginConfig>('auth', 'config')
      .pipe(tap((config) => (this.loginConfig = config)));
  }
}
