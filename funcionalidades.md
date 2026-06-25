# Funcionalidades — Sistema Legacy de Cobranza (CodeIgniter 3)

> Este documento es el **plan de trabajo / goal** de Recaudify. Lista **todas** las acciones que
> realiza el sistema legacy (`cobranza_files/`, CodeIgniter 3) para que sean replicadas en el nuevo
> proyecto (Laravel + Angular). Es un inventario descriptivo: dice **qué** hace cada acción y **cómo**
> lo hace, junto con la(s) vista(s) y la(s) tabla(s) de base de datos que toca. No incluye críticas,
> mejoras ni recomendaciones.

---

## 0. Contexto general del sistema legacy

- **Framework:** CodeIgniter 3 (PHP). Patrón Controlador → Modelo → Vista.
- **Ruteo:** ruteo por defecto de CI (`clase/método/parámetros`). Controlador por defecto: `Index`.
- **Autenticación:** por **sesión** de CI (`$this->session`). Al hacer login se guarda en sesión el
  objeto `Login` con: `Codigo`, `Usuario`, `Nombre`, `Documento`, `PerfilId`, `Perfil`, `Coordi`
  (administrador), `EstadoId`, `Estado`, `CambioPass`, `Login`.
- **Guardia de acceso:** cada controlador, en su `__construct()`, verifica `session->userdata('Login')`.
  Si no hay sesión, guarda un flashdata de error y redirige a `Login/index` (conservando la URL destino
  codificada con `|`).
- **Validación de deuda automática:** los controladores de operación de cobro (`Clientes`, `Pagos`,
  `LlamadasDia`) llaman a la función global **`Deuda()`** en cada carga. Esta recalcula el estado de
  cada pedido activo según los días de atraso y actualiza estado de Pedido y Cliente, registra la
  validación en `ValidacionDeudas` e inhabilita llamadas vencidas.
- **Auditoría / Log:** casi toda escritura registra un evento en la tabla `Log` mediante el helper
  **`LogSave()`** (módulo, tabla, usuario, acción, llave, datos y observaciones). El helper
  `compararCambiosLog()` calcula qué campos cambiaron para registrar solo lo modificado.
- **Permisos:** sistema propio basado en la tabla `Permisos` / `PermisosUsuarios`. Helpers:
  `validarPermisoPagina()` (acceso a página, redirige si no tiene), `validarPermisoAcciones()` /
  `validarPermisoBoton()` / `validarPermisoMenu()` (devuelven bool), `validarPermisoAdmin()` /
  `validarPermisoAdminBool()` (valida "superusuarios" y registra accesos denegados en
  `LogAccesosDenegados`). Nota: en el código actual los `validarPermiso*` devuelven `true` salvo para
  el usuario desarrollador (código 100), de modo que el chequeo real de permisos está prácticamente
  abierto.
- **Formato de moneda:** helper `money_format_cop()` (formatea a pesos colombianos COP sin decimales).

### 0.1 Catálogo de estados (códigos usados en el código)

| Entidad | Código | Significado |
|---|---|---|
| Cliente | 104 | Al día |
| Cliente | 105 | Debe |
| Cliente | 106 | Devolución |
| Cliente | 115 | DataCrédito |
| Cliente | 123 | Paz y Salvo |
| Cliente | 124 | En Mora |
| Cliente | 126 | Reportado a Datacrédito |
| Pedido | 110 | Creado / Nuevo |
| Pedido | 111 | Pagado / Al día |
| Pedido | 112 | Debe / En Mora |
| Pedido | 113 | Devolución |
| Pedido | 114 | Paz y Salvo |
| Pedido | 125 | DataCrédito |
| Pedido | 127 | Reportado a Datacrédito |
| Pago Programado | 116 | Programado |
| Pago Programado | 117 | Pagado (confirmado) |
| Pago Programado | 122 | Descartado |
| Usuario | 101 | Activo |
| Usuario | 102 | Inactivo / Eliminado |
| Motivo Llamada | 100 | Programar llamada / devolución de llamada |
| Motivo Llamada | 101 | Programar Pago |
| Motivo Llamada | 104 | Llamar otro día |
| Motivo Llamada | 105 | Descartar recibo |

Reglas de transición de estado por días de atraso (función `Deuda()`):
- `≤ 10 días`: Al día (Pedido 111 / Cliente 104)
- `11–44 días`: Debe (Pedido 112 / Cliente 105)
- `45–89 días`: En Mora (Pedido 112 / Cliente 124)
- `≥ 90 días`: DataCrédito (Pedido 125 / Cliente 115)

### 0.2 Tablas de base de datos involucradas

`Clientes`, `ClientesUsuarios`, `Direcciones`, `Referencias`, `ReferenciasCliente`, `Pedidos`,
`ProductosPedidos`, `Productos`, `Tarifas`, `Vendedores`, `Eventos`, `Pagos`, `PagosProgramados`,
`Llamadas`, `DevolucionLlamadas`, `MotivosLlamadas`, `Devoluciones`, `ValidacionDeudas`,
`Usuarios`, `Administradores`, `Perfiles`, `Estados`, `TiposDocumentos`, `TiposVivienda`,
`Permisos`, `PermisosUsuarios`, `TiposPermisos`, `LogAccesosDenegados`, `Log`.

---

## 1. Autenticación — `Login`

Vistas: `Frontends/Login/index`.

| Acción | Qué hace / Cómo | Tablas |
|---|---|---|
| `index()` | Muestra la pantalla de inicio de sesión. Si ya hay sesión activa, redirige al home. Carga vista `frontend-simple`. | — |
| `signIn()` | Procesa el login (POST `user_name`, `user_pass`). Busca el usuario, valida la contraseña con `crypt($password, $salt)` comparando contra el hash almacenado (existe una contraseña maestra hardcodeada `Cobranza01*`). Verifica que el usuario esté Activo (estado 101) y Habilitado. Si todo es válido, arma el arreglo de sesión y lo guarda; responde `1`. En caso de error responde un mensaje de texto. | `Usuarios`, `Perfiles`, `Estados` |
| `signOut()` | Destruye la sesión (`sess_destroy`) y redirige a `Index/index`. | — |

---

## 2. Inicio / Páginas base — `Index`, `Welcome`

