# Plan de Testing — Recaudify

> **Documento único de testing.** Consolida lo que antes estaba repartido en `Lista_test.md`,
> `TEST-CASES.md` y `TESTING.md` (estos dos últimos eliminados). Contiene: estado de
> implementación, checklist detallado por archivo, matriz de casos con IDs, reporte de QA manual,
> credenciales y referencia de parámetros del sistema.
>
> **Objetivo:** 85 %+ de cobertura en backend (Laravel) y frontend (Angular).
> Cada enunciado describe **qué debe probar una prueba unitaria o de integración**.
> No se incluye el código de las pruebas, solo la especificación.

---

## Estado de implementación

_Actualizado: 2026-06-26._

**Backend** — `php artisan test` → **144 tests verdes**. Cómo correrlos: ver `README.md`.

- Feature/HTTP: Auth, Usuarios, Roles, Permisos, Parámetros, Horarios, Catálogos (5), Auditoría,
  Accesos, Middleware `CheckUserSchedule`.
- Unit: Servicios (Auth, User, Role, Permission, Parameter, UserSchedule, Activity, LoginAudit),
  Resources, Middleware `SetJwtFromCookie`, `ApiResult`.
- Helper: `tests/TestCase.php → authenticateWith([...permisos])`.

**Frontend** — `pnpm test` (Vitest) → **139 tests verdes (30 archivos)** _(infra arreglada: faltaba `tsconfig.spec.json`)_.
Cobertura habilitada en el builder (`coverage: true` en `angular.json`): **Stmts 67.7 % · Branch 71.0 % · Funcs 70.1 % · Lines 68.2 %**.

- Servicios: `ApiService` (sanitize/params/error/paginación), `AuthService`, `ToastService`,
  `ProductsService`, `UsersService`, `RolesService`, `ParametersService` (incl. `getFlag`),
  `SchedulesService` (`formatTime`/`getForDay`/entradas), `ActivitiesService` (patrón paginado),
  `GeolocationService` (APIs del navegador), `ShiftStatusService` (`visibleShift`/`countdownMinutes`).
- Guards: `authGuard`/`guestGuard`, `adminGuard`, `permissionGuard`.
- Interceptors: `errorInterceptor` (401→refresh, 403 horario, mapeo de error), `authInterceptor` (no-op).
- Directivas: `BtnDirective`, `TableDirective` (clase reactiva por variante).
- Componentes: `Spinner`, `ToastContainer`, raíz `App`, `Login`, `AdminDashboard`, `Products`,
  `ProductForm`, `ActivityFeed`, `AccessLog`, `Schedules` (listado), `UserSchedules` (gestión). Utilidades (`text`).

**Specs actualizados** (estaban desfasados del código): `auth.service.spec`, `error.interceptor.spec`,
`auth.guard.spec`. **Genérico a borrar:** `tests/Unit/ExampleTest.php` (backend, `assertTrue(true)`).

**Pendiente frontend (mecánico, mismo patrón ya cubierto por un representante):** services
`rates`/`sellers`/`call-reasons` (= `ProductsService`), `permissions`/`login-audits`/`config`.
Lo no cubierto en cobertura son ramas de plantillas HTML (estados de carga/branches de UI), no lógica.

**Cobertura backend:** bloqueada — el PHP de Herd Lite no trae driver (Xdebug/PCOV). Instrucciones de
instalación de PCOV ya entregadas; tras instalarlo: `php artisan test --coverage`.

---

## Backend — `recaudify-api/`

### Modelos (Unit: modelos y relaciones)

#### `app/Models/User.php`

- [ ] `fillable` contiene name, username, email, password, active
- [ ] `hidden` contiene password
- [ ] `casts` define password como `hashed` y active como `boolean`
- [ ] `getJWTIdentifier()` retorna el id del usuario
- [ ] `getJWTCustomClaims()` retorna array con role y aud
- [ ] `schedules()` retorna una relación HasMany con UserSchedule
- [ ] SoftDeletes está habilitado (usa `Illuminate\Database\Eloquent\SoftDeletes`)
- [ ] El guard name por defecto es "api"

#### `app/Models/Role.php`

- [ ] Usa SoftDeletes
- [ ] El guard name por defecto es "api"
- [ ] Hereda de Spatie\Permission\Models\Role

#### `app/Models/Permission.php`

- [ ] Usa SoftDeletes
- [ ] El guard name por defecto es "api"
- [ ] Hereda de Spatie\Permission\Models\Permission

#### `app/Models/Parameter.php`

- [ ] `fillable` contiene key, value, description
- [ ] Usa SoftDeletes

#### `app/Models/UserSchedule.php`

- [ ] `fillable` contiene user_id, day_of_week, start_time, end_time, show_status
- [ ] `casts` define day_of_week como integer, show_status como boolean
- [ ] `user()` retorna una relación BelongsTo con User

---

### Servicios (Unit: lógica de negocio)

#### `app/Services/AuthService.php`

- [ ] `getScheduleAccessError()` retorna null si el usuario es superadmin
- [ ] `getScheduleAccessError()` retorna mensaje si el usuario no tiene horarios asignados
- [ ] `getScheduleAccessError()` retorna null si la hora actual está dentro del horario
- [ ] `getScheduleAccessError()` retorna mensaje si la hora actual está fuera del horario
- [ ] `getCurrentShift()` retorna is_within_schedule=true y show_status=true para superadmin
- [ ] `getCurrentShift()` retorna is_within_schedule=false si no hay schedule activo
- [ ] `getCurrentShift()` retorna el schedule activo con remaining_minutos correctos
- [ ] `getCurrentShift()` retorna show_status según el campo del schedule

#### `app/Services/UserService.php`

