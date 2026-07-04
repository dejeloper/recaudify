# NEGOCIO — Requerimientos del Core de Negocio

> Documento de **memoria de requerimientos**, no un checklist para tachar. Su objetivo es que no se
> te olvide qué hay que construir ni por qué, mientras avanzás módulo a módulo con asistencia de IA
> sin perder el control del diseño.
>
> **Fuente:** análisis de `funcionalidades.md` (inventario del legacy CodeIgniter 3, `cobranza_files/`).
> **Filtro aplicado:** solo lo que el legacy **realmente hace hoy**. No se agregan features nuevas
> del roadmap (`demo.md`) que el legacy no tenía — esas ya están en `planning.md`.
> **Transformación aplicada:** el legacy acopla entidades y lógica dentro de un mismo controlador
> (p. ej. `Clientes::NewClient()` crea Cliente + Dirección + Referencias + Evento + Pedido + Producto
> + Pago en una sola función). Este documento **desacopla** cada entidad en su propio módulo con CRUD
> estándar, y separa la lógica de negocio (cálculos, transiciones de estado) en servicios/motores
> independientes que los módulos consumen — no que cada módulo reimplemente.
>
> Versión: 1.0 · Última actualización: 2026-07-04

---

## 0. Por qué este documento existe

Vas a seguir haciendo *vibecoding* para tu negocio (no es un proyecto que vayas a entregar a un
equipo que lo revise línea por línea), así que el mayor riesgo no es "el código quedó feo" — es
**perder de vista qué falta, duplicar lógica entre módulos, o volver a acoplar cosas que ya
desacoplaste**. Por eso cada módulo de este documento explica:

1. **Qué hacía el legacy** (para no perder una regla de negocio que sí importa).
2. **Cómo se desacopla** en el nuevo sistema (qué entra en qué módulo, qué es catálogo/parámetro).
3. **Qué CRUD/operaciones necesita**, siguiendo siempre el mismo patrón (ver sección 1).

---

## 1. Convención estándar de CRUD (aplica a **todo** módulo nuevo)

Antes de escribir el primer módulo del Core, fijar esto evita que cada entidad nueva se sienta
"distinta" y facilita agregar piezas sin pensar el patrón de nuevo.

**Backend (Laravel)** — ya está fijado en `CLAUDE.md`, repetido aquí porque es la regla más
importante de todo este documento:

- Modelo con `SoftDeletes` siempre (nunca DELETE físico salvo GDPR/legal explícito).
- `{Entity}Repository` → queries. `{Entity}Service` → una responsabilidad por método.
- Controller delgado: Form Request valida → Service ejecuta → API Resource responde.
- Operaciones estándar por entidad con soft-delete: `index` (paginado + filtros), `show`,
  `store`, `update`, `destroy` (soft), `trashed`, `restore`, y `search/{term}` cuando el módulo lo
  necesite (patrón ya usado en `UserController::search`).
- Toda escritura relevante genera actividad (`spatie/activitylog`, ya integrado — reemplaza el
  `LogSave()` del legacy sin que cada módulo tenga que implementar su propio log).
- Nada de reglas de negocio "de una sola vez" dentro del controller: si dos módulos necesitan el
  mismo cálculo (p. ej. saldo, mora), ese cálculo vive en **un solo servicio** (ver Motor Financiero,
  sección 5) y ambos lo consumen — así se evita el patrón legacy de tener la misma fórmula copiada en
  `Pagos`, `Clientes` e `Importar`.

**Frontend (Angular)** — mismo patrón por módulo, ya usado en Users/Roles/Permissions/Parameters:

- `{entity}.service.ts` con signals: `items`, `loading`, `showTrashed`/`showDisabled`, métodos
  `load()`, `toggleTrashed()`, `remove()`, `restoreItem()`, CRUD crudo delegando a `ApiService`.
- Componente de listado + componente de formulario separados (`{entity}s.ts` / `{entity}-form.ts`).
- Permisos por módulo.acción vía `AuthService.hasPermission()`, igual que ya existe.

**Parametrización (en vez de hardcodear como el legacy):**

