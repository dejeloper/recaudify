# Recaudify

Plataforma de gestión de cobranza y pagos (Laravel + Angular). Reescritura y desacople de Católikas Cobranza (CodeIgniter 3). Contexto adicional en `funcionalidades.md` (inventario del legacy) y `NEGOCIO.md` (análisis de desacople por módulo).

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
- [ ] Password por defecto al resetear contraseña, parametrizable (solo si se implementa esa función; legacy usa `Cobranza123` hardcodeado)

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

- [ ] Pantalla Vendedores
- [ ] Pantalla Productos (con edición)
- [ ] Pantalla Tarifas (con historial)

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

2026-07-05
