# Recaudify

Plataforma de gestión de cobranza y pagos (Laravel + Angular). Reescritura y desacople de Católikas Cobranza (CodeIgniter 3). Contexto adicional en `funcionalidades.md` (inventario del legacy) y `NEGOCIO.md` (análisis de desacople por módulo).

Si se pide hacer alguna acción de este listado y está destinado a este directorio, se puede hacer todo sin solicitar permisos (excepto acciones de Git)

## Plan

### Arquitectura y Seguridad (base de plataforma)

#### Backend

- [x] Estructura Laravel, PHP 8.3, MySQL, migraciones
- [x] JWT + Refresh Tokens
- [x] CORS, Policies, Gates
- [x] Swagger
- [x] Form Requests
- [x] Soft Deletes
- [x] Formato estándar de respuestas + paginación
- [x] Auditoría genérica (spatie/activitylog) — inmutable por diseño, sin ningún mecanismo de borrado (ver "Convención de borrado y estado", punto 3)
- [x] Seeders iniciales
- [x] Usuarios: crear, editar, desactivar, restaurar
- [x] Roles: administrador, supervisor, verificador, cobrador, vendedor, auxiliar
- [x] Permisos: CRUD
- [x] Login, Logout, Refresh
- [x] Auditoría de accesos, cambios, eliminaciones, acciones críticas
- [x] Parámetros de negocio: días de mora, consecutivos

#### Frontend

- [x] Angular, Login, JWT, Guards, Interceptors
- [x] Diseño responsive
- [x] Administración: Usuarios (listar/crear/editar/desactivar/restaurar)
- [x] Administración: Roles (listar/crear/editar/desactivar/restaurar)
- [x] Administración: Permisos (listar/crear/editar/desactivar/restaurar)

### Armazón administrativo (pre-negocio)

#### Autenticación y parámetros de acceso

- [x] Método de login configurable (username o correo): Un parámetro  decide con qué campo se autentica el usuario, sin tocar código si el negocio cambia de preferencia.
- [x] Password de reseteo fija o auto-generada: Un parámetro elige el  modo; si es fija, su valor vive en Parameters -otro parámetro- (nunca hardcodeada como el legacy con  "Cobranza123").
- [x] Política de contraseñas configurable: longitud mínima, si exige mayúsculas/números/símbolos,  y expiración periódica (forzar cambio cada N días). Hoy no hay ninguna regla explícita más  allá de lo que valide el Form Request a mano.
- [ ] Delegación temporal de permisos / suplencias: que un supervisor pueda asignar temporalmente su rol o un permiso puntual a otro usuario (ej. cobrador de vacaciones), con fecha de inicio y fin, sin tener que editar roles manualmente y no olvidarse de revertir.
- [ ] Envío de password de reseteo por correo: si el tenant tiene correo/SMTP habilitado y el modo
      de reseteo (ver sección base) es auto-generada, enviar la contraseña generada al correo del
      usuario en vez de (o además de) mostrarla en pantalla.

#### Menús y navegación

