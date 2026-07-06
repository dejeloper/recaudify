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
