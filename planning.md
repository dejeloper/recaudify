# Recaudify — Planning

Plataforma de gestión de cobranza y pagos (Laravel + Angular). Reescritura y desacople de
Católikas Cobranza (CodeIgniter 3).

> **Este archivo no es un checklist para tachar.** Es la memoria de "esto se debe hacer" para que
> no se pierda de vista el plan mientras se avanza módulo a módulo (parte del trabajo lo hace la IA,
> parte lo vas a hacer vos directamente en los casos más específicos/complejos). Se usa como
> contexto junto con:
>
> - `funcionalidades.md` — inventario literal de qué hace el legacy hoy (fuente de verdad de reglas
>   de negocio a no perder).
> - `NEGOCIO.md` — el análisis módulo por módulo de cómo se desacopla cada pieza del legacy, con el
>   detalle y el razonamiento completo. **Este archivo no repite ese detalle**, solo prioriza y
>   resume qué construir en qué orden. Si falta contexto de un ítem, está desarrollado en `NEGOCIO.md`.
>
> Los ítems marcados `[x]` son hechos ya construidos (registro, no checklist). Todo lo demás son
> bullets de intención, no casillas para marcar.

---

## Ya implementado (base de plataforma)

**Backend:**

- [x] Estructura Laravel, PHP 8.3, MySQL, migraciones.
- [x] JWT + Refresh Tokens, CORS, Policies/Gates.
- [x] Swagger, Form Requests, Soft Deletes, formato estándar de respuestas + paginación.
- [x] Auditoría genérica (`spatie/activitylog`) y seeders iniciales.
- [x] Usuarios (crear/editar/desactivar/restaurar), Roles (administrador, supervisor, verificador,
      cobrador, vendedor, auxiliar), Permisos (CRUD), Login/Logout/Refresh.
- [x] Auditoría de accesos (login/logout), cambios, eliminaciones, acciones críticas.
- [x] Parámetros de negocio: días de mora, consecutivos.

**Frontend:**

- [x] Angular, Login, JWT, Guards, Interceptors, diseño responsive.
- [x] Administración: Usuarios, Roles, Permisos (listar/crear/editar/desactivar/restaurar).
 
---

## Fases (orden de construcción del Core, por prioridad y dependencia)

Basado en el orden sugerido en `NEGOCIO.md` §Resumen. Cada fase depende de que la anterior exista.

### Fase 1 — Catálogos base y parametrización

Catálogos simples, CRUD completo (index/show/store/update/destroy/trashed/restore, soft delete, sin
lógica de negocio). Buen punto de partida para fijar el patrón estándar antes de tocar algo con
reglas de negocio reales.

- Tipos de documento.
- Tipos de vivienda.
- Canal/Organización (lo que el legacy llama "iglesia").
- Zonas/Barrios.
- Motivos de gestión.
- Resultados de gestión.

**Parámetros nuevos a crear** en el módulo `Parameters` (adicional a días de mora / consecutivos ya
existentes) — todo esto está hardcodeado en el legacy y no debe volver a estarlo:

- Umbrales de mora para clasificar cartera (al día / próximo vencimiento / mora temprana / mora
  avanzada / prejurídico / jurídico / castigado / paz y salvo). El legacy solo tiene 4 umbrales fijos
  (≤10 / 11–44 / 45–89 / ≥90 días); el nuevo catálogo de estados es más granular y configurable.
- Días de vencimiento de un pago programado antes de descartarse automáticamente (legacy: 60 días
  fijos).
- Ventana de validez de una gestión de llamada antes de inhabilitarse (legacy: 1–8 días según
  motivo, fijo).
- Redondeo del pago mínimo por mora (legacy: al millar, fijo).
- Password por defecto al resetear contraseña — **solo si se decide implementar esa función**; no
  debe quedar un valor fijo en código como en el legacy (`Cobranza123`).

---

### Fase 2 — Vendedores, Productos y Tarifas

- **CRUD Vendedores** independiente (ya existe la pantalla en el frontend como catálogo de
  referencia; falta el modelo backend real).
- **CRUD Productos** — importante: incluir explícitamente **crear + editar/actualizar** (el legacy
  nunca tuvo un `update` de producto, solo alta y lookup por código; no repetir ese hueco), listar,
  activar/desactivar, categoría.
- **CRUD Tarifas** por producto (cuotas, valor, descuento) **con historial de cambios** — el legacy
  solo sobreescribe la tarifa, nosotros versionamos.
- La vinculación Vendedor↔Contrato se resuelve en la Fase 4 (vía Evento de venta) — acá solo el
  catálogo, sin vincular todavía nada operativo.

---

### Fase 3 — Clientes (identidad, sin pedido/contrato)

- **CRUD Cliente**: documento único por tenant, nombre, tipo de documento, tipo de vivienda,
  observaciones **versionadas** (historial de observaciones, no concatenar un string como el
  legacy).
- Sub-recursos como relación real 1\:N (el legacy fuerza 1 dirección y 3 teléfonos/referencias
  fijos — acá no hay ese límite duro):
  - Direcciones.
  - Teléfonos.
  - Referencias.