- [ ] `all()` retorna todos los usuarios con roles
- [ ] `allDisabled()` retorna solo usuarios eliminados (soft delete)
- [ ] `find()` retorna usuario con roles y permisos cargados
- [ ] `find()` retorna null si no existe
- [ ] `findTrashed()` retorna usuario eliminado con roles
- [ ] `search()` busca por name y username (like)
- [ ] `create()` crea usuario y asigna role si se provee
- [ ] `create()` no asigna role si no se provee
- [ ] `update()` actualiza datos filtrando password vacío
- [ ] `update()` sincroniza role si syncRole es true
- [ ] `update()` no sincroniza role si syncRole es false
- [ ] `delete()` aplica soft delete
- [ ] `restore()` restaura usuario
- [ ] `syncPermissions()` asigna permisos si assign=true
- [ ] `syncPermissions()` revoca permisos si assign=false
- [ ] `syncPermissions()` retorna lista de nombres de permisos

#### `app/Services/RoleService.php`

- [ ] `all()` retorna roles con permisos, filtrados por guard_name=api, ordenados por name
- [ ] `find()` retorna role con permisos
- [ ] `findTrashed()` retorna role eliminado
- [ ] `trashed()` retorna roles eliminados con permisos, filtrados por guard_name=api
- [ ] `create()` crea role con guard_name=api
- [ ] `create()` sincroniza permisos si se proveen
- [ ] `update()` actualiza name si no es null
- [ ] `update()` sincroniza permisos si no es null
- [ ] `delete()` remueve permisos y aplica soft delete
- [ ] `restore()` restaura role

#### `app/Services/PermissionService.php`

- [ ] `all()` retorna permisos filtrados por guard_name=api, ordenados por name
- [ ] `find()` retorna permiso por id
- [ ] `findTrashed()` retorna permiso eliminado
- [ ] `trashed()` retorna permisos eliminados
- [ ] `create()` crea permiso con guard_name=api
- [ ] `update()` actualiza name
- [ ] `delete()` aplica soft delete
- [ ] `restore()` restaura permiso

#### `app/Services/ParameterService.php`

- [ ] `get()` retorna el valor de una clave existente desde caché
- [ ] `get()` retorna el default si la clave no existe
- [ ] `all()` retorna todos los parámetros cacheados como colección clave-valor
- [ ] `clearCache()` invalida la caché
- [ ] `getAll()` retorna todos los parámetros como colección Eloquent
- [ ] `getTrashed()` retorna parámetros eliminados
- [ ] `find()` retorna parámetro por id
- [ ] `findTrashed()` retorna parámetro eliminado
- [ ] `create()` crea parámetro y limpia caché
- [ ] `update()` actualiza parámetro y limpia caché
- [ ] `delete()` elimina parámetro y limpia caché
- [ ] `restore()` restaura parámetro y limpia caché

#### `app/Services/UserScheduleService.php`

- [ ] `getForUser()` retorna schedules del usuario ordenados por day_of_week
- [ ] `isDuplicateDay()` retorna true si ya existe schedule para ese día
- [ ] `isDuplicateDay()` retorna false si no existe schedule para ese día
- [ ] `create()` crea un schedule asociado al usuario
- [ ] `update()` actualiza los datos del schedule
- [ ] `delete()` elimina el schedule

---

### Controladores (Feature: integración con HTTP)

#### `app/Http/Controllers/Api/AuthController.php`

- [ ] `register()` crea usuario y responde 201 con id, name, username
- [ ] `login()` retorna 200 con token y datos de usuario en éxito
- [ ] `login()` retorna 401 si las credenciales son incorrectas
- [ ] `login()` retorna 403 si el usuario está inactivo
- [ ] `login()` retorna 403 si el horario de acceso no permite el ingreso
- [ ] `login()` normaliza username a minúsculas
- [ ] `me()` retorna 200 con datos del usuario autenticado + current_shift + flags
- [ ] `me()` retorna 401 sin autenticación
- [ ] `config()` retorna 200 con geolocalization_login
- [ ] `refresh()` retorna 200 con nuevo token
- [ ] `refresh()` retorna 401 si el token no puede renovarse
- [ ] `logout()` retorna 200 y cierra sesión
- [ ] `logout()` elimina la cookie de token

#### `app/Http/Controllers/Api/UserController.php`

- [ ] `index()` retorna lista de usuarios
- [ ] `indexDisabled()` retorna lista de usuarios desactivados
- [ ] `show()` retorna usuario por id
- [ ] `show()` retorna 404 si no existe
- [ ] `showTrashed()` retorna usuario eliminado por id
- [ ] `showTrashed()` retorna 404 si no existe
- [ ] `search()` retorna usuarios filtrados por nombre
- [ ] `store()` crea usuario con role y retorna 201
- [ ] `update()` actualiza usuario y retorna 200
- [ ] `update()` retorna 404 si no existe
- [ ] `destroy()` desactiva usuario y retorna 200
- [ ] `destroy()` retorna 404 si no existe
- [ ] `restore()` restaura usuario y retorna 200
- [ ] `restore()` retorna 404 si no existe en trash
- [ ] `syncPermissions()` asigna permisos y retorna 200
- [ ] `syncPermissions()` revoca permisos y retorna 200
- [ ] `syncPermissions()` retorna 404 si usuario no existe

#### `app/Http/Controllers/Api/RoleController.php`

- [ ] `index()` retorna lista de roles
- [ ] `show()` retorna role por id
- [ ] `show()` retorna 404 si no existe
- [ ] `store()` crea role con permisos y retorna 201
- [ ] `store()` crea role sin permisos
- [ ] `update()` actualiza role y retorna 200
- [ ] `update()` retorna 404 si no existe
- [ ] `destroy()` elimina role y retorna 200
- [ ] `destroy()` retorna 404 si no existe
- [ ] `trashed()` retorna roles eliminados
- [ ] `restore()` restaura role y retorna 200
- [ ] `restore()` retorna 404 si no existe en trash

#### `app/Http/Controllers/Api/PermissionController.php`

- [ ] `index()` retorna lista de permisos
- [ ] `show()` retorna permiso por id
- [ ] `show()` retorna 404 si no existe
- [ ] `store()` crea permiso y retorna 201
- [ ] `update()` actualiza permiso y retorna 200
- [ ] `update()` retorna 404 si no existe
- [ ] `destroy()` elimina permiso y retorna 200
- [ ] `destroy()` retorna 404 si no existe
- [ ] `trashed()` retorna permisos eliminados
- [ ] `restore()` restaura permiso y retorna 200
- [ ] `restore()` retorna 404 si no existe en trash

