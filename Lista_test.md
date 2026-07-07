# Cobertura de Tests — Recaudify

> Dashboard único de cobertura. Fuente de verdad: los archivos de test (`*Test.php`, `*.spec.ts`).
> Este documento es una vista resumida; los detalles de cada caso están en los propios tests.
>
> **Objetivo:** 85 %+ de cobertura en backend (Laravel) y frontend (Angular).

_Actualizado: 2026-07-06._

---

## Estado general

| Capa     | Tests                   | Resultado | Cobertura medida                         |
| -------- | ----------------------- | --------- | ----------------------------------------- |
| Backend  | **150** (358 assertions)| ✅ Verde  | Bloqueada (sin PCOV/Xdebug en Herd Lite) |
| Frontend | **53 spec / 303 tests** | ✅ Verde  | Stmts 65.3 % / Branch 70.8 % / Func 69.4 % |

---

## Backend — `recaudify-api`

### Controladores (10 endpoints)

| Controller | Archivo test | Tests | Estado | Notas |
|---|---|---|---|---|
| `AuthController` | `tests/Feature/Auth/LoginTest.php` + `RegisterTest.php` | 16 | ✅ | login, register, me, refresh, logout, config, loginLocation |
| `UserController` | `tests/Feature/User/UserTest.php` | 13 | ✅ | CRUD + reset-password + sync-permissions |
| `RoleController` | `tests/Feature/Role/RoleTest.php` | 7 | ✅ | CRUD + trashed + restore |
| `PermissionController` | `tests/Feature/Permission/PermissionTest.php` | 7 | ✅ | CRUD + trashed + restore |
| `ParameterController` | `tests/Feature/Parameter/ParameterTest.php` | 12 | ✅ | CRUD + show + trashed + restore |
| `UserScheduleController` | `tests/Feature/Schedule/UserScheduleTest.php` | 6 | ✅ | CRUD + conflicto día |
| `ActivityController` | `tests/Feature/Activity/ActivityTest.php` | 4 | ✅ | auth, shape (`ActivityResource`), filtro `user`, paginación |
| `LoginAuditController` | `tests/Feature/LoginAudit/LoginAuditTest.php` | 5 | ✅ | auth, shape (`LoginAuditResource`), filtros `status`/`user_id`, paginación |

### Servicios (10 servicios)

| Service | Archivo test | Tests | Estado |
|---|---|---|---|
| `AuthService` | `tests/Unit/Services/AuthServiceTest.php` | 7 | ✅ |
| `UserService` | `tests/Unit/Services/UserServiceTest.php` | 6 | ✅ |
| `RoleService` | `tests/Unit/Services/RoleServiceTest.php` | 5 | ✅ |
| `PermissionService` | `tests/Unit/Services/PermissionServiceTest.php` | 4 | ✅ |
| `ParameterService` | `tests/Unit/Services/ParameterServiceTest.php` | 6 | ✅ |
| `UserScheduleService` | `tests/Unit/Services/UserScheduleServiceTest.php` | 3 | ✅ |
| `LoginAuditService` | `tests/Unit/Services/LoginAuditServiceTest.php` | 5 | ✅ |
| `PasswordResetService` | `tests/Unit/Services/PasswordResetServiceTest.php` | 5 | ✅ | fixed, random, fallback a random (vacío/mode ausente), logging vía `LoggingService::logSecurity` |
| `ActivityService` | `tests/Unit/Services/ActivityServiceTest.php` | 7 | ✅ | filtros por causer/username, `"sistema"`, `log_name`, `model`, subject-label |
| `LoggingService` | — | — | ⬜ | Sin lógica propia (wrapper) |

### Recursos, Middleware, Utilidades

| Componente | Archivo test | Tests | Estado |
|---|---|---|---|
| `ApiResult` (DTO) | `tests/Unit/ApiResultTest.php` | 8 | ✅ |
| `SetJwtFromCookie` (middleware) | `tests/Unit/Middleware/SetJwtFromCookieTest.php` | 3 | ✅ |
| `CheckUserSchedule` (middleware) | `tests/Feature/Middleware/CheckUserScheduleTest.php` | 4 | ✅ |
| Recursos API (User/Role/Permission/Parameter/Schedule) | `tests/Unit/Resources/ResourceTest.php` | 3 | ✅ |
| Modelos (relaciones, casts, traits) | `tests/Unit/Models/ModelActivityTest.php` | 13 | ✅ |

### Matriz de casos (integración)

