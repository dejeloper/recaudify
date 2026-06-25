# Plan de Ejecución — Recaudify

> Hoja de ruta **ordenada por fases** para reescribir el sistema legacy (CodeIgniter 3, ver
> `funcionalidades.md`) en Recaudify (Laravel API + Angular). Define **en qué orden** se construye y
> **cómo** se aborda cada bloque, respetando dependencias.
>
> - **`funcionalidades.md`** = el _qué_ (goal de comportamiento del legacy).
> - **`planning.md`** = checklist plano de tareas por área (estado vivo de avance).
> - **Este archivo** = el _cómo y en qué orden_ (secuencia por fases con dependencias).
>
> **Decisión de modelado ya tomada** (en `planning.md`): se **moderniza** el modelo, no se replica el
> legacy 1:1. En particular: cliente → **muchos contratos** (rompe el 1:1 cliente↔pedido del legacy),
> identidad de cliente por documento, catálogos de estado nuevos. El comportamiento de negocio sí se
> replica según `funcionalidades.md`.

---

## Estado actual (Fase 0 — completada)

Ya construido y funcionando, base sobre la que se apoya todo lo demás:

- **API:** infra Laravel + MySQL, JWT (login/refresh/logout/me), CORS, Swagger, Form Requests, soft
  deletes, formato estándar de respuestas. Módulos: **Usuarios, Roles, Permisos, Parámetros, Horarios**.
  Patrón establecido: `Controller → Service → API Resource`, rutas con middleware `permission:modulo.accion`.
- **Front:** Angular zoneless + signals, `ApiService`, auth (login/register, token, guards,
  interceptores), `permissionGuard`, `AuthService.can()`, navegación/app-shell, admin de
  usuarios/roles/permisos/parámetros/horarios, componentes `Spinner` y `Toast`.

> El plan de ejecución cubre **lo que falta**: todo el dominio de cobranza.

---

## Patrón de trabajo por funcionalidad (se repite en todas las fases)

Para **cada** entidad/funcionalidad nueva, el trabajo se descompone así (reutilizando los patrones ya
establecidos en Usuarios/Roles):

**Backend (`recaudify-api/`)**

1. Migración de tabla(s) (con `SoftDeletes`, montos en enteros).
2. Modelo Eloquent + relaciones.
3. Form Request(s) para validación.
4. Service/Action por caso de uso (`CrearClienteService`, etc.) — la lógica vive aquí, no en el controlador.
5. API Resource(s) para la respuesta.
6. Controlador delgado + rutas con `permission:modulo.accion`.
7. Seeders de catálogos / permisos nuevos.
8. Tests (PHPUnit).

**Frontend (`recaudify-web/`)**

1. Interfaces/modelos TS (`*.model.ts`).
2. Servicio de feature (consume `ApiService`).
3. Componente(s) `OnPush` + signals, ruta `loadComponent` lazy.
4. Protección con `permissionGuard` + `*appHasPermission`.

---

## Componentes transversales a habilitar temprano

Estos no son un módulo de negocio pero los necesitan varias fases; conviene resolverlos antes o en
paralelo con la Fase 1:

| Transversal                                       | Por qué / quién lo necesita                                                           | Fase sugerida   |
| ------------------------------------------------- | ------------------------------------------------------------------------------------- | --------------- |
| **Auditoría / Log** (equivalente a `LogSave`)     | Casi toda escritura del legacy registra Log. Lo usan Clientes, Pagos, Gestiones, etc. | Antes de Fase 2 |
| **Manejo de montos** (enteros COP, sin decimales) | Pedidos, cuotas, pagos, saldos.                                                       | Fase 1          |
| **Paginación estándar** (`data:{items,meta}`)     | Todos los listados grandes (clientes, pagos).                                         | Fase 1          |
| **Storage de archivos**                           | Evidencias/comprobantes en Gestiones y Pagos.                                         | Antes de Fase 4 |
| **Scheduler + Queue** (cron cPanel)               | Recálculo de mora (`Deuda()`) y descarte automático de recibos vencidos.              | Antes de Fase 4 |
| **Catálogo de estados + reglas de mora**          | Reglas 10/45/90 días → al día/debe/mora/datacrédito.                                  | Fase 1          |

