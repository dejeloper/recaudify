# Casos de prueba — Recaudify

Lista de casos para pruebas unitarias e integración. **No son tests implementados aún**, son los escenarios a cubrir.

---

## Backend (`recaudify-api/`)

### Auth

| ID    | Caso                                                              | Tipo        | Método / Ruta             |
| ----- | ----------------------------------------------------------------- | ----------- | ------------------------- |
| B-A01 | Login con credenciales correctas → 200 + token                    | Integration | `POST /api/auth/login`    |
| B-A02 | Login con contraseña incorrecta → 401                             | Integration | `POST /api/auth/login`    |
| B-A03 | Login con usuario inexistente → 401                               | Integration | `POST /api/auth/login`    |
| B-A04 | Login con usuario inactivo → 403                                  | Integration | `POST /api/auth/login`    |
| B-A05 | Login fuera del horario del usuario → 403 + mensaje               | Integration | `POST /api/auth/login`    |
| B-A06 | `me()` con token válido devuelve flags de parámetros              | Integration | `GET /api/auth/me`        |
| B-A07 | `me()` incluye `geolocalization_login_enabled`                    | Unit        | `AuthController::me()`    |
| B-A08 | `me()` incluye `shift_status_enabled` y `shift_countdown_enabled` | Unit        | `AuthController::me()`    |
| B-A09 | `config()` público devuelve `geolocalization_login` sin token     | Integration | `GET /api/auth/config`    |
| B-A10 | Token expirado en ruta protegida → 401                            | Integration | cualquier ruta `auth:api` |
| B-A11 | Sin token en ruta protegida → 401 (no 500)                        | Integration | cualquier ruta `auth:api` |
| B-A12 | Refresh de token → nuevo token válido                             | Integration | `POST /api/auth/refresh`  |
| B-A13 | Logout invalida el token                                          | Integration | `POST /api/auth/logout`   |

### Usuarios

| ID    | Caso                                       | Tipo        | Método / Ruta                  |
| ----- | ------------------------------------------ | ----------- | ------------------------------ |
| B-U01 | Listar usuarios activos (paginado)         | Integration | `GET /api/users`               |
| B-U02 | Listar usuarios desactivados               | Integration | `GET /api/users/disabled`      |
| B-U03 | Buscar usuario por nombre                  | Integration | `GET /api/users/search/{name}` |
| B-U04 | Crear usuario con datos válidos → 201      | Integration | `POST /api/users`              |
| B-U05 | Crear usuario con username duplicado → 422 | Integration | `POST /api/users`              |
| B-U06 | Crear usuario sin campos requeridos → 422  | Integration | `POST /api/users`              |
| B-U07 | Editar usuario → 200                       | Integration | `PUT /api/users/{id}`          |
| B-U08 | Ver usuario por ID → 200                   | Integration | `GET /api/users/{id}`          |
| B-U09 | Ver usuario eliminado (trashed) → 200      | Integration | `GET /api/users/trashed/{id}`  |
| B-U10 | Soft delete usuario → 200                  | Integration | `DELETE /api/users/{id}`       |
| B-U11 | Restaurar usuario eliminado → 200          | Integration | `POST /api/users/{id}/restore` |
| B-U12 | Sin permiso `usuarios.ver` → 403           | Integration | `GET /api/users`               |
| B-U13 | `UserResource` incluye roles y permisos    | Unit        | `UserResource`                 |

### Roles

| ID    | Caso                                 | Tipo        | Método / Ruta                  |
| ----- | ------------------------------------ | ----------- | ------------------------------ |
| B-R01 | Listar roles con permisos            | Integration | `GET /api/roles`               |
| B-R02 | Crear rol con permisos → 201         | Integration | `POST /api/roles`              |
| B-R03 | Crear rol con nombre duplicado → 422 | Integration | `POST /api/roles`              |
| B-R04 | Editar rol → 200                     | Integration | `PUT /api/roles/{id}`          |
| B-R05 | Soft delete rol → 200                | Integration | `DELETE /api/roles/{id}`       |
| B-R06 | Restaurar rol eliminado → 200        | Integration | `POST /api/roles/{id}/restore` |
| B-R07 | Listar roles eliminados              | Integration | `GET /api/roles/trashed`       |

