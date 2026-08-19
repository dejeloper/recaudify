# Recaudify

Plataforma de gestión de cobranza y pagos (Laravel + Angular). Reescritura y desacople de Católikas Cobranza (CodeIgniter 3). Contexto adicional en `funcionalidades.md` (inventario del legacy) y `NEGOCIO.md` (análisis de desacople por módulo).

Si se pide hacer alguna acción de este listado y está destinado a este directorio, se puede hacer todo sin solicitar permisos (excepto acciones de Git)

## Plan

### Arquitectura y Seguridad (base de plataforma)

#### Backend

- [x] Estructura Laravel, PHP 8.3, MySQL, migraciones
- [x] Convención de transacciones: `DB::transaction()` en el Service para toda escritura multi-tabla
- [x] Aplicar transacciones a `UserService::create/update` y `RoleService::create/update/delete`
- [x] `after_commit` activado en `config/queue.php`
- [x] Formateo PHP: hooks `PostToolUse` y `Stop` apuntan a Prettier, no a Pint
- [x] Convención de dinero: enteros COP (sin centavos), columnas `bigInteger`, helper `App\Support\Money`
- [x] `Money`: redondeo al millar, parseo de montos con formato y reparto exacto entre cuotas
- [x] JWT + Refresh Tokens
- [x] CORS + bypass de superadmin por Gate (autorización real por permisos Spatie, sin Policies)
- [x] Swagger
- [x] Form Requests
- [x] Soft Deletes
- [x] Formato estándar de respuestas + paginación
- [x] Auditoría genérica con spatie/activitylog
- [x] Seeders iniciales
- [x] Usuarios: crear, editar, desactivar, restaurar
- [ ] Roles: administrador, supervisor, verificador, cobrador, vendedor, auxiliar
- [x] Permisos: CRUD
- [x] Login, Logout, Refresh
- [x] Auditoría de accesos, cambios, eliminaciones, acciones críticas
- [ ] Parámetros de negocio: días de mora, consecutivos
- [x] Seeder: roles operativos gestor, recaudador, vendedor, cerrador
- [ ] Tabla `sucursales` + `sucursal_id` (nullable) en clientes, contratos, pagos, gestiones y usuarios
- [ ] Permiso "ver todas las sucursales" (scoping, no multi-tenancy)

#### Frontend

- [x] Angular, Login, JWT, Guards, Interceptors
- [x] Diseño responsive
- [x] Administración: Usuarios (listar/crear/editar/desactivar/restaurar)
- [x] Administración: Roles (listar/crear/editar/desactivar/restaurar)
- [x] Administración: Permisos (listar/crear/editar/desactivar/restaurar)

### Armazón administrativo (pre-negocio)

#### Autenticación y parámetros de acceso

- [x] Método de login configurable (username o correo)
- [x] Password de reseteo fija o auto-generada
- [x] Política de contraseñas configurable
- [ ] Delegación temporal de permisos / suplencias
- [x] `email_notifications_enabled` marcado como no editable mientras no exista SMTP
- [ ] Envío de password de reseteo por correo

#### Menús y navegación

- [x] Menú dinámico por permisos
- [x] Menú configurable desde Settings (builder de menú)
- [ ] Favoritos / accesos rápidos personalizados
- [ ] Menú con contadores/badges en vivo
- [ ] Búsqueda global / paleta de comandos (Ctrl+K)
- [x] Breadcrumbs dinámicos
- [ ] Historial de navegación reciente
- [ ] Menú distinto por rol/perfil (layout)
- [ ] Atajos de teclado configurables
- [x] Menú colapsable / modo compacto

#### Logs y auditoría

> Detalle: `docs/contexto/decisiones.md` → Auditoría técnica vs historia de negocio

