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
> Versión: 1.0 · Última actualización: 2026-07-05

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

- Borrado y estado siguen la **"Convención de borrado y estado"** de `planning.md` — ya no es
  "`SoftDeletes` siempre": cada entidad cae en uno de tres grupos, y hay que decidir explícitamente
  a cuál pertenece antes de generar el modelo:
  1. **`SoftDeletes`** — "eliminar" es solo dejar de aparecer en listados activos, recuperable:
     Cliente, Vendedor, Cobrador, Producto, Tarifa y todos los catálogos simples (Tipos de
     documento, Canal/Organización, Zonas, Motivos/Resultados de gestión).
  2. **Estado explícito, sin `SoftDeletes` como mecanismo de negocio** — Contrato (ciclo de vida
     borrador/activo/suspendido/cancelado/finalizado); `SoftDeletes` ahí solo como salvavidas para
     "se creó por error y nunca debió existir", nunca para cancelación/finalización.
  3. **Sin borrado lógico, eventos de historial que se corrigen con otro evento, nunca se
     ocultan** — Pago, Pago Programado, Devolución, Gestión/Llamada, Compromiso. También aplica al
     log de auditoría (`spatie/activitylog`): inmutable, sin ningún mecanismo de borrado.
  4. **DELETE físico permitido** — solo datos verdaderamente transitorios que nunca se confirmaron
     como hecho de negocio (borradores de contrato nunca activados, filas de importación fallidas).
- `{Entity}Repository` → queries. `{Entity}Service` → una responsabilidad por método.
- Controller delgado: Form Request valida → Service ejecuta → API Resource responde.
- Operaciones estándar por entidad **del grupo 1** (`SoftDeletes`): `index` (paginado + filtros),
  `show`, `store`, `update`, `destroy` (soft), `trashed`, `restore`, y `search/{term}` cuando el
  módulo lo necesite (patrón ya usado en `UserController::search`). Las entidades de los grupos 2 y
  3 **no** exponen `destroy`/`trashed`/`restore` — su "baja" es una transición de estado o un evento
  nuevo (reverso, descarte, cancelación), nunca un soft-delete.
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

- Umbrales de días de mora (`≤10 / 11–44 / 45–89 / ≥90`) — hoy hardcodeados en `Deuda()`. **No se
  resuelve como `Parameters` escalares**: es una lista de reglas relacionadas (rango de días →
  estado resultante), así que se modela como su propio catálogo ("Días de cambio de estado") con
  filas `día_desde`/`día_hasta`/`estado_resultante`/`color`/`icono` — el orden de evaluación se
  deriva de `día_desde`, no necesita un campo `orden` aparte. El `color`/`icono` de la regla vigente
  es lo que permite pintar el estado de cartera de un contrato en listados y en la Vista 360° del
  cliente sin mapear el nombre del estado a un color a mano en cada pantalla.
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
- **Asignación Cliente↔Cobrador** (reemplaza `ClientesUsuarios`) — a qué cartera pertenece el
  cliente, no a qué Usuario. El Cobrador es una entidad propia (una bolsa de cartera), independiente
  de qué Usuario la opera en un momento dado (sección 9) — el legacy asigna el cliente directo al
  usuario que lo creó; eso no aplica más.
- **Invariante**: todos los Contratos de un mismo Cliente pertenecen siempre a la misma cartera
  (mismo Cobrador) — nunca se reparten entre carteras distintas, ni siquiera para casos jurídicos o
  especiales (confirmado como regla sin excepción; ver sección 9). Se valida en el Service, no hace
  falta una restricción de base de datos.
- **Vista 360° / Timeline** — agregación de lectura (no una tabla propia): contratos, pagos,
  Interacciones (contactos reales, sección 9) con el detalle de Gestión por contrato dentro de cada
  una, documentos, cambios de estado — en una sola consulta ordenada cronológicamente. Reemplaza
  `Clientes::Log()`/`VerLog()` (que hoy muestra el log crudo del helper `LogSave`) con algo legible.
