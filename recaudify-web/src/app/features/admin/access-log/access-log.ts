import { DatePipe } from '@angular/common';
import { Component, inject, OnInit, signal } from '@angular/core';
import { BtnDirective } from '@core/directives/btn.directive';
import { Spinner } from '@core/components/spinner/spinner';
import { LoginAudit, LoginAuditStatusFilter } from '@core/interfaces/login-audit.interface';
import { LoginAuditsService } from '@core/services/login-audits.service';

const REASON_LABELS: Record<string, string> = {
  invalid_credentials: 'Credenciales incorrectas',
  inactive: 'Usuario inactivo',
  out_of_schedule: 'Fuera de horario',
  locked_out: 'Bloqueado por intentos fallidos',
};

@Component({
  selector: 'app-access-log',
  imports: [Spinner, DatePipe, BtnDirective],
  templateUrl: './access-log.html',
})
export class AccessLog implements OnInit {
  protected readonly service = inject(LoginAuditsService);

  protected readonly audits = this.service.items;
  protected readonly meta = this.service.meta;
  protected readonly loading = this.service.loading;
  protected readonly loadingMore = this.service.loadingMore;
  protected readonly status = signal<LoginAuditStatusFilter>('all');

  ngOnInit() {
    this.service.load();
  }

  protected setStatus(status: LoginAuditStatusFilter) {
    if (this.status() === status) return;
    this.status.set(status);
    this.service.load(status === 'all' ? {} : { status });
  }

  protected loadMore() {
    this.service.loadMore();
  }

  protected hasMore() {
    return this.service.hasMore();
  }

  protected reasonLabel(reason: string | null): string {
    return reason ? (REASON_LABELS[reason] ?? reason) : '';
  }

  protected mapsUrl(audit: LoginAudit): string | null {
    if (!audit.geolocation) return null;
    const { latitude, longitude } = audit.geolocation;
    return `https://www.google.com/maps?q=${latitude},${longitude}`;
  }
}
