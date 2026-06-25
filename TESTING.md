# Reporte de Pruebas — Recaudify

**Fecha:** 2026-06-24  
**Entorno:** local (`http://localhost:8000` API · `http://localhost:4200` SPA)  
**Usuarios probados:** `superadmin` / `admin`

---

## Credenciales de prueba

| Usuario       | Contraseña       | Rol           |
| ------------- | ---------------- | ------------- |
| `superadmin`  | `superadmin1234` | superadmin    |
| `admin`       | `admin1234`      | administrador |
| `coordinador` | `admin1234`      | coordinador   |
| `auxiliar`    | `admin1234`      | auxiliar      |

> **Nota:** El login requiere permiso de geolocalización del navegador. Sin él, el botón "Ingresar" no ejecuta la petición.

---

## Resultados por módulo

### Auth

| Prueba                                               | Estado | Notas                                            |
| ---------------------------------------------------- | ------ | ------------------------------------------------ |
| Login con credenciales correctas (superadmin)        | ✅ OK  | Redirige a `/dashboard`, token en `localStorage` |
| Login con credenciales correctas (admin)             | ✅ OK  |                                                  |
| Bloqueo sin permiso de geolocalización               | ✅ OK  | El form muestra aviso y no envía                 |
| Logout                                               | ✅ OK  | Limpia token y redirige a `/login`               |
| `guestGuard` (usuario logueado va a `/login`)        | ✅ OK  | Redirige a `/dashboard`                          |
| `authGuard` (usuario sin sesión va a ruta protegida) | ✅ OK  | Redirige a `/login`                              |
| `adminGuard` (coordinador intenta ir a `/admin/*`)   | ✅ OK  | Redirige a `/dashboard`                          |

---

### Dashboard

| Prueba                                     | Estado | Notas                            |
| ------------------------------------------ | ------ | -------------------------------- |
| Carga el dashboard tras login (superadmin) | ✅ OK  | Muestra nombre y rol del usuario |
| Carga el dashboard tras login (admin)      | ✅ OK  | Muestra nombre y rol del usuario |

---

### Usuarios (`/admin/users`)

| Prueba                                                   | Estado | Notas                                                      |
| -------------------------------------------------------- | ------ | ---------------------------------------------------------- |
| Listar usuarios                                          | ✅ OK  | 5 usuarios mostrados en tabla                              |
| Ver usuarios desactivados                                | ✅ OK  | Sección separada en la misma página                        |
| Crear usuario (nombre, username, email, rol, contraseña) | ✅ OK  | Toast "Usuario creado." + aparece en tabla                 |
| Editar usuario (cambio de nombre)                        | ✅ OK  | Toast "Usuario actualizado."                               |
| Desactivar usuario (soft delete con confirm dialog)      | ✅ OK  | Toast "Usuario desactivado." + pasa a sección desactivados |
| Restaurar usuario                                        | ✅ OK  | Toast "Usuario activado." + vuelve a lista activa          |
| Acceso de admin a módulo usuarios                        | ✅ OK  | Visualiza y puede gestionar todos los usuarios             |

---

### Roles (`/admin/roles`)

| Prueba                                        | Estado | Notas                                                     |
| --------------------------------------------- | ------ | --------------------------------------------------------- |
| Listar roles con permisos asociados           | ✅ OK  | 4 roles: superadmin, administrador, coordinador, auxiliar |
| Crear rol con permisos seleccionados          | ✅ OK  | Toast "Rol creado."                                       |
| Eliminar rol (soft delete con confirm dialog) | ✅ OK  | Toast "Rol eliminado."                                    |
| Ver roles eliminados                          | ✅ OK  | Sección "Ver eliminados" disponible                       |

---

### Permisos (`/admin/permissions`)

