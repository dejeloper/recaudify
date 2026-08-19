# Decisiones de negocio

Reglas del dominio acordadas con el cliente y su **porqué**. `planning.md` dice *qué* hay que
construir; este archivo dice *por qué es así* y qué se descartó.

**Regla de alcance transversal:** V2.0 = paridad funcional con el legacy + arquitectura nueva. Lo que
no exista hoy en el legacy se marca `[2.1]` y no se implementa, pero **sí se le deja el hueco en el
modelo de datos** cuando agregarlo después costaría una migración con cartera viva.

---

## Corte y convivencia

El legacy sigue **productivo** mientras se construye Recaudify. El corte es **big-bang**: un día se
apaga el viejo y arranca el nuevo. No hay convivencia.

El corte exige paridad en Clientes, Catálogos, Pagos, Cobranza, Llamadas del día, Devoluciones y
Reportes. **No bloquean el corte** la importación por CSV (la migración es manual) ni el backup
(excluido por contrato).

---

## Multi-tenancy y sucursales

**Multi-tenancy queda fuera.** Una empresa, una base de datos. El aislamiento entre empresas se
resuelve con hosting y BD separados, que es la versión que funciona sin código extra.

**Sucursal no es tenant.** Un solo dueño con varias sedes que comparten cartera: mismos clientes,
cambian cobradores, medios de pago y usuarios que gestionan. Eso es *scoping* (`sucursal_id` +
permiso de "ver todas"), no multi-tenancy.

Se crea `sucursal_id` nullable en las tablas operativas **desde el día uno**, aunque la UI no lo
muestre: agregarlo después es tocar quince tablas con datos vivos.

---

## Cliente y contrato

- **Se crean por separado.** Un cliente puede existir sin contrato. El legacy los creaba juntos.
- **El centro operativo es el contrato**, no el cliente: la deuda, la mora, el plan de cuotas y el
  estado viven ahí. El cliente es la ficha que los agrupa. Montarlo al revés repite el error del
  legacy en cuanto haya dos contratos.
- **N contratos por cliente.** Una compra nueva **siempre** es un contrato nuevo, aunque el cliente
  ya tenga deuda. Nunca se fusiona en el contrato viejo. La consolidación del cobro se resuelve en
  Cobranza.
- **Documento único de verdad.** En el legacy el `unique` está comentado y hay duplicados; el sistema
  nuevo se diseña como si no existieran, y la limpieza ocurre en la migración manual.
- **Observaciones append-only con categoría.** Nunca se editan ni se borran: si te equivocaste,
  escribes otra. Se descartó el `varchar(5000)` concatenado del legacy. Un timeline unificado
  (observaciones + gestiones + pagos + cambios de estado) queda para `[2.1]`, sobre la misma data.

---

## Tarifas: la tarifa no se referencia, se copia

Al agregar un producto a un contrato se guarda una línea con producto, tarifa, versión, precio,
número de cuotas y valor de cuota, **todo congelado en el momento de la venta**. La tarifa maestra es
el origen de esos valores, no la fuente de verdad del contrato.

Esto resuelve tres problemas de una vez:

- Un contrato puede mezclar productos de tarifas distintas: cada línea trae la suya.
- Subir o cambiar una tarifa **nunca** toca contratos vivos. El valor pactado es el que se paga.
- El historial de tarifas sale gratis: cada contrato guarda la versión que usó y con qué números.

La tarifa maestra pasa a ser **versionada**: editarla crea una versión nueva, no sobrescribe. Vender
con una tarifa anterior es posible seleccionando una versión histórica, con autorización de
admin/coordinador (parámetro, por defecto exigida).

---

## Plan de cuotas

El contrato guarda `periodicidad_dias` y `dia_de_cobro`, y al crearlo se **genera un plan de cuotas
explícito**: una fila por cuota, con fecha de vencimiento y montos. El motor de mora lee ese plan; no
recalcula fechas.

Semanal, quincenal, mensual o "cada 20 días" son el mismo código con otro número. Como el plan está
materializado, cambiar la periodicidad a mitad de contrato es regenerar las cuotas futuras dejando
rastro.