---

## Fases (orden por dependencias)

| Fase | Bloque                                   | Depende de | Mapea a `funcionalidades.md` |
| ---- | ---------------------------------------- | ---------- | ---------------------------- |
| 1    | Catálogos base + transversales           | Fase 0     | §4, §0.1                     |
| 2    | Clientes                                 | Fase 1     | §3                           |
| 3    | Contratos (Pedidos), Productos y Cartera | Fase 2     | §3 (pedido), §5 (plan)       |
| 4    | Pagos y Recibos                          | Fase 3     | §5                           |
| 5    | Cobranza / Gestiones (llamadas)          | Fase 4     | §6, §7                       |
| 6    | Devoluciones                             | Fase 4     | §8                           |
| 7    | Reportes                                 | Fases 2–5  | §10, §5.6                    |
| 8    | Importación y Backup                     | Fases 2–4  | §9, §14                      |
| 9    | Migración de datos legacy + Despliegue   | Todas      | —                            |

---

### Fase 1 — Catálogos base y transversales

**Objetivo:** dejar disponibles todos los catálogos y reglas de los que dependen Clientes y Contratos.

**Backend**

- Catálogos CRUD + seeders: `TiposDocumentos`, `TiposVivienda`, `Productos`, `Tarifas` (tarifa↔producto),
  `Vendedores`, `Eventos`, `MotivosLlamadas`, `Zonas`.
- **Estados** de cliente/contrato/cobranza/recibo como catálogo nuevo (mapeando los códigos legacy de
  `funcionalidades.md` §0.1 a estados con nombre/clave manejables).
- **Parámetros de negocio** (reusa el módulo `parameters` ya existente): umbrales de mora (10/45/90
  días), consecutivo de recibos, días para descarte automático (15), ventana de descarte (60).
- Infra transversal: auditoría/Log, helper de montos, paginación estándar.

**Frontend**

- Pantalla "Administración de catálogos" (tabla genérica reutilizable para los catálogos simples).

**Resultado:** catálogos administrables y reglas parametrizadas listas para usar.

---

### Fase 2 — Clientes

**Objetivo:** CRUD y ficha 360° del cliente.

**Backend**

- Modelo Cliente (identidad por documento), con **múltiples teléfonos, direcciones y referencias**.
- Casos de uso: crear, editar (por secciones: datos, dirección, teléfonos, referencias, observaciones),
  buscar (documento/nombre/teléfono/dirección/estado), ficha 360°, log/historial por cliente,
  asignación cliente↔usuario/cobrador, estado del cliente.
- (Diferido a fase de datos: detección/fusión de duplicados — `planning.md`).

**Frontend**

- Listado/búsqueda de clientes, crear/editar cliente, ficha 360° del cliente.

**Nota de comportamiento (legacy):** el alta de cliente del legacy crea en una sola transacción
cliente+dirección+referencias+evento+**pedido**+producto+abono inicial. En el modelo nuevo, el alta de
cliente y la de su primer contrato pueden separarse; el flujo de UI "crear cliente con su primer
contrato" se arma sobre las Fases 2+3.

---

### Fase 3 — Contratos (Pedidos), Productos y Cartera

**Objetivo:** contratos por cliente y su plan de pagos.

**Backend**

- Modelo Contrato (1 cliente → N contratos), productos asociados, descripción de lo financiado.
- Cartera: generar plan de pagos al crear el contrato, periodicidad (semanal/quincenal/mensual),
  número de cuotas, valor de cuota, saldo por contrato y consolidado por cliente.
- Operaciones: consultar/editar/cerrar/cancelar contrato, agregar productos, **cambio de tarifa**,
  **cambio de fecha de pago**.

**Frontend**

- Detalle de contrato + plan de pagos; pantalla de pedidos/contratos.

---

### Fase 4 — Pagos y Recibos

**Objetivo:** ciclo completo de cobro de un contrato.

**Backend**