El legacy tiene reglas de negocio *hardcodeadas en código* que en Recaudify deben vivir en el módulo
`Parameters` (ya existe la infraestructura — `ParameterType`, `/admin/parameters`). Ejemplos
puntuales detectados en el legacy que **no deben volver a quedar hardcodeados**:

- Umbrales de días de mora (`≤10 / 11–44 / 45–89 / ≥90`) — hoy hardcodeados en `Deuda()`.
- Días de vencimiento de un pago programado antes de descartarlo automáticamente (`60` días).
- Ventana de validez de una gestión de llamada antes de inhabilitarla (`1–8` días según motivo).
- Contraseña por defecto al resetear (`Cobranza123`) — no debería ser un valor fijo en código.
- Redondeo del pago mínimo por mora (al millar) — parametrizable si cambia por tenant.

---

## 2. Clientes

### Legacy (acoplado)

`Clientes::NewClient()` es una única transacción gigante: crea Cliente + Dirección + hasta 3
Referencias + Evento (vendedor/iglesia/barrio/fecha) + Pedido + ProductoPedido + Pago inicial
opcional, todo en un solo POST. La ficha (`Consultar`) también mezcla cliente+dirección+pedido en
una sola pantalla/consulta.

### Desacoplado — módulos

- **CRUD Clientes** (identidad, sin pedido): documento (único por tenant), nombre, tipo de
  documento, tipo de vivienda, observaciones (histórico, no solo "concatenar string" como el
  legacy). Búsqueda por nombre/documento/teléfono/dirección/estado (`Buscar`, `SearchJson`,
  `SearchJsonAsignado` del legacy → un único endpoint `search` con filtros).
- **Direcciones** como sub-recurso de Cliente (1\:N — legacy asume 1 dirección, decoupled ya lo
  admite el roadmap): dirección, etapa/torre/apto/manzana/interior/casa, barrio, zona.
- **Teléfonos** como sub-recurso de Cliente (legacy tiene 3 campos fijos `Tel1/Tel2/Tel3` → pasar a
  relación 1\:N real).
- **Referencias** como catálogo reutilizable + `ReferenciasCliente` (vínculo) — legacy limita a 3,
  el nuevo modelo no debería tener ese límite duro (o parametrizarlo si se quiere mantener).
- **Detección y fusión de duplicados** — el legacy no lo tiene implementado; es nuevo pero
  imprescindible porque al desacoplar identidad se vuelve más fácil crear duplicados por error.
- **Asignación Cliente↔Usuario** (`ClientesUsuarios`) — quién puede operar sobre qué cliente. Debe
  ser una relación independiente, no algo que se decide solo al crear el cliente (legacy: se asigna
  automáticamente al usuario que lo creó).
- **Vista 360° / Timeline** — agregación de lectura (no una tabla propia): pedidos, pagos,
  gestiones, documentos, cambios de estado, en una sola consulta ordenada cronológicamente. Reemplaza
  `Clientes::Log()`/`VerLog()` (que hoy muestra el log crudo del helper `LogSave`) con algo legible.
- **Conteo por estado** (`Contador`, `ConteoClientes`) — un reporte, no lógica del módulo Clientes.

---

## 3. Vendedores

### Legacy (acoplado)

`Vendedores` no tiene CRUD propio (solo `obtenerVendedoresCod()` por AJAX); el vendedor se crea
implícitamente como parte del `Evento` al dar de alta un cliente.

### Desacoplado

- **CRUD Vendedores** independiente (ya existe en el frontend como catálogo — falta el modelo
  backend real, ver nota en `Lista_test.md`).
- **Vinculación Vendedor↔Pedido/Contrato** vía el Evento de venta (sección 4), no vía el cliente
  directamente — un vendedor participa en la *venta* (pedido), no "es dueño" del cliente.
- Estadísticas de vendedor (ventas del período, clientes nuevos) — reporte, no CRUD.

---

## 4. Evento de venta (Vendedor + Iglesia/Barrio + Fecha)

### Legacy (acoplado)