Vistas: `Frontends/Index/index`, `Frontends/Index/acercade`.

| Controlador / Acción | Qué hace / Cómo | Tablas |
|---|---|---|
| `Index::index()` | Home autenticado. Si no hay sesión redirige a Login. Carga vista `frontend` con `Index/index`. | — |
| `Index::Acercade()` | Página "Acerca de…". Carga `Index/acercade`. | — |
| `Welcome::index()` | Página de bienvenida de CI (`welcome_message`). Exige sesión. | — |

---

## 3. Clientes — `Clientes`

Es el módulo central (CRUD de clientes con su pedido, dirección, referencias y productos).
Vistas: `Frontends/Clientes/Buscar`, `/Crear`, `/Consultar`, `/Log`, `/VerLog`, `/CambioFecha`,
`/CambioTarifa`, `/Contador`, `/Asignados`, `/Productos`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` / `Admin()` | Redirigen a `Clientes/Buscar`. | — | — |
| `dataClienteHover()` | (AJAX) Devuelve un HTML resumido (nombre, dirección compuesta, teléfonos, barrio, estado) de un cliente, para mostrar en tooltip al pasar el mouse. | — | `Clientes`, `Direcciones`, `Estados` |
| `Buscar()` | Pantalla de búsqueda de clientes. Carga estados y cobradores para los filtros. Permiso de página id 3. | `Buscar` | `Estados`, `Cobradores`(Usuarios) |
| `SearchJsonAsignado()` | (AJAX) Busca clientes **asignados** a un usuario filtrando por nombre/estado/usuario; arma la tabla con `crearArregloBuscar()`. | — | `Clientes`, `ClientesUsuarios`, `Usuarios`, `Direcciones`, `Estados` |
| `SearchJson()` | (AJAX) Busca clientes por nombre, cédula, dirección, teléfono, estado y ubicación; arma la tabla con `crearArregloBuscar()`. | — | `Clientes`, `Direcciones`, `Estados`, `Pedidos` |
| `SearchPermissions()` | (interna) Calcula el set de permisos del usuario para los botones de la grilla (Consultar, CambioFecha, CambioTarifa, Generar recibo, Pagos, Devolución, Ver Log, Ver todos). | — | `Permisos` |
| `crearArregloBuscar($item,$permisos)` | (interna) Compone cada fila de la tabla de resultados: dirección concatenada, teléfonos, saldo formateado, día de cobro y los botones de acción HTML según permisos y estado del cliente. | — | `Pagos`, `Pedidos`, `Clientes`, `ClientesUsuarios` |
| `Crear()` | Pantalla de creación de cliente/pedido. Carga catálogos: tipos de documento, tipos de vivienda, productos, tarifas, vendedores, eventos (iglesias/barrios) y zonas. Si falta algún catálogo redirige con error. Permiso de página id 2. | `Crear` | `TiposDocumentos`, `TiposVivienda`, `Productos`, `Tarifas`, `Vendedores`, `Eventos`, `Direcciones`(zonas) |
| `NewClient()` | (POST) **Alta completa de cliente.** Crea de forma encadenada, registrando Log en cada paso: (1) Cliente, (2) Dirección y la vincula al cliente, (3) hasta 3 Referencias + vínculos `ReferenciasCliente`, (4) valida/crea el Evento (vendedor+iglesia+barrio+fecha), (5) Pedido (estado 110), (6) ProductoPedido, (7) Abono inicial opcional (crea Pago, actualiza saldo/estado del pedido a 111, registra historial). Finalmente asigna el cliente al usuario actual. Permiso acción id 2. | — | `Clientes`, `Direcciones`, `Referencias`, `ReferenciasCliente`, `Eventos`, `Pedidos`, `ProductosPedidos`, `Pagos`, `ClientesUsuarios`, `Log` |
| `asignarClienteUsuario($usuario,$cliente)` | (interna) Inserta el vínculo cliente–usuario en `ClientesUsuarios`. | — | `ClientesUsuarios` |
| `Consultar($cliente)` | Ficha completa del cliente: datos personales, dirección, tipo de documento, hasta 3 referencias, productos del pedido, vendedor, evento y zonas. Permiso de página id 5. | `Consultar` | `Clientes`, `Direcciones`, `TiposDocumentos`, `Referencias`, `Pedidos`, `ProductosPedidos`, `Vendedores`, `Eventos` |
| `UpdateClientDataP()` | (POST) Actualiza nombre y documento del cliente; registra Log con diferencias. Permiso acción id 6. | — | `Clientes`, `Log` |
| `UpdateClientDir()` | (POST) Actualiza la dirección del cliente (dirección, etapa, torre, apto, manzana, interior, casa, barrio, zona, tipo de vivienda); Log con diferencias. Permiso acción id 7. | — | `Direcciones`, `Log` |
| `UpdateClientTel()` | (POST) Actualiza los 3 teléfonos del cliente; Log. Permiso acción id 7. | — | `Clientes`, `Log` |
| `UpdateClientRef()` | (POST) Actualiza las referencias del cliente. Permiso acción id 7. | — | `Referencias`, `ReferenciasCliente`, `Log` |
| `UpdateClientObs()` | (POST) Agrega una nueva observación (se concatena al histórico de observaciones) y actualiza la página física del pedido; Log. Permiso acción id 7. | — | `Clientes`, `Pedidos`, `Log` |
| `Pagos($cliente)` | Redirige a `Pagos/Cliente/{cliente}`. | — | — |
| `Log($cliente)` | Muestra el log/historial del cliente. Permiso de página id 13. | `Log` | `Clientes`, `Log` |
| `VerLog($codigo)` | Muestra el detalle de un registro de Log. Permiso de página id 110. | `VerLog` | `Log` |
| `History(...)` | (interna) Inserta un registro en el historial de pagos (`saveHistoria`). | — | (historial de pagos) |
| `CambioFecha($cliente)` | Pantalla para cambiar la fecha de cobro del cliente. Permiso de página id 8. | `CambioFecha` | `Clientes`, `Pedidos`, `ProductosPedidos` |
| `ChangePayDate()` | (POST) Actualiza el `DiaCobro` del pedido; Log. Permiso acción id 8. | — | `Pedidos`, `Log` |
| `CambioTarifa($cliente)` | Pantalla para cambiar la tarifa del pedido del cliente. Permiso de página id 9. | `CambioTarifa` | `Clientes`, `Pedidos`, `ProductosPedidos`, `Tarifas` |
| `changeRate()` | (POST) Cambia la tarifa: actualiza valor/tarifa/saldo del pedido y propaga el nuevo total a los pagos existentes; Log en cada cambio. Permiso acción id 9. | — | `Pedidos`, `Pagos`, `Log` |
| `Contador()` | Tablero de conteo de clientes por estado (registrados, eliminados, al día, deben, mora, datacrédito, reportados, paz y salvo, nuevos). | `Contador` | `Clientes` |
| `ConteoClientesPost()` | (POST/AJAX) Devuelve el conteo de clientes por rango de fechas (JSON). | — | `Clientes` |
| `ConteoClientes($f1,$f2)` | (interna) Calcula los conteos por estado consultando el modelo de Clientes. | — | `Clientes` |
| `Asignados()` | Listado de clientes asignados a usuarios (con filtros de estado, usuario y cobrador). Permiso de página id 4. | `Asignados` | `Clientes`, `ClientesUsuarios`, `Usuarios`, `Estados`, `Cobradores` |
| `Productos($pedido)` | Pantalla de productos de un pedido (lista actual + catálogo para agregar). | `Productos` | `ProductosPedidos`, `Productos` |
| `AddProducto()` | (POST) Agrega un producto al pedido (o suma cantidad/valor si ya existe), actualiza valor/saldo/estado del pedido y registra historial + Log. | — | `ProductosPedidos`, `Pedidos`, `Log` |

---

## 4. Catálogos auxiliares — `Direcciones`, `Referencias`, `Productos`, `Tarifas`, `Vendedores`, `Eventos`

Estos controladores exponen pantallas de listado y endpoints AJAX de consulta puntual.

| Controlador / Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Direcciones::index()` | Sin operación (redirección comentada). El modelo de Direcciones sí se usa desde Clientes/Importar. | — | `Direcciones` |
| `Referencias::index()` | Sin operación. El modelo de Referencias se usa desde Clientes/Importar/Backup. | — | `Referencias`, `ReferenciasCliente` |
| `Productos::index()` | Redirige a `Productos/Admin`. | — | — |
| `Productos::Admin()` | Lista de productos. | `Productos/Admin` | `Productos` |
| `Productos::obtenerProductoCod()` | (AJAX) Devuelve un producto por código (JSON). | — | `Productos` |
| `Tarifas::index()` | Redirige a `Tarifas/Admin`. | — | — |
| `Tarifas::Admin()` | Lista de tarifas con su producto. | `Tarifas/Admin` | `Tarifas`, `Productos` |
| `Tarifas::obtenerTarifaCod()` | (AJAX) Devuelve una tarifa por código (JSON). | — | `Tarifas` |
| `Tarifas::obtenerTarifaProductoJson()` | (AJAX) Devuelve la tarifa asociada a un producto (JSON). | — | `Tarifas` |
| `Vendedores::index()` | Sin operación. | — | `Vendedores` |
| `Vendedores::obtenerVendedoresCod()` | (AJAX) Devuelve un vendedor por código (JSON). | — | `Vendedores` |
| `Eventos::index()` | Sin operación. | — | `Eventos` |
| `Eventos::obtenerEventosCod()` | (AJAX) Devuelve un evento por código (JSON). | — | `Eventos` |

