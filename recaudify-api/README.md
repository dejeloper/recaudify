# Recaudify API

API REST construida con Laravel para la gestión de cobranzas y recaudación.

## Descripción General

**Recaudify API** es el backend de un sistema de recaudación que permite gestionar cobros, deudas, pagos y clientes. Proporciona una API REST segura con autenticación JWT, control de acceso basado en roles (RBAC) usando Spatie Permission, y está diseñada para escalar en producción con Docker.

## Stack Tecnológico

- **Laravel 13** — Framework PHP
- **MySQL** — Base de datos relacional
- **Redis** — Cache y sesiones
- **JWT** — Autenticación stateless
- **Spatie Permission** — Roles y permisos
- **Docker** — Contenedores para desarrollo y producción

## Estructura del Proyecto

```
app/
├── Http/
│   ├── Controllers/    # Controladores de la API
│   ├── Middleware/      # Middleware de seguridad
│   └── Requests/       # Form requests de validación
├── Models/             # Modelos Eloquent
├── Services/           # Lógica de negocio
└── Providers/          # Service providers
config/                 # Configuración de Laravel
database/
├── migrations/         # Migraciones de base de datos
└── seeders/            # Seeders de datos iniciales
routes/
└── api.php            # Definición de rutas de la API
```

## Estado del Proyecto

En fase de construcción.
