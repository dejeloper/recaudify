# Recaudify

Plataforma SaaS de gestión de cobranza y pagos (Laravel + Angular). Migración y reescritura de Católikas Cobranza (CodeIgniter 3).

## Backend (recaudify-api)

### Infraestructura

- [x] Crear estructura inicial del proyecto (Laravel, PHP 8.3+).
- [x] Configurar conexión MySQL y convención de migraciones.
- [x] Configurar autenticación JWT (HS256, TTL 15 min, refresh 4 h).
- [x] Configurar renovación y expiración de tokens (refresh).
- [x] Configurar CORS para el dominio de Vercel.
- [x] Configurar autorización por roles (policies/gates).
- [x] Documentar la API con Swagger/OpenAPI.
- [x] Configurar validación centralizada (Form Requests).
- [x] Configurar soft deletes en entidades de negocio.
- [x] Crear seeders de catálogos base (estados, tipos, roles).
- [ ] Definir formato estándar de respuestas API (recursos, paginación, errores).
- [ ] Soportar paginación en el formato estándar de respuestas API: `data: { items: [], meta: { total, page, perPage, lastPage } }`
- [ ] Configurar auditoría (registro de cambios y acciones críticas).
- [ ] Configurar carga y almacenamiento de archivos.
- [ ] Configurar manejo de montos en enteros/decimales de precisión fija.
- [ ] Configurar scheduler vía cron de cPanel.
- [ ] Configurar cola con driver database + cron.

### Clientes

- [ ] Modelar cliente único (identidad por documento).
- [ ] Soportar múltiples contratos por cliente.
- [ ] Soportar múltiples teléfonos.
- [ ] Soportar múltiples direcciones.
- [ ] Soportar referencias.
- [ ] Crear cliente.
- [ ] Editar cliente.
- [ ] Buscar cliente (documento, nombre, teléfono, contrato).
- [ ] Consultar cliente (ficha 360°).
- [ ] Historial de cliente.
- [ ] Log de actividad por cliente.
- [ ] Detectar duplicados.
- [ ] Fusionar duplicados.
- [ ] Asignar estado de cliente (catálogo).

### Contratos y Productos

- [ ] Romper la relación 1:1 (cliente → muchos contratos).
- [ ] Consolidar cartera por cliente.
- [ ] Crear contrato.
- [ ] Consultar contrato.
- [ ] Editar contrato.
- [ ] Cerrar contrato.
- [ ] Cancelar/anular contrato.
- [ ] Asociar descripción de lo financiado.
- [ ] Asignación de productos a clientes.
- [ ] Cambio de tarifa.
- [ ] Cambio de fecha de pago.

### Cartera

- [ ] Generar plan de pagos al crear el contrato.
- [ ] Configurar periodicidad (semanal, quincenal, mensual).
- [ ] Configurar número de cuotas.
- [ ] Calcular valor de cuota.
- [ ] Calcular saldo pendiente por contrato.
- [ ] Calcular saldo total por cliente.
- [ ] Recalcular saldos al registrar/anular pagos.

### Cobranza

- [ ] Registrar gestión: llamada.
- [ ] Registrar gestión: visita.
- [ ] Registrar acuerdo de pago.
- [ ] Registrar promesa de pago (con fecha comprometida).
- [ ] Registrar observación.
- [ ] Gestionar tipos de gestión (catálogo).
- [ ] Gestionar resultados de gestión (catálogo).
- [ ] Consultar historial por cliente.
- [ ] Consultar historial por contrato.
- [ ] Consultar historial por usuario.
- [ ] Clasificar estado de cobranza por mora: al día.
- [ ] Clasificar estado de cobranza por mora: próximo vencimiento.
- [ ] Clasificar estado de cobranza por mora: mora temprana.
- [ ] Clasificar estado de cobranza por mora: mora avanzada.
- [ ] Clasificar estado de cobranza por mora: prejurídico.
- [ ] Clasificar estado de cobranza por mora: jurídico.
- [ ] Generar bandeja de trabajo del cobrador.
- [ ] Gestión de cobros diarios.
- [ ] Llamadas del día.
- [ ] Rellamar cliente.
- [ ] Hacer seguimiento de promesas (cumplidas/incumplidas).

### Pagos

- [ ] Registrar pago.
- [ ] Registrar abono parcial.
- [ ] Registrar pago total.
- [ ] Permitir pagos menores a la cuota.
- [ ] Permitir pagos mayores a la cuota.
- [ ] Definir regla de aplicación del pago a cuotas.
- [ ] Actualizar saldos automáticamente.
- [ ] Anular/reversar pago (con auditoría).
- [ ] Generar recibo (consecutivo).
- [ ] Adjuntar comprobantes.
- [ ] Consultar historial de pagos por cliente y contrato.
- [ ] Listado de cobros pendientes.

### Devoluciones

- [ ] Registrar devolución.
- [ ] Consultar devoluciones.

### Verificaciones