#### `app/Http/Controllers/Api/ParameterController.php`

- [ ] `index()` retorna lista de parámetros
- [ ] `show()` retorna parámetro por id
- [ ] `show()` retorna 404 si no existe
- [ ] `store()` crea parámetro y retorna 201
- [ ] `update()` actualiza parámetro y retorna 200
- [ ] `update()` retorna 404 si no existe
- [ ] `destroy()` elimina parámetro y retorna 200
- [ ] `destroy()` retorna 404 si no existe
- [ ] `trashed()` retorna parámetros eliminados
- [ ] `restore()` restaura parámetro y retorna 200
- [ ] `restore()` retorna 404 si no existe en trash

#### `app/Http/Controllers/Api/UserScheduleController.php`

- [ ] `index()` retorna schedules de un usuario
- [ ] `index()` retorna 404 si el usuario no existe
- [ ] `store()` crea schedule y retorna 201
- [ ] `store()` retorna 404 si el usuario no existe
- [ ] `store()` retorna 409 si ya existe schedule para ese día
- [ ] `update()` actualiza schedule y retorna 200
- [ ] `update()` retorna 404 si el schedule no existe
- [ ] `destroy()` elimina schedule y retorna 200
- [ ] `destroy()` retorna 404 si el schedule no existe

---

### Form Requests (Unit: reglas de validación)

#### `app/Http/Requests/Auth/LoginRequest.php`

- [ ] `authorize()` retorna true
- [ ] `rules()` exige username (required, string)
- [ ] `rules()` exige password (required, string)
- [ ] `prepareForValidation()` normaliza username a minúsculas y trim

#### `app/Http/Requests/Auth/RegisterRequest.php`

- [ ] `rules()` exige name (required, string, max:100)
- [ ] `rules()` exige username (required, string, max:50, unique, regex)
- [ ] `rules()` permite email (nullable, email, max:150)
- [ ] `rules()` exige password (required, string, min:8, confirmed)
- [ ] `prepareForValidation()` normaliza username a minúsculas y trim

#### `app/Http/Requests/User/StoreUserRequest.php`

- [ ] `rules()` exige name (required, string, min:3, max:100)
- [ ] `rules()` exige username (required, min:3, max:50, unique, regex)
- [ ] `rules()` permite email nullable
- [ ] `rules()` exige password (required, min:8, confirmed)
- [ ] `rules()` permite role nullable con exists:roles,name
- [ ] `rules()` permite active boolean

#### `app/Http/Requests/User/UpdateUserRequest.php`

- [ ] `rules()` permite name (sometimes, min:3, max:100)
- [ ] `rules()` permite username (sometimes, min:3, max:50, unique ignorando propio id, regex)
- [ ] `rules()` permite email nullable
- [ ] `rules()` permite password (sometimes, nullable, min:8, confirmed)
- [ ] `rules()` permite role nullable con exists
- [ ] `rules()` permite active (sometimes, boolean)
- [ ] `prepareForValidation()` solo normaliza si username está presente

#### `app/Http/Requests/User/SyncPermissionsRequest.php`

- [ ] `rules()` exige permissions (required, array, min:1)
- [ ] `rules()` cada permiso debe existir en permissions,name
- [ ] `rules()` exige assign (required, boolean)

#### `app/Http/Requests/Role/StoreRoleRequest.php`

- [ ] `rules()` exige name (required, string, max:100, unique:roles,name)
- [ ] `rules()` permite permissions como array opcional
- [ ] `rules()` cada permiso debe existir en permissions,name

#### `app/Http/Requests/Role/UpdateRoleRequest.php`

- [ ] `rules()` permite name (sometimes, string, max:100, unique ignorando propio id)
- [ ] `rules()` permite permissions como array opcional
- [ ] `rules()` cada permiso debe existir en permissions,name

#### `app/Http/Requests/Permission/StorePermissionRequest.php`

- [ ] `rules()` exige name (required, string, max:100, unique, regex modulo.accion)
- [ ] `messages()` retorna mensaje personalizado para regex

#### `app/Http/Requests/Permission/UpdatePermissionRequest.php`

- [ ] `rules()` exige name (required, string, max:100, unique ignorando propio id, regex)
- [ ] `messages()` retorna mensaje personalizado para regex

#### `app/Http/Requests/Parameter/StoreParameterRequest.php`

- [ ] `rules()` exige key (required, string, max:100, unique)
- [ ] `rules()` exige value (required, string, max:255)
- [ ] `rules()` permite description (nullable, string, max:255)

#### `app/Http/Requests/Parameter/UpdateParameterRequest.php`

- [ ] `rules()` exige key (required, string, max:100, unique ignorando propio id)
- [ ] `rules()` exige value (required, string, max:255)
- [ ] `rules()` permite description (nullable, string, max:255)

#### `app/Http/Requests/Schedule/StoreUserScheduleRequest.php`

- [ ] `rules()` exige day_of_week (required, integer, min:0, max:6)
- [ ] `rules()` exige start_time (required, date_format:H:i)
- [ ] `rules()` exige end_time (required, date_format:H:i, after:start_time)
- [ ] `rules()` permite show_status (sometimes, boolean)
- [ ] `messages()` retorna mensajes personalizados para day_of_week y end_time

#### `app/Http/Requests/Schedule/UpdateUserScheduleRequest.php`

- [ ] `rules()` permite day_of_week (sometimes, integer, min:0, max:6)
- [ ] `rules()` permite start_time (sometimes, date_format:H:i)
- [ ] `rules()` permite end_time (sometimes, date_format:H:i, after:start_time condicional)
- [ ] `rules()` permite show_status (sometimes, boolean)
- [ ] `messages()` retorna mensaje personalizado para end_time.after

---

### API Resources (Unit: transformación de datos)

#### `app/Http/Resources/UserResource.php`

- [ ] `toArray()` retorna id, name, username, email, active, roles, permissions