| ID | Caso | Tipo | Estado |
|---|---|---|---|
| B-A01 | Login exitoso → 200 + token | Integration | ✅ |
| B-A02 | Login contraseña incorrecta → 401 | Integration | ✅ |
| B-A03 | Login usuario inexistente → 401 | Integration | ✅ |
| B-A04 | Login usuario inactivo → 403 | Integration | ✅ |
| B-A05 | Login fuera de horario → 403 | Integration | ✅ |
| B-A06 | `me()` devuelve flags de parámetros | Integration | ✅ |
| B-A10 | Token expirado en ruta protegida → 401 | Integration | ✅ |
| B-A11 | Sin token en ruta protegida → 401 | Integration | ✅ |
| B-A12 | Refresh de token → nuevo token válido | Integration | ✅ |
| B-A13 | Logout invalida el token | Integration | ✅ |
| B-U01 | Listar usuarios activos | Integration | ✅ |
| B-U04 | Crear usuario válido → 201 | Integration | ✅ |
| B-U05 | Crear usuario con username duplicado → 422 | Integration | ✅ |
| B-U10 | Soft delete usuario → 200 | Integration | ✅ |
| B-U11 | Restaurar usuario eliminado → 200 | Integration | ✅ |
| B-U12 | Sin permiso `users.view` → 403 | Integration | ✅ |
| B-U13 | Reset password genera aleatoria por defecto | Integration | ✅ |
| B-U14 | Reset password usa valor fijo configurado | Integration | ✅ |
| B-U15 | Reset password 404 si usuario no existe | Integration | ✅ |
| B-R02 | Crear rol con permisos → 201 | Integration | ✅ |
| B-R03 | Crear rol con nombre duplicado → 422 | Integration | ✅ |
| B-P02 | Crear permiso formato `modulo.accion` → 201 | Integration | ✅ |
| B-P04 | Crear permiso con formato inválido → 422 | Integration | ✅ |
| B-PM04 | Mostrar parámetro por id | Integration | ✅ |
| B-PM05 | Mostrar parámetro inexistente → 404 | Integration | ✅ |
| B-PM06 | Listar parámetros eliminados (trashed) | Integration | ✅ |
| B-PM07 | `ParameterService::get()` valor cacheado | Unit | ✅ |
| B-PM08 | `ParameterService::get()` default si no existe | Unit | ✅ |
| B-PM09 | Cache se invalida al crear/editar/eliminar | Unit | ✅ |
| B-PM10 | Restaurar parámetro eliminado | Integration | ✅ |
| B-PM11 | Restaurar parámetro no eliminado → 404 | Integration | ✅ |
| B-S02 | Crear horario en día libre → 201 | Integration | ✅ |
| B-S03 | Crear horario en día ocupado → 409 | Integration | ✅ |
| B-S06 | `CheckUserSchedule` bloquea fuera de horario | Unit | ✅ |
| B-S08 | `CheckUserSchedule` no aplica a superadmin | Unit | ✅ |
| B-AC01 | `ActivityController` requiere auth → 401 | Integration | ✅ |
| B-AC02 | `ActivityController` shape de respuesta paginada | Integration | ✅ |
| B-AC03 | `ActivityController` filtro por `user` | Integration | ✅ |
| B-LA01 | `LoginAuditController` requiere auth → 401 | Integration | ✅ |
| B-LA02 | `LoginAuditController` filtros `status`/`user_id` | Integration | ✅ |

---

## Frontend — `recaudify-web`

### Servicios (18 archivos, 18 con spec)

| Service | Spec | Estado |
|---|---|---|
| `ApiService` | ✅ `api.service.spec.ts` | ✅ |
| `AuthService` | ✅ `auth.service.spec.ts` | ✅ |
| `ToastService` | ✅ `toast.service.spec.ts` | ✅ |
| `UsersService` | ✅ `users.service.spec.ts` | ✅ (incluye `resetPassword()`) |
| `RolesService` | ✅ `roles.service.spec.ts` | ✅ |
| `ParametersService` | ✅ `parameters.service.spec.ts` | ✅ |
| `SchedulesService` | ✅ `schedules.service.spec.ts` | ✅ |
| `ShiftStatusService` | ✅ `shift-status.service.spec.ts` | ✅ |
| `ActivitiesService` | ✅ `activities.service.spec.ts` | ✅ |
| `ProductsService` | ✅ `products.service.spec.ts` | ✅ |
| `GeolocationService` | ✅ `geolocation.service.spec.ts` | ✅ |
| `AuditService` | ✅ `audit.service.spec.ts` | ✅ |
| `PermissionsService` | ✅ `permissions.service.spec.ts` | ✅ |
| `ConfigService` | ✅ `config.service.spec.ts` | ✅ |
| `CallReasonsService` | ✅ `call-reasons.service.spec.ts` | ✅ |
| `RatesService` | ✅ `rates.service.spec.ts` | ✅ |
| `SellersService` | ✅ `sellers.service.spec.ts` | ✅ |
| `LoginAuditsService` | ✅ `login-audits.service.spec.ts` | ✅ |

