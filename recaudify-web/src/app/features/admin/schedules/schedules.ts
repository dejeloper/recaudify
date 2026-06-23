import { Component, DestroyRef, inject, OnInit, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { RouterLink } from '@angular/router';
import { BtnDirective } from '@core/directives/btn.directive';
import { TableDirective } from '@core/directives/table.directive';
import { User } from '@core/models/user';
import { UsersService } from '@core/services/users.service';

@Component({
  selector: 'app-schedules',
  imports: [RouterLink, BtnDirective, TableDirective],
  templateUrl: './schedules.html',
})
export class Schedules implements OnInit {
  private readonly usersService = inject(UsersService);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly users = signal<User[]>([]);
  protected readonly loading = signal(true);

  ngOnInit() {
    this.usersService.getAll()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: users => { this.users.set(users); this.loading.set(false); },
        error: () => this.loading.set(false),
      });
  }
}
