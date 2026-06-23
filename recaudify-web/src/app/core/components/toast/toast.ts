import { Component, inject } from '@angular/core';
import { Toast, ToastService, ToastType } from '@core/services/toast.service';

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
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 w-80 pointer-events-none">
      @for (toast of toastService.toasts(); track toast.id) {
        <div
          class="flex items-start gap-3 rounded-xl border px-4 py-3 shadow-lg pointer-events-auto"
          [class]="style(toast).wrapper"
        >
          <svg
            class="mt-0.5 h-5 w-5 shrink-0"
            [class]="style(toast).icon"
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

          <p class="flex-1 text-sm leading-5">{{ toast.message }}</p>

          <button
            (click)="toastService.dismiss(toast.id)"
            class="shrink-0 rounded p-0.5 opacity-60 hover:opacity-100 transition-opacity"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

  protected style(toast: Toast) {
    return STYLES[toast.type];
  }
}
