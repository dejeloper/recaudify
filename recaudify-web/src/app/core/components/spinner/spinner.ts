import { Component, input } from '@angular/core';

@Component({
  selector: 'app-spinner',
  template: `
    @if (show()) {
      <div class="flex items-center justify-center py-16">
        <div class="flex flex-col items-center gap-3">
          <svg class="h-8 w-8 animate-spin text-slate-400" fill="none" viewBox="0 0 24 24">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="3"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            />
          </svg>
          @if (label()) {
            <p class="text-sm text-gray-400">{{ label() }}</p>
          }
        </div>
      </div>
    }
  `,
})
export class Spinner {
  readonly show = input.required<boolean>();
  readonly label = input<string>('');
}
