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
        ],
      },
    ],
  },
  {path: '**', redirectTo: 'dashboard'},
];