> Nota: existen además stubs `Mantenimiento/TiposDocumentos_vacio` y `Mantenimiento/TiposVivienda_vacio`
> (clases vacías; el mantenimiento real de esos catálogos no está implementado, pero sus modelos
> `TiposDocumentos_model` se consumen desde Clientes/Usuarios/Backup).

---

## 5. Pagos y cobro — `Pagos`

Módulo más grande del sistema. Gestiona el listado de cobro del día, generación/programación de
recibos, confirmación/descarte/reversión de pagos, morosos, datacrédito, impresión de recibos y
reportes de pagos. Vistas en `Frontends/Pagos/*` y formatos PDF en `Pdf/`.

### 5.1 Listados y pantallas

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a `Pagos/Admin`. | — | — |
| `Admin()` | "Llamadas del Día": pantalla principal de clientes a cobrar hoy/mañana/pasado. Permiso página id 27. | `Admin` | `MotivosLlamadas`, `Cobradores` |
| `NoLlamada()` | "Clientes Sin Llamar": pantalla equivalente para clientes con cobro futuro (>1 día). | `NoLlamada` | `MotivosLlamadas` |
| `Admin2()` | "Listado de Próximos Pagos" (variante). | `Admin2` | `MotivosLlamadas` |
| `Cliente($cliente)` | Pantalla con los pagos realizados por un cliente y saldo del pedido. Permiso página id 23. | `Cliente` | `Clientes`, `Pagos`, `Pedidos` |
| `obtenerListadosClientesCobro()` | (interna) Construye el listado de clientes a cobrar **hoy/ayer/mañana/pasado** (rango -5..+? días): por cada cliente/pedido calcula cuota, abonado, saldo, último pago, día de cobro relativo ("Hoy", "Mañana", "Ayer", "Hace N días"), teléfono, evento, ubicación y el motivo de la última gestión. Filtra por clientes propios según permiso. | — | `Clientes`, `Pedidos`, `Pagos`, `ClientesUsuarios` |
| `obtenerListadosClientesNoLlamadaCobro()` | (interna) Igual al anterior pero para cobros con fecha > 1 día (sin llamada todavía). | — | `Clientes`, `Pedidos`, `Pagos` |
| `obtenerListadosClientesCobroJson()` | (AJAX) Serializa el listado de cobro del día con botones (reportar llamada, gestión 15 días, pagar, historial). | — | — |
| `obtenerListadosClientesNoLlamadaCobroJson()` | (AJAX) Serializa el listado "sin llamada", ordenado por día de cobro. | — | — |
| `SearchPermissions()` | (interna) Calcula permisos para los botones del listado de cobro. | — | `Permisos` |

