# Decisiones tomadas

> El "por qué" y el "qué se descartó" de cada decisión técnica detectada en el código, `planning.md`, `NEGOCIO.md` y los commits.

## Reescritura completa en vez de migración incremental

Se decidió reescribir el sistema legacy (CodeIgniter 3, `cobranza_files/`) desde cero en Laravel 13 + Angular 21, documentando primero el inventario completo de funcionalidades en `funcionalidades.md`. La razón es que el legacy acopla entidades y lógica dentro de un mismo controlador —por ejemplo, `Clientes::NewClient()` crea Cliente, Dirección, Referencias, Evento, Pedido, Producto y Pago en una sola función— y duplica fórmulas de negocio en varios lugares. Se descartó mantener el legacy y exponerle solo una API por encima, así como migrar módulo por módulo sin desacoplar el modelo de datos. Es el eje de todo el proyecto y sigue vigente.

## Convención de borrado y estado por grupo de entidad

En vez de usar `SoftDeletes` como mecanismo universal, cada entidad cae en uno de cuatro grupos. El primero es `SoftDeletes` para catálogos e identidad recuperable (Cliente, Producto, Tarifa, Vendedor, Cobrador y catálogos simples). El segundo es estado explícito sin soft-delete como mecanismo de negocio, como el Contrato con su ciclo borrador/activo/suspendido/cancelado/finalizado. El tercero es ausencia total de borrado lógico, con eventos de historial que se corrigen agregando otro evento, aplicado a Pago, Pago Programado, Devolución, Gestión, Compromiso y al log de auditoría. El cuarto es DELETE físico permitido, reservado a datos transitorios que nunca llegaron a confirmarse como hecho de negocio, como borradores de contrato nunca activados o filas de importación fallidas.

La razón de esta separación es que el legacy mezclaba "ocultar", "estado de negocio" y "auditoría" en una sola columna (`Habilitado`), lo que volvía ambiguo qué significaba realmente "borrar" según la entidad. Se descartó aplicar `SoftDeletes` a todo sin distinguir el tipo de entidad. Esta convención quedó fijada en `planning.md` antes de empezar a construir el dominio de cobranza, y hay que aplicarla explícitamente a cada entidad nueva antes de generar su modelo.

## Autenticación stateless con JWT y refresh, guard único `api`

Se eligió `php-open-source-saver/jwt-auth` con TTL de 15 minutos, refresh de 4 horas, HS256, y un único guard `api` para todas las rutas salvo login y register. El objetivo es desacoplar el frontend SPA del backend sin depender de sesiones de servidor, que era el enfoque del legacy con sesiones de CodeIgniter. Se descartaron las sesiones basadas en cookie/servidor. Sigue vigente.

## Autorización real con Spatie Permission

El legacy operó años sin control de acceso real: `validarPermiso*` devolvía `true` siempre salvo para un usuario hardcodeado, según quedó documentado en `NEGOCIO.md` §13. Por eso se decidió construir autorización real con Spatie Permission, usando middleware `permission:modulo.accion` por ruta y `role:administrador` por grupo, más un rol `superadmin` con bypass de gate. Se descartó copiar el esquema legacy de `Permisos`/`PermisosUsuarios`/`Administradores`, que era una lista paralela de "superusuarios" hardcodeada. Sigue vigente.

## Parametrización en vez de valores hardcodeados

Las reglas de negocio configurables —mora, contraseña de reseteo, política de contraseñas, campo de login— viven en el módulo `Parameters`, tipado con `ParameterType`/`ParameterCast`, nunca en código. Esto responde a que el legacy hardcodea umbrales de mora dentro de `Deuda()`, la contraseña de reseteo `Cobranza123`, y ventanas de validez de llamada, de forma que cualquier cambio de negocio exigía tocar código y redesplegar. Se descartó usar constantes de PHP/TS o variables de `.env` para reglas de negocio (el `.env` queda reservado a configuración de infraestructura). Sigue vigente; la política de contraseñas y el campo de login configurable ya están implementados (commits `d74f1ba` y `1928f50`).