### Guards, Interceptors, Directivas

| Archivo | Spec | Estado |
|---|---|---|
| `auth.guard.ts` | ✅ `auth.guard.spec.ts` | ✅ |
| `admin.guard.ts` | ✅ `admin.guard.spec.ts` | ✅ |
| `permission.guard.ts` | ✅ `permission.guard.spec.ts` | ✅ |
| `auth.interceptor.ts` | ✅ `auth.interceptor.spec.ts` | ✅ |
| `error.interceptor.ts` | ✅ `error.interceptor.spec.ts` | ✅ |
| `btn.directive.ts` | ✅ `btn.directive.spec.ts` | ✅ (incluye variante `table-neutral`) |
| `table.directive.ts` | ✅ `table.directive.spec.ts` | ✅ |

### Componentes (25 features, 25 con spec)

| Componente | Spec | Estado |
|---|---|---|
| `Spinner` | ✅ `spinner.spec.ts` | ✅ |
| `Toast` | ✅ `toast.spec.ts` | ✅ |
| `App` (raíz) | ✅ `app.spec.ts` | ✅ |
| `Login` | ✅ `login.spec.ts` | ✅ |
| `AdminDashboard` | ✅ `admin-dashboard.spec.ts` | ✅ |
| `Products` | ✅ `products.spec.ts` | ✅ |
| `ProductForm` | ✅ `product-form.spec.ts` | ✅ |
| `ActivityFeed` | ✅ `activity.spec.ts` | ✅ |
| `AccessLog` | ✅ `access-log.spec.ts` | ✅ |
| `Schedules` | ✅ `schedules.spec.ts` | ✅ |
| `UserSchedules` | ✅ `user-schedules.spec.ts` | ✅ |
| `Users` | ✅ `users.spec.ts` | ✅ |
| `UserForm` | ✅ `user-form.spec.ts` | ✅ |
| `Roles` | ✅ `roles.spec.ts` | ✅ |
| `RoleForm` | ✅ `role-form.spec.ts` | ✅ |
| `Permissions` | ✅ `permissions.spec.ts` | ✅ |
| `PermissionForm` | ✅ `permission-form.spec.ts` | ✅ |
| `Parameters` | ✅ `parameters.spec.ts` | ✅ |
| `ParameterForm` | ✅ `parameter-form.spec.ts` | ✅ |
| `CallReasons` | ✅ `call-reasons.spec.ts` | ✅ |
| `CallReasonForm` | ✅ `call-reason-form.spec.ts` | ✅ |
| `Rates` | ✅ `rates.spec.ts` | ✅ |
| `RateForm` | ✅ `rate-form.spec.ts` | ✅ |
| `Sellers` | ✅ `sellers.spec.ts` | ✅ |
| `SellerForm` | ✅ `seller-form.spec.ts` | ✅ |
| `Dashboard` | ✅ `dashboard.spec.ts` | ✅ |
| `AppShell` | ✅ `app-shell.spec.ts` | ✅ |

### Utilidades

| Archivo | Spec | Estado |
|---|---|---|
| `text.ts` | ✅ `text.spec.ts` | ✅ |

### Matriz de casos (frontend)

