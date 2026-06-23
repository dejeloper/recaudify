import { Component, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'app-login',
  imports: [FormsModule],
  templateUrl: './login.html',
})
export class Login implements OnInit {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected username = '';
  protected password = '';
  protected showPassword = false;
  protected readonly error = signal('');
  protected readonly loading = signal(false);

  ngOnInit(): void {
    this.username = 'admin';
    this.password = 'admin1234';
  }

  submit() {
    this.error.set('');
    this.loading.set(true);

    this.auth.login(this.username, this.password).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        this.error.set(err.message ?? 'Usuario o contraseña incorrectos');
        this.loading.set(false);
      },
    });
  }
}