## Motor Financiero como servicio único de cálculo

Todo cálculo de saldo, cuota y mora, además de la clasificación del estado de cartera, debe vivir en un único servicio llamado Motor Financiero. Ningún otro módulo —Pagos, Contratos, Devoluciones, Cobranza— reimplementa la fórmula; todos notifican al Motor y leen su resultado. La razón es que el legacy repite la misma lógica de "saldo ≤ 0 → estado 111/114" en `conf()`, `Reverse()`, `changeRate()` e `Importar/conf()`, y la función global `Deuda()` corre como side-effect en cada carga de pantalla en vez de ser un job aislado. Se descartó dejar que cada módulo calculara su propio saldo o mantener el recálculo como side-effect de request. Esta decisión está diseñada en `NEGOCIO.md` §7 pero **no implementada todavía**: hoy no existe tabla ni servicio de Contrato o Cartera en el código.

## N contratos por cliente, nunca fusionados

Un Cliente puede tener cero, uno o varios Contratos simultáneos, y una compra nueva nunca se fusiona dentro de un contrato existente con saldo: siempre crea un Contrato nuevo. El legacy fuerza una relación 1:1 de facto entre Cliente y Pedido, y no había necesidad de negocio para fusionar; hacerlo además complicaría la consolidación de cobro. Se descartó mantener el 1:1 legacy o fusionar compras nuevas en el saldo de un contrato vigente. Está confirmada en `planning.md` y `NEGOCIO.md` §5, pendiente de implementación.

## Cobrador como entidad independiente del Usuario

El Cobrador es una entidad propia que representa una cartera, separada del Usuario que la opera en un momento dado. Todos los Contratos de un mismo Cliente viven siempre en la misma cartera, sin excepción, ni siquiera si algún contrato llega a estado jurídico o castigado. Esto responde a que el legacy asigna el cliente directo al usuario que lo creó (`ClientesUsuarios`), lo que impide reasignar personal sin tocar cliente por cliente. Se descartó la asignación directa Cliente↔Usuario y la posibilidad de repartir contratos de un mismo cliente entre carteras distintas. La invariante se valida en el Service, no como constraint de base de datos, y está pendiente de implementación.

## Interacción y Gestión como dos niveles de registro de contacto

Se separa el contacto real —la Interacción, una sola fila sin importar cuántos contratos toque— del efecto de ese contacto sobre cada contrato —la Gestión, con motivo, resultado y referencia tanto a la Interacción como al Contrato—. La razón es que el legacy registra una fila por contacto asumiendo 1 cliente = 1 pedido, y con N contratos por cliente eso ya no alcanza: el cliente puede responder distinto por cada deuda en la misma llamada. Se descartó seguir con una sola fila por llamada como hace el legacy. Está diseñada en `NEGOCIO.md` §9, pendiente de implementación.

## Sin Docker, despliegue manual sobre VPS único

El backend corre en un único VPS de Hostinger (Ubuntu 24.04, Nginx + PHP-FPM + MySQL + Certbot), desplegado por SSH vía GitHub Actions al hacer push a `develop` o `main`; el frontend se despliega en Vercel. Esto no está documentado explícitamente en ningún lugar, pero es el estado real de `.github/workflows/` y `vps_deploy_guide.md`, y contradice al `README.md` de `recaudify-api`, que menciona Docker y Redis (ver `errores-conocidos.md`). Se descartaron los contenedores y Redis: cache, cola y sesión usan el driver `database`. Es la infraestructura vigente en producción.

---

## Convención de borrado y estado (detalle)

Se fija **antes** de construir los módulos de negocio, para no repetir el error del legacy de mezclar
"ocultar", "estado de negocio" y "auditoría" en una sola columna (`Habilitado smallint`).