`Eventos` combina tres conceptos en una sola tabla: vendedor, "iglesia" (organización/canal) y
barrio, más la fecha del evento de venta. Se crea sobre la marcha dentro de `NewClient()` si no
existe la combinación.

### Desacoplado

- **Catálogo "Canal/Organización"** (lo que el legacy llama iglesia) — independiente.
- **Catálogo Zonas/Barrios** — independiente (ya está insinuado como "zonas" en `Direcciones`).
- **Evento de venta** = combinación Vendedor + Canal + Zona + Fecha, como entidad propia vinculada al
  Pedido/Contrato (no al Cliente). Así un cliente puede tener contratos de distintos eventos/vendedores
  sin reescribir su ficha.

---

## 5. Pedidos → Contratos

### Legacy (acoplado)

Un Cliente tiene **un** Pedido implícito (relación 1:1 de facto, aunque el modelo no lo fuerce
explícitamente); el Pedido nace con estado 110 dentro de `NewClient()`, junto con su primer
`ProductoPedido`. Cambiar tarifa, cambiar fecha de cobro y agregar productos son acciones separadas
sobre ese mismo Pedido.

### Desacoplado

- **CRUD Contratos** (renombre de "Pedido"), con relación **N contratos por 1 cliente** (ya está en
  `planning.md`, se confirma aquí que el legacy nunca lo necesitó porque no había necesidad de negocio
  para eso — pero es la pieza que más valor agrega al desacoplar).
- **Ciclo de vida del contrato** explícito (borrador/activo/suspendido/cancelado/finalizado), en vez
  de inferirlo de combinaciones de estado numérico (110/111/112/113/114/125/127) como hace el legacy.
- **Cambio de tarifa** (`CambioTarifa`/`changeRate`) — acción propia del contrato, que dispara
  recálculo en el Motor Financiero (sección 7), no que el controlador de Clientes actualice `Pagos`
  directamente como hoy.
- **Cambio de fecha de cobro** (`CambioFecha`/`ChangePayDate`) — acción propia del contrato.
- **Productos del contrato** (`ProductosPedidos`) — sub-recurso: agregar/quitar producto,
  recalculando valor total vía Motor Financiero (el legacy hace la suma a mano dentro de
  `AddProducto()`).
- **Refinanciación / Reestructuración / Congelación** — no existen en el legacy (es lógica nueva del
  roadmap, no de este inventario), pero se anota acá porque el ciclo de vida del contrato es el lugar
  natural para agregarlas después sin volver a tocar Clientes.

---

## 6. Productos y Tarifas

### Legacy

`Productos` y `Tarifas` son catálogos simples (CRUD casi inexistente, solo lookup por AJAX). Ya
están en el frontend (`recaudify-web` tiene las pantallas construidas como catálogo de referencia),
falta el modelo backend real (fue removido intencionalmente — ver `Lista_test.md`, sección de
catálogos pendientes de replanteo).

### Desacoplado

- **CRUD Productos** (nombre, categoría, estado activo/inactivo).
- **CRUD Tarifas** por producto (cuotas, valor, descuento) con **historial de cambios** — el legacy
  no versiona tarifas, solo sobreescribe.
- Nada de esto conoce al Cliente directamente; solo se referencia desde el Contrato.

---

## 7. Motor Financiero (Cartera / Deuda)

### Legacy (acoplado — es la pieza más "mezclada" del sistema)

La función global `Deuda()` recorre pedidos activos y, en la misma pasada: calcula días de atraso,
decide el nuevo estado, actualiza `Pedidos` y `Clientes` directamente, escribe en
`ValidacionDeudas`, inhabilita llamadas vencidas (`validarLlamadas()`) y registra Log. Se ejecuta
como side-effect al cargar `Clientes`, `Pagos` o `LlamadasDia` — no es un job aislado.

### Desacoplado

- **Motor Financiero** como servicio único, fuente de verdad para:
  - Calcular saldo, cuota, capital, interés, descuento, mora de un contrato.
  - Recalcular saldo de contrato/cliente/cartera tras pago, reverso, cambio de tarifa o devolución.
  - Clasificar el **estado de cartera** del contrato según días de atraso (al día / próximo
    vencimiento / mora temprana / mora avanzada / prejurídico / jurídico / castigado / paz y salvo —
    superset de los 4 estados legacy, parametrizable en umbrales, ver sección 1).
