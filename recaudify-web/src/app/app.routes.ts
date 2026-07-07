import { Routes } from '@angular/router';
import { authGuard, authOnlyGuard, guestGuard } from './core/guards/auth.guard';
import { adminGuard } from './core/guards/admin.guard';

export const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then((m) => m.Login),
    canActivate: [guestGuard],
  },
  {
    path: 'change-password',
    loadComponent: () =>
      import('./features/auth/change-password/change-password').then((m) => m.ChangePassword),
    canActivate: [authOnlyGuard],
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
            loadComponent: () =>
              import('./features/admin/admin-dashboard/admin-dashboard').then(
                (m) => m.AdminDashboard,
              ),
          },
          {
            path: 'users',
            loadComponent: () => import('./features/admin/users/users').then((m) => m.Users),
          },
          {
            path: 'users/new',
            loadComponent: () =>
              import('./features/admin/users/user-form/user-form').then((m) => m.UserForm),
          },
          {
            path: 'users/:id/edit',
            loadComponent: () =>
              import('./features/admin/users/user-form/user-form').then((m) => m.UserForm),
          },
          {
            path: 'roles',
            loadComponent: () => import('./features/admin/roles/roles').then((m) => m.Roles),
          },
          {
            path: 'roles/new',
            loadComponent: () =>
              import('./features/admin/roles/role-form/role-form').then((m) => m.RoleForm),
          },
          {
            path: 'roles/:id/edit',
            loadComponent: () =>
              import('./features/admin/roles/role-form/role-form').then((m) => m.RoleForm),
          },
          {
            path: 'permissions',
            loadComponent: () =>
              import('./features/admin/permissions/permissions').then((m) => m.Permissions),
          },
          {
            path: 'permissions/new',
            loadComponent: () =>
              import('./features/admin/permissions/permission-form/permission-form').then(
                (m) => m.PermissionForm,
              ),
          },
          {
            path: 'permissions/:id/edit',
            loadComponent: () =>
              import('./features/admin/permissions/permission-form/permission-form').then(
                (m) => m.PermissionForm,
              ),
          },
          {
            path: 'schedules',
            loadComponent: () =>
              import('./features/admin/schedules/schedules').then((m) => m.Schedules),
          },
          {
            path: 'schedules/:userId',
            loadComponent: () =>
              import('./features/admin/schedules/user-schedules/user-schedules').then(
                (m) => m.UserSchedules,
              ),
          },
          {
            path: 'products',
            loadComponent: () =>
              import('./features/admin/products/products').then((m) => m.Products),
          },
          {
            path: 'products/new',
            loadComponent: () =>
              import('./features/admin/products/product-form/product-form').then(
                (m) => m.ProductForm,
              ),
          },
          {
            path: 'products/:id/edit',
            loadComponent: () =>
              import('./features/admin/products/product-form/product-form').then(
                (m) => m.ProductForm,
              ),
          },
          {
            path: 'rates',
            loadComponent: () => import('./features/admin/rates/rates').then((m) => m.Rates),
          },
          {
            path: 'rates/new',
            loadComponent: () =>
              import('./features/admin/rates/rate-form/rate-form').then((m) => m.RateForm),
          },
          {
            path: 'rates/:id/edit',
            loadComponent: () =>
              import('./features/admin/rates/rate-form/rate-form').then((m) => m.RateForm),
          },
          {
            path: 'sellers',
            loadComponent: () => import('./features/admin/sellers/sellers').then((m) => m.Sellers),
          },
          {
            path: 'sellers/new',
            loadComponent: () =>
              import('./features/admin/sellers/seller-form/seller-form').then((m) => m.SellerForm),
          },
          {
            path: 'sellers/:id/edit',
            loadComponent: () =>
              import('./features/admin/sellers/seller-form/seller-form').then((m) => m.SellerForm),
          },
          {
            path: 'call-reasons',
            loadComponent: () =>
              import('./features/admin/call-reasons/call-reasons').then((m) => m.CallReasons),
          },
          {
            path: 'call-reasons/new',
            loadComponent: () =>
              import('./features/admin/call-reasons/call-reason-form/call-reason-form').then(
                (m) => m.CallReasonForm,
              ),
          },
          {
            path: 'call-reasons/:id/edit',
            loadComponent: () =>
              import('./features/admin/call-reasons/call-reason-form/call-reason-form').then(
                (m) => m.CallReasonForm,
              ),
          },
          {
            path: 'activity',
            loadComponent: () =>
              import('./features/admin/activity/activity').then((m) => m.ActivityFeed),
          },
          {
            path: 'access-log',
            loadComponent: () =>
              import('./features/admin/access-log/access-log').then((m) => m.AccessLog),
          },
          {
            path: 'parameters',
            loadComponent: () =>
              import('./features/admin/parameters/parameters').then((m) => m.Parameters),
          },
          {
            path: 'parameters/new',
            loadComponent: () =>
              import('./features/admin/parameters/parameter-form/parameter-form').then(
                (m) => m.ParameterForm,
              ),
          },
          {
            path: 'parameters/:id/edit',
            loadComponent: () =>
              import('./features/admin/parameters/parameter-form/parameter-form').then(
                (m) => m.ParameterForm,
              ),
          },
          {
            path: 'menu-items',
            loadComponent: () =>
              import('./features/admin/menu-items/menu-items').then((m) => m.MenuItems),
          },
          {
            path: 'menu-items/new',
            loadComponent: () =>
              import('./features/admin/menu-items/menu-item-form/menu-item-form').then(
                (m) => m.MenuItemForm,
              ),
          },
          {
            path: 'menu-items/:id/edit',
            loadComponent: () =>
              import('./features/admin/menu-items/menu-item-form/menu-item-form').then(
                (m) => m.MenuItemForm,
              ),
          },
        ],
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