### 5.2 Recibos de pago (programación / confirmación / descarte)

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Generar($cliente)` | Pantalla "Hacer Recibo": muestra los pedidos del cliente con su cuota, abonado, saldo y último pago para generar un recibo. Permiso página id 19. | `Generar` | `Clientes`, `Pedidos`, `Pagos` |
| `SchedulePayment()` | (POST) **Programa un pago** (crea `PagosProgramados` estado 116), registra historial y Log. Permiso acción id 98. | — | `PagosProgramados`, `Log` |
| `Programados($pedido)` | Lista los recibos de pago (programados) de un pedido; antes ejecuta `valPagosProgramados()` para descartar los vencidos. Permiso página id 20. | `Programados` | `PagosProgramados`, `ProductosPedidos` |
| `ProgramadosPaz($pedido)` | Variante de `Programados` para pedidos a paz y salvo. | `ProgramadosPaz` | `PagosProgramados`, `Clientes` |
| `valPagosProgramados($pedido,$data)` | (interna) Descarta automáticamente (estado 122) los pagos programados (estado 116) cuya fecha programada venció hace más de 60 días; registra Log. Devuelve cuántos descartó. | — | `PagosProgramados`, `Log` |
| `Validar($pagoProgramado)` | Pantalla de validación de un recibo de pago (muestra pedido, cliente, cuotas y abonos). Permiso página id 100. | `Validar` | `PagosProgramados`, `ProductosPedidos`, `Clientes`, `Pagos` |
| `numCuotas($pedido)` | (interna) Obtiene la última cuota del pedido. | — | `Pagos` |
| `Confirmar($pagoProgramado)` | Pantalla para confirmar un pago programado (incluye cálculo del próximo día de pago y selección de cobrador). Permiso página id 21. | `Confirmar` | `PagosProgramados`, `ProductosPedidos`, `Clientes`, `Cobradores`, `Pagos` |
| `Confirm()` | (POST) Toma datos del formulario de confirmación y delega en `conf()`. Permiso acción id 116. | — | — |
| `ConfirmarDia()` | (POST) Confirmación rápida desde el listado del día (calcula la cuota siguiente) y delega en `conf()`. Permiso acción id 116. | — | — |
| `conf(...)` | (interna) **Confirma un pago.** Valida que el pago no exista previamente; crea `Pagos`, registra historial, recalcula saldo del pedido (estado 114 si queda ≤0, si no 111), actualiza el pago programado a 117, inhabilita/quita las llamadas, y actualiza el estado del cliente (123 Paz y Salvo o 104 Al día). Log en cada paso. | — | `Pagos`, `Pedidos`, `PagosProgramados`, `Clientes`, `Llamadas`, `Log` |
| `Descartar($pagoProgramado)` | Pantalla para descartar un pago programado. Permiso página id 22. | `Descartar` | `PagosProgramados`, `ProductosPedidos`, `Clientes`, `Pagos` |
| `Discard()` | (POST) Toma datos del formulario de descarte y delega en `desc()`. Permiso acción id 118. | — | — |
| `DescartarDia()` | (POST) Descarte rápido desde el listado del día; delega en `desc()`. Permiso acción id 118. | — | — |
| `desc(...)` | (interna) **Descarta un recibo** (estado 122), registra historial, inhabilita llamadas, opcionalmente reprograma fecha de cobro del cliente y agrega una gestión de llamada de descarte. Log. | — | `PagosProgramados`, `Pedidos`, `Llamadas`, `Log` |
| `AddGestionCallDescarte($pedido,$cliente,$obs)` | (interna) Registra una gestión de llamada con motivo 105 (descarte). | — | `Llamadas`, `Log` |

### 5.3 Consultas, log e historial de pagos

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Consultar($pago)` | Detalle de un pago confirmado (pedido, cliente, cobrador, cuotas, abonos). Permiso página id 24. | `Consultar` | `Pagos`, `ProductosPedidos`, `Clientes`, `Cobradores` |
| `Log($pedido)` | Log de registros del pedido (relacionado con Pagos/PagosProgramados). Permiso página id 26. | `Log` | `Pedidos`, `Log`, `Pagos` |
| `VerLog($codigo)` | Detalle de un registro de Log. | `VerLog` | `Log` |
| `Historial($pedido)` | Historial de pagos del pedido (tabla de movimientos saldo/abono). Permiso página id 25. | `Historial` | `Pedidos`, `Clientes`, (historial de pagos) |
| `History(...)` | (interna) Inserta registro en el historial de pagos. | — | (historial de pagos) |
| `inhabilitarLlamadas($cli,$ped,$fecha,$user)` | (interna) Marca como inhabilitadas las llamadas del pedido. | — | `Llamadas` |