- **Job programado** (reemplaza el side-effect de `Deuda()` en cada request): recalcula cartera,
  cambia estados, vence promesas — corre en background, no en cada carga de pantalla.
- **Ningún otro módulo calcula saldo por su cuenta.** Pagos, Devoluciones, Contratos y Cobranza
  *notifican* al Motor Financiero y *leen* su resultado — nunca reimplementan la fórmula (esto es
  exactamente el `## Principios del Core` que ya quedó anotado en `demo.md`, y la razón por la que el
  legacy tiene la misma lógica de "saldo ≤ 0 → estado 111/114" repetida en `conf()`, `Reverse()`,
  `changeRate()` e `Importar/conf()`).
- **DataCrédito** (`ReportarData`) es una transición de estado más del Motor, no un flujo aparte.
- **Pago mínimo por mora** (`calcularSaldoMinimo`) vive acá, no en el controlador de Pagos.

---

## 8. Pagos

### Legacy (acoplado)

`Pagos` mezcla: listado de cobro del día, programación de recibo, confirmación, descarte, reverso,
morosos, DataCrédito, impresión de recibos y reportes — todo en un mismo controlador gigantesco
(`Pagos.php`), con la lógica de recálculo de saldo duplicada dentro de `conf()`, `desc()` y
`Reverse()`.

### Desacoplado

- **CRUD Pago** (registro final, confirmado): crear, listar, consultar, filtrar por
  usuario/cobrador/rango de fechas.
- **Pago Programado** como entidad propia (no un estado del Pago): programar, confirmar (crea el
  Pago real y notifica al Motor Financiero), descartar (manual o automático por vencimiento —
  scheduler, ver sección 7), listar/filtrar.
- **Reverso de pago** — acción propia con permiso elevado (el legacy exige "superusuario" vía
  `validarPermisoAdmin`; mantener ese requisito de permiso más estricto para esta acción específica).
- **Recibos** — generación/impresión (individual y por lote) es una capa de *presentación* sobre
  Pago Programado, no lógica de negocio. Control de copias impresas (`agregarCopia`) es un
  contador auxiliar, no debería vivir mezclado con la confirmación del pago.
- **Listado "cobro del día"** (`obtenerListadosClientesCobro`, `NoLlamada`, `Rellamar`) es una
  *vista agregada de solo lectura* sobre Contratos + Pagos Programados + Gestiones — no un módulo con
  su propio estado ni tablas nuevas.

---

## 9. Cobranza (Gestión de llamadas)

### Legacy (acoplado)

`Cobradores::AddCall()` decide comportamiento distinto según el `MotivoLlamada` (104 = reagenda con
fecha, 101 = programa pago, otros = registro simple), todo en un mismo método, escribiendo a la vez
en `Llamadas`, `DevolucionLlamadas` y potencialmente `PagosProgramados`.

### Desacoplado

- **Catálogo Motivos de gestión / Resultados de gestión** (ya en `planning.md` como catálogo de
  configuración) — independiente de la lógica de registro.
- **CRUD Gestión** (llamada/visita/correo/WhatsApp/SMS/observación): registrar, listar por
  cliente/contrato/usuario.
- **Compromisos** (promesa de pago, acuerdo, reprogramación) como entidad propia vinculada a la
  Gestión, no un side-effect oculto según el motivo (el legacy decide "¿esto programa un pago?" con
  un `if` sobre el id del motivo — en el nuevo sistema el compromiso se crea explícitamente,
  independiente de qué motivo disparó la gestión).
- **Agenda del cobrador** (bandeja diaria/semanal, clientes prioritarios, promesas por vencer/vencidas)
  — vista agregada de solo lectura, igual que el listado de cobro de Pagos.
- **Asignación de clientes a cobrador** — ya cubierto por Clientes↔Usuario (sección 2); Cobranza solo
  lee esa asignación, no la administra.

---

## 10. Devoluciones