- [x] Registro de accesos (IP, dispositivo, fecha) por login exitoso y fallido
- [ ] Visor de logs de sistema en UI (sin prioridad)
- [ ] Correlación de intentos fallidos → alerta automática in-app
- [x] Comando programado de purga de logs vencidos
- [ ] Exportar logs filtrados a CSV/Excel
- [ ] Nivel de verbosidad por canal configurable desde Parameters
- [x] **Trazabilidad por request-id:** middleware `AssignRequestId` que genera
- [x] El id que llega de afuera se valida antes de usarse
- [ ] Frontend: enviar `X-Request-Id` propio y mostrarlo en los mensajes de error
- [ ] Alerta de intentos fallidos de login por correo
- [x] Diff visual de cambios en el feed de actividad
- [ ] Separar auditoría técnica (`activity_log`) de la historia de negocio (tabla propia)
- [x] Congelar el causer en `activity_log`: guardar id, username y nombre como snapshot, no solo la FK
- [x] El causer congelado sobrevive al borrado o renombrado del usuario
- [ ] Motivo obligatorio y registrado en toda acción que pase por autorización
- [x] API: filtro por rango de fechas en el listado de actividad
- [x] Índice en `created_at` de `activity_log`
- [x] Endpoint de API para purgar logs vencidos (única vía de borrado)
- [x] Corregir el trait de auditoría: importaba namespaces inexistentes del paquete
- [x] Corregir `dontLogEmptyChanges()` → `dontSubmitEmptyLogs()` (método inexistente en la v5)
- [x] Corregir `RoleService`: `activity()->withChanges()` → `withProperties()` (método inexistente)
- [x] Alinear la tabla `activity_log` con el paquete: quitar `attribute_changes`, agregar `batch_uuid`
- [ ] Interfaz: filtro por rango de fechas en la pantalla de actividad
- [ ] Interfaz: acción de purga (con confirmación y vista previa de cuántos registros elimina)
- [x] Cron diario que ejecuta el comando de purga
- [x] Alcance: la **lectura** del log es global

#### Dashboard y métricas

- [x] Health check / endpoint de estado
- [ ] Dashboard con KPIs reales de plataforma
- [ ] Incorporar una librería de gráficas (ej. ngx-charts o Chart.js)
- [ ] Gráfica de logins por hora/día de la semana
- [ ] Mapa de accesos geográfico
- [ ] Widget "usuarios conectados ahora"
- [ ] Tablero de salud del sistema
- [ ] Métricas de uso de API por endpoint
- [x] `/api/health` público, con estados ok/degraded/down, 503 solo si falla BD, caché o almacenamiento
- [x] `/api/health` y `/up` excluidos del canal de log `http`
- [ ] Configurar el chequeo de uptime externo contra `/api/health` en el VPS

#### Sesiones y seguridad

- [x] Bloqueo por intentos fallidos de login (rate limiting / lockout)
- [x] Gestión de sesiones activas
- [x] Expiración de sesión por inactividad configurable
- [x] Deslogueo proactivo por inactividad en el frontend
- [x] Listado de sesiones/tokens activos + revocación remota
- [x] Revocar todas las sesiones de un usuario específico desde el panel admin
- [x] Bloqueo tras intentos fallidos (rate limit + lockout)
- [ ] Panel de administración de rate limiting
- [ ] Detección de acceso desde ubicación/IP inusual
- [ ] Modo "vista previa como otro usuario" (impersonar) para administrador/soporte
- [ ] Confirmación por correo de acceso desde dispositivo nuevo

#### Notificaciones

> Detalle: `docs/contexto/decisiones.md` → Notificaciones

- [ ] Sistema de notificaciones in-app (campana con contador)
- [ ] Centro de notificaciones (histórico)
- [ ] Plantillas de correo / Mailables editables
- [ ] WhatsApp como canal de aviso

#### Configuración y ajustes

- [ ] Panel de configuración general (Settings) unificado
- [ ] ~~Exportación genérica a CSV/Excel~~
- [ ] Panel de feature flags
- [x] Modo mantenimiento con aviso configurable
- [x] Middleware `CheckMaintenanceMode` con permiso `maintenance.bypass` y parámetros de alcance y mensaje
- [x] Las tareas programadas se saltan mientras el mantenimiento está activo
- [x] `/api/health` y `/api/auth/*` siguen respondiendo en mantenimiento
- [ ] Frontend: pantalla de aviso cuando la API responde 503 con `data.maintenance`
- [ ] Backups automatizados con notificación de resultado
- [ ] Panel de ajustes generales de plataforma

#### Experiencia de usuario y observabilidad