- [x] Menú dinámico por permisos: el menú lateral/superior se arma en base a los permisos reales del usuario logueado (`AuthService.hasPermission()`), no con ítems ocultos a mano por rol — si mañana se crea un permiso nuevo, el ítem aparece solo con asignarlo, sin tocar el componente de menú.
- [ ] Menú configurable desde Settings (builder de menú): una pantalla de administración donde se define qué ítems existen, en qué orden, con qué ícono y bajo qué permiso, guardado en BD en vez de hardcodeado en el `routes.ts`/componente Angular — útil si el negocio quiere reordenar o renombrar secciones sin pedir un deploy.
- [ ] Favoritos / accesos rápidos personalizados: que cada usuario pueda marcar sus pantallas más usadas (ej. un cobrador que vive en "Agenda" y "Pagos programados") y tenerlas arriba de todo, sin afectar el menú de los demás usuarios.
- [ ] Menú con contadores/badges en vivo: mostrar en el ítem del menú un número (ej. "Pagos programados por vencer hoy: 12", "Importaciones fallidas: 2") para que el usuario sepa que hay algo pendiente sin tener que entrar a mirar.
- [ ] Búsqueda global / paleta de comandos (Ctrl+K): buscador que salta directo a cualquier pantalla, cliente o contrato por nombre, sin navegar el árbol de menú — muy útil una vez haya muchos módulos (Clientes, Contratos, Cobranza, Reportes, Catálogos, etc.).
- [ ] Breadcrumbs dinámicos: mostrar la ruta de navegación actual (ej. "Clientes > Juan Pérez > Contratos > #123") derivada de la ruta activa, para ubicarse rápido en pantallas anidadas.
- [ ] Historial de navegación reciente: lista de "últimas 5 pantallas/clientes visitados" por usuario, para volver rápido a lo que se estaba revisando sin repetir la búsqueda.
- [ ] Menú distinto por rol/perfil (layout): que el cobrador vea un menú simple centrado en Agenda y Pagos, mientras que administrador ve el menú completo — evita que roles operativos se pierdan entre opciones que nunca van a usar.
- [ ] Atajos de teclado configurables: acciones rápidas (nuevo cliente, nuevo pago, buscar) vía teclado, documentadas en un modal de ayuda (`?`), pensado para usuarios de alto volumen (cobradores/cajeros) que ganan tiempo real no usando mouse.
- [ ] Menú colapsable / modo compacto: opción de colapsar el menú lateral a solo íconos, preferencia guardada por usuario (localStorage o perfil), para pantallas chicas o usuarios que prefieren más espacio de contenido.

#### Logs y auditoría

- [x] Registro de accesos (IP, dispositivo, fecha) por login exitoso y fallido: complementa la auditoría de acciones (`spatie/activitylog`) con auditoría de acceso, que hoy no está cubierta explícitamente en planning.md.
- [ ] Visor de logs de sistema en UI: hoy `business`/`app-errors`/`security`/`http` solo existen como archivos planos en `storage/logs/`. Una pantalla de admin que los liste/filtre (por canal, fecha, usuario, nivel) evita tener que entrar por SSH a leer un archivo cada vez que algo falla en producción.
- [ ] Correlación de intentos fallidos → alerta automática in-app: ya se captura `LoginAudit` y existe el canal `security`, pero nadie los cruza. Detectar "N intentos fallidos del mismo usuario/IP en X minutos" y notificar al administrador (in-app) en vez de que quede solo como registro pasivo que hay que ir a mirar (la variante por correo queda en la sección final).
- [ ] Comando programado de purga de logs vencidos: `config/activitylog.php` ya define `clean_after_days` (365) pero no hay ningún comando agendado en `routes/console.php` que ejecute la limpieza — hoy crecería indefinidamente.
- [ ] Exportar logs filtrados a CSV/Excel: para pasarle un rango de fechas a soporte o auditoría externa sin acceso al servidor.
- [ ] Nivel de verbosidad por canal configurable desde Parameters: poder subir/bajar detalle de `app-errors`/`http` en caliente (ej. modo debug temporal) sin cambiar `config/logging.php` y redesplegar.
- [ ] Trazabilidad por request-id: propagar un ID único de request en cabecera de respuesta y en cada línea de log de ese request, para poder seguir un problema puntual del usuario a través de los 4 canales sin adivinar por timestamp.
- [ ] Alerta de intentos fallidos de login por correo: variante por correo de la "Correlación de
      intentos fallidos → alerta automática" de esta misma sección — hoy esa idea ya se puede
      construir en su versión in-app; el envío por correo se agrega cuando haya SMTP.
- [x] Diff visual de cambios en el feed de actividad: `activitylog` ya guarda `old`/`attributes`, pero la pantalla actual los lista crudo; mostrar "campo X: valor A → valor B" en vez de JSON crudo hace el feed realmente legible para un no-técnico.

#### Dashboard y métricas