- **Indicador agregado de estado de cartera** (nuevo, no existe en legacy): un *valor calculado de
  lectura*, nunca una columna propia, que muestra el peor estado de cartera entre los contratos
  activos del cliente — un cliente con 3 contratos donde 1 está en mora avanzada se lista como "mora
  avanzada", no como un promedio ni exige que los 3 estén al día para considerarse "al día". Sirve
  para listados/búsqueda; el detalle real siempre vive en cada Contrato.
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
- **Nunca fusionar una compra nueva dentro de un contrato existente con saldo** — hoy, cuando un
  cliente con deuda compra otro producto, la práctica informal es dejarlo "como el mismo producto"
  del contrato vigente. Confirmado que esa práctica se rompe: cada compra es siempre un **Contrato
  nuevo**, independiente del saldo que el cliente ya tenga en otros. La consolidación de cobro entre
  varios contratos de un mismo cliente se resuelve en Cobranza (sección 9), no fusionando contratos.
- **Ciclo de vida del contrato** explícito (borrador/activo/suspendido/cancelado/finalizado), en vez
  de inferirlo de combinaciones de estado numérico (110/111/112/113/114/125/127) como hace el legacy.
  Grupo 2 de la convención de borrado (`planning.md`): estado explícito como mecanismo principal,
  `SoftDeletes` solo como salvavidas si el contrato nunca debió existir — nunca para cancelar/finalizar.
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
    superset de los 4 estados legacy), leyendo las reglas del catálogo "Días de cambio de estado"
    (día_desde/día_hasta/estado_resultante/color/icono, ver sección 1) en vez de umbrales
    hardcodeados o parámetros escalares sueltos.
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

> Grupo 3 de la convención de borrado (`planning.md`): Pago y Pago Programado no tienen borrado
> lógico ni booleano; reverso/descarte se modelan como evento propio vinculado al registro
> original, que nunca se oculta de los listados.

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
  *vista agregada de solo lectura* sobre Contratos + Pagos Programados + Gestiones, agrupada **por
  Cliente** (sección 9) — no un módulo con su propio estado ni tablas nuevas, y no una fila por
  Contrato (eso duplicaría al mismo cliente si tiene varios contratos venciendo el mismo día).

---

## 9. Cobranza (Gestión de llamadas)

### Legacy (acoplado)

`Cobradores::AddCall()` decide comportamiento distinto según el `MotivoLlamada` (104 = reagenda con
fecha, 101 = programa pago, otros = registro simple), todo en un mismo método, escribiendo a la vez
en `Llamadas`, `DevolucionLlamadas` y potencialmente `PagosProgramados`.

### Desacoplado

> Grupo 3 de la convención de borrado (`planning.md`): Gestión/Llamada y Compromiso son eventos de
> historial, sin borrado lógico; se corrigen con otro evento, nunca se ocultan ni se marcan como
> borrados. (Los catálogos Motivos/Resultados de gestión sí son grupo 1, `SoftDeletes`.)

- **Catálogo Motivos de gestión / Resultados de gestión** (ya en `planning.md` como catálogo de
  configuración) — independiente de la lógica de registro.
- **Interacción vs. Gestión — dos niveles, no uno.** El legacy registra una fila por contacto
  asumiendo 1 cliente = 1 pedido; con N contratos por cliente eso ya no alcanza. Se separan:
  - **Interacción**: el contacto real (Cliente, Usuario, medio — llamada/visita/correo/WhatsApp/SMS,
    fecha, observación general). **Una fila por contacto real**, sin importar cuántos contratos
    toque — llamar una vez a alguien con 3 contratos en mora sigue siendo *una* Interacción.
  - **Gestión** (por contrato): el efecto de esa Interacción sobre un Contrato puntual — motivo,
    resultado, y el Compromiso si lo hay. Referencia a la Interacción (para saber que vinieron del
    mismo contacto) y al Contrato. Necesaria como fila separada porque en la misma llamada el
    cliente puede responder distinto por cada deuda (promete pagar el contrato A, rechaza el B).
- **Compromisos** (promesa de pago, acuerdo, reprogramación) como entidad propia vinculada a la
  Gestión (por contrato, no a la Interacción) — no un side-effect oculto según el motivo (el legacy
  decide "¿esto programa un pago?" con un `if` sobre el id del motivo — en el nuevo sistema el
  compromiso se crea explícitamente, independiente de qué motivo disparó la gestión). Una sola
  Interacción puede generar varios Compromisos a la vez, uno por cada contrato prometido.
- **Cadencia de cobro consolidada por Cliente**: el ciclo de contacto regular es **una Interacción
  por mes por Cliente**, sin importar cuántos contratos tenga — no una llamada por contrato. Llamar
  a la misma persona varias veces por mes por productos distintos es exactamente lo que se quiere
  evitar (mala experiencia + mal uso del tiempo del cobrador).