### Legacy

Bastante ya desacoplado en sí mismo: `Devoluciones::Generar()` crea el registro, cambia estado del
contrato y del cliente, y anula pagos programados pendientes. Es un buen ejemplo de un módulo legacy
que **no** hace falta romper mucho, solo alinearlo al Motor Financiero (que sea el Motor quien decide
el nuevo estado del contrato, no `Devoluciones` directamente).

### Desacoplado

- **CRUD Devolución**: registrar, aprobar/rechazar (nuevo — el legacy no tiene aprobación, genera
  directo), consultar, listar por fecha/usuario.
- Al generarse, notifica al Motor Financiero (recalcula) en vez de escribir el estado del contrato
  directamente.

---

## 11. Importación y Backup

### Legacy (acoplado)

`Importar::ClientesUp()` y `Mantenimiento/Backup` reimplementan **desde cero** la misma lógica de
"crear cliente + dirección + evento + pedido + producto + pago" que ya existe en
`Clientes::NewClient()`, con sus propias copias de `conf()`/`History()`. Es la duplicación más clara
de todo el legacy.

### Desacoplado

- **Importación de Clientes/Contratos/Pagos desde CSV** debe reusar los mismos Services de Clientes,
  Contratos y Pagos fila por fila — nunca reimplementar la creación a mano como hace el legacy tres
  veces (`NewClient`, `Importar::ClientesUp`, `Backup::import_clients_backup`).
- **Backup completo** (export/import) es una utilidad de migración de datos, no un módulo de negocio;
  vive en `planning.md` bajo "Migración" más que como feature de producto.

---

## 12. Reportes

Todos de solo lectura, ya identificados 1:1 en el legacy — no requieren desacoplar nada, solo
construirse sobre las entidades ya modularizadas (nunca sobre tablas transaccionales crudas, como ya
quedó anotado en `demo.md`):

- Conteo de clientes por estado.
- Conteo de pagos por estado (programados/confirmados/descartados).
- Cartera por usuario/cobrador (pagos, programados, descartados, totales).
- Totales de cartera por estado.

---

## 13. Usuarios, Permisos y Auditoría

### Legacy

Ya migrado y mejorado en Recaudify (Spatie Roles/Permissions + `spatie/activitylog`). Una sola nota
de negocio a preservar del legacy: el sistema de permisos legacy (`Permisos`/`PermisosUsuarios`) en
la práctica **no bloqueaba nada** (`validarPermiso*` devuelve `true` salvo para un usuario
hardcodeado) — es decir, el legacy operó años sin control de acceso real. Vale la pena documentarlo
para no asumir "en legacy esto estaba protegido" al migrar una regla: **no lo estaba**, así que hay
que decidir explícitamente el permiso correcto en cada endpoint nuevo, no copiar el legacy.

- Reset de contraseña con valor por defecto (`Cobranza123`) → si se replica esta funcionalidad,
  el valor debe salir de `Parameters`, nunca hardcodeado (ver sección 1).

---

## Resumen — módulos nuevos a construir (orden sugerido por dependencia)

1. Catálogos base: Tipos de documento, Tipos de vivienda, Canal/Organización, Zonas, Motivos de
   gestión (desbloquean todo lo demás y son CRUD simples — buen calentamiento del patrón estándar).
2. Vendedores + Productos + Tarifas (catálogos con algo más de forma).
3. Clientes (identidad, contactos, referencias, duplicados).
4. Evento de venta (vendedor + canal + zona + fecha).
5. Contratos (N por cliente) + Productos del contrato.
6. Motor Financiero (cálculo de saldo/cuota/mora + clasificación de cartera + job programado).
7. Pagos + Pagos Programados + Recibos.
8. Cobranza (gestiones, compromisos, agenda).
9. Devoluciones.
10. Reportes.
11. Importación (reusando los Services de 3, 5 y 7 — nunca reimplementar).

Cada uno de estos, al implementarse, debe volver a este documento y confirmarse contra su sección
antes de darse por "terminado" (no por checklist marcado, sino releyendo si el desacople sigue
teniendo sentido una vez hay código real).