| Prueba (backend API)                                   | Estado | Notas                     |
| ------------------------------------------------------ | ------ | ------------------------- |
| Listar 25 permisos (5 módulos × 5 acciones)            | ✅ OK  | Listado correcto en tabla |
| Crear permiso (`POST /api/permissions`)                | ✅ OK  | `id=26` generado          |
| Editar permiso (`PUT /api/permissions/26`)             | ✅ OK  |                           |
| Eliminar permiso (`DELETE /api/permissions/26`)        | ✅ OK  | Soft delete               |
| Restaurar permiso (`POST /api/permissions/26/restore`) | ✅ OK  |                           |

> ⚠️ **Bug encontrado y corregido en frontend:**  
> El formulario "Nuevo/Editar permiso" tenía el botón "Guardar" siempre deshabilitado.  
> **Causa:** `isValid` era un `computed()` signal que leía `this.formName` siendo `formName` una propiedad plana (`string`). En Angular zoneless/OnPush, un `computed()` que no lee señales reactivas solo se evalúa una vez (con el valor inicial `''`) y nunca vuelve a ejecutarse.  
> **Fix aplicado:** `formName` se convirtió en `signal('')` y `isValid` se cambió a un getter regular (`get isValid()`). El template usa `[ngModel]="formName()" (ngModelChange)="formName.set($event)"`.  
> **Archivos modificados:**
>
> - `recaudify-web/src/app/features/admin/permissions/permission-form/permission-form.ts`
> - `recaudify-web/src/app/features/admin/permissions/permission-form/permission-form.html`

---

### Horarios (`/admin/schedules`)

| Prueba                                           | Estado | Notas                                                       |
| ------------------------------------------------ | ------ | ----------------------------------------------------------- |
| Listar usuarios con opción de gestionar horarios | ✅ OK  | 5 usuarios listados                                         |
| Ver horarios de un usuario (días de semana)      | ✅ OK  | 7 tarjetas (Lunes–Domingo), muestra "Sin horario" si no hay |
| Crear horario (hora inicio, hora fin, visible)   | ✅ OK  | Aparece en tarjeta del día con formato AM/PM                |
| Editar horario                                   | ✅ OK  | Hora actualizada correctamente                              |
| Eliminar horario (con confirm dialog)            | ✅ OK  | Vuelve a estado "Sin horario — acceso bloqueado"            |

#### Control de acceso por horario — pruebas de bloqueo (superadmin configura, admin intenta entrar)

> Contexto: hoy es **miércoles ~19:00 hs**. Usuario `admin` tenía `00:00–23:59` todos los días.

| Escenario                           | Configuración                         | Resultado login admin                                                                               | Estado |
| ----------------------------------- | ------------------------------------- | --------------------------------------------------------------------------------------------------- | ------ |
| Sin día miércoles (eliminado)       | Sin horario en miércoles              | Bloqueado — toast: _"Se acabó tu tiempo laboral, usuario. Intenta ingresar en tu próximo horario."_ | ✅ OK  |
| Miércoles solo mañana `08:00–12:00` | Horario vigente no cubre las 19:00 hs | Bloqueado — mismo mensaje                                                                           | ✅ OK  |
| Miércoles restaurado `00:00–23:59`  | Horario cubre el horario actual       | Login exitoso → redirige a `/dashboard`                                                             | ✅ OK  |

El middleware `check.schedule` en el backend valida el horario **en el momento del login** y devuelve un error 403 que el frontend muestra como toast de error.

---

### Parámetros (`/admin/parameters`)

| Prueba                                              | Estado | Notas                                                  |
| --------------------------------------------------- | ------ | ------------------------------------------------------ |
| Listar parámetros                                   | ✅ OK  | 2 parámetros: `shift-status`, `shift-status-countdown` |
| Crear parámetro (clave, valor, descripción)         | ✅ OK  | Toast "Parámetro creado."                              |
| Editar parámetro                                    | ✅ OK  | Toast "Parámetro actualizado."                         |
| Eliminar parámetro (soft delete con confirm dialog) | ✅ OK  | Toast "Parámetro eliminado."                           |
| Ver parámetros eliminados                           | ✅ OK  | Sección "Ver eliminados" disponible                    |

