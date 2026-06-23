import {Routes} from '@angular/router';
import {authGuard, guestGuard} from './core/guards/auth.guard';
import {adminGuard} from './core/guards/admin.guard';

export const routes: Routes = [
  {path: '', redirectTo: 'dashboard', pathMatch: 'full'},
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then((m) => m.Login),
    canActivate: [guestGuard],
  },
  {
    path: '',
    loadComponent: () => import('./layout/app-shell/app-shell').then((m) => m.AppShell),
    canActivate: [authGuard],
    children: [
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard').then((m) => m.Dashboard),
      },
      {
        path: 'admin',
        canActivate: [adminGuard],
        children: [
          {
            path: '',
            loadComponent: () => import('./features/admin/admin-dashboard/admin-dashboard').then((m) => m.AdminDashboard),
          },
          {
            path: 'roles',
            loadComponent: () => import('./features/admin/roles/roles').then((m) => m.Roles),
          },
          {
            path: 'roles/new',
            loadComponent: () => import('./features/admin/roles/role-form/role-form').then((m) => m.RoleForm),
          },
          {
            path: 'roles/:id/edit',
            loadComponent: () => import('./features/admin/roles/role-form/role-form').then((m) => m.RoleForm),
          },
          {
            path: 'permissions',
            loadComponent: () => import('./features/admin/permissions/permissions').then((m) => m.Permissions),
          },
          {
            path: 'permissions/new',
            loadComponent: () => import('./features/admin/permissions/permission-form/permission-form').then((m) => m.PermissionForm),
          },
          {
            path: 'permissions/:id/edit',
            loadComponent: () => import('./features/admin/permissions/permission-form/permission-form').then((m) => m.PermissionForm),
          },
          {
            path: 'schedules',
            loadComponent: () => import('./features/admin/schedules/schedules').then((m) => m.Schedules),
          },
          {
            path: 'schedules/:userId',
            loadComponent: () => import('./features/admin/schedules/user-schedules/user-schedules').then((m) => m.UserSchedules),
          },
          {
            path: 'parameters',
            loadComponent: () => import('./features/admin/parameters/parameters').then((m) => m.Parameters),
          },
          {
            path: 'parameters/new',
            loadComponent: () => import('./features/admin/parameters/parameter-form/parameter-form').then((m) => m.ParameterForm),
          },
          {
            path: 'parameters/:id/edit',
            loadComponent: () => import('./features/admin/parameters/parameter-form/parameter-form').then((m) => m.ParameterForm),
          },
        ],
      },
    ],
  },
  {path: '**', redirectTo: 'dashboard'},
];
