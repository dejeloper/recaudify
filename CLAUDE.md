# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Recaudify is a SaaS for debt collection and payment management (cobranza). It is a rewrite of a legacy CodeIgniter 3 app. The monorepo contains two subprojects:

- `recaudify-api/` — Laravel 13, PHP 8.3+, REST API
- `recaudify-web/` — Angular 21, SPA

---

## Commands

### Backend (`recaudify-api/`)

```bash
php artisan serve                        # Start dev server on :8000
php artisan migrate --seed               # Run migrations + seeders
php artisan migrate:fresh --seed         # Reset DB and reseed
php artisan test                         # Run PHPUnit tests
php artisan test --filter=TestName       # Run a single test
composer run dev                         # Start server + queue + logs + vite concurrently
vendor/bin/pint                          # Fix code style (Laravel Pint)
php artisan l5-swagger:generate          # Regenerate Swagger docs
```

### Frontend (`recaudify-web/`)

Package manager: **pnpm** (configured in `angular.json` and `package.json`). Never use `npm` or `yarn` here.

```bash
pnpm start          # Dev server on :4200
pnpm build          # Production build
pnpm test           # Run unit tests (Vitest)
pnpm add <pkg>      # Install a dependency
```

---

## Backend Architecture

**Auth:** JWT via `php-open-source-saver/jwt-auth`. TTL 15 min, refresh 4h, HS256. Guard name is `api`. All routes except `POST /api/auth/login` and `POST /api/auth/register` require `auth:api` middleware.

**Authorization:** Spatie Laravel Permission. Roles and permissions are seeded. Controllers use `->middleware('permission:scope.action')` per route and `->middleware('role:administrador')` per group.

**API responses:**

- Use API Resources (`App\Http\Resources\`) for user data — never raw `response()->json()` with manual field mapping.
- Controllers extend `App\Http\Controllers\Api\ApiController` (holds OpenAPI metadata).
- All endpoints documented with `#[OA\...]` PHP attributes (swagger-php).

**Conventions:**

- Form Requests in `App\Http\Requests\{Module}\` for all validation.
- All business models use `SoftDeletes`.
- `env()` is forbidden outside `config/` files — always use `config()`.
- JWT custom claims include the user's primary role (`getJWTCustomClaims()`).

---

## Frontend Architecture

**Change detection:** Zoneless (`provideZonelessChangeDetection()`) + `ChangeDetectionStrategy.OnPush` on every component. No `zone.js` dependency.

**State:** Angular Signals. Use `signal()` for local state, `computed()` for derived state. `AuthService` exposes `isAuthenticated` as a `computed` signal and `currentUser` as a `signal<User | null>`.

**HTTP:** `ApiService` (`core/services/api.service.ts`) is the single HTTP abstraction. It builds URLs as `{apiUrl}/{controller}/{action}`, sets security headers, sanitizes body keys against prototype pollution. Never use `HttpClient` directly.

**Auth flow:**

1. `authInterceptor` attaches `Authorization: Bearer <token>` from `AuthService.token`.
2. `authGuard` / `guestGuard` use `auth.isAuthenticated()` (signal call).
3. Token stored in `localStorage` under key `auth_token` and mirrored in `_token` signal.

**Routing:** All routes use `loadComponent` (lazy). `withComponentInputBinding()` enabled.

**Shared components** (`core/components/`):

- `Spinner` (`core/components/spinner/spinner.ts`) — selector `app-spinner`. Inputs: `show` (required `boolean`), `label` (optional `string`). Renders a centered SVG spinner while `show` is `true`; nothing when `false`. Import and use in any page that needs a loading state.

```html
<app-spinner [show]="loading()" /> <app-spinner [show]="loading()" label="Cargando usuarios..." />
```

- `ToastContainer` (`core/components/toast/toast.ts`) — selector `app-toast`. Mounted once in `app.html`. Never import directly in pages. Use `ToastService` (`core/services/toast.service.ts`) to trigger toasts from anywhere. Types: `success`, `error`, `warning`, `info`. Default duration: 5 s. Toasts auto-dismiss and can be closed manually.

```typescript
private readonly toast = inject(ToastService);

this.toast.success('Usuario creado correctamente.');
this.toast.error('No se pudo guardar. Intente nuevamente.');
this.toast.warning('El rol no tiene permisos asignados.');
this.toast.info('Recuerda guardar los cambios.');

// custom duration (ms), 0 = never auto-dismiss
this.toast.success('Importación completada.', 8000);
```

**Conventions:**

- `inject()` only — no constructor injection.
- Component fields: `private readonly` for injected services, `protected` for template-bound state.
- Subscriptions in components must use `takeUntilDestroyed(this.destroyRef)`.
- `User` interface has `email: string | null` (nullable from API).

---

## Environment

| Variable                | Default                 |
| ----------------------- | ----------------------- |
| `APP_URL`               | `http://localhost:8000` |
| `FRONTEND_URL`          | `http://localhost:4200` |
| `DB_DATABASE`           | `recaudify`             |
| `JWT_TTL`               | `15` (minutes)          |
| `JWT_REFRESH_TTL`       | `240` (minutes)         |
| `L5_SWAGGER_CONST_HOST` | falls back to `APP_URL` |

Angular API URL is set in `src/environments/environment.ts` → `apiUrl`.