- [ ] Health check / endpoint de estado: un endpoint simple que reporte estado de BD, cola y colas de jobs (relevante una vez exista el job programado del Motor Financiero) para monitoreo básico en el VPS.
- [ ] Dashboard con KPIs reales de plataforma: usuarios activos hoy, logins exitosos/fallidos, accesos denegados por horario, jobs fallidos — antes de tener negocio, ya hay datos suficientes (`LoginAudit`, `activity_log`, `UserSchedule`) para un dashboard operativo real.
- [ ] Incorporar una librería de gráficas (ej. ngx-charts o Chart.js): hoy no existe ninguna en el proyecto; es prerequisito de cualquier gráfica antes de llegar a los reportes de negocio de `NEGOCIO.md` §12.
- [ ] Gráfica de logins por hora/día de la semana: usa `LoginAudit` que ya existe, ayuda a detectar patrones de uso normal (para luego notar anomalías).
- [ ] Mapa de accesos geográfico: `LoginAudit` ya captura lat/long/accuracy — un mapa simple de "desde dónde se conecta el equipo" es casi gratis con el dato que ya se guarda.
- [ ] Widget "usuarios conectados ahora": requiere primero tener noción de sesión activa (ver sección Seguridad), pero es un indicador simple y muy visual para el dashboard.
- [ ] Tablero de salud del sistema: estado de BD, cola, caché, espacio en disco — ver "Health check" más abajo, este sería su representación visual en el dashboard.
- [ ] Métricas de uso de API por endpoint: el canal `http` ya registra método/path/status/duración por request — agregarlas (percentiles de tiempo de respuesta, endpoints más lentos, tasa de error por ruta) da visibilidad de performance antes de que el negocio genere volumen real.
- [ ] Health check / endpoint de estado: un `/api/health` que reporte estado de BD, cola, caché y espacio en disco, pensado para monitoreo externo (uptime checks) en el VPS — no existe hoy ningún endpoint de este tipo.

#### Sesiones y seguridad

- [ ] Bloqueo por intentos fallidos de login (rate limiting / lockout): tras N (paramétrico) intentos fallidos,  bloquear temporalmente el usuario y la IP. El legacy no tenía ningún control de este tipo;  es una brecha real de seguridad a cerrar antes de tener datos de negocio sensibles.
- [ ] Gestión de sesiones activas: ver qué dispositivos/tokens tiene activos un usuario y poder  revocar uno o todos remotamente (útil si se pierde un dispositivo o se sospecha de un acceso  indebido). Se apoya en el JWT + refresh token ya existente. (Paramétrico si se quiere o no, por defecto true)
- [ ] Expiración de sesión por inactividad configurable: además del TTL fijo del JWT (15 min), un parámetro de "cerrar sesión tras X(parametrizable) minutos sin actividad" a nivel de frontend/UX.
- [ ] Listado de sesiones/tokens activos + revocación remota: hoy solo hay login/logout/refresh del JWT, sin ningún lugar donde ver "qué dispositivos tienen sesión abierta ahora mismo" ni forma de cerrar una sesión ajena (útil si se pierde un celular o hay sospecha de acceso indebido).
- [ ] Bloqueo tras intentos fallidos (rate limit + lockout): actualmente el único throttle encontrado es `throttle:10,1` hardcodeado en el refresh; no hay bloqueo de cuenta ni de IP tras varios intentos fallidos de login.
- [ ] Panel de administración de rate limiting: en vez de límites fijos en el código de rutas, poder ajustar los límites por endpoint sensible desde Parameters/Settings.
- [ ] Detección de acceso desde ubicación/IP inusual: dado que `LoginAudit` ya guarda IP y geolocalización, comparar contra el histórico del usuario y marcar/alertar accesos atípicos (otro país, otro dispositivo nunca visto) es una extensión natural de un dato que ya existe.
- [ ] Modo "vista previa como otro usuario" (impersonar) para administrador/soporte: para reproducir un problema que reporta un cobrador sin pedirle contraseña, dejando registro en auditoría de cuándo y quién impersonó a quién (nunca silencioso).
- [ ] Confirmación por correo de acceso desde dispositivo nuevo: enviar un aviso "se inició sesión
      desde un dispositivo nuevo", reutilizando `LoginAudit` — depende de tener correo saliente
      configurado.

#### Notificaciones

