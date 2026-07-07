import { Injectable, signal } from '@angular/core';
import { Toast, ToastType } from '@core/interfaces/toast.interface';

@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly toasts = signal<Toast[]>([]);

  private _defaultDuration = 5000;

  setDefaultDuration(ms: number): void {
    this._defaultDuration = ms;
  }

  success(message: string, duration?: number): void {
    this._add(message, 'success', duration ?? this._defaultDuration);
  }

  error(message: string, duration?: number): void {
    this._add(message, 'error', duration ?? this._defaultDuration);
  }

  warning(message: string, duration?: number): void {
    this._add(message, 'warning', duration ?? this._defaultDuration);
  }

  info(message: string, duration?: number): void {
    this._add(message, 'info', duration ?? this._defaultDuration);
  }

  dismiss(id: string): void {
    this.toasts.update((list) => list.filter((t) => t.id !== id));
  }

  private _add(message: string, type: ToastType, duration: number): void {
    const id = crypto.randomUUID();
    this.toasts.update((list) => [...list, { id, message, type }]);
    if (duration > 0) {
      setTimeout(() => this.dismiss(id), duration);
    }
  }
}