- [ ] Registrar verificación de cliente/contrato.
- [ ] Gestionar estados de verificación (pendiente, aprobado, rechazado).
- [ ] Asociar evidencias a la verificación.
- [ ] Consultar historial de verificaciones.

### Documentos y Evidencias

- [ ] Cargar contratos escaneados.
- [ ] Cargar fotografías.
- [ ] Cargar comprobantes.
- [ ] Cargar documentos adicionales.
- [ ] Asociar al cliente.
- [ ] Asociar al contrato.
- [ ] Asociar a pagos.
- [ ] Asociar a gestiones.
- [ ] Asociar a verificaciones.
- [ ] Validar tipo y tamaño de archivo.

### Personal

#### Cobradores
- [ ] Crear cobrador.
- [ ] Editar cobrador.
- [ ] Gestión de cobrador.
- [ ] Asignación de clientes a cobrador.

#### Vendedores
- [ ] Crear vendedor.
- [ ] Editar vendedor.
- [ ] Listar vendedores.

### Usuarios y Seguridad

- [x] Crear usuarios.
- [x] Editar usuarios.
- [x] Desactivar usuarios.
- [x] Restaurar usuario desactivado.
- [x] Crear rol administrador.
- [x] Crear rol supervisor.
- [x] Crear rol verificador.
- [x] Crear rol vendedor.
- [x] Crear rol cobrador.
- [x] Crear rol auxiliar.
- [x] Definir permisos por rol sobre cada módulo.
- [ ] Registrar accesos (login/logout).
- [ ] Registrar cambios.
- [ ] Registrar eliminaciones.
- [ ] Registrar acciones críticas.

### Reportes

- [ ] Reporte de cartera / cuentas por cobrar.
- [ ] Reporte contable / resumen financiero.
- [ ] Reporte de ventas por período.

### Importación de Datos

- [ ] Importar clientes desde archivo.
- [ ] Importar pagos desde archivo.

### Configuración (catálogos)

- [ ] Gestionar estados de clientes.
- [ ] Gestionar estados de contratos.
- [ ] Gestionar estados de cobranza.
- [ ] Gestionar estados de verificación.
- [ ] Gestionar tipos de gestión.
- [ ] Gestionar resultados de gestión.
- [ ] Gestionar tipos de documentos.
- [ ] Gestionar tipos de vivienda.
- [ ] Gestionar tipos de pago.
- [ ] Gestionar tarifas.
- [ ] Gestionar parámetros de negocio (consecutivos, días de mora).
- [ ] Gestionar parámetros de cartera (periodicidades).
- [ ] Gestionar eventos del sistema.

### Backup

- [ ] Generar backup de base de datos.
- [ ] Descargar backup.
- [ ] Restaurar backup.

## Frontend (recaudify-web)

### Configuración inicial

- [x] Crear estructura inicial (Angular).
- [x] Configurar entorno para apuntar a la API.
- [x] Crear sistema de autenticación (login, token Bearer, expiración).
- [ ] Crear sistema de navegación.
- [ ] Crear sistema de permisos por rol.
- [x] Crear interceptores HTTP (token, errores, logout).
- [ ] Crear componentes compartidos (tablas, formularios, modales, subida de archivos).
- [ ] Crear diseño responsive.

### Pantallas

- [x] Login.
- [x] Dashboard.
- [ ] Ficha 360° del cliente.
- [ ] Listado / búsqueda de clientes.
- [ ] Crear / editar cliente.
- [ ] Detalle de contrato + plan de pagos.
- [ ] Registro de pagos.
- [ ] Devoluciones.
- [ ] Bandeja de cobranza / registro de gestiones.
- [ ] Llamadas del día.
- [ ] Verificaciones.
- [ ] Carga y visualización de evidencias.
- [ ] Reportes (cartera, contable, ventas).
- [ ] Importación de datos.
- [ ] Administración de usuarios.
- [ ] Administración de catálogos.
- [ ] Backup.

## Despliegue y Operación

- [ ] Desplegar el front en Vercel (build, variables de entorno).
- [ ] Desplegar la API en cPanel (document root a `public/`, `.env`, permisos de `storage/`).
- [ ] Configurar cron de cPanel para el scheduler.
- [ ] Definir estrategia de backups (MySQL + evidencias).
- [ ] Definir estrategia de logs.
- [ ] Definir monitoreo básico.

## Migración de Datos (Católikas → Recaudify)

- [ ] Mapear el modelo viejo (1:1) al nuevo (cliente → contratos).
- [ ] Migrar clientes (con consolidación de duplicados).
- [ ] Migrar contratos (reasociados al cliente correcto).
- [ ] Migrar cuotas / plan de pagos.
- [ ] Migrar pagos.
- [ ] Migrar devoluciones.
- [ ] Migrar evidencias.
- [ ] Migrar usuarios.
- [ ] Validar consistencia (totales).
- [ ] Validar saldos (migrado = calculado).
- [ ] Validar historial completo.
- [ ] Validar que ningún contrato quede huérfano.

## Nuevas tareas

- Use this format to add new tasks

## Actualizado

2026-06-14