- [ ] Sistema de notificaciones in-app (campana con contador): el modelo `User` ya puede usar el trait `Notifiable` de Laravel pero no hay ninguna clase `Notification` implementada todavía; es la base para "recordatorio de reseteo enviado", "job falló", "acceso desde dispositivo nuevo", etc., sin tener que inventar un canal nuevo para cada aviso.
- [ ] Centro de notificaciones (histórico): que el usuario pueda ver notificaciones pasadas ya leídas/no leídas, no solo un toast que desaparece a los 5s como hoy (`ToastService`).
- [ ] Plantillas de correo / Mailables editables: los mensajes que el sistema envía (reseteo de
      password, alertas) deberían salir de una plantilla configurable, no de texto fijo en el
      código. Requiere primero tener SMTP configurado y al menos un `Mailable` implementado (hoy no
      hay ninguno; el mailer por defecto es `log`).
- [ ] WhatsApp como canal de aviso: mismo concepto que las anteriores (alertas de seguridad, avisos
      de jobs, confirmaciones) pero por WhatsApp en vez de correo — requiere contratar un proveedor
      de WhatsApp Business API; no hay ninguna integración ni credencial hoy. Se anota como canal a
      futuro, sin ideas específicas más allá de las ya listadas por correo (aplican igual a
      WhatsApp una vez haya proveedor).

#### Configuración y ajustes

- [ ] Panel de configuración general (Settings) unificado: hoy los "Parameters" ya existen pero conviene una pantalla única de configuración de plataforma (nombre de empresa, logo, zona horaria, moneda, SMTP, límites) en vez de que cada feature invente su propio lugar.
- [ ] Exportación genérica a CSV/Excel: un mecanismo reusable de exportación (igual que se plantea un CRUD genérico para catálogos) para no reimplementar "exportar a Excel" en cada módulo de reportes cuando llegue esa fase.
- [ ] Panel de feature flags: activar/desactivar módulos en construcción (ej. mostrar/ocultar "Cobranza" mientras se termina) sin necesidad de un deploy, apoyado en la infraestructura de `Parameters` que ya existe.
- [ ] Modo mantenimiento con aviso configurable: bloquear escritura o todo acceso salvo administrador, con un mensaje editable, para migraciones delicadas sin apagar el servidor.
- [ ] Backups automatizados con notificación de resultado: programar dump de BD (usa la infraestructura de scheduling de la sección anterior) y avisar éxito/fallo, en vez de depender de un backup manual como el legacy (`Mantenimiento/Backup`).
- [ ] Panel de ajustes generales de plataforma: nombre de empresa, logo, zona horaria, moneda, SMTP — hoy `Parameters` es genérico pero no hay una pantalla que agrupe "la configuración de la empresa" como una unidad clara para el administrador.

#### Experiencia de usuario y observabilidad

- [ ] Internacionalización (i18n) de textos de UI: aunque el negocio sea local, tenerlo resuelto desde el inicio evita reescribir strings hardcodeados si en el futuro se necesita otro idioma o simplemente estandarizar textos en un solo lugar.
- [ ] Theming claro/oscuro: no está implementado en el frontend hoy (no hay ningún mecanismo de tema); dado que ya se usan `ChangeDetectionStrategy.OnPush` + signals en todo el proyecto, es buen momento de dejarlo resuelto con una señal global antes de que crezcan más pantallas.
- [ ] Auditoría de accesibilidad (a11y) básica: contraste, `aria-label`, navegación por teclado en los componentes compartidos (`Spinner`, `ToastContainer`) — más fácil de corregir ahora que hay pocos componentes que después con decenas de pantallas de negocio.

### Infraestructura de jobs y colas

#### Jobs

- [ ] Panel de "failed jobs": listar, ver el error y poder reintentar/descartar jobs fallidos desde
      la UI en vez de por consola (`php artisan queue:failed`).
- [ ] Reintentos automáticos configurables por tipo de job: cuántas veces reintentar y con qué
      backoff antes de marcarlo como fallido definitivo, en vez del valor por defecto de Laravel.
- [ ] Notificación cuando un job falla repetidamente: aviso in-app en vez de descubrirlo días
      después revisando `failed_jobs` manualmente (la variante por correo queda en la sección
      final).
- [ ] Notificación por correo cuando un job falla repetidamente: variante por correo de la
      notificación anterior (hoy construible como aviso in-app).

#### Schedule