- [ ] Internacionalización (i18n) de textos de UI
- [ ] Theming claro/oscuro
- [ ] Auditoría de accesibilidad (a11y) básica

### Infraestructura de jobs y colas

> Detalle: `docs/contexto/decisiones.md` → Infraestructura de Jobs

#### Jobs

- [ ] Panel de jobs fallidos: listar, ver error, reintentar o descartar (sin prioridad)
- [ ] Reintentos automáticos configurables por tipo de job
- [ ] Notificación cuando un job falla repetidamente
- [ ] Notificación por correo cuando un job falla repetidamente

#### Schedule

- [x] Programación de comandos (`schedule`) desde el día uno
- [x] Comando `activity:purge` (con `--days` y `--dry-run`), programado a diario
- [x] Parámetro `activity_log_purge_time` (HH:MM) para cambiar la hora del cron sin redesplegar
- [x] Validación por clave en el módulo de Parámetros
- [x] Usuario `sistema` (inactivo, rol `sistema` con solo `audit.purge`) que firma las tareas automáticas
- [ ] Documentar en `vps_deploy_guide.md` la entrada de cron `* * * * * php artisan schedule:run`

### Convención de borrado y estado

> Detalle completo: `docs/contexto/decisiones.md` → Convención de borrado y estado
"ocultar", "estado de negocio" y "auditoría" en una sola columna (`Habilitado`).

#### SoftDeletes (cuando "eliminar" es solo "dejar de aparecer en listados activos, recuperable")

- [ ] Aplicar `SoftDeletes` a Cliente, Producto, Tarifa y catálogos genéricos
- [ ] Resolver el gotcha de `unique` + `SoftDeletes` en Cliente (`documento`)

#### Cuando NO aplica SoftDeletes — usar estado explícito o no borrar nunca

- [ ] Contrato: ciclo de vida explícito como mecanismo principal, no `SoftDeletes`
- [ ] Pago y Pago Programado: sin borrado lógico; el reverso es un evento vinculado
- [ ] Devolución, Gestión y Compromiso: sin borrado lógico; se corrigen con otro evento
- [x] Log de auditoría inmutable por registro: sin borrado individual, solo purga por retención

#### Cuando SÍ se puede borrar físico (datos sin realidad de negocio todavía)

- [ ] Definir política de borrado físico para datos transitorios (borradores, importaciones fallidas)

### Ciclo de vida y estados

> Detalle: `docs/contexto/decisiones.md` → Motor de estados genérico

- [x] Tabla `states` (entidad, clave, nombre, inicial, final, color, ícono)
- [x] Tabla `state_transitions` (de → a, permiso, automática, exige autorización, exige motivo)
- [x] Service que ejecuta y valida las transiciones
- [x] Registrar cada cambio de estado en auditoría (quién, cuándo, por qué)
- [ ] Aplicar el motor a Cliente, Contrato, Pago, Gestión y Compromiso (bloqueado: los modelos no existen)
- [x] Seeders de estados y transiciones por entidad
- [x] Trait `HasState` para entidades con ciclo de vida
- [x] Permisos `states.{view,create,edit,delete,restore}`
- [ ] Aplicar `HasState` a Cliente, Contrato, Pago y Compromiso cuando existan esos modelos
- [ ] Enganchar `requires_authorization` con el módulo de Autorizaciones (hoy se recibe como parámetro)
- [x] Endpoints REST de estados
- [x] Reglas de integridad del catálogo
- [x] Documentación OpenAPI de estados y transiciones
- [ ] Pantalla de administración de estados y transiciones (frontend)
- [ ] Pantalla de administración de estados y transiciones

### Autorizaciones

> Detalle: `docs/contexto/decisiones-negocio.md` → Autorizaciones

- [ ] Tabla `autorizaciones`
- [ ] Tipos de solicitud como catálogo (quién puede aprobar cada uno)
- [ ] Tipo: reverso de pago
- [ ] Tipo: descuento / condonación / rebaja (con su efecto sobre el saldo del contrato)
- [ ] Tipo: cambio manual de estado
- [ ] Tipo: venta a cliente con deuda
- [ ] Tipo: uso de tarifa histórica
- [ ] Bandeja de solicitudes pendientes para admin/coordinador

### Catálogos base y parametrización