- Registrar pago (abono parcial / total, menor o mayor a la cuota), regla de aplicación a cuotas,
  recálculo automático de saldos y estados (al día / paz y salvo).
- Recibos de pago: programar, confirmar, descartar (manual y **automático por vencimiento** vía job),
  **reversar** pago (con auditoría), recibo con consecutivo, adjuntar comprobantes.
- Mora: **recálculo automático de estado** (equivalente a `Deuda()`, job programado), pago mínimo de
  mora (`calcularSaldoMinimo`), reporte a DataCrédito.
- Historial de pagos por cliente/contrato; listado de cobros pendientes.

**Frontend**

- Registro de pagos, generación/impresión de recibos, listado de cobros pendientes.

---

### Fase 5 — Cobranza / Gestiones de llamada

**Objetivo:** bandeja de trabajo del cobrador y registro de gestiones.

**Backend**

- Registrar gestión: llamada, visita, acuerdo de pago, **promesa de pago** (fecha comprometida),
  observación. Catálogos de tipos y resultados de gestión.
- Historial por cliente/contrato/usuario; clasificación por mora (al día → jurídico).
- Bandeja del cobrador, **llamadas del día**, **rellamar**, seguimiento de promesas (cumplidas/incumplidas).

**Frontend**

- Bandeja de cobranza / registro de gestiones, pantalla de llamadas del día.

---

### Fase 6 — Devoluciones

**Objetivo:** registrar y consultar devoluciones.

**Backend**

- Generar devolución (cambia estado de contrato y cliente, anula recibos programados pendientes),
  listar por fecha, consultar detalle.

**Frontend**

- Pantalla de devoluciones.

---

### Fase 7 — Reportes

**Objetivo:** tableros y reportes operativos/financieros.

**Backend + Frontend**

- Conteos de clientes y pagos por estado, cartera por usuario, totales de cartera por estado,
  detalle de pagos por usuario y fecha; reporte contable y de ventas por período.

---

### Fase 8 — Importación y Backup

**Objetivo:** carga masiva y respaldo.

**Backend + Frontend**

- Importar clientes y pagos desde archivo (CSV).
- Backup: generar/descargar/restaurar (export e import de la cartera).

---

### Fase 9 — Migración de datos legacy y Despliegue

**Objetivo:** llevar la data de Católikas a Recaudify y poner en producción.

- **Migración:** mapear modelo viejo (1:1) → nuevo (cliente→contratos), migrar clientes (con
  consolidación de duplicados), contratos, cuotas, pagos, devoluciones, usuarios; validar consistencia,
  saldos (migrado = calculado), historial y que ningún contrato quede huérfano.
- **Despliegue:** front en Vercel, API en cPanel (`public/`, `.env`, permisos `storage/`), cron del
  scheduler, estrategia de backups/logs/monitoreo.

---

## Decisiones abiertas (a confirmar antes de empezar cada fase)

1. **Estados** — ¿catálogo nuevo con claves semánticas (recomendado, ya implícito en `planning.md`) o
   conservar los códigos numéricos legacy (104, 111…) para facilitar la migración? Afecta Fase 1.
2. **"Crear cliente con primer contrato"** — ¿se mantiene como un único flujo de UI (como el legacy) o
   se separan en dos pasos? Afecta el alcance de UI de las Fases 2–3.
3. **Recálculo de mora** — ¿job programado por cron (recomendado) que recorre la cartera, o recálculo
   on-demand al abrir cada pantalla como hace el legacy con `Deuda()`? Afecta Fase 4.
4. **Periodicidad de cuotas** — el legacy es esencialmente mensual; `planning.md` contempla
   semanal/quincenal/mensual. ¿Se construye multi-periodicidad desde el inicio? Afecta Fase 3.

---

## Cómo se usa este plan

- Cada fase se ejecuta de arriba hacia abajo respetando dependencias.
- Al construir una funcionalidad, validar el comportamiento esperado en la sección correspondiente de
  `funcionalidades.md` y marcar el avance en `planning.md`.
- Las "decisiones abiertas" se resuelven con el usuario justo antes de iniciar la fase afectada.