El reparto del total entre cuotas pasa por `App\Support\Money::split()`, que garantiza que la suma
del plan cuadre exactamente con el valor del contrato.

---

## Mora

**Cinco estados fijos**, con los días parametrizables:

| Tramo por defecto | Estado |
| --- | --- |
| 0–15 días | Al día |
| 16–30 | Deben |
| 31–45 | En mora |
| 46–90 | Pre-datacrédito |
| +90 | Jurídico / centrales de riesgo |

> ⚠️ El legacy usa **cuatro** tramos (≤10 / 11–44 / 45–89 / ≥90). Al migrar hay que decidir si los
> clientes existentes se remapean a la escala nueva.

**El recálculo es un cron que puede dispararse también a mano** con un botón. Nunca como efecto
colateral de abrir una pantalla, que es como funciona el legacy (`Deuda()` corre en cada carga de
página: si nadie entra no se actualiza nada, y si entran diez se recalcula diez veces).

**Interés de mora:** parametrizable (sin interés / % fijo / diario / semanal / mensual), **apagado
por defecto** hasta confirmar si el negocio lo cobra realmente.

**Pago mínimo:** hay un catálogo de ocho fórmulas registradas, de las cuales solo se implementan dos
(`CUOTAS_VENCIDAS` y la fórmula del legacy con redondeo al millar). Las otras seis existen como
catálogo y se implementan cuando un cliente las pida.

**DataCrédito** es, por ahora, solo un listado de clientes para proceso jurídico externo. No hay
integración.

---

## Dinero

Siempre **enteros de pesos colombianos, sin centavos**, con redondeo **al millar**. El legacy ya lo
hace así (`Valor int`, `Saldo int`), así que la convención coincide con la realidad.

Un **sobrepago** deja saldo negativo: se guarda, no se procesa. Qué hacer con él queda para `[2.1]`.

---

## Actores

El legacy metió tres cosas distintas dentro de "Cobradores". Van separadas:

| Eje | Quién es | En el modelo |
| --- | --- | --- |
| Quién gestiona | El **gestor**: llama o escribe. Usa el sistema. | `gestor_id` en la gestión |
| Quién recauda | El **recaudador** (el "motorizado"): va hasta la casa. Puede no usar el sistema. | `recaudador_id` en el pago |
| Cómo entró la plata | Efectivo, transferencia, consignación, datáfono | `metodo_pago_id` |

Además, en la venta: el **vendedor** (comisiona) y el **cerrador** (auxiliar que toma los datos del
cliente, **no comisiona**, cargo opcional). Se guardan `vendedor_id` y `cerrador_id` en el contrato
desde ya, aunque el reporte de rendimiento por cerrador sea `[2.1]`.

Hoy casi todo el recaudo es por transferencia, pero no se descarta volver a cobradores en calle.

---

## Gestión y cobranza

El flujo acordado:

```
gestión (se registra SIEMPRE, conteste o no, con canal y resultado)
   └─ si promete pagar → compromiso de pago (fecha + monto)
         ├─ se confirma el dinero → nace el pago real, compromiso "cumplido"
         └─ vence sin confirmarse → "incumplido", vuelve a la agenda
```

Eso separa **promesa** de **pago**, que es justo lo que el legacy mezcla en `PagosProgramados`.

**Cadencia de contacto:** parametrizable, con dos modos. Por defecto **por estado de mora** (al día
1/mes, deben 1/semana, mora 2/semana, pre-datacrédito diaria), alternativa **fija** (N por periodo).
La cadencia **nunca bloquea** al gestor: solo alimenta la agenda y el reporte de cumplimiento.

**Seguimiento al gestor:** como toda gestión queda registrada con canal y resultado, el reporte de
gestiones / promesas hechas / promesas cumplidas / efectividad es una consulta, no un módulo. El
escalamiento automático tras N incumplimientos queda `[2.1]`.

**Evidencia adjunta** (foto, audio) queda `[2.1]`: hoy todo es físico con numeración, y no hay
storage montado.

---

## Pagos

- **Descarte de compromiso:** parametrizable, por defecto 60 días sin confirmar → `incumplido`.
- **Reverso:** no borra el pago, lo mueve a un estado final y queda visible. Exige autorización
  (parámetro), dentro de una ventana de tiempo (parámetro) y por un rol autorizado (parámetro).