---

## Resumen

| Módulo     | Total         | ✅ OK  | ⚠️ Bug    | ❌ Falla |
| ---------- | ------------- | ------ | --------- | -------- |
| Auth       | 7             | 7      | 0         | 0        |
| Dashboard  | 2             | 2      | 0         | 0        |
| Usuarios   | 7             | 7      | 0         | 0        |
| Roles      | 4             | 4      | 0         | 0        |
| Permisos   | 5 (API)       | 5      | 1 (front) | 0        |
| Horarios   | 5 + 3 bloqueo | 8      | 0         | 0        |
| Parámetros | 4             | 4      | 0         | 0        |
| **Total**  | **37**        | **37** | **1**     | **0**    |

---

## Parámetros de sistema en BD

Estos parámetros controlan comportamiento del sistema. Edítalos desde `/admin/parameters` o directamente en la BD.

### Estado actual

| ID  | Clave                    | Valor actual | Descripción                                             |
| --- | ------------------------ | ------------ | ------------------------------------------------------- |
| 1   | `shift-status`           | `true`       | Activa/desactiva el control de horarios de acceso       |
| 2   | `shift-status-countdown` | `true`       | Activa/desactiva el conteo regresivo de cierre de turno |
| 4   | `geolocalization_login`  | `true`       | Requiere permiso de geolocalización al iniciar sesión   |

### Cambiar parámetros vía SQL

```sql
-- Desactivar control de horarios (permite acceso libre sin restricción de turno)
UPDATE parameters SET value = 'false' WHERE key = 'shift-status';

-- Reactivar control de horarios
UPDATE parameters SET value = 'true' WHERE key = 'shift-status';

-- Desactivar conteo regresivo de turno
UPDATE parameters SET value = 'false' WHERE key = 'shift-status-countdown';

-- Reactivar conteo regresivo de turno
UPDATE parameters SET value = 'true' WHERE key = 'shift-status-countdown';

-- Desactivar geolocalización en login (permite entrar sin permiso de ubicación)
UPDATE parameters SET value = 'false' WHERE key = 'geolocalization_login';

-- Reactivar geolocalización en login
UPDATE parameters SET value = 'true' WHERE key = 'geolocalization_login';

-- Ver estado actual
SELECT id, key, value, description, deleted_at FROM parameters;

-- Restaurar un parámetro eliminado (soft delete)
UPDATE parameters SET deleted_at = NULL WHERE key = 'nombre-clave';
```

### Cambiar parámetros vía API (requiere token de superadmin o admin)

```bash
# 1. Obtener token
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"superadmin","password":"superadmin1234","latitude":4.6097,"longitude":-74.0817}' | \
  python -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# 2. Listar parámetros
curl -s http://localhost:8000/api/parameters \
  -H "Authorization: Bearer $TOKEN"

# 3. Editar un parámetro (reemplaza {ID} con el id numérico)
curl -s -X PUT http://localhost:8000/api/parameters/{ID} \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"key":"shift-status","value":"false","description":"Control de horarios desactivado"}'
```

### Cambiar parámetros vía Frontend

1. Ir a `http://localhost:4200/admin/parameters`
2. Clic en **Editar** sobre el parámetro deseado
3. Modificar el campo **Valor**
4. Clic en **Guardar**

---

## Datos de prueba generados (para limpiar BD)

Los siguientes registros fueron creados durante las pruebas y pueden eliminarse:

```sql
-- Usuario de prueba (id=5)
DELETE FROM model_has_roles WHERE model_id = 5 AND model_type = 'App\\Models\\User';
DELETE FROM users WHERE username = 'test.usuario';

-- Horario de prueba (fue eliminado durante las pruebas, no requiere acción)
-- Parámetro de prueba (fue eliminado durante las pruebas, verificar si quedó en soft delete)
SELECT * FROM parameters WHERE deleted_at IS NOT NULL;
-- Si existe: DELETE FROM parameters WHERE key = 'test-parametro';
```