- [ ] Programación de comandos (`schedule`) desde el día uno: dejar el mecanismo de cron de Laravel
      ya armado (aunque el primer comando real sea trivial, como la purga de logs) para que cuando
      llegue el job del Motor Financiero (`NEGOCIO.md` §7) solo haya que agregar el comando, no
      montar la infraestructura de scheduling.

### Convención de borrado y estado

Fijar esto antes de construir el resto de módulos — evita repetir el error del legacy de mezclar
"ocultar", "estado de negocio" y "auditoría" en una sola columna (`Habilitado`).

#### SoftDeletes (cuando "eliminar" es solo "dejar de aparecer en listados activos, recuperable")

- [ ] Aplicar `SoftDeletes` a: Cliente, Producto, Tarifa y todos los catálogos genéricos de la Fase
      "Catálogos base y parametrización" (Tipos de documento, Tipo de contrato, Motivos de gestión,
      Vendedores, Eventos, Tipo de producto, Tipo de eventos, Cobradores, Métodos de pago,
      Sucursales, Días de cambio de estado) — es el equivalente mejorado del `Habilitado` del legacy
- [ ] Resolver el gotcha de `unique` + `SoftDeletes` en Cliente (`documento`): índice único
      compuesto con `deleted_at`, o validación en el Service contra solo registros no borrados

#### Cuando NO aplica SoftDeletes — usar estado explícito o no borrar nunca

- [ ] Punto 1 — Contrato: campo de estado explícito (borrador/activo/suspendido/cancelado/
      finalizado) como mecanismo principal; `SoftDeletes` solo como salvavidas para el caso de
      "se creó por error y nunca debió existir", nunca para representar cancelación/finalización
- [ ] Punto 2 — Pago, Pago Programado: sin borrado lógico ni booleano; reverso/descarte se modelan
      como evento propio vinculado al registro original, que nunca se oculta de los listados
- [ ] Punto 2 — Devolución, Gestión/Llamada, Compromiso: sin borrado lógico; son eventos de
      historial, se corrigen con otro evento (no se ocultan ni se marcan como borrados)
- [ ] Punto 3 — Log de auditoría (`spatie/activitylog`): inmutable por diseño, sin ningún mecanismo
      de borrado (ni `SoftDeletes` ni booleano) bajo ninguna circunstancia

#### Cuando SÍ se puede borrar físico (datos sin realidad de negocio todavía)

- [ ] Punto 4 — Definir política de limpieza para datos verdaderamente transitorios que nunca
      llegaron a confirmarse como hecho de negocio: borradores de contrato nunca activados, archivos
      o filas de importación fallidas. Estos sí aceptan DELETE físico porque no hay nada que auditar
      todavía (no contradice la regla de "nada se elimina", que aplica a información que ya existió)

### Catálogos base y parametrización

#### Catálogos

> Una tabla/modelo por catálogo (los campos no son iguales entre todos: Motivos necesita color,
> "Días de cambio de estado" necesita rango de días + color/ícono), pero **una sola implementación de CRUD
> compartida** para todos — no repetir Controller/Service/Repository por catálogo. Dos permisos
> para todos: `catalogos.ver` y `catalogos.gestionar` (implica crear/editar/eliminar/restaurar), no
> uno por catálogo. Frontend: **un componente de listado/formulario que se dibuja según el
> modelo** (metadata: qué columnas, qué campos y de qué tipo — texto, color, etc.), no una pantalla
> por catálogo; agrupados en una sola pantalla `/admin/catalogos` con selector.
>
> "Tipos de vivienda" queda fuera de alcance (no aporta valor real). Canal/Organización y Zona **no**
> son catálogos propios: quedan absorbidos como campos del catálogo/entidad Eventos (barrio/zona es
> un string libre, no una jerarquía con catálogo detrás). "Resultados de gestión" queda fuera de
> alcance por ahora — todo lo que el legacy distinguía como "resultado" se cubre con Motivos de
> gestión.
>
> Ver `catalogos-schema-demo.sql` en la raíz del repo para un esquema ilustrativo (no DDL de
> producción) de estos catálogos y cómo se conectan al núcleo de negocio (Cliente, Contrato, Pagos,
> Gestiones).

