-- =========================================================================
-- Recaudify — Esquema de demostración: catálogos y sus vínculos
-- =========================================================================
-- Ilustra el modelo entidad-relación descrito en NEGOCIO.md y planning.md.
-- NO es DDL de producción: faltan engine/charset explícitos, índices de
-- rendimiento adicionales, y las migraciones reales de Laravel manejarán
-- esto con `Schema::create()`. El objetivo es mostrar, en un solo lugar,
-- dónde vive cada catálogo y a qué tabla del núcleo de negocio se conecta.
--
-- Convenciones usadas (ver planning.md, "Convención de borrado y estado"):
--   - deleted_at NULLABLE  -> SoftDeletes (catálogos, Cliente, Producto, Tarifa)
--   - sin deleted_at       -> nunca se oculta (Pago, Gestión) o el estado vive
--                             en un campo propio (Contrato: estado_ciclo_vida)
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1) CATÁLOGOS GENÉRICOS — misma forma (id, nombre, SoftDeletes), comparten
--    UNA sola implementación de CRUD (Repository/Service/Controller
--    genérico), cada uno en su propia tabla (planning.md, "Catálogos base").
-- -------------------------------------------------------------------------

CREATE TABLE tipos_documento (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    codigo      VARCHAR(20)  NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

-- id explícito en los INSERT de este archivo solo para que las FK de abajo
-- sean fáciles de seguir a simple vista; en Laravel el id lo asigna el auto_increment.
INSERT INTO tipos_documento (id, nombre, codigo) VALUES
(1, 'Cédula de ciudadanía', 'CC'),
(2, 'Cédula de extranjería', 'CE'),
(3, 'NIT', 'NIT'),
(4, 'Pasaporte', 'PAS');

CREATE TABLE tipos_contrato (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO tipos_contrato (id, nombre) VALUES
(1, 'Venta a crédito'),
(2, 'Venta de contado'),
(3, 'Consignación'),
(4, 'Comodato');

CREATE TABLE tipos_producto (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO tipos_producto (id, nombre) VALUES
(1, 'Biblias y libros'),
(2, 'Electrodomésticos'),
(3, 'Ropa y calzado'),
(4, 'Joyería');

CREATE TABLE tipos_evento (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO tipos_evento (id, nombre) VALUES
(1, 'Puerta a puerta'),
(2, 'Feria comercial'),
(3, 'Referido'),
(4, 'Redes sociales');

CREATE TABLE metodos_pago (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO metodos_pago (id, nombre) VALUES
(1, 'Efectivo'),
(2, 'Transferencia'),
(3, 'Tarjeta'),
(4, 'Consignación bancaria');

CREATE TABLE sucursales (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO sucursales (id, nombre) VALUES
(1, 'Bogotá - Centro'),
(2, 'Medellín - Poblado'),
(3, 'Cali - Norte'),
(4, 'Barranquilla - Centro');

-- Motivos de gestión: catálogo genérico + 1 campo propio (color)
CREATE TABLE motivos_gestion (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    color       VARCHAR(7)   NULL, -- '#RRGGBB'
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO motivos_gestion (id, nombre, color) VALUES
(1, 'Llamada exitosa', '#22C55E'),
(2, 'No contesta', '#F59E0B'),
(3, 'Reagendar', '#3B82F6'),
(4, 'Cliente molesto', '#EF4444');

-- Bajo revisión, no confirmado (planning.md, "Catálogos base") — se incluye solo para
-- mostrar la forma que tendría si se construye.
CREATE TABLE clasificacion_clientes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(100) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO clasificacion_clientes (id, nombre) VALUES
(1, 'Nuevo'),
(2, 'Recurrente'),
(3, 'Referido'),
(4, 'VIP');

-- -------------------------------------------------------------------------
-- 2) CATÁLOGO CON RELACIÓN PROPIA: reglas de cambio de estado de cartera.
--    Reemplaza los "umbrales de mora" que se iban a modelar como Parameters
--    sueltos. El orden de evaluación se
--    deriva de dia_desde — no hay columna "orden".
-- -------------------------------------------------------------------------

CREATE TABLE reglas_cambio_estado (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre            VARCHAR(100) NOT NULL,           -- p.ej. "Mora temprana"
    dia_desde         SMALLINT UNSIGNED NOT NULL,
    dia_hasta         SMALLINT UNSIGNED NULL,           -- NULL = sin techo (ej. "≥90")
    estado_resultante VARCHAR(50) NOT NULL,             -- al_dia | proximo_vencimiento | mora_temprana | mora_avanzada | prejuridico | juridico | castigado | paz_y_salvo
    color             VARCHAR(7)  NULL,
    icono             VARCHAR(50) NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,
    UNIQUE KEY uq_reglas_rango (dia_desde, dia_hasta)
);

INSERT INTO reglas_cambio_estado (id, nombre, dia_desde, dia_hasta, estado_resultante, color, icono) VALUES
(1, 'Al día',        0, 10,   'al_dia',        '#22C55E', 'check-circle'),
(2, 'Mora temprana', 11, 44,  'mora_temprana', '#F59E0B', 'clock'),
(3, 'Mora avanzada', 45, 89,  'mora_avanzada', '#F97316', 'alert-triangle'),
(4, 'Jurídico',       90, NULL, 'juridico',     '#EF4444', 'gavel');

-- -------------------------------------------------------------------------
-- 3) CATÁLOGOS CON RELACIONES PROPIAS — comparten el motor de CRUD, pero no
--    entran en la pantalla genérica /admin/catalogos.
-- -------------------------------------------------------------------------

CREATE TABLE vendedores (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    telefono    VARCHAR(30)  NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

INSERT INTO vendedores (id, nombre, telefono) VALUES
(1, 'Carlos Ramírez', '3001112233'),
(2, 'Diana Torres', '3002223344'),
(3, 'Luis Fernández', '3003334455'),
(4, 'Marta Gómez', '3004445566');

-- Cobrador = cartera/bolsa de clientes, NO un usuario (planning.md, Fase
-- Cartera/Cobranza). La identidad operativa se resuelve en usuario_cobrador.
CREATE TABLE cobradores (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(150) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL
);

-- Nombres de carteras, no de personas — la persona que las opera vive en usuario_cobrador.
INSERT INTO cobradores (id, nombre) VALUES
(1, 'Cartera Norte'),
(2, 'Cartera Sur'),
(3, 'Cartera Centro'),
(4, 'Cartera Referidos');

-- Eventos = Vendedor + Canal (campo propio) + Zona/Barrio (string libre) +
-- Tipo de evento + Fecha. Canal y Zona NO son catálogos independientes.
CREATE TABLE eventos (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    vendedor_id     BIGINT UNSIGNED NOT NULL,
    tipo_evento_id  BIGINT UNSIGNED NOT NULL,
    canal           VARCHAR(150) NULL, -- antes "iglesia" en el legacy; campo propio, no catálogo
    barrio          VARCHAR(150) NULL, -- string libre, sin jerarquía ni catálogo detrás
    fecha           DATE NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    deleted_at      TIMESTAMP NULL,
    CONSTRAINT fk_eventos_vendedor    FOREIGN KEY (vendedor_id)    REFERENCES vendedores(id),
    CONSTRAINT fk_eventos_tipo_evento FOREIGN KEY (tipo_evento_id) REFERENCES tipos_evento(id)
);

INSERT INTO eventos (id, vendedor_id, tipo_evento_id, canal, barrio, fecha) VALUES
(1, 1, 1, 'Iglesia Cristiana Central', 'El Prado', '2026-01-15'),
(2, 2, 2, 'Feria Comercial Chapinero', 'Chapinero', '2026-02-10'),
(3, 3, 3, 'Referido cliente #12', 'Suba', '2026-03-05'),
(4, 4, 4, 'Campaña Facebook Ads', 'Kennedy', '2026-04-20');

-- -------------------------------------------------------------------------
-- 4) NÚCLEO DE NEGOCIO — solo lo necesario para mostrar dónde se enganchan
--    los catálogos (no es el modelo completo de Contratos/Pagos/Cobranza).
-- -------------------------------------------------------------------------

CREATE TABLE clientes (
    id                        BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_documento_id         BIGINT UNSIGNED NOT NULL,
    documento                 VARCHAR(30)  NOT NULL,
    nombre                    VARCHAR(150) NOT NULL,
    clasificacion_cliente_id  BIGINT UNSIGNED NULL, -- catálogo bajo revisión
    sucursal_id               BIGINT UNSIGNED NULL,
    created_at                TIMESTAMP NULL,
    updated_at                TIMESTAMP NULL,
    deleted_at                TIMESTAMP NULL,
    CONSTRAINT fk_clientes_tipo_documento FOREIGN KEY (tipo_documento_id)        REFERENCES tipos_documento(id),
    CONSTRAINT fk_clientes_clasificacion  FOREIGN KEY (clasificacion_cliente_id) REFERENCES clasificacion_clientes(id),
    CONSTRAINT fk_clientes_sucursal       FOREIGN KEY (sucursal_id)              REFERENCES sucursales(id),
    -- único compuesto con deleted_at (gotcha de SoftDeletes + unique): permite dar
    -- de alta un documento nuevo si el cliente original ya fue soft-deleted.
    UNIQUE KEY uq_clientes_documento (documento, deleted_at)
);

INSERT INTO clientes (id, tipo_documento_id, documento, nombre, clasificacion_cliente_id, sucursal_id) VALUES
(1, 1, '123456789', 'Ana María López', 1, 1),
(2, 1, '987654321', 'Pedro Sánchez', 2, 2),
(3, 2, 'CE456789', 'Julia Restrepo', 3, 3),
(4, 3, '900123456', 'Comercial El Ahorro SAS', 4, 4);

CREATE TABLE direcciones (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id  BIGINT UNSIGNED NOT NULL,
    direccion   VARCHAR(200) NOT NULL,
    barrio      VARCHAR(150) NULL, -- string libre, igual que en Eventos
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    CONSTRAINT fk_direcciones_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

INSERT INTO direcciones (id, cliente_id, direccion, barrio) VALUES
(1, 1, 'Calle 45 #23-10', 'El Prado'),
(2, 2, 'Carrera 10 #5-20', 'Chapinero'),
(3, 3, 'Avenida 68 #34-12', 'Suba'),
(4, 4, 'Calle 80 #12-45', 'Kennedy');

CREATE TABLE telefonos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id  BIGINT UNSIGNED NOT NULL,
    numero      VARCHAR(30) NOT NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    CONSTRAINT fk_telefonos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id)
);

INSERT INTO telefonos (id, cliente_id, numero) VALUES
(1, 1, '3101234567'),
(2, 2, '3112345678'),
(3, 3, '3123456789'),
(4, 4, '3134567890');

-- Asignación Cliente <-> Cobrador (la "cesta" de cartera, no el Usuario)
CREATE TABLE cliente_cobrador (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   BIGINT UNSIGNED NOT NULL,
    cobrador_id  BIGINT UNSIGNED NOT NULL,
    created_at   TIMESTAMP NULL,
    CONSTRAINT fk_cliente_cobrador_cliente  FOREIGN KEY (cliente_id)  REFERENCES clientes(id),
    CONSTRAINT fk_cliente_cobrador_cobrador FOREIGN KEY (cobrador_id) REFERENCES cobradores(id)
);

INSERT INTO cliente_cobrador (id, cliente_id, cobrador_id) VALUES
(1, 1, 1),
(2, 2, 2),
(3, 3, 3),
(4, 4, 4);

-- Asignación Usuario <-> Cobrador CON HISTORIAL (fecha_fin NULL = vigente).
-- Reasignar personal = cerrar una fila + abrir otra; nunca se toca cliente
-- por cliente (planning.md, Fase Cartera/Cobranza).
CREATE TABLE usuario_cobrador (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id    BIGINT UNSIGNED NOT NULL, -- FK a la tabla `users` ya existente
    cobrador_id   BIGINT UNSIGNED NOT NULL,
    fecha_inicio  TIMESTAMP NOT NULL,
    fecha_fin     TIMESTAMP NULL,
    CONSTRAINT fk_usuario_cobrador_cobrador FOREIGN KEY (cobrador_id) REFERENCES cobradores(id)
);

-- usuario_id son ids de ejemplo de la tabla `users` ya existente.
-- Fila 2+3 muestran el caso de uso central: al usuario 11 se le cierra su
-- período en "Cartera Sur" (fecha_fin) y el usuario 12 la retoma justo
-- después — cliente_cobrador (arriba) NO se tocó para nada.
INSERT INTO usuario_cobrador (id, usuario_id, cobrador_id, fecha_inicio, fecha_fin) VALUES
(1, 10, 1, '2025-01-01 08:00:00', NULL),
(2, 11, 2, '2025-01-01 08:00:00', '2026-01-01 08:00:00'),
(3, 12, 2, '2026-01-01 08:00:00', NULL),
(4, 13, 3, '2025-06-01 08:00:00', NULL);

CREATE TABLE productos (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_producto_id  BIGINT UNSIGNED NOT NULL,
    nombre            VARCHAR(150) NOT NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,
    CONSTRAINT fk_productos_tipo FOREIGN KEY (tipo_producto_id) REFERENCES tipos_producto(id)
);

INSERT INTO productos (id, tipo_producto_id, nombre) VALUES
(1, 1, 'Biblia de Estudio Reina Valera'),
(2, 2, 'Licuadora Oster 3 velocidades'),
(3, 3, 'Chaqueta impermeable talla M'),
(4, 4, 'Anillo de plata 925');

-- Tarifas versionadas: una tarifa "vieja" se soft-deletea, nunca se
-- sobreescribe (a diferencia del legacy).
CREATE TABLE tarifas (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id BIGINT UNSIGNED NOT NULL,
    cuotas      SMALLINT UNSIGNED NOT NULL,
    valor       DECIMAL(12,2) NOT NULL,
    descuento   DECIMAL(12,2) NULL,
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    deleted_at  TIMESTAMP NULL,
    CONSTRAINT fk_tarifas_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
);

INSERT INTO tarifas (id, producto_id, cuotas, valor, descuento) VALUES
(1, 1, 12, 120000.00, NULL),
(2, 2, 18, 450000.00, 20000.00),
(3, 3, 10, 180000.00, NULL),
(4, 4, 24, 600000.00, 50000.00);

CREATE TABLE contratos (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id              BIGINT UNSIGNED NOT NULL,
    tipo_contrato_id        BIGINT UNSIGNED NOT NULL,
    evento_id               BIGINT UNSIGNED NOT NULL,
    tarifa_id               BIGINT UNSIGNED NOT NULL,
    sucursal_id             BIGINT UNSIGNED NULL,
    estado_ciclo_vida       VARCHAR(20) NOT NULL DEFAULT 'borrador', -- borrador|activo|suspendido|cancelado|finalizado
    regla_estado_actual_id  BIGINT UNSIGNED NULL, -- caché de lectura: última regla de cartera aplicada por el Motor Financiero
    dia_cobro               TINYINT UNSIGNED NOT NULL,
    created_at              TIMESTAMP NULL,
    updated_at              TIMESTAMP NULL,
    -- SIN deleted_at como mecanismo principal (planning.md, "Convención de borrado y estado", punto 1): el
    -- ciclo de vida vive en estado_ciclo_vida. Se deja la columna solo como
    -- salvavidas para error de captura (contrato que nunca debió existir).
    deleted_at              TIMESTAMP NULL,
    CONSTRAINT fk_contratos_cliente       FOREIGN KEY (cliente_id)             REFERENCES clientes(id),
    CONSTRAINT fk_contratos_tipo          FOREIGN KEY (tipo_contrato_id)       REFERENCES tipos_contrato(id),
    CONSTRAINT fk_contratos_evento        FOREIGN KEY (evento_id)              REFERENCES eventos(id),
    CONSTRAINT fk_contratos_tarifa        FOREIGN KEY (tarifa_id)              REFERENCES tarifas(id),
    CONSTRAINT fk_contratos_sucursal      FOREIGN KEY (sucursal_id)            REFERENCES sucursales(id),
    CONSTRAINT fk_contratos_regla_estado  FOREIGN KEY (regla_estado_actual_id) REFERENCES reglas_cambio_estado(id)
);

-- regla_estado_actual_id 1='Al día', 2='Mora temprana', 3='Mora avanzada' (ver reglas_cambio_estado).
INSERT INTO contratos (id, cliente_id, tipo_contrato_id, evento_id, tarifa_id, sucursal_id, estado_ciclo_vida, regla_estado_actual_id, dia_cobro) VALUES
(1, 1, 1, 1, 1, 1, 'activo', 1, 15),
(2, 2, 1, 2, 2, 2, 'activo', 2, 20),
(3, 3, 2, 3, 3, 3, 'finalizado', 1, 10),
(4, 4, 1, 4, 4, 4, 'suspendido', 3, 5);

CREATE TABLE contrato_productos (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id  BIGINT UNSIGNED NOT NULL,
    producto_id  BIGINT UNSIGNED NOT NULL,
    cantidad     SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    valor        DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_contrato_productos_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id),
    CONSTRAINT fk_contrato_productos_producto FOREIGN KEY (producto_id) REFERENCES productos(id)
);

INSERT INTO contrato_productos (id, contrato_id, producto_id, cantidad, valor) VALUES
(1, 1, 1, 1, 120000.00),
(2, 2, 2, 1, 450000.00),
(3, 3, 3, 2, 360000.00),
(4, 4, 4, 1, 600000.00);

-- SIN deleted_at (planning.md, "Convención de borrado y estado", punto 2): un pago nunca se oculta.
-- El reverso es OTRO registro que apunta al original vía reversado_de_id.
CREATE TABLE pagos (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id      BIGINT UNSIGNED NOT NULL,
    metodo_pago_id   BIGINT UNSIGNED NOT NULL,
    valor            DECIMAL(12,2) NOT NULL,
    fecha            DATE NOT NULL,
    reversado_de_id  BIGINT UNSIGNED NULL,
    created_at       TIMESTAMP NULL,
    CONSTRAINT fk_pagos_contrato FOREIGN KEY (contrato_id)     REFERENCES contratos(id),
    CONSTRAINT fk_pagos_metodo   FOREIGN KEY (metodo_pago_id)  REFERENCES metodos_pago(id),
    CONSTRAINT fk_pagos_reverso  FOREIGN KEY (reversado_de_id) REFERENCES pagos(id)
);

-- El pago 4 reversa al pago 3 (mismo contrato, mismo valor, reversado_de_id
-- apunta al original) — el 3 nunca se borra ni se oculta, ambos quedan
-- visibles en el historial del contrato 3.
INSERT INTO pagos (id, contrato_id, metodo_pago_id, valor, fecha, reversado_de_id) VALUES
(1, 1, 1, 10000.00, '2026-02-15', NULL),
(2, 2, 2, 25000.00, '2026-02-20', NULL),
(3, 3, 3, 180000.00, '2026-01-10', NULL),
(4, 3, 3, 180000.00, '2026-01-12', 3);

CREATE TABLE pagos_programados (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id       BIGINT UNSIGNED NOT NULL,
    fecha_programada  DATE NOT NULL,
    estado            VARCHAR(20) NOT NULL DEFAULT 'programado', -- programado|pagado|descartado
    created_at        TIMESTAMP NULL,
    CONSTRAINT fk_pagos_programados_contrato FOREIGN KEY (contrato_id) REFERENCES contratos(id)
);

INSERT INTO pagos_programados (id, contrato_id, fecha_programada, estado) VALUES
(1, 1, '2026-03-15', 'programado'),
(2, 2, '2026-03-20', 'pagado'),
(3, 3, '2026-01-25', 'descartado'),
(4, 4, '2026-04-05', 'programado');

-- invalidated_at es una MARCA DE VIGENCIA, no un borrado (NEGOCIO.md §13):
-- la gestión sigue visible en el historial siempre.
CREATE TABLE gestiones (
    id                 BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    contrato_id        BIGINT UNSIGNED NOT NULL,
    usuario_id         BIGINT UNSIGNED NOT NULL,
    motivo_gestion_id  BIGINT UNSIGNED NOT NULL,
    observacion        TEXT NULL,
    invalidated_at     TIMESTAMP NULL,
    created_at         TIMESTAMP NULL,
    CONSTRAINT fk_gestiones_contrato FOREIGN KEY (contrato_id)      REFERENCES contratos(id),
    CONSTRAINT fk_gestiones_motivo   FOREIGN KEY (motivo_gestion_id) REFERENCES motivos_gestion(id)
);

-- Gestión 2 queda con invalidated_at (venció su ventana de validez sin
-- seguimiento) — sigue visible en el historial, solo deja de ser "accionable".
INSERT INTO gestiones (id, contrato_id, usuario_id, motivo_gestion_id, observacion, invalidated_at) VALUES
(1, 1, 10, 1, 'Cliente confirma pago para el día 15', NULL),
(2, 2, 11, 2, 'No contesta, se reintentará mañana', '2026-02-25 00:00:00'),
(3, 3, 12, 3, 'Se reagenda llamada para la próxima semana', NULL),
(4, 4, 13, 4, 'Cliente indica que no puede pagar este mes', NULL);