- **Agenda del cobrador** (bandeja diaria/semanal, clientes prioritarios, promesas por vencer/
  vencidas) — vista agregada de solo lectura, agrupada **por Cliente** (no por Contrato): un cliente
  con 3 contratos vencidos aparece como una sola tarea, con el detalle de los 3 contratos adentro.
- **Asignación Cliente↔Cobrador** (sección 2) — Cobranza lee esa asignación, no la administra.
  **Invariante confirmada, sin excepción**: todos los Contratos de un mismo Cliente viven siempre en
  la misma cartera (mismo Cobrador), incluso si alguno llega a estado jurídico/castigado — no se
  reparten entre carteras distintas. Un contrato en estado especial se gestiona distinto *por su
  propio estado de cartera* (sección 7), no moviendo al cliente de cobrador.

---

## 10. Devoluciones

### Legacy

Bastante ya desacoplado en sí mismo: `Devoluciones::Generar()` crea el registro, cambia estado del
contrato y del cliente, y anula pagos programados pendientes. Es un buen ejemplo de un módulo legacy
que **no** hace falta romper mucho, solo alinearlo al Motor Financiero (que sea el Motor quien decide
el nuevo estado del contrato, no `Devoluciones` directamente).

### Desacoplado

> Grupo 3 de la convención de borrado (`planning.md`): sin borrado lógico — es un evento de
> historial, no se oculta ni se marca como borrado.

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

- **Conteo de clientes por estado** — cuenta personas, usa el indicador agregado por Cliente
  (sección 2: peor estado entre sus contratos activos).
- Conteo de pagos por estado (programados/confirmados/descartados).
- Cartera por usuario/cobrador (pagos, programados, descartados, totales).
- **Totales de cartera por estado** — suma valores, siempre por Contrato (sección 7), nunca por
  Cliente. "Clientes en mora" (cuenta personas) y "cartera en mora" (suma dinero) son dos métricas
  distintas que responden preguntas distintas — no conviene mezclarlas en un solo número.

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
- **Usuario no necesita campo de estado propio**: el legacy usa un código 101/102 (Activo/Inactivo)
  que en la práctica es sinónimo de `Habilitado`. En Recaudify ya está cubierto por `SoftDeletes`
  (activo = no borrado, inactivo = soft-deleted) — agregar un enum de estado encima duplicaría lo
  que `deleted_at` ya resuelve.

### Otras tablas del legacy que no hay que replicar tal cual

`funcionalidades.md` §0.1/§0.2 lista una tabla `Estados` única compartida por Cliente, Pedido,
Usuario y Pago Programado, con códigos que conviven por convención de rango, no por diseño — es la
raíz del mismo problema de mezclar conceptos que ya se resolvió módulo por módulo en este documento
(cada entidad con su propio campo de estado tipado, nunca una tabla `Estados` compartida). Además:

- **`Perfiles`** (catálogo de roles) y **`Permisos`/`PermisosUsuarios`/`TiposPermisos`** ya están
  reemplazados por Roles/Permissions de Spatie.
- **`Administradores`** — lista aparte de "superusuarios" que gatea acciones críticas en el legacy;
  es un segundo sistema de autorización paralelo y hardcodeado. El Role `administrador` de Spatie ya
  cubre este caso; no crear una segunda lista.
- **`LogAccesosDenegados`** — log de accesos denegados; es auditoría, no catálogo. Lo reemplaza
  `spatie/activitylog` (o un log de autorización fallida dentro del mismo mecanismo).
- **`ValidacionDeudas`** — historial de cada corrida de `Deuda()`; es el log de ejecución de un job,
  no un catálogo ni un estado. Lo reemplaza el historial/auditoría del Job programado del Motor
  Financiero (sección 7).
- **Pago Programado** tiene su propio enum (Programado/Pagado/Descartado) directo en su tabla, sin
  pasar por ninguna tabla de estados compartida — es un conjunto cerrado de 3 valores intrínseco a
  esa entidad (sección 8).
- **Gestión/Llamada** no tiene un estado de negocio: el legacy la "inhabilita"
  (`validarLlamadas()`) cuando vence su ventana de validez. En Recaudify eso es una **marca de
  vigencia** (p. ej. `invalidated_at` en el propio registro), no un borrado ni un estado — el evento
  sigue visible en el historial siempre, solo deja de ser "accionable" para la agenda (sección 9).

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