- [ ] CRUD genérico compartido (base Repository/Service/Controller reutilizable por los catálogos) (`SoftDeletes`)
- [ ] Catálogo: Tipos de documento
- [ ] Catálogo: Tipo de contrato
- [ ] Catálogo: Motivos de gestión (con campo color)
- [ ] Catálogo: Vendedores (CRUD básico vía el motor genérico; la vinculación con Evento de venta vive en la Fase de Contratos)
- [ ] Catálogo: Eventos (registro de Vendedor + Canal/Organización + Zona/Barrio (string libre) + Tipo de evento + Fecha — ver Fase "Evento de venta y Contratos")
- [ ] Catálogo: Tipo de producto
- [ ] Catálogo: Tipo de eventos
- [ ] Catálogo: Cobradores (CRUD básico vía el motor genérico; la asignación Usuario↔Cobrador y Cliente↔Cobrador vive en la Fase de Cartera/Cobranza)
- [ ] Catálogo: Métodos de pago
- [ ] Catálogo: Clasificación de clientes — **bajo revisión, no confirmado**: riesgo de solaparse con la clasificación de cartera que calcula el Motor Financiero; no construir hasta tener un caso de uso concreto que no sea redundante
- [ ] Catálogo: Sucursales (una sola empresa con varias sedes físicas — no confundir con Multi Tenancy, que sigue fuera de este documento)
- [ ] Catálogo: Días de cambio de estado (reglas de mora 15/30/45/90 días → estado de cartera; reemplaza el punto de "Umbrales de mora" que antes estaba pensado como Parámetro suelto). Cada regla: `día_desde`/`día_hasta` (rango explícito, no un solo umbral ambiguo), `estado_resultante`, `color` e `icono` (sin campo `orden` — se deriva de `día_desde`). El color/ícono de la regla vigente es lo que pinta el estado de cartera en listados y en la Vista 360° del cliente sin mapear el nombre del estado a un color a mano en cada pantalla

#### Parámetros nuevos

> Los umbrales de mora ya no van acá — son el catálogo "Días de cambio de estado" de arriba (una
> lista de reglas relacionadas, no un valor escalar suelto).

- [ ] Días de vencimiento de un pago programado antes de descartarse automáticamente (legacy: 60 días fijos)
- [ ] Ventana de validez de una gestión de llamada antes de inhabilitarse (legacy: 1–8 días según motivo, fijo)
- [ ] Redondeo del pago mínimo por mora (legacy: al millar, fijo)
- [x] Password por defecto al resetear contraseña, parametrizable (solo si se implementa esa función; legacy usa `Cobranza123` hardcodeado)

### Vendedores, Productos y Tarifas

#### Vendedores

- [ ] Ya cubierto por el catálogo genérico (ver "Catálogos base y parametrización") — acá no hay tarea adicional de CRUD, solo la vinculación con Evento de venta más abajo

#### Productos

- [ ] Crear producto
- [ ] Editar/actualizar producto (el legacy nunca tuvo `update`, solo alta y lookup por código — no repetir ese hueco)
- [ ] Listar productos
- [ ] Activar/desactivar producto (`SoftDeletes`)
- [ ] Categoría de producto (catálogo Tipo de producto, Fase 1)

#### Tarifas

- [ ] CRUD Tarifas por producto (cuotas, valor, descuento) (`SoftDeletes`)
- [ ] Historial de cambios de tarifa (el legacy solo sobreescribe, acá se versiona)

### Clientes

#### CRUD Cliente

- [ ] `SoftDeletes` con índice único compuesto (`documento` + `deleted_at`) para no bloquear alta de un cliente nuevo con el mismo documento de uno ya desactivado
- [ ] Documento único por tenant
- [ ] Nombre, tipo de documento
- [ ] Observaciones versionadas (historial, no concatenar un string como el legacy)

#### Sub-recursos de Cliente

- [ ] Direcciones (relación 1\:N real — el legacy fuerza 1 sola; barrio/zona es un campo de texto libre, no un catálogo)
- [ ] Teléfonos (relación 1\:N real — el legacy fuerza 3 campos fijos)
- [ ] Referencias (relación 1\:N real — el legacy limita a 3)

#### Identidad y búsqueda