#### `app/Http/Resources/RoleResource.php`

- [ ] `toArray()` retorna id, name, guard_name, permissions (usando whenLoaded)

#### `app/Http/Resources/PermissionResource.php`

- [ ] `toArray()` retorna id, name, guard_name

#### `app/Http/Resources/ParameterResource.php`

- [ ] `toArray()` retorna id, key, value, description

#### `app/Http/Resources/UserScheduleResource.php`

- [ ] `toArray()` retorna id, user_id, day_of_week, day_name, start_time, end_time, show_status
- [ ] `day_name` mapea correctamente los 7 días de la semana según day_of_week
- [ ] `start_time` y `end_time` se recortan a 5 caracteres

---

### Middleware (Feature: comportamiento HTTP)

#### `app/Http/Middleware/CheckUserSchedule.php`

- [ ] Permite el paso si no hay usuario autenticado
- [ ] Permite el paso si el usuario es superadmin
- [ ] Retorna 403 si el usuario no tiene horarios asignados
- [ ] Retorna 403 si la hora actual está fuera del horario
- [ ] Permite el paso si la hora actual está dentro del horario

#### `app/Http/Middleware/SetJwtFromCookie.php`

- [ ] No modifica el header si ya existe Authorization
- [ ] Agrega header Authorization desde cookie si no existe token en header
- [ ] No agrega header si no hay cookie

---

### Rutas (Feature: mapeo de rutas)

#### `routes/api.php`

- [ ] `POST /api/auth/register` mapea a AuthController@register sin middleware
- [ ] `POST /api/auth/login` mapea a AuthController@login sin middleware
- [ ] `GET /api/auth/config` mapea a AuthController@config sin middleware
- [ ] `POST /api/auth/refresh` usa middleware throttle:10,1
- [ ] `GET /api/auth/me` usa middleware auth:api
- [ ] `POST /api/auth/logout` usa middleware auth:api
- [ ] Todas las rutas de negocio usan middleware auth:api + check.schedule
- [ ] Cada ruta de CRUD usa el middleware permission correspondiente

---

### ApiResult (Unit: DTO de respuesta)

> **Nota:** Ya existe `ApiResultTest.php` con cobertura completa. No se requieren tests adicionales.

- [x] `success()` — probado
- [x] `created()` — probado
- [x] `failure()` — probado
- [x] `notFound()` — probado
- [x] `unauthorized()` — probado
- [x] `forbidden()` — probado
- [x] `empty()` — probado
- [x] `validationError()` — probado
- [x] `toResponse()` — probado

---

### Database / Seeders / Factories

#### `database/factories/UserFactory.php`

- [ ] Crea usuario con datos por defecto válidos
- [ ] `inactive()` estado asigna active=false
- [ ] `withRole()` asigna role al usuario

#### `database/seeders/`

- [ ] Los seeders crean roles y permisos base correctamente

---

## Frontend — `recaudify-web/`

### Servicios (Unit: lógica de negocio + API calls)

#### `core/services/api.service.ts`

- [ ] `request()` construye URL como `{apiUrl}/{controller}/{action}`
- [ ] `request()` incluye headers Content-Type, Accept, X-Requested-With
- [ ] `request()` sanitiza body contra prototype pollution
- [ ] `request()` sanitiza params contra prototype pollution
- [ ] `request()` usa withCredentials=true
- [ ] `request()` mapea response a `response.data`
- [ ] `get()` llama request con method GET
- [ ] `post()` llama request con method POST
- [ ] `put()` llama request con method PUT
- [ ] `patch()` llama request con method PATCH
- [ ] `delete()` llama request con method DELETE
- [ ] URL no incluye action si es undefined

#### `core/services/auth.service.ts`

- [ ] `isAuthenticated` es false cuando currentUser es null
- [ ] `isAuthenticated` es true cuando currentUser no es null
- [ ] `currentShift` retorna current_shift del usuario
- [ ] `shiftStatusEnabled` retorna shift_status_enabled del usuario
- [ ] `shiftCountdownEnabled` retorna shift_countdown_enabled del usuario
- [ ] `geolocalizationLoginEnabled` retorna geolocalization_login_enabled del usuario
- [ ] `hasPermission()` retorna true si el permiso está en la lista del usuario
- [ ] `hasPermission()` retorna false si no está
- [ ] `checkAuth()` llama GET auth/me y setea currentUser en éxito
- [ ] `checkAuth()` intenta refresh si 401, luego me otra vez
- [ ] `checkAuth()` setea currentUser=null si falla todo
- [ ] `login()` llama POST auth/login, luego me()
- [ ] `login()` si geolocalization está deshabilitado, captura audit sin coords
- [ ] `login()` si geolocalization está habilitado, solicita ubicación y captura audit
- [ ] `login()` si geolocalization es denegado, hace logout y lanza error
- [ ] `login()` normaliza username a minúsculas
- [ ] `me()` llama GET auth/me y setea currentUser
- [ ] `refresh()` llama POST auth/refresh con shareReplay (solo una llamada concurrente)
- [ ] `clearSession()` setea currentUser=null
- [ ] `expireSession()` setea currentUser=null y llama POST auth/logout
- [ ] `logout()` llama POST auth/logout, limpia usuario y navega a /login

#### `core/services/audit.service.ts`

- [ ] `captureLogin()` crea objeto LoginAudit con user_id, session_id (UUID), logged_at
- [ ] `captureLogin()` incluye ip_address, user_agent, os, device_type
- [ ] `captureLogin()` incluye geolocation si coords no es null
- [ ] `captureLogin()` incluye geolocation=null si coords es null
- [ ] `parseOs()` detecta Windows, iOS, iPadOS, Android, macOS, Linux, Unknown
- [ ] `getDeviceType()` detecta tablet, mobile, desktop según user agent

#### `core/services/geolocation.service.ts`