- **Detección y fusión de duplicados** — no existe en el legacy, es nuevo pero necesario: al
  desacoplar la identidad del cliente del pedido, se vuelve más fácil crear un cliente duplicado por
  error.
- **Búsqueda unificada** (nombre/documento/teléfono/dirección/estado) — un único endpoint de
  búsqueda con filtros, no las 3 variantes que tiene el legacy (`Buscar`/`SearchJson`/
  `SearchJsonAsignado`).
- **Vista 360° / Timeline** de solo lectura: agregación de contratos, pagos, gestiones, documentos y
  cambios de estado en una sola consulta cronológica (reemplaza el log crudo de `Clientes::Log()`).
- **Asignación Cliente↔Cobrador** (no Cliente↔Usuario directo) — ver el detalle completo en la
  Fase 6. Anotado acá porque es donde vive el campo/relación, aunque el concepto de "Cobrador" como
  entidad se construye después.

---

### Fase 4 — Evento de venta y Contratos

- Entidad **Evento de venta** = Vendedor + Canal/Organización + Zona + Fecha, vinculada al
  **Contrato** (no al Cliente). Los catálogos que usa (Canal, Zona) ya existen desde la Fase 1.
- **CRUD Contratos, N por cliente.** Este es el cambio estructural más importante de todo el plan:
  **el legacy fuerza un 1:1 cliente↔pedido de facto y eso no aplica más.** Un cliente puede tener 0,
  1 o varios contratos (activos o cerrados) simultáneamente. No se debe modelar ni pensar "el pedido
  del cliente" en singular en ningún lado del nuevo sistema.
- Ciclo de vida del contrato explícito: borrador / activo / suspendido / cancelado / finalizado — en
  vez de inferirlo de combinaciones de códigos numéricos como hace el legacy (110/111/112/113/114/
  125/127).
- Acciones propias del contrato: cambiar tarifa (dispara recálculo en el Motor Financiero, Fase 5),
  cambiar fecha de cobro, agregar/quitar producto del contrato (recalcula el total vía Motor
  Financiero, nunca sumando a mano como hace `AddProducto()` en el legacy).
- **Explícitamente fuera de esta fase** (no meterlo ahora, aunque el ciclo de vida deja el lugar
  natural para agregarlo después): refinanciación, reestructuración, congelación de contrato. Son
  funcionalidades que no existen en el legacy — quedan para otra fase futura, no forman parte de
  este plan.

---

### Fase 5 — Motor Financiero (cartera / deuda)

Servicio único, fuente de verdad de todo cálculo financiero — ningún otro módulo recalcula saldo
por su cuenta (el legacy repite la misma fórmula "saldo ≤ 0 → estado 111/114" en `conf()`,
`Reverse()`, `changeRate()` e `Importar::conf()`; acá se hace una sola vez).

- Calcular saldo, cuota, capital, interés, descuento y mora de un contrato.
- Recalcular saldo de contrato/cliente/cartera tras un pago, un reverso, un cambio de tarifa o una
  devolución.
- Clasificar el estado de cartera de cada contrato según los umbrales parametrizados en la Fase 1.
- Job programado en background que recalcula cartera y cambia estados automáticamente — reemplaza
  el side-effect de `Deuda()` ejecutándose en cada carga de pantalla (Clientes/Pagos/LlamadasDia) del
  legacy.
- DataCrédito es una transición de estado más del Motor, no un flujo aparte.
- El cálculo de pago mínimo por mora vive acá, no en el controlador de Pagos.

---

### Fase 6 — Cartera / Cobranza — Cobrador como entidad, no como rol de usuario

**Este es el cambio de modelo más importante junto con el de Contratos (Fase 4).**

Un **Cobrador** es una entidad de negocio: una cartera/bolsa de clientes. **No está vinculado 1:1 a
un Usuario.** Un Usuario se **asigna** para operar uno (o varios) Cobradores en un momento dado; los
**Clientes se asignan al Cobrador**, no al Usuario directamente. Si el Usuario que operaba un
Cobrador se va o se reasigna, el Cobrador — con toda su cartera de clientes — se reasigna a otro
Usuario en una sola operación, sin tener que tocar cliente por cliente. Esto reemplaza tanto:

- el uso del Role "cobrador" de Spatie como si fuera identidad operativa (el Role sigue existiendo,
  pero pasa a ser **solo permiso de sistema**, separado de qué cartera opera), como
- la tabla `ClientesUsuarios` del legacy, que asigna el cliente directo al usuario que lo creó.

Ítems concretos:

- **CRUD Cobrador** (la "cesta"/cartera): nombre o código identificador, estado activo/inactivo.
- **Asignación Usuario↔Cobrador**: qué usuario opera esa cartera ahora mismo, con historial de quién
  la operó antes (para trazabilidad, no para borrar el rastro al reasignar).
- **Asignación Cliente↔Cobrador**: a qué cartera pertenece cada cliente.
- Catálogo de Motivos/Resultados de gestión ya construido en la Fase 1.
- **CRUD Gestión** (llamada/visita/correo/WhatsApp/SMS/observación): registrar, listar por
  cliente/contrato/usuario.
