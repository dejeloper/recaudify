<!-- context7 -->

Use the `ctx7` CLI to fetch current documentation whenever the user asks about a library, framework, SDK, API, CLI tool, or cloud service -- even well-known ones like React, Next.js, Prisma, Express, Tailwind, Django, or Spring Boot. This includes API syntax, configuration, version migration, library-specific debugging, setup instructions, and CLI tool usage. Use even when you think you know the answer -- your training data may not reflect recent changes. Prefer this over web search for library docs.

Do not use for: refactoring, writing scripts from scratch, debugging business logic, code review, or general programming concepts.

## Steps

1. Resolve library: `npx ctx7@latest library <name> "<user's question>"` — use the official library name with proper punctuation (e.g., "Next.js" not "nextjs", "Customer.io" not "customerio", "Three.js" not "threejs")
2. Pick the best match (ID format: `/org/project`) by: exact name match, description relevance, code snippet count, source reputation (High/Medium preferred), and benchmark score (higher is better). If results don't look right, try alternate names or queries (e.g., "next.js" not "nextjs", or rephrase the question)
3. Fetch docs: `npx ctx7@latest docs <libraryId> "<user's question>"`
4. Answer using the fetched documentation

You MUST call `library` first to get a valid ID unless the user provides one directly in `/org/project` format. Use the user's full question as the query -- specific and detailed queries return better results than vague single words. Do not run more than 3 commands per question. Do not include sensitive information (API keys, passwords, credentials) in queries.

For version-specific docs, use `/org/project/version` from the `library` output (e.g., `/vercel/next.js/v14.3.0`).

If a command fails with a quota error, inform the user and suggest `npx ctx7@latest login` or setting `CONTEXT7_API_KEY` env var for higher limits. Do not silently fall back to training data.

<!-- context7 -->

---

## General

- **Never make git commits** unless the user explicitly asks with words like "commit", "hacer commit", or "guarda los cambios en git".
- Be concise and direct. Do not ask unnecessary questions or over-explain. Provide short answers unless the user asks for detail.

---

## Laravel conventions

- Controllers are thin: validate via Form Request, delegate to a Service or Action class, return an API Resource.
- Never put database queries directly in controllers — use repositories or service classes.
- Each Service/Action class should do one thing. Name it after what it does: `CreateUserService`, `SendPaymentReminderAction`.
- Form Requests handle all validation — no `$request->validate()` inline in controllers.
- `env()` is forbidden outside `config/` files — always use `config()`.
- All business models use `SoftDeletes`.
- Use API Resources for all responses — never raw `response()->json()` with manual field mapping.

---

## Angular conventions

**Package manager:** always **pnpm**. Never use `npm` or `yarn`.

**Change detection:** Zoneless (`provideZonelessChangeDetection()`) + `ChangeDetectionStrategy.OnPush` on every component. No `zone.js`.

**State:** Angular Signals. Use `signal()` for local state, `computed()` for derived state.

**Dependency injection:** `inject()` only — no constructor injection.

**Component fields:** `private readonly` for injected services, `protected` for template-bound state.

**Subscriptions:** always use `takeUntilDestroyed(this.destroyRef)` in components.

**Routing:** all routes use `loadComponent` (lazy). `withComponentInputBinding()` enabled.

---

## Coding Standards

### Interfaces & Types (Frontend)

- Always define a TypeScript interface or type for every data model, API response, and form value. Never use `any` or anonymous object types for domain data.
- Place interfaces in a dedicated `*.model.ts` or `*.types.ts` file alongside the feature, or in `core/models/` when shared across features.
- Prefer `interface` over `type` for object shapes; use `type` for unions and aliases.

### Components (Angular)

- Each component must have a **single, clear responsibility**. If a template grows beyond ~80 lines or a component handles more than one concern, split it.
- Extract repeated UI into shared components under `core/components/` or a feature-level `components/` folder.
- Never put business logic inside components — delegate to services.
- Every component must declare `ChangeDetectionStrategy.OnPush` and use signals for state.

### Services & Business Logic (Angular)

- Business logic lives in services, not components or templates.
- Services are `providedIn: 'root'` unless scoped to a feature module.
- Keep service methods focused: one method, one action.

### Controllers & Classes (Laravel)

- Avoid deep nesting: extract early-return guards, extract helper methods.
- Name things clearly — a name that needs a comment is a name that needs changing.
- No dead code: remove unused imports, variables, methods, and commented-out blocks.

---

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
vendor/bin/pint                          # Fix code style (Laravel Pint)
php artisan l5-swagger:generate          # Regenerate Swagger docs
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

## Backend Architecture

**Auth:** JWT via `php-open-source-saver/jwt-auth`. TTL 15 min, refresh 4h, HS256. Guard name is `api`. All routes except `POST /api/auth/login` and `POST /api/auth/register` require `auth:api` middleware.

**Authorization:** Spatie Laravel Permission. Roles and permissions are seeded. Controllers use `->middleware('permission:scope.action')` per route and `->middleware('role:administrador')` per group.

**Conventions:**

- Form Requests in `App\Http\Requests\{Module}\` for all validation.
- JWT custom claims include the user's primary role (`getJWTCustomClaims()`).
- Controllers extend `App\Http\Controllers\Api\ApiController` (holds OpenAPI metadata).
- All endpoints documented with `#[OA\...]` PHP attributes (swagger-php).

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
| `JWT_REFRESH_TTL`       | `240` (minutes)         |
| `L5_SWAGGER_CONST_HOST` | falls back to `APP_URL` |

Angular API URL is set in `src/environments/environment.ts` → `apiUrl`.
