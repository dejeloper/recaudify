import { Component, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  imports: [FormsModule, RouterLink],
  templateUrl: './register.html',
})
export class Register {
  private auth = inject(AuthService);
  private router = inject(Router);

  name = '';
  email = '';
  password = '';
  passwordConfirmation = '';
  error = signal('');
  loading = signal(false);

  submit() {
    this.error.set('');
    this.loading.set(true);

    this.auth.register(this.name, this.email, this.password, this.passwordConfirmation).subscribe({
      next: () => this.router.navigate(['/dashboard']),
      error: (err) => {
        const errors = err.error?.errors;
        this.error.set(errors ? Object.values(errors).flat().join(' ') : (err.error?.message ?? 'Error al registrarse'));
        this.loading.set(false);
      },
    });
  }
}