- **Compromisos** (promesa de pago, acuerdo, reprogramación) como entidad propia vinculada a la
  gestión — explícita, no inferida del motivo como hace el legacy (`AddCall()` decide "¿esto
  programa un pago?" con un `if` sobre el id del motivo).
- **Agenda del cobrador** (bandeja diaria/semanal, clientes prioritarios, promesas por vencer/
  vencidas) — vista agregada de solo lectura sobre Contratos + Pagos Programados + Gestiones, no una
  tabla ni estado propio.

---

### Fase 7 — Pagos

- **CRUD Pago** (registro confirmado): crear, listar, consultar, filtrar por usuario/cobrador/rango
  de fechas.
- **Pago Programado** como entidad propia (no un estado del Pago): programar, confirmar (crea el
  Pago real y notifica al Motor Financiero), descartar (manual o automático por vencimiento, vía el
  job de la Fase 5).
- **Reverso de pago**: acción con permiso elevado — mantener el mismo nivel de exigencia que el
  legacy (hoy pide "superusuario").
- **Recibos**: generación/impresión individual y por lote, control de copias impresas — es una capa
  de presentación sobre el Pago Programado, no lógica de negocio.
- **Listados "cobro del día" / "sin llamar" / "volver a llamar"**: vistas agregadas de solo lectura
  sobre Contratos + Pagos Programados + Gestiones, no tablas ni estados nuevos.

---

### Fase 8 — Devoluciones

- **CRUD Devolución**: registrar, aprobar/rechazar (nuevo respecto al legacy, que genera la
  devolución directo sin paso de aprobación), consultar, listar por fecha/usuario.
- Al generarse, notifica al Motor Financiero para que recalcule — no escribe el estado del contrato
  directamente (a diferencia del legacy, que sí lo hace).

---

### Fase 9 — Reportes (solo lectura)

Construir siempre sobre las entidades ya modularizadas de las fases anteriores, nunca sobre tablas
transaccionales crudas.

- Conteo de clientes por estado.
- Conteo de pagos por estado (programados/confirmados/descartados).
- Cartera por usuario/cobrador (pagos, programados, descartados, totales).
- Totales de cartera por estado.

---

### Fase 10 — Importación

- Reusar los mismos Services de Clientes (Fase 3), Contratos (Fase 4) y Pagos (Fase 7) fila por
  fila para importar desde CSV.
- **No reimplementar** la creación de cliente+contrato+pago a mano — el legacy comete ese error tres
  veces distintas (`Clientes::NewClient`, `Importar::ClientesUp`, `Backup::import_clients_backup`,
  cada una con su propia copia de la lógica de saldo/estado). No repetirlo acá.
- Migración de datos legacy → Recaudify (clientes, contratos, cuotas, pagos, devoluciones, usuarios)
  usa este mismo mecanismo de importación, no un proceso aparte.

---

## Frontend (recaudify-web) — screens por fase

Mismo orden que el backend; cada entidad sigue el patrón ya usado en Users/Roles/Permissions/
Parameters (servicio con signals + componente de listado + componente de formulario, permisos por
`módulo.acción`).

- Fase 1: pantallas de catálogos (genérico, reutilizable entre todos).
- Fase 2: Vendedores, Productos (con edición), Tarifas (con historial).
- Fase 3: Listado/búsqueda de clientes, ficha de cliente (datos + sub-recursos), vista 360°/timeline,
  flujo de detección/fusión de duplicados.
- Fase 4: Evento de venta (como parte del formulario de contrato), listado/detalle de contratos,
  plan de pagos.
- Fase 5: sin pantalla propia (motor interno); sí una vista de solo lectura del estado de cartera
  dentro de la ficha del contrato/cliente.
- Fase 6: administración de Cobradores (CRUD + asignación de Usuario↔Cobrador), asignación
  Cliente↔Cobrador, bandeja de gestión/cobranza, agenda del cobrador.
- Fase 7: registro de pagos, pagos programados (programar/confirmar/descartar), reverso, recibos.
- Fase 8: devoluciones (registrar/aprobar/consultar).
- Fase 9: pantallas de reportes.
- Fase 10: importación (subir CSV, ver resultado).

---

## Explícitamente fuera de este documento (fase futura — no tocar todavía)

Estas son las funcionalidades del roadmap de producto (`demo.md`) que **no vienen del legacy** y que
se decidió no incluir en este plan por ahora. No se detallan acá a propósito — cuando llegue el
momento se retoman desde `demo.md`, no desde este archivo:

Multi Tenancy, SaaS (onboarding, suscripciones, marketplace), Integraciones (API pública, webhooks,
pasarelas de pago, facturación electrónica), Automatizaciones avanzadas (notificaciones, plantillas,
eventos del sistema como automatización), Verificaciones, Documentos/Evidencias, Dashboards
ejecutivo/supervisor/vendedor, Inteligencia Artificial, Portal del Cliente.

---

## Actualizado

2026-07-04