### Permisos

| ID    | Caso                                                   | Tipo        | Método / Ruta                        |
| ----- | ------------------------------------------------------ | ----------- | ------------------------------------ |
| B-P01 | Listar permisos                                        | Integration | `GET /api/permissions`               |
| B-P02 | Crear permiso con formato válido `modulo.accion` → 201 | Integration | `POST /api/permissions`              |
| B-P03 | Crear permiso con nombre duplicado → 422               | Integration | `POST /api/permissions`              |
| B-P04 | Crear permiso con formato inválido → 422               | Integration | `POST /api/permissions`              |
| B-P05 | Editar permiso → 200                                   | Integration | `PUT /api/permissions/{id}`          |
| B-P06 | Soft delete permiso → 200                              | Integration | `DELETE /api/permissions/{id}`       |
| B-P07 | Restaurar permiso eliminado → 200                      | Integration | `POST /api/permissions/{id}/restore` |

### Horarios

| ID    | Caso                                                | Tipo        | Método / Ruta                    |
| ----- | --------------------------------------------------- | ----------- | -------------------------------- |
| B-S01 | Listar horarios de un usuario                       | Integration | `GET /api/users/{id}/schedules`  |
| B-S02 | Crear horario en día sin horario → 201              | Integration | `POST /api/users/{id}/schedules` |
| B-S03 | Crear horario en día ya ocupado → 409               | Integration | `POST /api/users/{id}/schedules` |
| B-S04 | Editar horario existente → 200                      | Integration | `PUT /api/schedules/{id}`        |
| B-S05 | Eliminar horario → 200                              | Integration | `DELETE /api/schedules/{id}`     |
| B-S06 | `CheckUserSchedule` bloquea fuera del horario → 403 | Unit        | `CheckUserSchedule` middleware   |
| B-S07 | `CheckUserSchedule` permite dentro del horario      | Unit        | `CheckUserSchedule` middleware   |
| B-S08 | `CheckUserSchedule` no aplica a superadmin          | Unit        | `CheckUserSchedule` middleware   |

### Parámetros

| ID     | Caso                                                             | Tipo        | Método / Ruta                       |
| ------ | ---------------------------------------------------------------- | ----------- | ----------------------------------- |
| B-PM01 | Listar parámetros                                                | Integration | `GET /api/parameters`               |
| B-PM02 | Crear parámetro → 201                                            | Integration | `POST /api/parameters`              |
| B-PM03 | Crear parámetro con clave duplicada → 422                        | Integration | `POST /api/parameters`              |
| B-PM04 | Editar parámetro → 200                                           | Integration | `PUT /api/parameters/{id}`          |
| B-PM05 | Soft delete parámetro → 200                                      | Integration | `DELETE /api/parameters/{id}`       |
| B-PM06 | Restaurar parámetro → 200                                        | Integration | `POST /api/parameters/{id}/restore` |
| B-PM07 | `ParameterService::get()` devuelve valor cacheado                | Unit        | `ParameterService`                  |
| B-PM08 | `ParameterService::get()` devuelve default si la clave no existe | Unit        | `ParameterService`                  |
| B-PM09 | Cache se invalida al crear/editar/eliminar un parámetro          | Unit        | `ParameterService`                  |
| B-PM10 | Seeder inserta los 3 parámetros iniciales con `firstOrCreate`    | Unit        | `ParameterSeeder`                   |

---

## Frontend (`recaudify-web/`)

### Auth / Login