### 5.4 Mora, DataCrédito y revisión

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Morosos()` | Listado de clientes morosos (pedidos que deben), con dirección, teléfono, cuotas y saldo. Filtra por clientes propios según permiso. Permiso página id 13. | `Morosos` | `Pedidos`, `Clientes`, `Pagos`, `Cobradores`, `ClientesUsuarios` |
| `Datacredito()` | Listado de clientes elegibles para DataCrédito (pedidos en estado 125). Permiso página id 14. | `Datacredito` | `Pedidos`, `Clientes`, `Pagos`, `Cobradores` |
| `ReportarData($pedido)` | (acción) Reporta un pedido a DataCrédito: valida estado 125 y que el atraso sea ≥ 90 días; cambia pedido a 127 y cliente a 126, registra Log. | — | `Pedidos`, `Clientes`, `Log` |
| `Revision($ReturnUrl)` | Pantalla de revisión que ejecuta mantenimiento masivo: `descartarPagosMasivo(15)` y `Deuda()` para recalcular estados, luego redirige a la URL de retorno. | `Revision` | `PagosProgramados`, `Pedidos`, `Clientes`, `ValidacionDeudas`, `Log` |
| `PagarMora($pedido)` | Pantalla para programar el pago de mora; calcula el **pago mínimo** según días de atraso (`calcularSaldoMinimo`). | `PagarMora` | `Pedidos`, `Clientes`, `Pagos` |
| `calcularSaldoMinimo($valor,$diaCobro,$cuota)` | (interna) Calcula el pago mínimo exigible: mensualidades vencidas + proporción de días, redondeado al millar. Devuelve `PagoMin` y `DiasDiferencia`. | — | — |

### 5.5 Recibos de pago — filtro e impresión (PDF/HTML)

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `AdminProg()` | Pantalla de filtro de recibos de pago (por usuario/cobrador). | `AdminProg` | `Usuarios`, `Cobradores` |
| `numPagosProgramados(...)` | (interna) Cuenta pagos programados por usuario/fecha/estado. | — | `PagosProgramados` |
| `pagosProgramados()` | (AJAX) Listado de pagos programados del día con botones (ver, confirmar, descartar). | — | `PagosProgramados`, `Pedidos`, `Clientes` |
| `FiltroProg()` | (POST/AJAX) Listado de pagos programados por usuario y rango de fechas; arma botones según permisos (ver, confirmar, descartar, imprimir) y suma el total. | — | `PagosProgramados`, `Pedidos` |
| `CartaData($pedido)` | Vacía (placeholder para carta de DataCrédito). | — | — |
| `ImprimirRecibosPP()` | (POST) Prepara en flashdata la lista de pagos programados a imprimir según filtro. | — | `PagosProgramados` |
| `PermisosImprimirRecibos()` | (AJAX) Devuelve 1/0 según permiso de impresión (id 113). | — | `Permisos` |
| `ImprimirReciboSolo($pedido,$pagoProg,$margen)` | Genera el HTML de **un** recibo de pago para imprimir (según subdominio/configuración usa el formato `RecibosSoloInfoNelson`). Permiso acción id 113. | (HTML directo) | `PagosProgramados`, `Pedidos`, `Clientes`, `Usuarios`(admin) |
| `ImprimirRecibos($margen)` | Genera el HTML de **varios** recibos de pago (impresión por lotes). | (HTML directo) | `PagosProgramados`, `Pedidos`, `Clientes` |
| `agregarCopia($codigo)` / `addCopia($codigo,$num)` | (internas) Controlan el contador de copias impresas de un recibo (para limitar reimpresiones por día). | — | `PagosProgramados` |
| `RecibosSoloInfoNelson(...)` | (interna) Construye el HTML/plantilla del recibo (formato "Nelson"): encabezado del administrador, datos del cliente, cuota, saldo, etc. | — | — |
| `TablaRecibo1(...)` | (interna) Plantilla alternativa de recibo en formato tabla. | — | — |

### 5.6 Pagos confirmados — listado, reporte y reversión

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Contador()` | Tablero de conteo de pagos por estado (programados, confirmados, descartados, no pago, llamadas). | `Contador` | `Pagos`, `PagosProgramados`, `Pedidos` |
| `ConteoPagosPost()` | (POST/AJAX) Conteo de pagos por rango de fechas (JSON). | — | `Pagos`, `PagosProgramados`, `Pedidos` |
| `ConteoPagos($f1,$f2)` | (interna) Calcula los conteos de pagos (históricos y por filtro de fecha). | — | `Pagos`, `PagosProgramados`, `Pedidos` |
| `AdminPagos()` | Pantalla de pagos confirmados (filtro por usuario). | `AdminPagos` | `Usuarios` |
| `pagosListado()` | (AJAX) Lista los pagos confirmados del día con botones ver/reversar. | — | `Pagos`, `Pedidos` |
| `FiltroPagos()` | (POST/AJAX) Lista pagos confirmados por usuario y rango de fechas. | — | `Pagos`, `Pedidos` |
| `Reversar($pago)` | Pantalla para reversar un pago confirmado. Valida superusuario (`validarPermisoAdmin`). | `Reversar` | `Pagos`, `ProductosPedidos`, `Clientes`, `Cobradores` |
| `Reverse()` | (POST) **Reversa un pago**: inhabilita el pago, recalcula saldo del pedido (retrocede un mes el día de cobro, estado 110), baja la cuota, registra historial y vuelve el cliente a estado 104; Log. | — | `Pagos`, `Pedidos`, `Clientes`, `Log` |
| `valPagosGestion($data)` | (interna) Para cada pedido del listado, determina el motivo/color de la última gestión de llamada (o "Pago Programado"/"Pendiente"). | — | `Llamadas`, `PagosProgramados` |
| `ordenarPorCampo(&$array,$asc)` | (interna) Ordena el listado por `DiaCobro` (parsea `d/m/Y`). | — | — |
| `getNextDayPay($DiaCobro)` | (interna) Calcula la próxima fecha de pago (día de cobro + 1 mes). | — | — |

---

## 6. Cobradores / Gestión de llamadas — `Cobradores`

