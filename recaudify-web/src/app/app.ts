import { Component, DestroyRef, inject, OnInit } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterOutlet } from '@angular/router';
import { ToastContainer } from '@core/components/toast/toast';
import { ParametersService } from '@core/services/parameters.service';
import { ToastService } from '@core/services/toast.service';

@Component({
  selector: 'app-root',
  imports: [RouterOutlet, ToastContainer],
  templateUrl: './app.html',
})
export class App implements OnInit {
  private readonly parametersService = inject(ParametersService);
  private readonly toastService = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  ngOnInit(): void {
    this.parametersService
      .getConfigValue<number>('toast_duration_ms')
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((ms) => {
        if (ms) this.toastService.setDefaultDuration(ms);
      });
  }
}