- [ ] Detección de duplicados (no existe en el legacy)
- [ ] Fusión de duplicados
- [ ] Búsqueda unificada por nombre/documento/teléfono/dirección/estado (un único endpoint, no las 3 variantes del legacy)
- [ ] Vista 360° / Timeline de solo lectura (contratos, pagos, Interacciones con el detalle de Gestión por contrato adentro, documentos, cambios de estado)
- [ ] Indicador agregado de estado de cartera (valor calculado de lectura, no columna propia): gana el peor estado entre los contratos activos del cliente — no un promedio, no exige que todos estén al día
- [ ] Asignación Cliente↔Cobrador (no Cliente↔Usuario directo — ver módulo Cartera/Cobranza)

### Evento de venta y Contratos

#### Evento de venta

- [ ] Entidad Evento de venta (catálogo "Eventos" — ver Fase 1) = Vendedor + Canal/Organización (campo propio) + Zona/Barrio (string libre) + Tipo de evento (catálogo Fase 1) + Fecha, vinculada al Contrato (no al Cliente)

#### Contratos

- [ ] CRUD Contratos, N por cliente — incluye Tipo de contrato (catálogo Fase 1); elimina el 1:1 cliente/pedido del legacy; un cliente puede tener 0, 1 o varios contratos simultáneos
- [ ] Nunca fusionar una compra nueva dentro de un contrato existente con saldo (regla confirmada): aunque el cliente ya tenga deuda, cada compra nueva es siempre un Contrato nuevo. La consolidación del cobro entre varios contratos del mismo cliente se resuelve en Cobranza, no fusionando contratos
- [ ] Ciclo de vida explícito: borrador / activo / suspendido / cancelado / finalizado — NO usar `SoftDeletes` como mecanismo de estado (ver "Convención de borrado y estado", punto 1); `SoftDeletes` solo aplica como salvavidas si el contrato se creó por error y nunca debió existir
- [ ] Cambiar tarifa (dispara recálculo en el Motor Financiero)
- [ ] Cambiar fecha de cobro
- [ ] Agregar/quitar producto del contrato (recalcula total vía Motor Financiero, no a mano)

### Motor Financiero

#### Cálculo

- [ ] Calcular saldo, cuota, capital, interés, descuento y mora de un contrato
- [ ] Recalcular saldo tras pago, reverso, cambio de tarifa o devolución
- [ ] Clasificar estado de cartera de cada contrato según las reglas del catálogo "Días de cambio de estado" (Fase 1) — no umbrales sueltos en Parámetros

#### Automatización

- [ ] Job programado en background que recalcula cartera y cambia estados (reemplaza el side-effect de `Deuda()` en cada request del legacy)
- [ ] DataCrédito como transición de estado más del Motor
- [ ] Cálculo de pago mínimo por mora (vive acá, no en Pagos)

### Cartera / Cobranza

#### Cobrador (entidad independiente del Usuario)

- [ ] CRUD básico de Cobrador ya cubierto por el catálogo genérico (ver "Catálogos base y parametrización") — acá vive la lógica de asignación:
- [ ] Asignación Usuario↔Cobrador con historial (permite reasignar personal sin tocar cliente por cliente)
- [ ] Asignación Cliente↔Cobrador
- [ ] Mantener el Role Spatie "cobrador" solo como permiso de sistema, separado de la identidad operativa
- [ ] **Invariante confirmada, sin excepción**: todos los contratos de un mismo cliente viven siempre en la misma cartera (mismo Cobrador) — nunca se reparten entre carteras distintas, ni siquiera si un contrato llega a estado jurídico/castigado (ese caso se gestiona distinto por el propio estado de cartera del contrato, no moviendo al cliente de cobrador). Se valida en el Service al asignar, no hace falta constraint de base de datos

#### Gestión

> Sin borrado lógico (ver "Convención de borrado y estado", punto 2): Interacciones, Gestiones y
> Compromisos son eventos de historial, se corrigen con otro evento, nunca se ocultan.