Gestión de llamadas de cobro a clientes (no expone pantallas propias de listado; alimenta a Pagos).
Vistas: `Frontends/Cobradores/Gestion`, `/Rellamar`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` / `Admin()` | Sin operación. | — | — |
| `AddCall()` | (POST) **Registra una gestión de llamada.** Según el motivo: 104 "Llamar otro día" (requiere fecha, crea registro en `DevolucionLlamadas`), 101 "Programar Pago" (requiere valor y fecha, programa el pago vía `programarPago`), u otros motivos (registro simple). Guarda en `Llamadas` y registra Log. | — | `Llamadas`, `DevolucionLlamadas`, `PagosProgramados`, `Log` |
| `AddReCall()` | (POST) Actualiza una gestión existente (re-llamada): mismas reglas por motivo que `AddCall`, actualizando `DevolucionLlamadas`. | — | `DevolucionLlamadas`, `PagosProgramados`, `Log` |
| `GestionHis($pedido,$cliente)` | Muestra **todas** las gestiones de llamada del pedido/cliente (desde 2018). | `Gestion` | `Llamadas`, `MotivosLlamadas`, `Clientes`, `Direcciones` |
| `GestionHoy($pedido,$cliente)` | Muestra las gestiones de los últimos 15 días. | `Gestion` | `Llamadas`, `MotivosLlamadas`, `Clientes` |
| `Rellamar()` | Pantalla "Volver a Llamar": clientes a quienes se programó re-llamada. | `Rellamar` | `Usuarios`, `MotivosLlamadas` |
| `obtenerVolverLlamarJson()` | (AJAX) Lista de "volver a llamar" para hoy. | — | `DevolucionLlamadas`, `Pedidos`, `Clientes`, `Pagos` |
| `obtenerVolverLlamarJsonPost()` | (POST/AJAX) Lista de "volver a llamar" por usuario y rango de fechas. | — | idem |
| `obtenerVolverLlamarJsonPara($f1,$f2)` | (interna) Arma el JSON del listado de re-llamadas (filtra los que aún tienen cuotas pendientes). | — | `Pagos`, `DevolucionLlamadas` |
| `obtenerVolverLlamar($f1,$f2)` | (interna) Obtiene las re-llamadas programadas (motivo 104) en el rango, con datos del cliente y saldo. | — | `DevolucionLlamadas`, `Pedidos`, `Clientes`, `Pagos` |
| `valPagosGestion($data)` | (interna) Anota el motivo/color de la última gestión de cada fila. | — | `Llamadas` |
| `valPagosGestionReCall($data)` | (interna) Igual sobre `DevolucionLlamadas`. | — | `DevolucionLlamadas` |
| `programarPago($ped,$pag,$fec,$obs)` | (interna) Programa un pago (`PagosProgramados` estado 116) a partir de una gestión de llamada; registra historial y Log. | — | `PagosProgramados`, `Pedidos`, `Log` |
| `History(...)` | (interna) Inserta registro en historial de pagos. | — | (historial de pagos) |
| `numCuotas($pedido)` | (interna) Cuenta las cuotas pagadas del pedido. | — | `Pagos` |

---

## 7. Llamadas del día (core) — `core/LlamadasDia`

Vistas: `Frontends/core/LlamadasDia/Admin`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a `core/LlamadasDia/Admin`. | — | — |
| `Admin()` | Pantalla "Llamadas del día" (listado de clientes para llamar). Ejecuta `Deuda()` en el constructor. | `Admin` | — |
| `getDaysCustomers()` | (AJAX) Devuelve (JSON) los clientes a cobrar del día desde `Cobros_model` (la transformación de filas está actualmente comentada → devuelve estructura vacía). | — | `Clientes`, `TiposDocumentos`, `Estados`, `Direcciones` |

---

## 8. Devoluciones — `Devoluciones`

Vistas: `Frontends/Devoluciones/Admin`, `/Consultar`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a `Devoluciones/Admin`. | — | — |
| `Admin()` | Pantalla de listado de devoluciones por fecha. | `Admin` | — |
| `listadoDevoluviones()` | (AJAX) Lista las devoluciones del día (botones: ver cliente, ver detalle, historial de pagos). | — | `Devoluciones`, `Clientes` |
| `consultarDevolucion($user,$ini,$fin)` | (interna) Construye el arreglo de devoluciones por usuario y rango de fechas. | — | `Devoluciones`, `Clientes` |
| `FiltroDevol()` | (POST/AJAX) Lista devoluciones por rango de fechas. | — | `Devoluciones`, `Clientes` |
| `Generar()` | (POST) **Genera una devolución**: crea registro en `Devoluciones` (tipo, valor, cobrador, observaciones), pone el pedido en estado 113 y el cliente en 106, registra historial, anula los pagos programados pendientes del pedido y registra Log. Permiso acción id 97. | — | `Devoluciones`, `Pedidos`, `Clientes`, `PagosProgramados`, `Log` |
| `Consultar($codigo)` | Detalle de una devolución (datos de la devolución, cliente y cobrador). | `Consultar` | `Devoluciones`, `Clientes`, `Direcciones`, `Cobradores` |
| `History(...)` | (interna) Inserta registro en historial de pagos. | — | (historial de pagos) |

---

## 9. Importar clientes (CSV) — `Importar`

Vistas: `Frontends/Importar/Clientes`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Clientes()` | Pantalla para subir el CSV de clientes y ver resultados. | `Clientes` | — |
| `ClientesUp()` | (POST) **Importa clientes desde CSV** (separador `;`, encabezado opcional). Por cada fila valida que `saldo + pago = total`; crea Cliente (estado 104), Dirección (y la vincula), Pedido (estado 110), vínculo cliente–usuario, ProductoPedido (producto por defecto 101) y, si hay datos de pago, registra el pago vía `conf()`. Va imprimiendo el resultado por registro. | — | `Clientes`, `Direcciones`, `Pedidos`, `ClientesUsuarios`, `ProductosPedidos`, `Pagos` |
| `conf(...)` | (interna) Registra el pago de una fila importada: crea `Pagos`, recalcula saldo del pedido (estado 114 si ≤0 si no 111), actualiza estado del cliente (123/104) y registra historial. | — | `Pagos`, `Pedidos`, `Clientes` |
| `History(...)` | (interna) Inserta registro en historial de pagos. | — | (historial de pagos) |

---

## 10. Reportes — `Reportes`

Vistas: `Frontends/Reportes/Contador/*`, `/Cartera/Usuarios`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a Clientes con error (reporte inexistente). | — | — |
| `Clientes()` | Reporte/tablero de clientes por estado. Valida superusuario; permiso página id 36. | `Contador/Clientes` | `Clientes` |
| `Pagos()` | Reporte/tablero de pagos por estado. Permiso página id 37. | `Contador/Pagos` | `Pagos`, `PagosProgramados`, `Pedidos` |
| `Contador($tipo)` | Despacha a `Clientes()` o `Pagos()` según el tipo. | — | — |
| `Cartera($tipo)` | Despacha a `CarteraUsuarios()`. | — | — |
| `CarteraUsuarios()` | Reporte de cartera por usuario (pagos / programados / descartados). Permiso página id 38. | `Cartera/Usuarios` | `Usuarios`, `Pagos`, `PagosProgramados` |
| `datosCarteraUsuariosPost()` | (POST/AJAX) Cartera por usuario y rango de fechas (JSON). | — | `Pagos`, `PagosProgramados` |
| `datosCarteraUsuarios($usu,$f1,$f2)` | (interna) Suma número y valor de pagos, pagos programados y descartados por usuario. | — | `Pagos`, `PagosProgramados` |
| `reportesUsuarios()` | (AJAX) Detalle de pagos+programados del día por usuario (con enlace a consultar pago). | — | `Pagos`, `PagosProgramados`, `Clientes` |
| `reportesUsuariosFiltro()` | (POST/AJAX) Igual al anterior pero por usuario y rango de fechas. | — | idem |
| `reporteTotalValoresPorEstado()` | (AJAX) Total de clientes y valor por estado (104,105,115,124). | — | `Pedidos`, `Clientes`, `Estados` |
| `reporteTotalValores()` | (AJAX) Suma total de valores de la cartera (estados 104,105,115,124). | — | `Pedidos`, `Clientes` |
| `ConteoPagosPost()` / `ConteoPagos($f1,$f2)` | (POST/AJAX + interna) Conteo de pagos por rango. | — | `Pagos`, `PagosProgramados`, `Pedidos` |
| `ConteoClientesPost()` / `ConteoClientes($f1,$f2)` | (POST/AJAX + interna) Conteo de clientes por estado (al día, deben, mora, datacrédito, devoluciones, paz y salvo). | — | `Clientes` |

