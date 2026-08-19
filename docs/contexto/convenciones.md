# Convenciones de código

## Estilo

- **Backend:** Prettier con `@prettier/plugin-php` (config en el `.prettierrc` de la raíz: `printWidth` 120, comillas dobles, trailing commas). Corre automático al guardar `.php` vía hook `PostToolUse` de Claude Code, y otra vez sobre los `.php` modificados al terminar la sesión (hook `Stop`). **No usar `vendor/bin/pint`**: está instalado como dependencia de Laravel pero su preset no coincide con el estilo commiteado (comillas simples), y correrlo reformatea ~120 archivos.
- **Frontend:** Prettier, corre automático al guardar `.ts/.html/.scss/.css` vía el mismo hook, y otra vez sobre todos los archivos modificados al terminar la sesión (hook `Stop`).
- **Lint Angular:** `eslint.config.js` con `@angular-eslint` — selector de componente obligatorio `app-` + kebab-case, directiva `app` + camelCase; reglas `recommended` + `stylistic` de `typescript-eslint`.
- **Imports:** sin regla de orden automatizada detectada (no hay `eslint-plugin-import` ni `perfectionist`); seguir el orden ya presente en cada archivo (Angular/core → librerías → relativos).

## Naming

- **PHP:** `PascalCase` para clases (`AuthService`, `UserRepository`), `camelCase` para métodos, `snake_case` para columnas de BD.
- **TypeScript:** `PascalCase` para clases/componentes/interfaces (sin sufijo `Component`, ej. `Login`, `Users`, `UserForm`), `camelCase` para variables/métodos, `kebab-case` para nombres de archivo (`user-form.ts`, `auth.service.ts`).
- **Selectores Angular:** prefijo `app-` obligatorio (impuesto por ESLint).
- **Rutas backend:** un controlador por dominio en `Http/Controllers/Api/`, un Form Request por acción en `Http/Requests/{Dominio}/`.

## Patrones que SÍ usamos

- Controlador delgado → Form Request valida → Service ejecuta (una responsabilidad por método) → Repository accede a datos → API Resource serializa dentro de `ApiResult` (wrapper uniforme).
- `env()` solo dentro de `config/*.php`, nunca en código de aplicación.
- **Dinero:** todo monto es un entero de pesos colombianos, sin centavos. Columnas `bigInteger`, cast `integer` en el modelo — nunca `decimal` ni `float`. El redondeo (al millar, hacia arriba, como el legacy), la lectura de montos que llegan de fuera y el reparto de un total entre cuotas pasan por `App\Support\Money`, no se reimplementan por módulo.
- **Transacciones:** todo método de Service que escriba en **más de una tabla** va envuelto en `DB::transaction()`. La transacción vive en el **Service**, nunca en el Controller ni en el Repository — el Service es quien conoce la unidad de negocio ("crear cliente con sus direcciones" es un solo hecho, aunque sean cuatro inserts). Los jobs se despachan con `after_commit` (ya activado en `config/queue.php`) para que nunca corran contra datos que todavía no existen.
- Auditoría automática de escritura vía `spatie/laravel-activitylog` + trait `LogsModelActivity` — no reimplementar logging a mano por módulo.
- Frontend: toda petición HTTP pasa por `ApiService` (nunca `HttpClient` directo), URLs `{apiUrl}/{controller}/{action}`.
- Angular zoneless + `ChangeDetectionStrategy.OnPush` + Signals en todo componente; `inject()` en vez de constructor DI; `takeUntilDestroyed(this.destroyRef)` para toda suscripción.
- Un servicio Angular por dominio con signals `items`/`loading`/`showTrashed` y métodos `load()`/`toggleTrashed()`/`remove()`/`restoreItem()` (patrón ya repetido en Users/Roles/Permissions/Parameters — usar igual para módulos nuevos).
- Componente de listado y componente de formulario separados por entidad (`{entity}s.ts` / `{entity}-form.ts`).
- Permisos vía `AuthService.hasPermission()` en frontend y middleware `permission:modulo.accion` de Spatie en backend — dos permisos (`ver`/`gestionar`) por catálogo, no uno por acción.
- Convención de borrado explícita por grupo de entidad (ver `decisiones.md`): no asumir `SoftDeletes` por defecto en entidades nuevas del dominio de cobranza.
- Commits en formato `tipo(scope): descripción` en inglés, imperativo, minúscula (`feat(auth): add force password change flow`) — ver `git log` para el patrón dominante.

## Patrones PROHIBIDOS

- Nada de `HttpClient` inyectado directo en un componente/servicio de feature (todo pasa por `ApiService`).
- Nada de queries Eloquent directas en un controlador — siempre a través de un Repository.
- Nada de escrituras multi-tabla sin transacción (es el bug del legacy: `Clientes.php` inserta cliente, dirección, referencias y pedido uno por uno, sin rollback — si falla el tercero quedan datos huérfanos).
- Nada de `vendor/bin/pint` (ver "Estilo").
- Nada de `$request->validate()` inline — toda validación vive en un Form Request.
- Nada de `any` ni tipos anónimos para modelos de dominio en TypeScript — interfaz/tipo dedicado en `core/interfaces/` o junto al feature.
- Nada de constructor injection en componentes/servicios Angular — solo `inject()`.
- Nada de `NgRx` ni librería de UI de terceros (Material/PrimeNG) — estado con Signals, UI propia.
- Nada de contraseñas o valores de negocio hardcodeados en código (ej. el legacy tenía `Cobranza123`) — todo configurable vía el módulo `Parameters`.

## Tests

- **Backend:** PHPUnit, `tests/Feature/{Dominio}/` para tests de integración HTTP (login, CRUD end-to-end), `tests/Unit/Services|Middleware|Resources|Models/` para lógica aislada. Un archivo `{Entidad}Test.php` por controlador/servicio.
- **Frontend:** Vitest, un `*.spec.ts` junto a cada archivo fuente (`auth.service.ts` → `auth.service.spec.ts`). Cobertura esperada: todo servicio, guard, interceptor, directiva y componente con lógica propia.
- Qué se testea sí o sí: happy path + al menos un caso de error (401/403/404/422) por endpoint, normalización de inputs (ej. username a minúsculas), y computed/signals derivados en componentes (ej. `canCreate`/`canEdit`).
- Meta declarada en `Lista_test.md`: 85 %+ de cobertura en ambas capas (cobertura de backend hoy bloqueada por falta de PCOV/Xdebug — ver `errores-conocidos.md`).
- Documentar cada archivo de test nuevo en `recaudify-api/pruebas.md` / `recaudify-web/pruebas.md` (tabla prueba → descripción) y actualizar `Lista_test.md`.

## Commits

- Formato observado (no forzado por commitlint, pero dominante en `git log`): `tipo(scope): descripción` — tipos usados: `feat`, `fix`, `refactor`, `chore`, `style`, `test`, `docs`, `ci`, `revert`.
- Scope = módulo o capa afectada (`auth`, `api`, `web`, `activity`, `planning`, `root`).
- **Nunca commitear sin que el usuario lo pida explícitamente** (regla de `CLAUDE.md`, reforzada por el hook `Stop` que valida que `planning.md` esté al día antes de terminar).