**1. `SoftDeletes`** — cuando "eliminar" significa solo "dejar de aparecer en listados activos, y es
recuperable". Aplica a: Cliente, Producto, Tarifa y todos los catálogos genéricos (tipos de
documento, tipo de contrato, motivos de gestión, vendedores, eventos, tipo de producto, tipo de
evento, cobradores, métodos de pago, sucursales, días de cambio de estado). Es el equivalente
mejorado del `Habilitado` del legacy.

> **Gotcha:** `unique` + `SoftDeletes` chocan. Si se borra "Efectivo" y se vuelve a crear, el índice
> único falla. Se resuelve con índice compuesto que incluya `deleted_at`, o validando en el Service
> solo contra registros no borrados. Aplica igual al `documento` del Cliente.

**2. Estado explícito, no `SoftDeletes`** — el Contrato usa un ciclo de vida propio
(borrador/activo/suspendido/cancelado/finalizado). `SoftDeletes` queda solo como salvavidas para "se
creó por error y nunca debió existir", jamás para representar cancelación o finalización.

**3. Sin borrado de ningún tipo** — Pago, Pago Programado, Devolución, Gestión y Compromiso son
**eventos de historial**: no se ocultan ni se marcan como borrados. Un reverso o un descarte se
modela como un evento propio vinculado al original, que sigue visible en los listados. Se corrigen
con otro evento, nunca borrando.

**4. Borrado físico permitido** — solo para datos que nunca llegaron a ser un hecho de negocio:
borradores de contrato jamás activados, filas de una importación fallida. No contradice la regla de
"nada se elimina", que aplica a información que sí existió.

**5. Log de auditoría** — inmutable a nivel de registro: no hay borrado individual ni edición. La
única forma de eliminar es la **purga por retención**, que borra por antigüedad, pasa por la API y
queda ella misma registrada.

---

## Auditoría técnica vs historia de negocio

Son **dos cosas distintas** y el legacy las mezcló en una sola tabla `Log`, lo que obligó a
reconstruir el timeline del cliente con un `WHERE Tabla IN (...) AND Llave = cliente`.

- **Auditoría técnica** (`activity_log`, spatie/activitylog): automática, inmutable, responde "quién
  cambió qué campo y cuándo". Es infraestructura.
- **Historia de negocio** (tabla propia): eventos con significado — contrato creado, gestión,
  compromiso, pago, cambio de estado de cartera, devolución, autorización. Alimenta la ficha 360° y
  es una **funcionalidad del producto**, no un log.

Reglas asociadas:

- **No se auditan lecturas**, solo escrituras. Auditar consultas multiplicaría el volumen sin un caso
  de uso que lo pida.
- **El autor va congelado**: cada registro guarda id, username y nombre del usuario en el momento del
  hecho. Sobrevive a que el usuario se borre o se renombre. Una FK viva perdería el nombre.
- **Motivo obligatorio** en toda acción que pase por el módulo de autorizaciones.
- **La purga solo por API** (endpoint y comando comparten Service), nunca por SQL suelto, y queda
  registrada con autor y cantidad eliminada.

---

## Motor de estados genérico

En vez de un enum por entidad, dos tablas: `states` (entidad, clave, nombre, inicial, final, color) y
`state_transitions` (de → a, permiso, si es automática, si exige autorización, si exige motivo).

El motivo es concreto: en el legacy agregar un estado obliga a tocar el flujo del código en varios
sitios. Con esto es un INSERT. El legacy ya tenía un catálogo (`Estados` + `TiposEstados`) pero **sin
transiciones**: qué puede pasar a qué vivía en el código.

Reglas que protegen el grafo: un solo estado inicial por entidad, no se borra el estado inicial ni
uno usado por una transición, un estado final no tiene transiciones de salida, ambos extremos deben
pertenecer a la misma entidad, y no hay duplicados ni transiciones a sí mismo.

El cambio de estado y su registro de auditoría van **en la misma transacción**: si falla el log, el
estado no se mueve.

---

## Catálogos: una tabla por catálogo, una sola implementación de CRUD

