# Arquitectura

## En una frase

Recaudify es un SaaS de cobranza (gestión de deudores, pagos y gestiones de cobro) que reescribe una app legacy en CodeIgniter 3, con backend Laravel 13 (API REST) y frontend Angular 21 (SPA); hoy el backend solo tiene implementado el módulo de autenticación/administración (usuarios, roles, permisos, parámetros, auditoría) y aún no el dominio de cobranza en sí.

## Stack

- **Lenguaje / runtime:** PHP 8.3+ (backend), TypeScript / Node (frontend, build con pnpm 11)
- **Framework principal:** Laravel 13 (API) + Angular 21 (SPA, standalone components, sin NgRx ni librería de UI)
- **Base de datos:** MySQL 8 (dos bases: `recaudify_staging` y `recaudify_prod`)
- **Servicios externos:** ninguno configurado actualmente. `.env.example` usa drivers "locales" para todo: `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`, `MAIL_MAILER=log`, `FILESYSTEM_DISK=local`. No hay Redis, AWS/S3, Twilio, pasarela de pagos ni Pusher/Reverb — los stubs de `config/services.php` y `config/filesystems.php` (postmark, ses, slack, s3) son el scaffolding por defecto de Laravel, sin paquetes instalados que los respalden.
- **Despliegue:** sin Docker. Frontend en Vercel; backend en un VPS único (Hostinger, Ubuntu 24.04) con Nginx + PHP-FPM + MySQL + Certbot, desplegado por SSH vía GitHub Actions (`deploy-staging.yml` / `deploy-prod.yml`) al hacer push a `develop` / `main`.
- **Documentación de API:** Swagger/OpenAPI generado con `darkaonline/l5-swagger` a partir de atributos `#[OA\...]` en los controladores.

## Mapa de carpetas

Backend — `recaudify-api/app/`:

- `Http/Controllers/Api/` → controladores delgados (Auth, User, Role, Permission, Parameter, UserSchedule, Activity, LoginAudit)
- `Http/Requests/` → Form Requests, una carpeta por dominio, validación fuera de los controladores
- `Http/Resources/` → API Resources para serializar respuestas
- `Http/Responses/ApiResult.php` → wrapper uniforme de respuesta HTTP
- `Http/Middleware/` → `SetJwtFromCookie` (JWT vía cookie httpOnly), `ForcePasswordChange`, `CheckUserSchedule`, `LogHttpRequests`
- `Services/` → lógica de negocio, un servicio por dominio (Auth, User, Role, Permission, Parameter, PasswordPolicy, PasswordReset, Logging, Activity, LoginAudit, UserSchedule)
- `Repositories/` → acceso a datos, uno por dominio (capa entre Service y Model)
- `Models/` → Eloquent models + carpeta `Concerns/` (ej. trait `LogsModelActivity`)
- `Enums/` → `ParameterCast`, `ParameterType`
- `OpenApi/` → clases de anotación Swagger por dominio
- `Jobs/`, `Console/Commands/` → existen pero están vacías (sin colas ni comandos custom todavía)

Frontend — `recaudify-web/src/app/`:

- `core/services/` → un servicio HTTP por dominio (auth, users, roles, permissions, products, rates, sellers, call-reasons, schedules, parameters, activities, audit, login-audits, config, geolocation, shift-status, toast) — toda petición pasa por `ApiService`
- `core/guards/` → `authGuard`/`authOnlyGuard`/`guestGuard` (auth.guard.ts), `admin.guard.ts`, `permission.guard.ts`
- `core/interceptors/` → `auth.interceptor.ts` (adjunta el token), `error.interceptor.ts`
- `core/interfaces/` → modelos TypeScript por dominio (uno por entidad)
- `core/components/` → componentes compartidos (`Spinner`, `ToastContainer`)
- `features/auth/` → `login/`, `change-password/`
- `features/admin/` → CRUD por dominio: users, roles, permissions, schedules, products, rates, sellers, call-reasons, parameters, activity (solo lectura), access-log (solo lectura), admin-dashboard
- `features/dashboard/` → dashboard general
- `layout/app-shell/` → shell autenticado que envuelve las rutas protegidas

Raíz del repo:

- `cobranza_files/` → app legacy en CodeIgniter 3, se mantiene solo como referencia de qué replicar
- `docs/`, `funcionalidades.md`, `Lista_test.md`, `planning.md`, `plan-ejecucion.md`, `NEGOCIO.md` → documentación de negocio/planificación, no código
- `vps_plan.md`, `vps_deploy_guide.md` → infraestructura y despliegue del VPS
- `.github/workflows/` → pipelines de despliegue por SSH

## Flujo de datos

El navegador llama a `ApiService` (Angular), que compone la URL como `{apiUrl}/{controller}/{action}` y agrega headers de seguridad; el `authInterceptor` adjunta el JWT. La petición llega al backend Laravel, pasa por el guard `api` (JWT vía `php-open-source-saver/jwt-auth`, con middlewares globales `SetJwtFromCookie`, `ForcePasswordChange`, `CheckUserSchedule`) y por el middleware `permission:*` de Spatie para autorización. El controlador valida con un Form Request, delega en un Service, que usa un Repository para leer/escribir en MySQL vía Eloquent, y la respuesta se serializa con una API Resource dentro de `ApiResult`. Casi toda escritura queda registrada por `spatie/laravel-activitylog` (trait `LogsModelActivity`).

## Lo que NO existe (y no hay que crear sin pedirlo)

- No hay Docker ni docker-compose — el despliegue es manual sobre el VPS (Nginx + PHP-FPM + MySQL).
- No hay Redis; cache, cola y sesión usan el driver `database`.
- No hay websockets/broadcasting real (`BROADCAST_CONNECTION=log`) ni colas en uso (`app/Jobs/` vacío, aunque `queue:listen` está en el script de dev).
- No se envían correos reales (`MAIL_MAILER=log`).
- No hay integración con AWS/S3, Twilio/SMS ni pasarelas de pago — los stubs de `config/services.php`/`filesystems.php` son scaffolding sin paquete instalado.
- No hay Policies de Laravel (`app/Policies` no existe); la autorización es solo middleware `permission:*` de Spatie.
- No hay tareas programadas/cron (`routes/console.php` solo tiene el comando `inspire`, sin `withSchedule()`).
- **No existe todavía el dominio de cobranza en el backend**: no hay tablas ni endpoints de clientes/deudores, pedidos/deudas, pagos, gestiones de llamadas, vendedores, tarifas ni motivos de llamada. El frontend ya tiene features y servicios para `products`, `rates`, `sellers`, `call-reasons` que llaman endpoints que aún no existen en `routes/api.php` — es un gap conocido, no un error a "corregir" sin más contexto.
- No hay librería de UI ni gestor de estado en el frontend (sin Material/PrimeNG, sin NgRx) — el estado es local vía Signals.