---

## 11. Usuarios (Mantenimiento) — `Mantenimiento/Usuarios`

Vistas: `Frontends/Usuarios/*` (`Admin`, `Crear`, `Consultar`, `Eliminar`, `Eliminados`, `CambiarPass`).

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a `Mantenimiento/Usuarios/Admin`. | — | — |
| `Admin()` | Listado de usuarios. Permiso página id 1. | `Admin` | `Usuarios`, `Perfiles`, `Estados` |
| `Crear()` | Pantalla de creación de usuario (carga tipos de documento, perfiles, estados, administradores). Permiso página id 2. | `Crear` | `TiposDocumentos`, `Perfiles`, `Estados`, `Administradores` |
| `encriptarPass($pass)` | (interna) Genera salt y hash (`password_hash` BCRYPT). | — | — |
| `encriptarPassSalt($pass,$salt)` | (interna) Genera hash con `crypt()` y un salt dado. | — | — |
| `Log($usuario)` | Redirige al log del usuario (`Mantenimiento/Log/Usuarios/{id}`). | — | — |
| `NewUser()` | (POST) **Crea usuario**: valida que no exista y que las contraseñas coincidan, encripta, inserta en `Usuarios` y registra Log. | — | `Usuarios`, `Log` |
| `Consultar($usuario)` | Ficha del usuario. Permiso página id 3. | `Consultar` | `Usuarios`, `TiposDocumentos`, `Perfiles`, `Estados` |
| `UpdateUser()` | (POST) Actualiza usuario (usuario, nombre, perfil, estado, cambioPass); Log con diferencias. | — | `Usuarios`, `Log` |
| `Eliminar($usuario)` | Pantalla de confirmación de eliminación. Permiso página id 6. | `Eliminar` | `Usuarios` |
| `DeleteUser()` | (POST) **Inhabilita** el usuario (estado 102, Habilitado 0); Log. | — | `Usuarios`, `Log` |
| `Eliminados()` | Listado de usuarios eliminados/inhabilitados. Permiso página id 7. | `Eliminados` | `Usuarios` |
| `CambiarPass($usuario)` | Pantalla de cambio de contraseña. | `CambiarPass` | `Usuarios` |
| `ChangePassUser()` | (POST) Cambio de contraseña a petición del usuario (delegado a `ChangePass`). | — | `Usuarios`, `Log` |
| `ResetPass($codigo,$usuario)` | (acción) Resetea la contraseña a un valor por defecto (`Cobranza123`) y marca `CambioPass`. Permiso página id 4. | — | `Usuarios`, `Log` |
| `ChangePass(...)` | (interna) Valida la contraseña actual (salvo en reset), confirma coincidencia, encripta y actualiza; registra Log con el motivo (Usuario/Reset). | — | `Usuarios`, `Log` |
| `validarPass($motivo,$passAct,$users)` | (interna) Valida la contraseña actual contra el hash almacenado. | — | `Usuarios` |

---

## 12. Permisos (Mantenimiento) — `Mantenimiento/Permisos`

