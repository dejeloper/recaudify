# Glosario y entidades

> Términos tal como se usan en Recaudify (no en general). Fuente: `NEGOCIO.md`, `planning.md`, `funcionalidades.md`, código actual.

## Términos del dominio

- **Cobranza** → nombre del dominio de negocio completo: gestión de deudores, pagos y gestiones de cobro. Módulo aún no implementado en el backend actual (solo existe el armazón administrativo).
- **Legacy** → la app original en CodeIgniter 3 (`cobranza_files/`), llamada internamente "Católikas Cobranza". Se mantiene solo como referencia de qué replicar, no se toca.
- **Cartera** → conjunto de Clientes/Contratos asignados a un Cobrador. También se usa como sinónimo del estado de mora agregado ("cartera en mora" = dinero en mora, distinto de "clientes en mora" = personas).
- **Estado de cartera** → clasificación de un Contrato según días de atraso (al día / próximo vencimiento / mora temprana / mora avanzada / prejurídico / jurídico / castigado / paz y salvo), calculada por el Motor Financiero según el catálogo "Días de cambio de estado". Superset de los 4 estados que tenía el legacy.
- **Indicador agregado de estado de cartera (del Cliente)** → valor calculado de lectura (no columna propia): el **peor** estado entre los contratos activos de un cliente. No es un promedio.
- **Motor Financiero** → servicio único (no implementado aún) que calcula saldo/cuota/capital/interés/descuento/mora y clasifica el estado de cartera. Ningún otro módulo reimplementa esta lógica.
- **Deuda()** → función global del legacy que recalculaba estado de cartera como side-effect en cada request de `Clientes`/`Pagos`/`LlamadasDia`. En Recaudify se reemplaza por un job programado del Motor Financiero.
- **Evento (de venta)** → en el nuevo modelo: combinación Vendedor + Canal/Organización + Zona/Barrio (texto libre) + Tipo de evento + Fecha, vinculada al Contrato (no al Cliente). En el legacy, "Eventos" mezclaba vendedor + "iglesia" (canal) + barrio en una sola tabla.
- **Iglesia** → nombre legacy del concepto que en Recaudify se llama **Canal/Organización** (catálogo independiente).
- **Interacción** → el contacto real con un cliente (llamada/visita/correo/WhatsApp/SMS): una fila por contacto, sin importar cuántos contratos toque.
- **Gestión (por contrato)** → el efecto de una Interacción sobre un Contrato puntual: motivo, resultado, referencia a la Interacción y al Contrato. Puede haber varias Gestiones (una por contrato) por cada Interacción.
- **Compromiso** → promesa de pago/acuerdo/reprogramación, entidad propia vinculada a una Gestión (no a la Interacción). Una Interacción puede generar varios Compromisos.
- **Cadencia de cobro** → ciclo de contacto regular: una Interacción por mes por Cliente (no por Contrato).
- **Habilitado** → columna del legacy que mezclaba "ocultar", "estado de negocio" y "auditoría" en un solo booleano. Reemplazada en Recaudify por la "Convención de borrado y estado" (ver `decisiones.md`).
- **Tenant** → tenant único mencionado en el contexto de "documento único por tenant" para Cliente; no hay multi-tenancy implementada como feature (`Sucursales` es una sola empresa con varias sedes, explícitamente distinto de multi-tenancy).

## Entidades principales (implementadas hoy)

- **User** → usuario del sistema (administración/operación), con roles Spatie. Campo `email` nullable. `active`/`SoftDeletes` para habilitar/deshabilitar. Relacionado con `UserSchedule` (horario de acceso permitido) y `LoginAudit`.
- **Role / Permission** → roles y permisos Spatie. Formato de permiso `modulo.accion` (ej. `users.view`). Roles conocidos: `superadmin`, `administrador`, `supervisor`, `verificador`, `cobrador`, `vendedor`, `auxiliar`, `coordinador`.
- **Parameter** → parámetro de configuración de negocio tipado (`ParameterType`, `ParameterCast`), reemplaza valores hardcodeados del legacy (contraseña de reseteo, política de contraseñas, campo de login, límites de paginación, duración de toast, etc.).
- **UserSchedule** → horario permitido de acceso por usuario; el middleware `CheckUserSchedule` bloquea login fuera de horario (excepto superadmin).
- **LoginAudit** → registro de intentos de login (éxito/fallo), IP, geolocalización (lat/long/accuracy).
- **Activity** (via `spatie/laravel-activitylog`) → log genérico de auditoría de cambios (trait `LogsModelActivity`), inmutable, sin mecanismo de borrado.

## Entidades del dominio de negocio (diseñadas en `NEGOCIO.md`, no implementadas aún)

- **Cliente** → identidad del deudor: documento (único por tenant), nombre, tipo de documento, observaciones versionadas. Sub-recursos 1:N reales: Direcciones, Teléfonos, Referencias (el legacy los limitaba a 1/3/3 fijos).
- **Contrato** (renombre de "Pedido" del legacy) → N por Cliente, ciclo de vida explícito borrador/activo/suspendido/cancelado/finalizado.
- **Vendedor, Producto, Tarifa** → catálogos; Tarifa lleva historial de cambios (el legacy solo sobreescribía).
- **Cobrador** → entidad independiente del Usuario, representa una cartera; asignación Usuario↔Cobrador con historial, y Cliente↔Cobrador.
- **Pago / Pago Programado** → registro de cobro real vs. programación; sin borrado lógico, reverso/descarte como evento propio.
- **Devolución** → evento de historial que notifica al Motor Financiero (no escribe el estado del contrato directamente).

## Siglas y nombres internos

- **RBAC** → Role-Based Access Control, implementado con Spatie Laravel Permission.
- **JWT** → autenticación stateless vía `php-open-source-saver/jwt-auth`.
- **ApiResult** → wrapper de respuesta HTTP uniforme (`app/Http/Responses/ApiResult.php`), con factories `success()/created()/failure()/unauthorized()/notFound()/forbidden()/empty()`.
- **ApiService** → única abstracción HTTP del frontend Angular; construye URLs `{apiUrl}/{controller}/{action}`.
- **VPS** → el servidor único de Hostinger donde corre el backend (staging y prod en el mismo VPS, rutas separadas).
