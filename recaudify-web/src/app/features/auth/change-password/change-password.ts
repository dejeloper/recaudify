import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { ApiError } from '@core/interfaces/api-error.interface';
import { PasswordPolicyConfig } from '@core/interfaces/login-config.interface';
import { AuthService } from '@core/services/auth.service';
import { ConfigService } from '@core/services/config.service';

@Component({
  selector: 'app-change-password',
  imports: [FormsModule],
  templateUrl: './change-password.html',
})
export class ChangePassword implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly config = inject(ConfigService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected currentPassword = '';
  protected password = '';
  protected passwordConfirmation = '';
  protected readonly error = signal('');
  protected readonly loading = signal(false);
  protected readonly policy = signal<PasswordPolicyConfig | null>(null);

  ngOnInit(): void {
    this.config
      .getLoginConfig()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((cfg) => this.policy.set(cfg.password_policy));
  }

  submit(): void {
    this.error.set('');

    if (this.password !== this.passwordConfirmation) {
      this.error.set('Las contraseñas no coinciden.');
      return;
    }

    this.loading.set(true);

    this.auth
      .changePassword(this.currentPassword, this.password, this.passwordConfirmation)
      .subscribe({
        next: () => {
          this.loading.set(false);
          this.router.navigate(['/dashboard']);
        },
        error: (err: ApiError) => {
          this.error.set(err.message ?? 'No se pudo actualizar la contraseña.');
          this.loading.set(false);
        },
      });
  }
}
