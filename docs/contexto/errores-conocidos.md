# Errores conocidos (gotchas)

> Las trampas que ya te han mordido. Cada una ahorra una hora de Claude (y tuya).

## Los README.md de los subproyectos están desactualizados

Pasa cuando alguien lee `recaudify-api/README.md` para entender el stack real del proyecto. La causa es que ese README menciona Docker y Redis como parte del stack ("Contenedores para desarrollo y producción", "Cache y sesiones"), pero el proyecto real no usa ninguno de los dos: no hay `docker-compose.yml`, y cache, cola y sesión corren sobre el driver `database` según `.env.example`, `config/queue.php` y `config/session.php`. El README quedó del scaffolding inicial y nunca se actualizó. La solución es confiar en `docs/contexto/arquitectura.md`, `.env.example` y los workflows reales de `.github/workflows/` para saber qué infraestructura existe de verdad, no en el README.

## Cobertura de tests del backend está bloqueada

Pasa cuando se quiere medir cobertura de PHPUnit con `php artisan test --coverage`. La causa es que el entorno local (Herd Lite) no tiene instalado PCOV ni Xdebug. Los 150 tests del backend corren y pasan igual, en verde; solo falta el driver de cobertura para medir el porcentaje. Queda pendiente instalar PCOV o Xdebug, como anota `Lista_test.md` en su sección P4.

## El frontend tiene pantallas para entidades que no existen en el backend

Pasa cuando se navega a `/admin/products`, `/admin/rates`, `/admin/sellers` o `/admin/call-reasons` esperando que llamen a un endpoint real de negocio. La causa es que estas pantallas y sus servicios (`products.service.ts`, `rates.service.ts`, `sellers.service.ts`, `call-reasons.service.ts`) se construyeron como catálogo de referencia antes de que exista el modelo backend real, que fue removido intencionalmente según `Lista_test.md` y `NEGOCIO.md` §3/§6. No es un bug para "corregir" agregando el endpoint sin más contexto: el diseño de datos de esas entidades todavía se está discutiendo en `planning.md` y `NEGOCIO.md`. Antes de implementar el backend de Productos, Tarifas, Vendedores o Motivos de gestión conviene releer esas secciones para el modelo desacoplado ya acordado.

## El legacy nunca tuvo control de acceso real

Pasa cuando se está migrando una regla de permisos del legacy y se asume que ya estaba protegida. La causa es que `validarPermiso*` del legacy devuelve `true` siempre, salvo para un usuario hardcodeado en la tabla `Administradores` — el sistema de permisos (`Permisos`/`PermisosUsuarios`) no bloqueaba nada en la práctica. La solución es no copiar el nivel de protección del legacy como referencia y decidir explícitamente el permiso Spatie correcto para cada endpoint nuevo, como queda documentado en `NEGOCIO.md` §13.

## `unique` + `SoftDeletes` en Cliente, gotcha pendiente de resolver

Va a pasar cuando se implemente el CRUD de Cliente y se defina la columna `documento` como única. La causa es que con `SoftDeletes`, el documento de un cliente ya desactivado sigue ocupando el índice único por defecto, lo que bloquearía el alta de un cliente nuevo con ese mismo documento. La solución, todavía no implementada, es usar un índice único compuesto con `deleted_at`, o validar en el Service contra solo los registros no borrados. Está anotado explícitamente en `planning.md` para no resolverlo a mano sin tener en cuenta esta restricción.

## Cosas que parecen rotas pero son a propósito

- `app/Jobs/` y `app/Console/Commands/` están vacíos: no hay colas ni comandos custom todavía, aunque la infraestructura de `jobs`/`failed_jobs` y `queue.php` con driver `database` ya está migrada. Es terreno preparado para el Motor Financiero, no un error.
- `MAIL_MAILER=log`: no se envían correos reales, cualquier "correo" queda solo en el log de Laravel. Es intencional hasta que se configure SMTP, como anota `ideas.txt`.
- `BROADCAST_CONNECTION=log`: no hay websockets ni broadcasting real todavía.
- No existe `app/Policies/`: toda la autorización pasa por middleware `permission:*` de Spatie, no por Policies de Laravel. No conviene agregar Policies sin que el negocio lo pida.
- `routes/console.php` solo tiene el comando `inspire` de ejemplo: no hay `withSchedule()` ni cron configurado todavía.