- [ ] **Interacción** (el contacto real): Cliente, Usuario, medio (llamada/visita/correo/WhatsApp/SMS), fecha, observación general — una sola fila por contacto real, sin importar cuántos contratos toque
- [ ] **Gestión por contrato** (el efecto de la Interacción sobre un Contrato puntual): motivo, resultado, referencia a la Interacción y al Contrato — necesaria como fila separada porque el cliente puede responder distinto por cada deuda en la misma llamada (promete pagar el contrato A, rechaza el B)
- [ ] Listado de Gestiones por cliente/contrato/usuario
- [ ] Compromisos (promesa de pago, acuerdo, reprogramación) como entidad propia vinculada a la Gestión (por contrato, no a la Interacción), no inferida del motivo — una sola Interacción puede generar varios Compromisos a la vez
- [ ] Cadencia de cobro consolidada por Cliente: una Interacción por mes por cliente como ciclo regular, sin importar cuántos contratos tenga — no una llamada por contrato

#### Agenda

- [ ] Bandeja diaria/semanal del cobrador, agrupada **por Cliente** (no por Contrato): un cliente con 3 contratos vencidos aparece como una sola tarea, con el detalle de los 3 contratos adentro
- [ ] Clientes prioritarios
- [ ] Promesas por vencer / vencidas

### Pagos

> Sin borrado lógico en ningún sub-módulo (ver "Convención de borrado y estado", punto 2): un pago
> nunca se oculta, se corrige con un evento de reverso/descarte que queda vinculado y visible junto
> al original.

#### Registro

- [ ] CRUD Pago: crear, listar, consultar — incluye Método de pago (catálogo Fase 1)
- [ ] Filtrar pagos por usuario/cobrador/rango de fechas

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

#### Consultas

- [ ] Listado "cobro del día" — agrupado **por Cliente** (ver Cartera/Cobranza, Agenda), no una fila por Contrato
- [ ] Listado "sin llamar"
- [ ] Listado "volver a llamar"

### Devoluciones

> Sin borrado lógico (ver "Convención de borrado y estado", punto 2): es un evento de historial, no
> se oculta ni se marca como borrado.

- [ ] CRUD Devolución: registrar
- [ ] Aprobar/rechazar devolución (nuevo respecto al legacy, que genera directo)
- [ ] Consultar devolución
- [ ] Listar por fecha/usuario
- [ ] Notificar al Motor Financiero al generarse (no escribir el estado del contrato directamente)

### Reportes

- [ ] Conteo de clientes por estado — cuenta personas, usa el indicador agregado por Cliente (peor estado entre sus contratos activos)
- [ ] Conteo de pagos por estado (programados/confirmados/descartados)
- [ ] Cartera por usuario/cobrador (pagos, programados, descartados, totales)
- [ ] Totales de cartera por estado — suma valores, siempre por Contrato, nunca por Cliente. "Clientes en mora" (personas) y "cartera en mora" (dinero) son dos métricas distintas, no mezclarlas en un solo número

### Importación

- [ ] Importar clientes desde CSV reusando el Service de Clientes
- [ ] Importar contratos desde CSV reusando el Service de Contratos
- [ ] Importar pagos desde CSV reusando el Service de Pagos
- [ ] Migración de datos legacy → Recaudify (clientes, contratos, cuotas, pagos, devoluciones, usuarios)

### Frontend — pantallas por módulo

#### Catálogos

- [ ] Pantallas de catálogos (componente genérico reutilizable)

#### Vendedores, Productos, Tarifas

- [x] Pantalla Vendedores
- [x] Pantalla Productos (con edición)
- [x] Pantalla Tarifas (con historial)

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
- [ ] Asignación Cliente↔Cobrador
- [ ] Bandeja de gestión/cobranza — registro de Interacción (contacto) con el detalle de Gestión por cada contrato del cliente adentro del mismo formulario
- [ ] Agenda del cobrador (agrupada por Cliente)

#### Pagos

- [ ] Registro de pagos
- [ ] Pagos programados (programar/confirmar/descartar)
- [ ] Reverso
- [ ] Recibos

#### Devoluciones

- [ ] Pantalla de devoluciones (registrar/aprobar/consultar)

#### Reportes

- [ ] Pantallas de reportes

#### Importación

- [ ] Pantalla de importación (subir CSV, ver resultado)

## Nuevas tareas

- Use this format to add new tasks

## Actualizado

2026-07-06