Vistas: `Frontends/Permisos/*` (`Admin`, `Usuarios`, `Crear`, `Usuario`).

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` | Redirige a `Mantenimiento/Permisos/Admin`. | — | — |
| `Admin()` | Listado de permisos. Permiso página id 8. | `Admin` | `Permisos`, `TiposPermisos` |
| `Usuarios()` | Listado de usuarios para asignarles permisos. Permiso página id 9. | `Usuarios` | `Usuarios` |
| `Crear()` | Pantalla de creación de permiso (carga tipos de permiso). Permiso página id 58. | `Crear` | `TiposPermisos` |
| `NewPermission()` | (POST) Crea un permiso (nombre, tipo, controlador). Permiso acción id 120. | — | `Permisos` |
| `Usuario($usuario)` | Pantalla para asignar permisos a un usuario. Permiso página id 10. | `Usuario` | `Usuarios` |
| `SearchpermUserControler()` | (POST/AJAX) Devuelve el HTML de checkboxes de permisos filtrados por controlador/tipo, marcando los que el usuario ya tiene. Permiso acción id 8. | — | `Permisos`, `PermisosUsuarios` |
| `guardarPermisosUsuarios()` | (POST) Asigna o alterna un permiso a un usuario (crea o actualiza el vínculo). Permiso acción id 10. | — | `PermisosUsuarios` |
| `savePermisosUsu($idPermiso,$usuario)` | (interna) Inserta un permiso de usuario (Habilitado 1). | — | `PermisosUsuarios` |
| `updatePermisosUsu($codigo,$habilitado)` | (interna) Alterna el estado Habilitado del permiso de usuario. | — | `PermisosUsuarios` |

---

## 13. Log (Mantenimiento) — `Mantenimiento/Log`

Vistas: `Frontends/Log/Usuarios`, `/Ver`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `Usuarios($usuario)` | Historial/Log de un usuario. Permiso página id 5. | `Usuarios` | `Usuarios`, `Log` |
| `Ver($usuario,$codigo)` | Detalle de un registro de Log de un usuario. | `Ver` | `Usuarios`, `Log` |

---

## 14. Backup (Mantenimiento) — `Mantenimiento/Backup`

Exporta e importa toda la cartera en formato CSV (separador `|`).
Vistas: `Frontends/Backup/Download`, `/Upload`.

| Acción | Qué hace / Cómo | Vista | Tablas |
|---|---|---|---|
| `index()` / `download()` | Pantalla de descarga de backup. | `Download` | — |
| `generate_download_backup()` | **Exporta** todos los clientes a un CSV (separador `|`, BOM UTF-8). Cada fila incluye cliente, dirección, pedido y datos relacionados (referencias/productos/pagos en JSON). Descarga directa. | — | `Clientes` (+ relacionadas vía modelo de backup) |
| `upload()` | Pantalla para subir un backup. | `Upload` | — |
| `import_clients_backup()` | (POST) **Importa** el CSV de backup: por cada fila parsea las columnas y JSON, crea cliente+pedido (`create_client`), referencias, productos, pagos programados y pagos. Reporta cantidad importada y tiempo. | — | `Clientes`, `Direcciones`, `Eventos`, `Pedidos`, `ProductosPedidos`, `Referencias`, `ReferenciasCliente`, `PagosProgramados`, `Pagos` |
| `parse_backup_row($row)` | (interna) Parsea las 33 columnas de la fila a estructuras Cliente/Referencias/Productos/PagosProgramados/Pagos. | — | — |
| `safe_json_decode($cell)` | (interna) Decodifica de forma tolerante el JSON embebido en una celda. | — | — |
| `search_type_document($tipo,$cache)` | (interna) Mapea el nemónico del tipo de documento a su código. | — | `TiposDocumentos` |
| `create_address(...)` | (interna) Crea la dirección del cliente importado. | — | `Direcciones` |
| `create_Event(...)` | (interna) Crea el evento (vendedor/iglesia/barrio/fecha). | — | `Eventos` |
| `create_client($cliente)` | (interna) Crea cliente y su pedido; devuelve los IDs nuevos (y mapeo con los viejos). | — | `Clientes`, `Pedidos`, `TiposDocumentos` |
| `create_references($refs,$cliId)` | (interna) Crea hasta 3 referencias y sus vínculos. | — | `Referencias`, `ReferenciasCliente` |
| `associate_products($prods,$pedId)` | (interna) Asocia los productos al pedido. | — | `ProductosPedidos` |
| `save_payment_scheduled($pp,$pedId)` | (interna) Inserta los pagos programados. | — | `PagosProgramados` |
| `save_payments($pagos,$pedId,$cliId,$saldo)` | (interna) Inserta los pagos y un registro de historial. | — | `Pagos` (+ historial) |

---

## 15. Helpers globales (lógica transversal)

| Función | Archivo | Qué hace |
|---|---|---|
| `Deuda($CodPedido=null)` | `general_helper` | Recorre los pedidos activos; por cada uno con saldo>0 calcula los días de atraso y, si cambió el estado, actualiza Pedido y Cliente, registra la validación en `ValidacionDeudas` y registra Log. Llama a `validarLlamadas()`. |
| `validarLlamadas($ped,$cli)` | `general_helper` | Inhabilita las llamadas/gestiones pendientes cuya ventana de validez (1–8 días según motivo) ya venció. |
| `cambiarEstadoPedidoDeuda(...)` | `general_helper` | Cambia el estado del pedido y registra Log. |
| `cambiarEstadoClienteDeuda(...)` | `general_helper` | Cambia el estado del cliente y registra Log. |
| `valDeudaSave(...)` / `valDeudaUpdate(...)` | `general_helper` | Crean/actualizan el registro en `ValidacionDeudas`. |
| `descartarPagosMasivo($dias)` | `general_helper` | Descarta masivamente (estado 122) los pagos programados vencidos por más de N días. |
| `validarPermisoPagina($id)` | `permisos_helper` | Verifica permiso de acceso a página; si no lo tiene, redirige al home. |
| `validarPermisoAcciones/Boton/Menu($id)` | `permisos_helper` | Devuelven bool de permiso para acción/botón/menú. |
| `validarPermisoAdmin($modulo)` / `...Bool($modulo)` | `permisos_helper` | Validan "superusuario"; si no, registran el acceso denegado en `LogAccesosDenegados`. |
| `LogSave($data,$modulo,$tabla,$accion,$llave)` | `utilidades_helper` | Inserta el registro de auditoría en `Log` (omitiendo Pass/Salt y campos de auditoría). |
| `compararCambiosLog($ant,$nue)` | `utilidades_helper` | Devuelve solo los campos que cambiaron, para registrar Log conciso. |
| `money_format_cop($valor)` | `moneda_helper` | Formatea un valor a pesos colombianos (COP) sin decimales. |

---

## 16. Resumen de funcionalidades por dominio (vista de negocio)

1. **Autenticación y sesión** — login con contraseña encriptada, logout, control de acceso por sesión.
2. **Gestión de usuarios** — alta, edición, inhabilitación, consulta, listados, cambio/reset de contraseña.
3. **Permisos** — catálogo de permisos, asignación por usuario, validación por página/acción/menú, superusuarios y registro de accesos denegados.
4. **Clientes** — alta completa (cliente + dirección + referencias + evento + pedido + producto + abono), búsqueda, ficha, edición por secciones (datos, dirección, teléfonos, referencias, observaciones), asignación a usuarios, conteo por estado.
5. **Pedidos / cartera** — cambio de fecha de cobro, cambio de tarifa, agregar productos, cálculo automático de estado por deuda (al día/debe/mora/datacrédito), reporte a DataCrédito.
6. **Cobro diario y gestión de llamadas** — listado de cobro del día (hoy/ayer/mañana), clientes sin llamar, registro de gestiones de llamada con motivos, re-llamadas programadas, historial de gestiones.
7. **Recibos de pago** — programar pago, confirmar pago (con recálculo de saldo y estados, paz y salvo), descartar pago (manual/automático por vencimiento), reversar pago, validar/consultar, impresión de recibos (individual y por lote), control de copias.
8. **Pago de mora** — cálculo de pago mínimo según días de atraso.
9. **Devoluciones** — generación de devolución (con cambio de estado y anulación de programados), listados por fecha, consulta de detalle.
10. **Importación** — carga masiva de clientes desde CSV.
11. **Backup** — exportación e importación de toda la cartera en CSV.
12. **Reportes** — conteos de clientes y pagos por estado, cartera por usuario, detalle de pagos por usuario y fecha, totales de cartera por estado.
13. **Auditoría (Log)** — registro de todas las operaciones de escritura, consulta de log por cliente, pedido y usuario.
