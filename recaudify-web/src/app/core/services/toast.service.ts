import { Injectable, signal } from '@angular/core';

export type ToastType = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  message: string;
  type: ToastType;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  readonly toasts = signal<Toast[]>([]);

  success(message: string, duration = 5000): void {
    this._add(message, 'success', duration);
  }

  error(message: string, duration = 5000): void {
    this._add(message, 'error', duration);
  }

  warning(message: string, duration = 5000): void {
    this._add(message, 'warning', duration);
  }

  info(message: string, duration = 5000): void {
    this._add(message, 'info', duration);
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
