import { Component, computed, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '@core/services/auth.service';

@Component({
  selector: 'app-admin-dashboard',
  imports: [RouterLink],
  templateUrl: './admin-dashboard.html',
})
export class AdminDashboard {
  private readonly auth = inject(AuthService);

  protected readonly canSeeUsers = computed(() => this.auth.hasPermission('users.view'));
  protected readonly canSeeRoles = computed(() => this.auth.hasPermission('roles.view'));
  protected readonly canSeePermissions = computed(() =>
    this.auth.hasPermission('permissions.view'),
  );
  protected readonly canSeeSchedules = computed(() => this.auth.hasPermission('schedules.view'));
  protected readonly canSeeParameters = computed(() => this.auth.hasPermission('parameters.view'));
  protected readonly canSeeCatalogs = computed(() => this.auth.hasPermission('catalogs.view'));
  protected readonly canSeeActivity = computed(() => this.auth.hasPermission('audit.view'));
  protected readonly canSeeAccessLog = computed(() => this.auth.hasPermission('access.view'));
}
