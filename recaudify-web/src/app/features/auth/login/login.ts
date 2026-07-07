import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';
import { ConfigService } from '@core/services/config.service';

@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
})
export class Login implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly config = inject(ConfigService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected username = '';
  protected password = '';
  protected showPassword = false;
  protected readonly error = signal('');
  protected readonly loading = signal(false);
  protected readonly geoRequired = signal(true);
  protected readonly loginField = signal<'username' | 'email'>('username');

  ngOnInit(): void {
    this.username = 'admin';
    this.password = 'admin1234';

    this.config
      .getLoginConfig()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((cfg) => {
        this.geoRequired.set(cfg.geolocalization_login);
        this.loginField.set(cfg.login_field);
      });
  }

  submit() {
    this.error.set('');
    this.loading.set(true);

    this.auth.login(this.username, this.password).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigate([this.auth.passwordExpired() ? '/change-password' : '/dashboard']);
      },
      error: (err) => {
        this.error.set(err.message ?? 'Usuario o contraseña incorrectos');
        this.loading.set(false);
      },
    });
  }
}