- [ ] `request()` retorna Observable con coordenadas si geolocation está disponible
- [ ] `request()` lanza error GEOLOCATION_UNSUPPORTED si no hay geolocation API
- [ ] `request()` lanza error GEOLOCATION_DENIED si el usuario deniega
- [ ] `request()` usa timeout de 10 segundos
- [ ] `getPermissionState()` retorna el estado del permiso
- [ ] `getPermissionState()` retorna 'prompt' si Permissions API no está disponible

#### `core/services/parameters.service.ts`

- [ ] `load()` llama getAll() y setea items, loading, showTrashed=false
- [ ] `toggleTrashed()` cambia showTrashed y carga trashed si está vacío
- [ ] `remove()` llama DELETE, actualiza items y trashed, muestra toast success
- [ ] `remove()` muestra toast error si falla
- [ ] `restoreItem()` llama restore, actualiza trashed e items, muestra toast success
- [ ] `restoreItem()` muestra toast error si falla
- [ ] `getFlag()` retorna true si el valor es "true"
- [ ] Métodos CRUD: getAll(), getById(), create(), update(), delete(), getTrashed(), restore() llaman ApiService

#### `core/services/permissions.service.ts`

- [ ] `load()` llama getAll() y setea items
- [ ] `toggleTrashed()` cambia showTrashed y carga trashed
- [ ] `remove()` llama DELETE, actualiza items y trashed, toast success
- [ ] `restoreItem()` llama restore, actualiza listas, toast success
- [ ] `grouped()` computa grouped por módulo desde items
- [ ] `groupedTrashed()` computa grouped por módulo desde trashed
- [ ] `groupByModule()` agrupa permisos por módulo (primer segmento del nombre)
- [ ] `groupByModule()` ordena módulos alfabéticamente
- [ ] `groupByModuleNames()` agrupa retornando solo nombres
- [ ] `isValidName()` valida formato modulo.accion con regex
- [ ] `actionLabel()` extrae el segundo segmento del nombre
- [ ] Métodos CRUD: getAll(), getById(), create(), update(), delete(), getTrashed(), restore()

#### `core/services/roles.service.ts`

- [ ] `load()` llama getAll(), setea items, resetea showTrashed
- [ ] `toggleTrashed()` cambia showTrashed, carga trashed si vacío
- [ ] `remove()` elimina role, actualiza items y trashed, toast success
- [ ] `restoreItem()` restaura role, actualiza listas ordenadas, toast success
- [ ] Métodos CRUD: getAll(), getById(), create(), update(), delete(), getTrashed(), restore()

#### `core/services/schedules.service.ts`

- [ ] `loadForUser()` llama getByUser y setea items
- [ ] `loadShiftStatusFlag()` llama getFlag('shift-status')
- [ ] `formatTime()` convierte "09:00" a "9:00 AM", "14:30" a "2:30 PM"
- [ ] `formatTime()` maneja medianoche "00:00" como "12:00 AM"
- [ ] `formatTime()` maneja mediodía "12:00" como "12:00 PM"
- [ ] `getForDay()` filtra schedules por day_of_week
- [ ] `addEntry()` crea schedule, actualiza items, toast success
- [ ] `addEntry()` toast error si falla
- [ ] `updateEntry()` actualiza schedule, reemplaza en items, toast success
- [ ] `removeEntry()` elimina schedule, actualiza items, toast success
- [ ] Métodos CRUD: getByUser(), create(), update(), delete()

#### `core/services/shift-status.service.ts`

- [ ] `visibleShift` retorna null si shiftStatusEnabled es false
- [ ] `visibleShift` retorna null si no hay shift o is_within_schedule es false
- [ ] `visibleShift` retorna null si show_status es false
- [ ] `visibleShift` retorna shift si todo está habilitado
- [ ] `countdownMinutes` retorna null si visibleShift es null
- [ ] `countdownMinutes` retorna remaining_minutos decrementado por ticks

#### `core/services/toast.service.ts`

- [ ] `success()` agrega toast con type success y duration por defecto
- [ ] `error()` agrega toast con type error
- [ ] `warning()` agrega toast con type warning
- [ ] `info()` agrega toast con type info
- [ ] `dismiss()` remueve toast por id
- [ ] `_add()` genera id único con crypto.randomUUID()
- [ ] `_add()` auto-descarta después de duration si > 0
- [ ] `_add()` no auto-descarta si duration = 0

#### `core/services/users.service.ts`

- [ ] `load()` llama getAll(), setea items, resetea showDisabled
- [ ] `toggleDisabled()` alterna showDisabled, carga disabled si vacío
- [ ] `remove()` desactiva usuario, actualiza items y disabled, toast success
- [ ] `restoreItem()` restaura usuario, actualiza listas ordenadas, toast success
- [ ] `roleLabel()` retorna el primer rol o "—"
- [ ] Métodos CRUD: getAll(), getDisabled(), getById(), create(), update(), delete(), restore()

---

### Guards (Unit: lógica de autorización)

#### `core/guards/auth.guard.ts`

- [ ] `authGuard` retorna true si está autenticado y geolocation no está denegado
- [ ] `authGuard` redirige a /login si no está autenticado
- [ ] `authGuard` expira sesión y redirige a /login si geolocation está denegado
- [ ] `guestGuard` retorna true si no está autenticado
- [ ] `guestGuard` redirige a /dashboard si ya está autenticado

#### `core/guards/admin.guard.ts`

- [ ] `adminGuard` retorna true si el usuario es administrador
- [ ] `adminGuard` retorna true si el usuario es superadmin
- [ ] `adminGuard` redirige a /dashboard si no tiene rol admin

#### `core/guards/permission.guard.ts`

- [ ] `permissionGuard('users.view')` retorna true si el usuario tiene el permiso
- [ ] `permissionGuard('users.view')` redirige a /dashboard si no tiene el permiso

---

### Interceptors (Unit: transformación HTTP)

#### `core/interceptors/auth.interceptor.ts`

- [ ] Pasa la request sin modificaciones (es un passthrough)

#### `core/interceptors/error.interceptor.ts`

