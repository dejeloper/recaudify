<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Recaudify API',
    version: '1.0.0',
    description: 'API REST para la gestión de clientes, cartera y cobranza.',
)]
#[OA\Server(
    url: L5_SWAGGER_CONST_HOST,
    description: 'Servidor activo',
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
)]
#[OA\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'token',
)]
#[OA\Tag(
    name: 'Auth',
    description: <<<'MD'
Autenticación basada en JWT (HS256).

- `POST /auth/login` devuelve un `access_token` (TTL 15 min) y un `refresh_token` (TTL 4 h).
- Adjuntar el token en cada request: `Authorization: Bearer <token>`.
- Antes de que expire, renovarlo con `POST /auth/refresh` usando el refresh token.
- `POST /auth/logout` invalida ambos tokens en el servidor.
MD
)]
#[OA\Tag(
    name: 'Roles',
    description: <<<'MD'
Un **rol** es un perfil de acceso que agrupa permisos (ej. `administrador`, `cobrador`).
Cada usuario tiene un rol principal que determina qué puede hacer en el sistema.

Los roles admiten soft delete: se pueden eliminar y restaurar sin perder su historial.
MD
)]
#[OA\Tag(
    name: 'Permisos',
    description: <<<'MD'
Un **permiso** representa una acción concreta sobre un módulo, con el formato `modulo.accion`
(ej. `clientes.ver`, `clientes.exportar`).

Los permisos se asignan a roles. También se pueden asignar permisos adicionales directamente
a un usuario para casos puntuales, sin cambiar su rol.
MD
)]
#[OA\Tag(
    name: 'Usuarios',
    description: <<<'MD'
Gestión de cuentas de acceso al sistema.

Desactivar un usuario (`DELETE /users/{id}`) aplica soft delete: la cuenta queda inactiva
pero recuperable. El login queda bloqueado hasta que se restaure.

Ver también: **Horarios** para restringir en qué franjas horarias puede iniciar sesión cada usuario.
MD
)]
#[OA\Tag(
    name: 'Horarios',
    description: <<<'MD'
Restricciones de acceso por día y franja horaria para cada usuario.

Si un usuario tiene horarios definidos, el middleware `check.schedule` bloquea cualquier
request fuera de esas franjas con 403. Si no tiene horarios, el acceso es libre.

- Los horarios se crean bajo `POST /users/{userId}/schedules`.
- Las mutaciones individuales (editar, eliminar, restaurar) usan `PUT/DELETE/POST /schedules/{id}`.
- Al restaurar, se valida que no exista ya un horario activo para el mismo día (409).
MD
)]
#[OA\Tag(
    name: 'Parámetros',
    description: <<<'MD'
Configuración general del sistema almacenada como pares clave/valor (ej. `max_intentos_login = 5`).

Solo accesible por usuarios con permisos del scope `parametros.*`. Admite soft delete.
MD
)]
class ApiInfo {}
