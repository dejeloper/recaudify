# Recaudify
Sistema de gestión de cobranzas y recaudación — monorepo con backend Laravel y frontend Angular.

## Plan

### Backend (recaudify-api)
#### Infraestructura y configuración
- [ ] Configurar Docker (docker-compose con Laravel, MySQL, Redis)
- [ ] Configurar variables de entorno (.env.example completo)
- [ ] Limpiar carpetas de solo-placeholder en Laravel (gitignore ruido)
- [ ] Instalar y configurar JWT (tymon/jwt-auth o Laravel Sanctum)
- [ ] Instalar y configurar Spatie Permission (roles y permisos)
- [ ] Configurar Redis para cache y sesiones

#### Base de datos
- [ ] Migración: clientes (clients)
- [ ] Migración: deudas (debts)
- [ ] Migración: cobros/pagos (payments)
- [ ] Migración: tabla de roles/permisos (Spatie)
- [ ] Seeders: datos iniciales (roles, usuario admin)

#### Autenticación
- [ ] Endpoint POST /api/auth/login
- [ ] Endpoint POST /api/auth/logout
- [ ] Endpoint POST /api/auth/refresh
- [ ] Endpoint GET /api/auth/me
- [ ] Middleware de autenticación JWT

#### Clientes
- [ ] Modelo Client con relaciones
- [ ] Endpoint GET /api/clients (listar)
- [ ] Endpoint POST /api/clients (crear)
- [ ] Endpoint GET /api/clients/{id} (ver)
- [ ] Endpoint PUT /api/clients/{id} (actualizar)
- [ ] Endpoint DELETE /api/clients/{id} (eliminar)

#### Deudas
- [ ] Modelo Debt con relaciones (client, payments)
- [ ] Endpoint GET /api/debts (listar con filtros)
- [ ] Endpoint POST /api/debts (crear)
- [ ] Endpoint GET /api/debts/{id} (ver)
- [ ] Endpoint PUT /api/debts/{id} (actualizar)
- [ ] Endpoint DELETE /api/debts/{id} (eliminar)

#### Pagos / Cobros
- [ ] Modelo Payment con relaciones (debt, client)
- [ ] Endpoint GET /api/payments (listar)
- [ ] Endpoint POST /api/payments (registrar pago)
- [ ] Endpoint GET /api/payments/{id} (ver)
- [ ] Lógica de actualización de saldo de deuda al registrar pago

#### Roles y permisos
- [ ] Definir roles: admin, cobrador, supervisor
- [ ] Aplicar políticas en controladores

### Frontend (recaudify-web)
#### Configuración inicial
- [ ] Inicializar proyecto Angular
- [ ] Configurar Angular Router
- [ ] Configurar HttpClient con interceptor de JWT
- [ ] Configurar entornos (environment.ts, environment.prod.ts)

#### Autenticación
- [ ] Pantalla de login
- [ ] Guard de rutas protegidas
- [ ] Servicio de autenticación (login, logout, refresh token)

#### Módulo Clientes
- [ ] Listado de clientes con búsqueda y paginación
- [ ] Formulario crear/editar cliente
- [ ] Vista detalle de cliente con sus deudas

#### Módulo Deudas
- [ ] Listado de deudas con filtros (estado, cliente)
- [ ] Formulario crear/editar deuda
- [ ] Vista detalle de deuda con historial de pagos

#### Módulo Pagos
- [ ] Listado de pagos
- [ ] Formulario registrar pago
- [ ] Confirmación y recibo de pago

### Infraestructura general
#### Monorepo
- [ ] Configurar .gitignore raíz del monorepo
- [ ] Documentar setup local en README raíz

## Nuevas tareas
- Use this format to add new tasks

## Actualizado
Actualizado por Jhonatan Guerrero el 2026-06-12 a las 15:00