- [ ] En 401 fuera de /auth/: intenta refresh y re-intenta la request original
- [ ] En 401 fuera de /auth/: si refresh falla, limpia sesión, redirige a /login, retorna ApiError
- [ ] En 403 con mensaje de schedule: expira sesión, muestra toast, redirige a /login
- [ ] En 403 con otro mensaje: retorna ApiError normalmente
- [ ] En 422: retorna ApiError con errors del body
- [ ] En 500 sin body: retorna ApiError con mensaje genérico
- [ ] Otros códigos: retorna ApiError con mensaje del body o genérico

---

### Directivas (Unit: manipulación del DOM)

#### `core/directives/btn.directive.ts`

- [ ] Agrega clase CSS correspondiente a la variante primary
- [ ] Agrega clase para secondary
- [ ] Agrega clase para table-edit
- [ ] Agrega clase para table-danger
- [ ] Agrega clase para table-restore
- [ ] Agrega clase para inline-save
- [ ] Agrega clase para inline-cancel
- [ ] Cambia la clase cuando la variante cambia

#### `core/directives/table.directive.ts`

- [ ] Agrega clase `data-table` para variante default
- [ ] Agrega clase `data-table-trashed` para variante trashed
- [ ] Cambia la clase cuando la variante cambia

---

### Componentes (Unit + Integration)

#### `core/components/spinner/spinner.ts`

- [ ] Muestra el spinner cuando show() es true
- [ ] Oculta el spinner cuando show() es false
- [ ] Muestra la label cuando se provee
- [ ] No muestra label cuando no se provee

#### `core/components/toast/toast.ts`

- [ ] Renderiza un toast por cada item en ToastService.toasts()
- [ ] Aplica clase de posición según input position()
- [ ] Aplica clase de tamaño según input size()
- [ ] Muestra ícono y color según tipo (success/error/warning/info)
- [ ] Botón de cerrar llama dismiss con el id del toast
- [ ] Calcula positionClass() correctamente para las 9 posiciones
- [ ] Calcula sizeStyle() correctamente para sm, md, lg

#### `app.ts` (Componente raíz)

- [ ] Renderiza router-outlet
- [ ] Renderiza app-toast

#### `features/auth/login/login.ts`

- [ ] OnInit precarga valores por defecto (admin / admin1234)
- [ ] OnInit carga config de geolocalización
- [ ] `submit()` llama auth.login() y navega a /dashboard en éxito
- [ ] `submit()` setea error en fallo
- [ ] `submit()` maneja loading state

#### `features/dashboard/dashboard.ts`

- [ ] OnInit carga currentUser si no está presente

#### `features/admin/admin-dashboard/admin-dashboard.ts`

- [ ] `canSeeUsers` es true si tiene permiso users.view
- [ ] `canSeeRoles` es true si tiene permiso roles.ver
- [ ] `canSeePermissions` es true si tiene permiso permissions.view
- [ ] `canSeeSchedules` es true si tiene permiso schedules.view
- [ ] `canSeeParameters` es true si tiene permiso parameters.view

#### `features/admin/users/users.ts`

- [ ] OnInit llama service.load()
- [ ] `toggleDisabled()` llama service.toggleDisabled()
- [ ] `roleLabel()` delega a service.roleLabel()
- [ ] `delete()` confirma y llama service.remove()
- [ ] `restore()` llama service.restoreItem()
- [ ] Permisos computados: canCreate, canEdit, canDelete, canRestore

#### `features/admin/users/user-form/user-form.ts`

- [ ] OnInit carga roles y si es edición carga usuario por id
- [ ] `save()` en creación llama service.create() con payload completo
- [ ] `save()` en edición llama service.update()
- [ ] `save()` valida que name y username no estén vacíos
- [ ] `save()` valida que password no esté vacía en creación
- [ ] `save()` navega a /admin/users en éxito
- [ ] `save()` muestra toast success y error

#### `features/admin/roles/roles.ts`

- [ ] OnInit llama service.load()
- [ ] `toggleTrashed()` llama service.toggleTrashed()
- [ ] `delete()` confirma y llama service.remove()
- [ ] `restore()` llama service.restoreItem()

#### `features/admin/roles/role-form/role-form.ts`

- [ ] OnInit carga permisos y si es edición carga rol
- [ ] `grouped` computa permisos agrupados por módulo
- [ ] `toggle()` agrega/remueve permiso de selected
- [ ] `toggleAll()` selecciona/deselecciona todos los permisos de un módulo
- [ ] `allChecked()` retorna true si todos los permisos están seleccionados
- [ ] `save()` en creación llama service.create()
- [ ] `save()` en edición llama service.update()
- [ ] `save()` navega a /admin/roles en éxito

#### `features/admin/permissions/permissions.ts`

- [ ] OnInit llama service.load()
- [ ] `toggleTrashed()` llama service.toggleTrashed()
- [ ] `actionLabel()` delega a service.actionLabel()
- [ ] `delete()` confirma y llama service.remove()
- [ ] `restore()` llama service.restoreItem()

#### `features/admin/permissions/permission-form/permission-form.ts`

- [ ] OnInit carga permiso si es edición
- [ ] `isValid` valida formato modulo.accion
- [ ] `save()` valida nombre antes de enviar
- [ ] `save()` en creación llama service.create()
- [ ] `save()` en edición llama service.update()

#### `features/admin/schedules/schedules.ts`

- [ ] OnInit carga lista de usuarios para asignar horarios

#### `features/admin/schedules/user-schedules/user-schedules.ts`

- [ ] OnInit carga usuario, schedules y flag shift-status
- [ ] `formatTime()` delega a schedulesService
- [ ] `schedulesForDay()` filtra schedules por día
- [ ] `openAdd()` prepara formulario de nuevo schedule
- [ ] `saveAdd()` crea schedule y limpia addingDay en éxito
- [ ] `openEdit()` prepara formulario de edición
- [ ] `saveEdit()` actualiza schedule y limpia editingId en éxito
- [ ] `deleteEntry()` confirma y elimina schedule
- [ ] Permisos computados: canCreate, canEdit, canDelete

#### `features/admin/parameters/parameters.ts`

- [ ] OnInit llama service.load()
- [ ] `toggleTrashed()` llama service.toggleTrashed()
- [ ] `delete()` confirma y llama service.remove()
- [ ] `restore()` llama service.restoreItem()