| ID | Caso | Tipo | Estado |
|---|---|---|---|
| F-A05 | Geo requerida + permiso denegado → error y logout | Unit | ✅ |
| F-A06 | `geolocalization_login=false` no solicita geo | Unit | ✅ |
| F-A09 | `guestGuard` redirige a `/dashboard` si autenticado | Unit | ✅ |
| F-A10 | `authGuard` redirige a `/login` si no autenticado | Unit | ✅ |
| F-A11 | `adminGuard` redirige si rol no es admin | Unit | ✅ |
| F-A13 | Token expirado activa refresh automático | Unit | ✅ |
| F-D02 | Widget shift-status visible si habilitado | Unit | ✅ |
| F-D04 | Contador regresivo (`countdownMinutes`) | Unit | ✅ |
| F-S01 | Lista usuarios con botón gestionar horarios | Unit | ✅ |
| F-S04 | Crear horario envía payload correcto | Unit | ✅ |
| F-S05 | Editar horario actualiza la entrada | Unit | ✅ |
| F-S06 | Eliminar horario (con/sin confirmación) | Unit | ✅ |
| F-SV01 | Toast success con auto-dismiss | Unit | ✅ |
| F-SV03 | Toast con `duration=0` no se cierra solo | Unit | ✅ |
| F-SV04 | `ApiService` construye URL `{apiUrl}/{controller}/{action}` | Unit | ✅ |
| F-SV05 | `ApiService` rechaza claves `__proto__` | Unit | ✅ |
| F-SV06 | `UsersService.resetPassword()` llama POST | Unit | ✅ |
| F-D05 | `BtnDirective` aplica clase `btn-table-neutral` | Unit | ✅ |
| F-U01 | `Users`: reset password (confirmar/cancelar) + toast | Unit | ✅ |
| F-U02 | `Users`: computeds `canCreate`/`canEdit`/`canDelete`/`canRestore` según permisos | Unit | ✅ |
| F-P01 | `Permissions`: `grouped`/`groupByModule`/`isValidName`/`actionLabel` | Unit | ✅ |
| F-PM01 | `Parameters`: `filterByType` y `availableTypes` | Unit | ✅ |
| F-A01 | Login exitoso redirige a `/dashboard` | E2E | 🔵 |
| F-U03 | Crear usuario muestra toast | E2E | 🔵 |
| F-R02 | Crear rol muestra toast | E2E | 🔵 |
| F-PM02 | Crear parámetro muestra toast | E2E | 🔵 |

---

## Gap Analysis

### P1 — Imprescindible

- [x] Arreglar build frontend: import de `ApiError` en `api.service.spec.ts`
- [x] Corregir mocks de `auth.guard.spec.ts` y `auth.service.spec.ts`
- [x] `AuthService.geolocalizationLoginEnabled` con default seguro (`?? true`)
- [x] Providers de `ActivatedRoute`/`Router` en `activity.spec.ts`
- [x] `PasswordResetServiceTest.php` (5 casos)
- [x] Test de `resetPassword()` en `users.service.spec.ts`
- [x] Variante `table-neutral` en `btn.directive.spec.ts`

### P2 — Cobertura de servicios y componentes principales

- [x] `audit.service.spec.ts`
- [x] `permissions.service.spec.ts`
- [x] Component specs: `Users`, `Roles`, `Permissions`, `Parameters`

### P3 — Cobertura secundaria

- [x] Tests de integración para `LoginAuditController`
- [x] Specs para `ConfigService`, `CallReasonsService`, `RatesService`, `SellersService`, `LoginAuditsService`
- [x] Component specs para `UserForm`, `RoleForm`, `PermissionForm`, `ParameterForm`, `CallReasons`(+form), `Rates`(+form), `Sellers`(+form)

### P4 — Pendiente

- [x] Borrar `tests/Unit/ExampleTest.php` (boilerplate)
- [x] `ActivityServiceTest` y tests de `ActivityController` (el servicio sí tiene lógica real, contrario a lo que decía esta lista)
- [x] Component specs para `Dashboard` (`features/dashboard/dashboard.ts`) y `AppShell` (`layout/app-shell/app-shell.ts`)
- [ ] Instalar PCOV/Xdebug en PHP de Herd Lite para desbloquear medición de cobertura backend

---

## Credenciales de prueba (seeder)

| Usuario | Contraseña | Rol |
|---|---|---|
| `superadmin` | `superadmin1234` | superadmin |
| `admin` | `admin1234` | administrador |
| `coordinador` | `admin1234` | coordinador |
| `auxiliar` | `admin1234` | auxiliar |

> Con `geolocalization_login=true`, el login del navegador requiere permiso de ubicación.

---

## Comandos de referencia

### Backend

```bash
php artisan test                     # Todos los tests
php artisan test --filter=LoginTest  # Test específico
vendor/bin/pint                      # Code style (Laravel Pint)
php artisan l5-swagger:generate      # Regenerar Swagger
```

### Frontend

```bash
pnpm test     # Unit tests (Vitest)
pnpm start    # Dev server :4200
pnpm build    # Production build
pnpm prettier --write .  # Formatear código
```
