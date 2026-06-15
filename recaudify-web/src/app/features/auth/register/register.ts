import { ChangeDetectionStrategy, Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { lower } from '../../../core/utils/text';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  imports: [FormsModule, RouterLink],
  templateUrl: './register.html',
  changeDetection: ChangeDetectionStrategy.OnPush,
})
export class Register {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected name = '';
  protected username = '';
  protected email = '';
  protected password = '';
  protected passwordConfirmation = '';
  protected readonly error = signal('');
  protected readonly loading = signal(false);

  submit() {
    this.error.set('');
    this.loading.set(true);

    this.auth.register(this.name, lower(this.username), this.email, this.password, this.passwordConfirmation).subscribe({
      next: () => {
        this.loading.set(false);
        this.router.navigate(['/dashboard']);
      },
      error: (err) => {
        const errors = err.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : (err.message ?? 'Error al registrarse'));
        this.loading.set(false);
      },
    });
  }
}
