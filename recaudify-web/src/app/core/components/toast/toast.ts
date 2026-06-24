import { Component, computed, inject, input } from '@angular/core';
import {
  SizeStyle,
  Toast,
  ToastPosition,
  ToastSize,
  ToastType,
} from '@core/interfaces/toast.interface';
import { ToastService } from '@core/services/toast.service';

const POSITION_CLASSES: Record<ToastPosition, string> = {
  1: 'top-5 left-5',
  2: 'top-5 left-1/2 -translate-x-1/2',
  3: 'top-5 right-5',
  4: 'top-1/2 -translate-y-1/2 left-5',
  5: 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
  6: 'top-1/2 -translate-y-1/2 right-5',
  7: 'bottom-5 left-5',
  8: 'bottom-5 left-1/2 -translate-x-1/2',
  9: 'bottom-5 right-5',
};

const SIZE_STYLES: Record<ToastSize, SizeStyle> = {
  sm: {
    container: 'w-72',
    card: 'px-3.5 py-2.5 gap-2.5',
    icon: 'h-4 w-4',
    closeIcon: 'h-3.5 w-3.5',
    text: 'text-xs',
  },
  md: {
    container: 'w-80',
    card: 'px-4 py-3 gap-3',
    icon: 'h-4 w-4',
    closeIcon: 'h-4 w-4',
    text: 'text-sm',
  },
  lg: {
    container: 'w-96',
    card: 'px-5 py-3.5 gap-3',
    icon: 'h-5 w-5',
    closeIcon: 'h-4 w-4',
    text: 'text-sm',
  },
};

const STYLES: Record<ToastType, { wrapper: string; icon: string; path: string }> = {
  success: {
    wrapper: 'bg-green-50 border-green-200 text-green-800',
    icon: 'text-green-500',
    path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  error: {
    wrapper: 'bg-red-50 border-red-200 text-red-800',
    icon: 'text-red-500',
    path: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
  },
  warning: {
    wrapper: 'bg-amber-50 border-amber-200 text-amber-800',
    icon: 'text-amber-500',
    path: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
  },
  info: {
    wrapper: 'bg-slate-50 border-slate-200 text-slate-700',
    icon: 'text-slate-400',
    path: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
  },
};

@Component({
  selector: 'app-toast',
  template: `
    <div
      class="fixed z-50 flex flex-col gap-2 pointer-events-none"
      [class]="positionClass() + ' ' + sizeStyle().container"
    >
      @for (toast of toastService.toasts(); track toast.id) {
        <div
          class="flex items-start rounded-xl border shadow-lg pointer-events-auto"
          [class]="style(toast).wrapper + ' ' + sizeStyle().card"
        >
          <svg
            class="mt-0.5 shrink-0"
            [class]="style(toast).icon + ' ' + sizeStyle().icon"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              [attr.d]="style(toast).path"
            />
          </svg>

          <p class="flex-1 leading-5" [class]="sizeStyle().text">{{ toast.message }}</p>

          <button
            (click)="toastService.dismiss(toast.id)"
            class="shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 transition-opacity"
          >
            <svg
              [class]="sizeStyle().closeIcon"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      }
    </div>
  `,
})
export class ToastContainer {
  protected readonly toastService = inject(ToastService);

  readonly position = input<ToastPosition>(5);
  readonly size = input<ToastSize>('sm');

  protected readonly positionClass = computed(() => POSITION_CLASSES[this.position()]);
  protected readonly sizeStyle = computed(() => SIZE_STYLES[this.size()]);

  protected style(toast: Toast) {
    return STYLES[toast.type];
  }
}