- **Recibos:** HTML imprimible en media carta. PDF y envío por canal quedan `[2.1]`. Hoy se imprime
  en formatos físicos preexistentes.
- **Consecutivo de recibos:** único global, con rastreo opcional por sucursal, automático por defecto
  o manual.
- **Reparto de un pago entre varios contratos:** parametrizable — el más viejo, el más vencido, o lo
  elige el cobrador.

---

## Validación de contrato

Feature **nueva**, no existe en el legacy. Se partió en dos para no bloquear el corte con una
integración de mensajería que aún no está contratada:

- **`[2.0]`** — al crear un contrato, el sistema detecta y avisa si el cliente ya tiene deuda, exige
  autorización para continuar (queda registrado quién y por qué), y el contrato nace en
  `pendiente_validación` con un checklist manual (documento, teléfono, dirección) antes de pasar a
  `activo`. Parametrizable si la validación es obligatoria.
- **`[2.1]`** — envío automático por SMS/email/WhatsApp y confirmación del cliente. El modelo ya
  queda listo (canal, resultado, fecha); solo se enchufa el proveedor.

Motivo del recorte: sin él, hay casos reales de productos entregados a datos falsos, por mala
confirmación al vender o por mala fe del vendedor.

---

## Autorizaciones

La autorización es **transversal**, no propia de descuentos: se pidió para reverso de pago, descuento,
cambio manual de estado, venta a cliente con deuda y uso de tarifa histórica.

Por eso es **un solo módulo genérico**: una solicitud polimórfica (qué se pide, sobre qué registro,
quién la pide, quién puede aprobarla, motivo, resultado, fecha). Cada caso nuevo se enchufa
declarando un tipo, no construyendo un flujo.

Descuentos y condonaciones **no son un módulo aparte**: son un tipo de solicitud más su efecto sobre
el saldo del contrato.

---

## Ciclo de vida

Automático por defecto, con excepciones manuales autorizadas. Casos reales que lo justifican: el
cliente que avisa que se va del país dos meses y vuelve, o el producto robado tras una venta mal
validada.

Cambiar un estado a mano exige autorización y motivo, y queda auditado.

---

## Alcance de lectura y gestión

**La lectura es global:** cualquier usuario puede consultar cualquier cliente, haya sido suyo o no.
**Lo que se restringe es gestionar**, no consultar: un gestor solo opera sobre los clientes de su
cartera (parámetro `manage_own_clients_only`). Admin y coordinador ven y hacen todo.

Cuando un cliente cambia de gestor, el nuevo ve toda la historia anterior y el anterior conserva
acceso de consulta. Se descartó evaluar permisos contra el histórico de asignación: obligaría a
resolver "¿quién era el dueño el 3 de marzo?" en cada consulta, ensuciando todos los queries para
proteger algo que no es secreto entre compañeros de la misma empresa.

El cambio de cobrador lo hace un coordinador o admin, y se registra **cuándo y por qué**.

---

## Contexto operativo

- **10 usuarios** en el legacy, con dos roles (admin y auxiliar).
- Volumen de altas de clientes: entre ~250 y ~2.800 por mes en el último año.
- Todo **online y web**. No hay trabajo offline ni app móvil.
- Despliegue: **Vercel** (frontend) + **VPS** (backend y crons).
- **Facturación electrónica (Factus)** llega en `[2.1]`, pero el modelo de Cliente se deja compatible
  desde ya (tipo y número de documento DIAN, régimen, email, municipio y departamento): es una
  columna hoy contra una migración con cartera viva después.

---

## Fuera de alcance

| Tema | Motivo |
| --- | --- |
| Backups | Excluido explícitamente por contrato; responsabilidad del cliente |
| Importador CSV | La migración es manual y la hace el desarrollador |
| Multi-tenancy | Ver arriba |
| Portal para el cliente final | No hay; se evaluará como feature futura |
| Geolocalización de direcciones | Se revisará en `[2.1]` |
| Notificaciones | Por ahora solo toasts. Ver `decisiones.md` |