> ⏸️ APARCADO. Detalle: `docs/contexto/decisiones.md` → Catálogos

#### Catálogos

> Detalle: `docs/contexto/decisiones.md` → Catálogos

- [ ] CRUD genérico compartido
- [ ] Catálogo: Tipos de documento
- [ ] Catálogo: Tipo de contrato
- [ ] Catálogo: Motivos de gestión (con campo color)
- [ ] Catálogo: Vendedores
- [ ] Catálogo: Eventos
- [ ] Catálogo: Tipo de producto
- [ ] Catálogo: Tipo de eventos
- [ ] Catálogo: Cobradores
- [ ] Catálogo: Métodos de pago
- [ ] Catálogo: Tipos de observación (contacto, acuerdo, incidencia, interna…)
- [ ] Catálogo: Fórmulas de pago mínimo (8 registros, solo 2 implementadas)
- [ ] Catálogo: Canales de gestión (llamada, SMS, WhatsApp, correo)
- [ ] Catálogo: Clasificación de clientes
- [ ] Catálogo: Sucursales (una sola empresa con varias sedes físicas
- [ ] Catálogo: Días de cambio de estado

#### Parámetros nuevos

> Los umbrales de mora son el catálogo "Días de cambio de estado", no un parámetro suelto.

- [ ] Días de vencimiento de un pago programado antes de descartarse automáticamente (legacy
- [ ] Ventana de validez de una gestión de llamada antes de inhabilitarse (legacy
- [ ] Redondeo del pago mínimo por mora (legacy: al millar, fijo)
- [ ] Interés de mora: activo/inactivo, porcentaje y periodicidad (fijo/diario/semanal/mensual)
- [ ] Fórmula de pago mínimo a usar (elige del catálogo de fórmulas)
- [ ] Cadencia de gestión: fija o por estado de mora (por defecto: por estado de mora)
- [ ] Consecutivo de recibos: global, con o sin rastreo por sucursal, automático o manual
- [ ] Reverso de pago: si exige autorización, ventana de tiempo permitida y rol autorizado
- [ ] Reparto de un pago entre varios contratos: más viejo / más vencido / lo elige el cobrador
- [ ] Permitir vender con tarifa histórica (por defecto sí, siempre con autorización)
- [ ] Validación de contrato obligatoria u opcional
- [x] Password por defecto al resetear contraseña, parametrizable

### Vendedores, Productos y Tarifas

#### Vendedores

- [ ] Ya cubierto por el catálogo genérico (ver "Catálogos base y parametrización")

#### Productos

- [ ] Crear producto
- [ ] Editar/actualizar producto (el legacy nunca tuvo `update`, solo alta y lookup por código
- [ ] Listar productos
- [ ] Activar/desactivar producto (`SoftDeletes`)
- [ ] Categoría de producto (catálogo Tipo de producto, Fase 1)

#### Tarifas

- [ ] CRUD Tarifas por producto (cuotas, valor, descuento) (`SoftDeletes`)
- [ ] Historial de cambios de tarifa (el legacy solo sobreescribe, acá se versiona)
- [ ] Versionado de tarifa: editar crea versión nueva, nunca sobrescribe
- [ ] Snapshot de tarifa en la línea de contrato (precio, cuotas y valor de cuota congelados)
- [ ] Seleccionar una versión histórica de tarifa al armar el contrato (requiere autorización)

### Clientes

#### CRUD Cliente

- [ ] `SoftDeletes` con índice único compuesto
- [ ] Documento único por tenant
- [ ] Nombre, tipo de documento
- [ ] Observaciones versionadas (historial, no concatenar un string como el legacy)
- [ ] Observaciones con categoría (`tipo_observacion_id`) y visibilidad (todos / solo coordinación)
- [ ] Observaciones append-only: no se editan ni se borran
- [ ] Campos compatibles con Factus: tipo de documento DIAN, régimen, email, municipio y departamento

#### Sub-recursos de Cliente

- [ ] Direcciones (relación 1\:N real
- [ ] Teléfonos (relación 1\:N real — el legacy fuerza 3 campos fijos)
- [ ] Referencias (relación 1\:N real — el legacy limita a 3)

#### Identidad y búsqueda

- [ ] Detección de duplicados (no existe en el legacy)
- [ ] Fusión de duplicados
- [ ] Búsqueda unificada por nombre/documento/teléfono/dirección/estado
- [ ] Vista 360° / Timeline de solo lectura
- [ ] Tabla de eventos de negocio que alimenta la Vista 360° (independiente de `activity_log`)
- [ ] Registrar como evento de negocio
- [ ] Indicador agregado de estado de cartera (valor calculado de lectura, no columna propia)
- [ ] Asignación Cliente↔Cobrador (no Cliente↔Usuario directo — ver módulo Cartera/Cobranza)

### Evento de venta y Contratos

#### Evento de venta

- [ ] Entidad Evento de venta (catálogo "Eventos"

#### Contratos

- [ ] CRUD Contratos, N por cliente
- [ ] Nunca fusionar una compra nueva dentro de un contrato existente con saldo (regla confirmada)
- [ ] Ciclo de vida explícito
- [ ] Cambiar tarifa (dispara recálculo en el Motor Financiero)
- [ ] Cambiar fecha de cobro
- [ ] Agregar/quitar producto del contrato (recalcula total vía Motor Financiero, no a mano)
- [ ] Campos `periodicidad_dias` y `dia_de_cobro` en el contrato
- [ ] Generar plan de cuotas explícito al crear el contrato (una fila por cuota)
- [ ] Regenerar cuotas futuras si cambia la periodicidad, con trazabilidad
- [ ] Alerta al crear contrato si el cliente ya tiene deuda + autorización para continuar
- [ ] Estado `pendiente_validación` con checklist manual
- [ ] Campos `vendedor_id` y `cerrador_id` (nullable) en el contrato

### Motor Financiero

#### Cálculo

- [ ] Calcular saldo, cuota, capital, interés, descuento y mora de un contrato
- [ ] Recalcular saldo tras pago, reverso, cambio de tarifa o devolución
- [ ] Clasificar estado de cartera según el catálogo "Días de cambio de estado"
- [ ] 5 estados de cartera fijos
- [ ] Implementar fórmula `CUOTAS_VENCIDAS`
- [ ] Implementar fórmula `MINIMO_CONTRACTUAL` (fórmula legacy, redondeo al millar)
- [ ] Cálculo de interés de mora, apagado por defecto
- [ ] Guardar saldo negativo (sobrepago) sin procesarlo

#### Automatización

- [ ] Job programado en background que recalcula cartera y cambia estados
- [ ] DataCrédito como transición de estado más del Motor
- [ ] Cálculo de pago mínimo por mora (vive acá, no en Pagos)
- [ ] Endpoint de API que dispara el recálculo de cartera
- [ ] Cron que llama a ese endpoint
- [ ] Botón en UI para ejecutar el recálculo manualmente

### Cartera / Cobranza

#### Cobrador (entidad independiente del Usuario)

- [ ] CRUD básico de Cobrador ya cubierto por el catálogo genérico
- [ ] Asignación Usuario↔Cobrador con historial (permite reasignar personal sin tocar cliente por cliente)
- [ ] Asignación Cliente↔Cobrador
- [ ] Mantener el Role Spatie "cobrador" solo como permiso de sistema, separado de la identidad operativa
- [ ] **Invariante confirmada, sin excepción**

#### Gestión

> Sin borrado lógico: son eventos de historial. Detalle: `docs/contexto/decisiones.md`

- [ ] **Interacción** (el contacto real)
- [ ] **Gestión por contrato** (el efecto de la Interacción sobre un Contrato puntual)
- [ ] Listado de Gestiones por cliente/contrato/usuario
- [ ] Compromisos como entidad propia vinculada a la Gestión, no inferidos del motivo
- [ ] Cadencia de cobro consolidada por Cliente
- [ ] Cadencia alternativa por estado de mora
- [ ] La cadencia nunca bloquea al gestor: solo alimenta agenda y reporte
- [ ] Registrar canal de la gestión (llamada/SMS/WhatsApp/correo) y su resultado
- [ ] Compromiso vencido sin confirmar → `incumplido` y vuelve a la agenda

#### Agenda

- [ ] Bandeja diaria/semanal del cobrador, agrupada **por Cliente** (no por Contrato)
- [ ] Clientes prioritarios
- [ ] Promesas por vencer / vencidas

### Pagos

> Sin borrado lógico: un pago nunca se oculta. Detalle: `docs/contexto/decisiones.md`

#### Registro

- [ ] CRUD Pago: crear, listar, consultar — incluye Método de pago (catálogo Fase 1)
- [ ] Filtrar pagos por usuario/cobrador/rango de fechas
- [ ] Separar `gestor_id` (quien gestiona), `recaudador_id` (quien recoge la plata) y `metodo_pago_id`
- [ ] Estado del pago: `pendiente` → `confirmado` (define quién puede confirmar)

#### Pagos programados

- [ ] Programar pago
- [ ] Confirmar pago (crea el Pago real y notifica al Motor Financiero)
- [ ] Descartar pago (manual y automático por vencimiento vía job programado)

#### Reversos

- [ ] Reverso de pago con permiso elevado (mismo nivel de exigencia que el legacy)

#### Recibos

- [ ] Generación/impresión individual de recibo
- [ ] Generación/impresión por lote
- [ ] Control de copias impresas
- [ ] Recibo HTML imprimible en media carta

#### Consultas

- [ ] Listado "cobro del día"
- [ ] Listado "sin llamar"
- [ ] Listado "volver a llamar"

### Devoluciones

> Sin borrado lógico: es un evento de historial. Detalle: `docs/contexto/decisiones.md`

- [ ] CRUD Devolución: registrar
- [ ] Aprobar/rechazar devolución (nuevo respecto al legacy, que genera directo)
- [ ] Consultar devolución
- [ ] Listar por fecha/usuario
- [ ] Notificar al Motor Financiero al generarse (no escribir el estado del contrato directamente)

### Reportes

- [ ] Conteo de clientes por estado
- [ ] Conteo de pagos por estado (programados/confirmados/descartados)
- [ ] Cartera por usuario/cobrador (pagos, programados, descartados, totales)
- [ ] Totales de cartera por estado
- [ ] Listado de clientes en estado jurídico para proceso externo (DataCrédito)
- [ ] Reporte por gestor: gestiones, promesas hechas, promesas cumplidas, % de efectividad

### Importación

- [ ] Importar clientes desde CSV reusando el Service de Clientes
- [ ] Importar contratos desde CSV reusando el Service de Contratos
- [ ] Importar pagos desde CSV reusando el Service de Pagos
- [ ] Migración de datos legacy → Recaudify (clientes, contratos, cuotas, pagos, devoluciones, usuarios)

### Frontend — pantallas por módulo

#### Catálogos

- [ ] Pantallas de catálogos (componente genérico reutilizable)
- [ ] Pantalla de estados y transiciones
- [ ] Pantalla de sucursales

#### Vendedores, Productos, Tarifas

- [x] Pantalla Vendedores (sin backend: 404)
- [x] Pantalla Productos (sin backend: 404)
- [x] Pantalla Tarifas (sin backend: 404)

#### Clientes

- [ ] Listado/búsqueda de clientes
- [ ] Ficha de cliente (datos + sub-recursos)
- [ ] Vista 360°/timeline
- [ ] Flujo de detección/fusión de duplicados

#### Contratos

- [ ] Formulario de contrato (con Evento de venta)
- [ ] Listado/detalle de contratos
- [ ] Plan de pagos

#### Cartera / Cobranza

- [ ] Administración de Cobradores (CRUD + asignación Usuario↔Cobrador)
- [ ] Bandeja de gestión/cobranza
- [ ] Agenda del cobrador (agrupada por Cliente)

#### Pagos

- [ ] Registro de pagos
- [ ] Pagos programados (programar/confirmar/descartar)
- [ ] Reverso
- [ ] Recibos

#### Autorizaciones

- [ ] Bandeja de solicitudes pendientes
- [ ] Modal de solicitud de autorización reutilizable

#### Devoluciones

- [ ] Pantalla de devoluciones (registrar/aprobar/consultar)

#### Reportes

- [ ] Pantallas de reportes

#### Importación

- [ ] Pantalla de importación (subir CSV, ver resultado)

## Nuevas tareas

- Use this format to add new tasks

## Actualizado

2026-08-19