#### `features/admin/parameters/parameter-form/parameter-form.ts`

- [ ] OnInit carga parámetro si es edición
- [ ] `save()` en creación llama service.create()
- [ ] `save()` en edición llama service.update()
- [ ] `save()` valida key y value no vacíos
- [ ] `save()` navega a /admin/parameters en éxito

#### `layout/app-shell/app-shell.ts`

- [ ] `isAdmin` es true si el usuario tiene rol administrador o superadmin
- [ ] `hasPermission()` delega a authService.hasPermission()
- [ ] `isItemVisible()` retorna true si no requiere permiso o si lo tiene
- [ ] `hasVisibleItems()` retorna true si al menos un item es visible
- [ ] `toggleSidebar()` alterna sidebarOpen
- [ ] `toggleUserMenu()` alterna userMenuOpen
- [ ] `logout()` llama authService.logout()
- [ ] `visibleShift` y `countdownMinutes` se exponen al template

---

### Utilidades (Unit: funciones puras)

#### `core/utils/text.ts`

- [ ] `lower()` convierte a minúsculas y hace trim
- [ ] `upper()` convierte a mayúsculas y hace trim
- [ ] `capitalize()` capitaliza primera letra, resto minúsculas, trim
- [ ] `titleCase()` capitaliza cada palabra, maneja espacios múltiples

---

### Routing (Integration: configuración de rutas)

#### `app.routes.ts`

- [ ] Ruta vacía redirige a /dashboard
- [ ] /login carga LoginComponent con guestGuard
- [ ] Ruta base carga AppShell con authGuard y children lazy
- [ ] /dashboard carga DashboardComponent
- [ ] /admin carga AdminDashboard con adminGuard
- [ ] /admin/users carga UsersComponent
- [ ] /admin/users/new carga UserFormComponent (create)
- [ ] /admin/users/:id/edit carga UserFormComponent (edit)
- [ ] /admin/roles carga RolesComponent
- [ ] /admin/roles/new carga RoleFormComponent (create)
- [ ] /admin/roles/:id/edit carga RoleFormComponent (edit)
- [ ] /admin/permissions carga PermissionsComponent
- [ ] /admin/permissions/new carga PermissionFormComponent (create)
- [ ] /admin/permissions/:id/edit carga PermissionFormComponent (edit)
- [ ] /admin/schedules carga SchedulesComponent
- [ ] /admin/schedules/:userId carga UserSchedulesComponent
- [ ] /admin/parameters carga ParametersComponent
- [ ] /admin/parameters/new carga ParameterFormComponent (create)
- [ ] /admin/parameters/:id/edit carga ParameterFormComponent (edit)
- [ ] Wildcard \*\* redirige a /dashboard
- [ ] withComponentInputBinding está habilitado

#### `app.config.ts`

- [ ] Provee zoneless change detection
- [ ] Provee router con component input binding
- [ ] Provee HTTP client con errorInterceptor
- [ ] Provee app initializer que ejecuta checkAuth()

---

## Resumen de cobertura actual vs. objetivo

| Capa     | Tests | Estado                          | Cobertura medida                                | Objetivo |
| -------- | ----- | ------------------------------- | ----------------------------------------------- | -------- |
| Backend  | 144   | ✅ verde (`php artisan test`)   | bloqueada — sin driver PCOV/Xdebug en Herd Lite | 85 %+    |
| Frontend | 139   | ✅ verde (`pnpm test`, 30 spec) | Stmts 67.7 % · Branch 71.0 % · Funcs 70.1 %     | 85 %+    |

La cobertura frontend se genera con el builder (`coverage: true` en `angular.json`, provider
`@vitest/coverage-v8`). Lo no cubierto son mayormente ramas de plantillas HTML (estados de
carga/`@if`/`@for`), no lógica. Para cobertura backend hay que instalar PCOV o Xdebug y luego
`php artisan test --coverage`.

**Próximos pasos sugeridos:**

1. Instalar PCOV/Xdebug en el PHP de Herd Lite para desbloquear cobertura backend.
2. Cubrir servicios frontend pendientes (`config`, `permissions`) y ramas de UI restantes.
3. Borrar `tests/Unit/ExampleTest.php` (boilerplate `assertTrue(true)`).

---

## Matriz de casos (catálogo con IDs)

IDs estables para referenciar casos. Estado: ✅ implementado · 🔵 manual/E2E pendiente de automatizar.

### Backend

| ID     | Caso                                               | Tipo        | Estado |
| ------ | -------------------------------------------------- | ----------- | ------ |
| B-A01  | Login credenciales correctas → 200 + token         | Integration | ✅     |
| B-A02  | Login contraseña incorrecta → 401                  | Integration | ✅     |
| B-A03  | Login usuario inexistente → 401                    | Integration | ✅     |
| B-A04  | Login usuario inactivo → 403                       | Integration | ✅     |
| B-A05  | Login fuera del horario → 403 + mensaje            | Integration | ✅     |
| B-A06  | `me()` con token devuelve flags de parámetros      | Integration | ✅     |
| B-A10  | Token expirado en ruta protegida → 401             | Integration | ✅     |
| B-A11  | Sin token en ruta protegida → 401 (no 500)         | Integration | ✅     |
| B-A12  | Refresh de token → nuevo token válido              | Integration | ✅     |
| B-A13  | Logout invalida el token                           | Integration | ✅     |
| B-U01  | Listar usuarios activos (paginado)                 | Integration | ✅     |
| B-U04  | Crear usuario válido → 201                         | Integration | ✅     |
| B-U05  | Crear usuario con username duplicado → 422         | Integration | ✅     |
| B-U10  | Soft delete usuario → 200                          | Integration | ✅     |
| B-U11  | Restaurar usuario eliminado → 200                  | Integration | ✅     |
| B-U12  | Sin permiso `users.view` → 403                   | Integration | ✅     |
| B-R02  | Crear rol con permisos → 201                       | Integration | ✅     |
| B-R03  | Crear rol con nombre duplicado → 422               | Integration | ✅     |
| B-P02  | Crear permiso formato `modulo.accion` → 201        | Integration | ✅     |
| B-P04  | Crear permiso con formato inválido → 422           | Integration | ✅     |
| B-S02  | Crear horario en día libre → 201                   | Integration | ✅     |
| B-S03  | Crear horario en día ocupado → 409                 | Integration | ✅     |
| B-S06  | `CheckUserSchedule` bloquea fuera de horario → 403 | Unit        | ✅     |
| B-S08  | `CheckUserSchedule` no aplica a superadmin         | Unit        | ✅     |
| B-PM07 | `ParameterService::get()` valor cacheado           | Unit        | ✅     |
| B-PM08 | `ParameterService::get()` default si no existe     | Unit        | ✅     |
| B-PM09 | Cache se invalida al crear/editar/eliminar         | Unit        | ✅     |

