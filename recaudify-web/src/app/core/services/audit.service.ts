import { Injectable } from '@angular/core';
import { LoginAudit } from '@core/interfaces/audit.interface';

@Injectable({ providedIn: 'root' })
export class AuditService {
  captureLogin(userId: number, ipAddress: string | null, coords: GeolocationCoordinates): void {
    const audit: LoginAudit = {
      user_id: userId,
      session_id: crypto.randomUUID(),
      logged_at: new Date().toISOString(),
      ip_address: ipAddress,
      user_agent: navigator.userAgent,
      os: this.parseOs(navigator.userAgent),
      device_type: this.getDeviceType(navigator.userAgent),
      geolocation: {
        latitude: coords.latitude,
        longitude: coords.longitude,
        accuracy: coords.accuracy,
      },
    };

    console.log('[Audit] Login:', audit);
  }

  private parseOs(ua: string): { name: string; version: string } {
    const rules: [RegExp, string][] = [
      [/Windows NT (\d+\.\d+)/, 'Windows'],
      [/iPhone OS ([\d_]+)/, 'iOS'],
      [/iPad.*OS ([\d_]+)/, 'iPadOS'],
      [/Android ([\d.]+)/, 'Android'],
      [/Mac OS X ([\d_]+)/, 'macOS'],
      [/Linux/, 'Linux'],
    ];

    for (const [regex, name] of rules) {
      const match = ua.match(regex);
      if (match) return { name, version: (match[1] ?? '').replace(/_/g, '.') };
    }

    return { name: 'Unknown', version: '' };
  }

  private getDeviceType(ua: string): 'mobile' | 'tablet' | 'desktop' {
    if (/iPad|Android(?!.*Mobile)|Tablet/i.test(ua)) return 'tablet';
    if (/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua)) return 'mobile';
    return 'desktop';
  }
}
