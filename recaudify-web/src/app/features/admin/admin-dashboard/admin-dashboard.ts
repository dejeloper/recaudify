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

  protected readonly canSeeUsers = computed(() => this.auth.hasPermission('usuarios.ver'));
  protected readonly canSeeRoles = computed(() => this.auth.hasPermission('roles.ver'));
  protected readonly canSeePermissions = computed(() => this.auth.hasPermission('permisos.ver'));
  protected readonly canSeeSchedules = computed(() => this.auth.hasPermission('horarios.ver'));
  protected readonly canSeeParameters = computed(() => this.auth.hasPermission('parametros.ver'));
}