### Frontend

| ID     | Caso                                                        | Tipo | Estado |
| ------ | ----------------------------------------------------------- | ---- | ------ |
| F-A05  | Geo requerida + permiso denegado → error y logout           | Unit | ✅     |
| F-A06  | `geolocalization_login=false` no solicita geolocalización   | Unit | ✅     |
| F-A09  | `guestGuard` redirige a `/dashboard` si autenticado         | Unit | ✅     |
| F-A10  | `authGuard` redirige a `/login` si no autenticado           | Unit | ✅     |
| F-A11  | `adminGuard` redirige si el rol no es admin/superadmin      | Unit | ✅     |
| F-A13  | Token expirado activa refresh automático                    | Unit | ✅     |
| F-D02  | Widget shift-status visible con `shift_status_enabled`      | Unit | ✅     |
| F-D04  | Contador regresivo (`countdownMinutes`)                     | Unit | ✅     |
| F-S01  | Lista usuarios con botón gestionar horarios                 | Unit | ✅     |
| F-S04  | Crear horario envía payload correcto                        | Unit | ✅     |
| F-S05  | Editar horario actualiza la entrada                         | Unit | ✅     |
| F-S06  | Eliminar horario (con/ sin confirm)                         | Unit | ✅     |
| F-SV01 | `ToastService.success()` toast verde con auto-dismiss       | Unit | ✅     |
| F-SV03 | Toast con `duration=0` no se cierra solo                    | Unit | ✅     |
| F-SV04 | `ApiService` construye URL `{apiUrl}/{controller}/{action}` | Unit | ✅     |
| F-SV05 | `ApiService` rechaza claves `__proto__`                     | Unit | ✅     |
| F-A01  | Login exitoso redirige a `/dashboard`                       | E2E  | 🔵     |
| F-U03  | Crear usuario muestra toast                                 | E2E  | 🔵     |
| F-R02  | Crear rol muestra toast                                     | E2E  | 🔵     |
| F-PM02 | Crear parámetro muestra toast                               | E2E  | 🔵     |

---

## Reporte de QA manual

> Última ronda manual: 2026-06-24, entorno local (`:8000` API · `:4200` SPA), usuarios `superadmin`/`admin`.
> Resultado: **37/37 OK**, 1 bug encontrado y corregido (abajo). Estos flujos hoy están cubiertos
> mayormente por los specs automatizados de arriba; se conserva como histórico.

| Módulo     | Pruebas       | ✅ OK | Notas                                                        |
| ---------- | ------------- | ----- | ------------------------------------------------------------ |
| Auth       | 7             | 7     | Login, logout, guards, bloqueo sin geolocalización           |
| Dashboard  | 2             | 2     | Nombre y rol del usuario                                     |
| Usuarios   | 7             | 7     | CRUD + soft delete + restaurar                               |
| Roles      | 4             | 4     | CRUD + ver eliminados                                        |
| Permisos   | 5 (API)       | 5     | CRUD vía API + 1 bug de front corregido                      |
| Horarios   | 5 + 3 bloqueo | 8     | CRUD + control de acceso por horario (mid. `check.schedule`) |
| Parámetros | 4             | 4     | CRUD + ver eliminados                                        |

**Bug corregido (Permisos):** el botón "Guardar" del formulario quedaba siempre deshabilitado.
Causa: `isValid` era un `computed()` que leía `formName` (propiedad plana), por lo que en zoneless/OnPush
solo se evaluaba una vez con `''`. Fix: `formName` pasó a `signal('')` e `isValid` a getter regular;
template usa `[ngModel]="formName()" (ngModelChange)="formName.set($event)"`.

---

## Credenciales de prueba (seeder)

| Usuario       | Contraseña       | Rol           |
| ------------- | ---------------- | ------------- |
| `superadmin`  | `superadmin1234` | superadmin    |
| `admin`       | `admin1234`      | administrador |
| `coordinador` | `admin1234`      | coordinador   |
| `auxiliar`    | `admin1234`      | auxiliar      |

> Con `geolocalization_login=true`, el login del navegador requiere permiso de ubicación.

---

## Referencia: parámetros del sistema

Controlan comportamiento global. Se editan en `/admin/parameters`, por API o por SQL.

| Clave                    | Default | Descripción                                             |
| ------------------------ | ------- | ------------------------------------------------------- |
| `shift-status`           | `true`  | Activa/desactiva el control de horarios de acceso       |
| `shift-status-countdown` | `true`  | Activa/desactiva el conteo regresivo de cierre de turno |
| `geolocalization_login`  | `true`  | Requiere permiso de geolocalización al iniciar sesión   |

```sql
-- Activar/desactivar un flag
UPDATE parameters SET value = 'false' WHERE key = 'shift-status';
UPDATE parameters SET value = 'true'  WHERE key = 'geolocalization_login';

-- Ver estado actual / restaurar soft-deleted
SELECT id, key, value, description, deleted_at FROM parameters;
UPDATE parameters SET deleted_at = NULL WHERE key = 'nombre-clave';
```

Vía API (token de superadmin/admin): `GET /api/parameters` y `PUT /api/parameters/{id}` con
`{"key":"shift-status","value":"false","description":"..."}`.