Cada catálogo tiene su tabla y su modelo (los campos no son iguales: motivos necesita color, "días de
cambio de estado" necesita rango de días), pero comparten **una sola implementación** de
Controller/Service/Repository. Dos permisos para todos (`catalogs.view` y `catalogs.manage`), no uno
por catálogo.

En frontend, un componente de listado/formulario que se dibuja según metadata del modelo, agrupado en
una pantalla con selector — no una pantalla por catálogo.

**Fuera de alcance:** "Tipos de vivienda" (no aporta valor; su controlador en el legacy está vacío) y
"Resultados de gestión" (se cubre con Motivos de gestión). Canal/Organización y Zona no son catálogos
propios: son campos de Eventos, y barrio/zona es texto libre.

> **Aparcado (2026-08-19).** El diseño se va a reformar. Del análisis: el CRUD genérico cubre bien
> unos 8 catálogos simples, pero **Tarifa, Producto y "Días de cambio de estado" no encajan** — tienen
> FK, dinero y versionado. Forzarlos dentro del genérico lo convierte en un framework. Las 4 pantallas
> existentes (productos, tarifas, vendedores, motivos) siguen sin backend y devuelven 404.

---

## Notificaciones: por ahora solo toasts

No se implementa un sistema de notificaciones. Cuando haga falta avisar algo se usa el `ToastService`
que ya existe en el frontend.

**Requisito para cuando se implemente:** que el punto de emisión sea **uno solo** (una fachada o
servicio `Notifier`), para que pasar de toast a campana in-app, correo o WhatsApp sea cambiar la
implementación y no recorrer todas las pantallas.

---

## Infraestructura de Jobs: se omite hasta tener consumidor

No se construye `app/Jobs` por ahora. Su primer consumidor real es el recálculo de mora del Motor
Financiero. El scheduler ya está montado y funcionando, así que agregar jobs después es enchufar, no
cimentar.

---

## Transacciones en el Service

Todo método de Service que escriba en **más de una tabla** va envuelto en `DB::transaction()`. La
transacción vive en el **Service**, nunca en el Controller ni en el Repository: el Service es quien
conoce la unidad de negocio ("crear cliente con sus direcciones" es un solo hecho, aunque sean cuatro
inserts).

El legacy tiene exactamente este bug: `Clientes.php` inserta cliente, dirección, referencias y pedido
uno por uno, sin rollback. Si falla el tercero quedan datos huérfanos.

Los jobs se despachan con `after_commit` (activado en `config/queue.php`) para que nunca corran
contra datos sin commitear.

---

## Dinero en enteros

Todo monto es un entero de pesos colombianos, sin centavos, con redondeo al millar. Columnas
`bigInteger`, cast `integer` — nunca `decimal` ni `float`. El redondeo, el parseo de montos que
llegan de fuera y el reparto de un total entre cuotas pasan por `App\Support\Money`.

El legacy ya usa enteros (`Valor int`, `Saldo int`), así que la convención coincide con los datos
reales que se van a migrar.

---

## Modo mantenimiento propio, además del de Laravel

`php artisan down` apaga la aplicación entera para desplegar y se controla por SSH. El modo
mantenimiento propio se activa desde la pantalla de Parámetros, permite que un administrador siga
trabajando, tiene alcance configurable (`all` bloquea todo, `writes` deja consultar) y mensaje
editable.

Decisiones asociadas:

- **Falla abierto:** si los parámetros no se pueden leer, deja pasar. Lo contrario dejaría a todos
  afuera por un fallo de caché, sin forma de entrar a apagarlo.
- **`/api/health` y `/api/auth/*` siguen respondiendo:** el monitor debe distinguir "en mantenimiento"
  de "caído", y alguien debe poder entrar a ver el aviso en vez de chocar con un login que rechaza
  credenciales correctas.
- **Las tareas programadas se pausan:** si se activa mantenimiento para corregir saldos a mano y el
  cron recalcula encima, el mantenimiento no sirvió de nada.

