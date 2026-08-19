# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
General Angular and Laravel conventions are defined in the global `~/.claude/CLAUDE.md`.

## General

- **Never make git commits** unless the user explicitly asks with words like "commit", "hacer commit", or "guarda los cambios en git".
- Be concise and direct. Do not ask unnecessary questions or over-explain. Provide short answers unless the user asks for detail.

## Contexto del proyecto

Antes de empezar cualquier tarea, revisa los siguientes archivos en la raíz del repo si son relevantes a la tarea:

- `funcionalidades.md` — plan de trabajo oficial de la reescritura legacy → Recaudify (funcionalidades a implementar).
- `Lista_test.md` — listado de pruebas/casos de test pendientes o realizados.
- `plan-ejecucion.md` — plan de ejecución del proyecto.
- `planning.md` — planificación general del proyecto.
- `vps_deploy_guide.md` — guía de despliegue en el VPS.
- `vps_plan.md` — plan de infraestructura/VPS.

Úsalos como contexto para entender el estado y las decisiones del proyecto antes de proponer cambios.

### Documentos de contexto (`docs/contexto/`)

Consulta estos documentos según lo que necesite la tarea — están basados solo en lo que existe en el repo, con huecos marcados `[PENDIENTE: ...]` donde no había evidencia suficiente:

- `docs/contexto/arquitectura.md` — stack, mapa de carpetas, flujo de datos, qué NO existe todavía.
- `docs/contexto/convenciones.md` — estilo, naming, patrones permitidos/prohibidos, tests, commits.
- `docs/contexto/decisiones.md` — decisiones técnicas detectadas en código/commits, con su porqué y lo descartado.
- `docs/contexto/glosario.md` — términos del dominio, entidades principales, siglas internas.
- `docs/contexto/flujo-de-trabajo.md` — pasos para hacer un cambio, checklist de "terminado", deploy.
- `docs/contexto/errores-conocidos.md` — gotchas del código/tests que ya han costado tiempo.

Estos documentos **no se actualizan automáticamente** (ni con `/update-plan` ni con ningún hook). Si el código cambia de forma que los vuelve obsoletos, actualízalos manualmente cuando se te pida.

## Project

Recaudify is a SaaS for debt collection and payment management (cobranza). It is a rewrite of a legacy CodeIgniter 3 app. The monorepo contains two subprojects:

- `recaudify-api/` — Laravel 13, PHP 8.3+, REST API
- `recaudify-web/` — Angular 21, SPA

## Commands

### Backend (`recaudify-api/`)

```bash
php artisan serve                        # Start dev server on :8000
php artisan migrate --seed               # Run migrations + seeders
php artisan migrate:fresh --seed         # Reset DB and reseed
php artisan test                         # Run PHPUnit tests
php artisan test --filter=TestName       # Run a single test
composer run dev                         # Start server + queue + logs + vite concurrently
php artisan l5-swagger:generate          # Regenerate Swagger docs
```

**Code style — do NOT run `vendor/bin/pint`.** PHP in this repo is formatted with Prettier
(`@prettier/plugin-php`), not with Pint. Pint is installed as a Laravel dependency but its default
preset disagrees with the committed style (single vs. double quotes, among others): running it
reformats ~120 files and buries any real change in noise. Format only the files you touched:

```bash
npx prettier --write <paths...>   # Format specific files (preferred)
pnpm format:php                   # Format all PHP (slow: several minutes)
pnpm check:php                    # Check without writing
```

### Frontend (`recaudify-web/`)

```bash
pnpm start          # Dev server on :4200
pnpm build          # Production build
pnpm test           # Run unit tests (Vitest)
pnpm add <pkg>      # Install a dependency
```

### Pre-commit

```bash
pnpm prettier --write .
```

Formats both subprojects (PHP included). This — not Pint — is the source of truth for PHP style.

## Backend Architecture

**Auth:** JWT via `php-open-source-saver/jwt-auth`. TTL 15 min, refresh 4h, HS256. Guard name is `api`. All routes except `POST /api/auth/login` and `POST /api/auth/register` require `auth:api` middleware.

**Authorization:** Spatie Laravel Permission. Roles and permissions are seeded. Controllers use `->middleware('permission:scope.action')` per route and `->middleware('role:administrador')` per group.

**Conventions:**

- Form Requests in `App\Http\Requests\{Module}\` for all validation.
- **Money:** every monetary amount is a whole-peso integer (COP). No cents. Columns are
  `bigInteger`, models cast to `integer` — never `decimal`, never `float`. Rounding, parsing of
  user/CSV input, and splitting an amount across installments go through `App\Support\Money`.
- **Transactions:** any Service method that writes to more than one table must be wrapped in
  `DB::transaction()`. The transaction belongs in the **Service** — never in the Controller or the
  Repository — because the Service is what knows the unit of business ("create a client with its
  addresses" is one fact, even if it's four inserts). Queue jobs dispatch `after_commit` (already
  enabled in `config/queue.php`) so they never run against data that isn't committed yet.
- JWT custom claims include the user's primary role (`getJWTCustomClaims()`).
- Controllers extend `App\Http\Controllers\Api\ApiController`.
- OpenAPI lives in **dedicated doc classes** under `app/OpenApi/{Module}/{Module}Docs.php`, never as
  attributes on the controllers themselves. Each endpoint is an empty method carrying its `#[OA\...]`
  attribute. When you add an endpoint, add its method to the matching `*Docs.php` class.

## Frontend Architecture

**HTTP:** `ApiService` (`core/services/api.service.ts`) is the single HTTP abstraction. It builds URLs as `{apiUrl}/{controller}/{action}`, sets security headers, sanitizes body keys against prototype pollution. Never use `HttpClient` directly.

**Auth flow:**

1. `authInterceptor` attaches `Authorization: Bearer <token>` from `AuthService.token`.
2. `authGuard` / `guestGuard` use `auth.isAuthenticated()` (signal call).
3. Token stored in `localStorage` under key `auth_token` and mirrored in `_token` signal.

**Shared components** (`core/components/`):

- `Spinner` — selector `app-spinner`. Inputs: `show` (required `boolean`), `label` (optional `string`).
- `ToastContainer` — selector `app-toast`. Use `ToastService` to trigger toasts from anywhere. Types: `success`, `error`, `warning`, `info`. Default duration: 5 s.

**Conventions:**

- `User` interface has `email: string | null` (nullable from API).

## Environment

| Variable                | Default                 |
| ----------------------- | ----------------------- |
| `APP_URL`               | `http://localhost:8000` |
| `FRONTEND_URL`          | `http://localhost:4200` |
| `DB_DATABASE`           | `recaudify`             |
| `JWT_TTL`               | `15` (minutes)          |
| `JWT_REFRESH_TTL`       | `120` (minutes)         |
| `L5_SWAGGER_CONST_HOST` | falls back to `APP_URL` |

Angular API URL is set in `src/environments/environment.ts` → `apiUrl`.