| ID    | Caso                                                                     | Tipo       | Componente / Servicio  |
| ----- | ------------------------------------------------------------------------ | ---------- | ---------------------- |
| F-A01 | Login exitoso redirige a `/dashboard`                                    | E2E        | `Login`, `AuthService` |
| F-A02 | Login con credenciales incorrectas muestra error inline                  | E2E        | `Login`                |
| F-A03 | Con `geolocalization_login=true` se muestra aviso azul de ubicación      | E2E / Unit | `Login`                |
| F-A04 | Con `geolocalization_login=false` NO se muestra aviso azul               | E2E / Unit | `Login`                |
| F-A05 | Con geo requerida y permiso denegado → muestra error y hace logout       | E2E        | `AuthService.login()`  |
| F-A06 | Con `geolocalization_login=false` el login no solicita geolocalización   | Unit       | `AuthService.login()`  |
| F-A07 | `ConfigService.getLoginConfig()` cachea la respuesta (una sola petición) | Unit       | `ConfigService`        |
| F-A08 | `geolocalizationLoginEnabled` computed refleja el flag del usuario       | Unit       | `AuthService`          |
| F-A09 | `guestGuard` redirige a `/dashboard` si ya está autenticado              | Unit       | `guestGuard`           |
| F-A10 | `authGuard` redirige a `/login` si no está autenticado                   | Unit       | `authGuard`            |
| F-A11 | `adminGuard` redirige a `/dashboard` si el rol no es admin/superadmin    | Unit       | `adminGuard`           |
| F-A12 | Logout limpia el token y redirige a `/login`                             | E2E        | `AuthService.logout()` |
| F-A13 | Token expirado activa el refresh automático                              | Unit       | `authInterceptor`      |

### Dashboard

| ID    | Caso                                                             | Tipo | Componente / Servicio |
| ----- | ---------------------------------------------------------------- | ---- | --------------------- |
| F-D01 | Dashboard muestra nombre y rol del usuario                       | E2E  | `Dashboard`           |
| F-D02 | Widget shift-status visible cuando `shift_status_enabled=true`   | Unit | `ShiftStatus`         |
| F-D03 | Widget shift-status oculto cuando `shift_status_enabled=false`   | Unit | `ShiftStatus`         |
| F-D04 | Contador regresivo visible cuando `shift_countdown_enabled=true` | Unit | `ShiftStatus`         |

### Usuarios

| ID    | Caso                                                        | Tipo | Componente / Servicio |
| ----- | ----------------------------------------------------------- | ---- | --------------------- |
| F-U01 | Lista usuarios activos en tabla                             | E2E  | `Users`               |
| F-U02 | Muestra sección de usuarios desactivados                    | E2E  | `Users`               |
| F-U03 | Crear usuario muestra toast "Usuario creado."               | E2E  | `Users`               |
| F-U04 | Editar usuario muestra toast "Usuario actualizado."         | E2E  | `Users`               |
| F-U05 | Desactivar usuario muestra confirm dialog                   | E2E  | `Users`               |
| F-U06 | Desactivar usuario mueve el registro a sección desactivados | E2E  | `Users`               |
| F-U07 | Restaurar usuario lo regresa a la lista activa              | E2E  | `Users`               |

### Roles

| ID    | Caso                                                  | Tipo | Componente / Servicio |
| ----- | ----------------------------------------------------- | ---- | --------------------- |
| F-R01 | Lista roles con chips de permisos                     | E2E  | `Roles`               |
| F-R02 | Crear rol muestra toast "Rol creado."                 | E2E  | `Roles`               |
| F-R03 | Eliminar rol muestra confirm y toast "Rol eliminado." | E2E  | `Roles`               |
| F-R04 | Sección "Ver eliminados" lista roles soft-deleted     | E2E  | `Roles`               |

### Permisos

| ID    | Caso                                                                        | Tipo | Componente / Servicio |
| ----- | --------------------------------------------------------------------------- | ---- | --------------------- |
| F-P01 | Lista permisos en tabla                                                     | E2E  | `Permissions`         |
| F-P02 | Formulario nuevo permiso — botón Guardar habilitado solo con formato válido | Unit | `PermissionForm`      |
| F-P03 | `isValid` getter retorna `false` para nombre vacío                          | Unit | `PermissionForm`      |
| F-P04 | `isValid` getter retorna `false` para formato sin punto                     | Unit | `PermissionForm`      |
| F-P05 | Crear permiso muestra toast "Permiso creado."                               | E2E  | `PermissionForm`      |
| F-P06 | Editar permiso carga el nombre actual en el input                           | E2E  | `PermissionForm`      |

### Horarios

| ID    | Caso                                                          | Tipo | Componente / Servicio |
| ----- | ------------------------------------------------------------- | ---- | --------------------- |
| F-S01 | Lista usuarios con botón de gestionar horarios                | E2E  | `Schedules`           |
| F-S02 | Vista de horario muestra 7 tarjetas (Lunes–Domingo)           | E2E  | `UserSchedule`        |
| F-S03 | Día sin horario muestra "Sin horario — acceso bloqueado"      | E2E  | `UserSchedule`        |
| F-S04 | Crear horario aparece en la tarjeta del día con formato AM/PM | E2E  | `UserSchedule`        |
| F-S05 | Editar horario actualiza la hora en la tarjeta                | E2E  | `UserSchedule`        |
| F-S06 | Eliminar horario vuelve a "Sin horario"                       | E2E  | `UserSchedule`        |

### Parámetros

| ID     | Caso                                                    | Tipo | Componente / Servicio |
| ------ | ------------------------------------------------------- | ---- | --------------------- |
| F-PM01 | Lista parámetros con clave, valor y descripción         | E2E  | `Parameters`          |
| F-PM02 | Crear parámetro muestra toast "Parámetro creado."       | E2E  | `Parameters`          |
| F-PM03 | Editar parámetro muestra toast "Parámetro actualizado." | E2E  | `Parameters`          |
| F-PM04 | Eliminar parámetro mueve a sección eliminados           | E2E  | `Parameters`          |
| F-PM05 | Restaurar parámetro lo regresa a la lista activa        | E2E  | `Parameters`          |

### Servicios compartidos

| ID     | Caso                                                                      | Tipo | Componente / Servicio |
| ------ | ------------------------------------------------------------------------- | ---- | --------------------- |
| F-SV01 | `ToastService.success()` muestra toast verde con auto-dismiss             | Unit | `ToastService`        |
| F-SV02 | `ToastService.error()` muestra toast rojo                                 | Unit | `ToastService`        |
| F-SV03 | Toast con `duration=0` no se cierra automáticamente                       | Unit | `ToastService`        |
| F-SV04 | `ApiService` construye la URL como `{apiUrl}/{controller}/{action}`       | Unit | `ApiService`          |
| F-SV05 | `ApiService` rechaza claves de body con `__proto__` (prototype pollution) | Unit | `ApiService`          |
| F-SV06 | `AuditService.captureLogin()` acepta `coords=null` sin error              | Unit | `AuditService`        |

---

## Resumen de cobertura

| Área                  | Casos backend | Casos frontend |
| --------------------- | :-----------: | :------------: |
| Auth                  |      13       |       13       |
| Usuarios              |      13       |       7        |
| Roles                 |       7       |       4        |
| Permisos              |       7       |       6        |
| Horarios              |       8       |       6        |
| Parámetros            |      10       |       5        |
| Servicios compartidos |       —       |       6        |
| **Total**             |    **58**     |     **47**     |

> Los casos marcados como **Unit** son candidatos a pruebas unitarias con PHPUnit (backend) o Vitest (frontend).
> Los marcados como **E2E** / **Integration** son candidatos a Playwright (frontend) o pruebas de feature con HTTPClient (backend).
