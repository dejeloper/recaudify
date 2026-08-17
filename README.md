# Recaudify

Plataforma SaaS de gestión de cobranza y pagos para empresas de crédito y finanzas. Reescritura moderna de "Católikas Cobranza" (legacy en CodeIgniter 3), construida como monorepo con Laravel en el backend y Angular en el frontend. Administra el ciclo de vida completo de una cartera de créditos: clientes, contratos, productos, cobradores, vendedores, pagos, devoluciones, gestiones de cobranza, mora y reportes financieros.

## Comenzando 🚀

Estas instrucciones te permitirán obtener una copia del proyecto en funcionamiento en tu máquina local para propósitos de desarrollo y pruebas.

Mira **Despliegue** para conocer como desplegar el proyecto.

### Pre-requisitos 📋

Necesitas tener instalado en tu máquina:

- PHP 8.3 o superior
- Composer
- Node.js 20+ y pnpm
- MySQL 8+
- Git

### Instalación 🔧

Clona el repositorio:

```bash
git clone https://github.com/dejeloper/recaudify.git
cd Recaudify
```

Instala el backend (API):

```bash
cd recaudify-api
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Instala el frontend (Web):

```bash
cd ../recaudify-web
pnpm install
```

Configura la URL de la API en `src/environments/environment.ts` → `apiUrl` apuntando a tu backend (por defecto `http://localhost:8000/api`).

Inicia ambos servidores en terminales separadas:

```bash
# Terminal 1 — Backend
cd recaudify-api
composer run dev

# Terminal 2 — Frontend
cd recaudify-web
pnpm start
```

El frontend estará disponible en `http://localhost:4200` y el backend en `http://localhost:8000`.

### Credenciales de prueba

Tras ejecutar `php artisan migrate --seed`, se crea un usuario administrador por defecto. Revisa los seeders en `database/seeders/` para conocer las credenciales iniciales.

## Ejecutando las pruebas ⚙️

### Pruebas del backend (PHPUnit)

```bash
cd recaudify-api
php artisan test
```

Para ejecutar un test específico:

```bash
php artisan test --filter=NombreDelTest
```

### Pruebas del frontend (Vitest)

```bash
cd recaudify-web
pnpm test
```

### Análisis de estilo de código ⌨️

**Backend — Laravel Pint:**

```bash
cd recaudify-api
vendor/bin/pint
```

Verifica y corrige automáticamente el estilo de código PHP siguiendo las convenciones de Laravel.

**Frontend — Prettier:**

```bash
cd recaudify-web
pnpm prettier --write .
```

Formatea el código TypeScript/HTML/CSS del frontend según la configuración del proyecto.

## Despliegue 📦

Guía completa de despliegue en VPS disponible en `vps_deploy_guide.md`. Cubre configuración de servidor (Ubuntu + Nginx + MySQL + PHP-FPM), usuario deploy, SSL con Let's Encrypt, y despliegue continuo desde GitHub.

## Construido con 🛠️

**Backend:**

* [Laravel 13](https://laravel.com/) — Framework PHP
* [JWT Auth](https://github.com/php-open-source-saver/jwt-auth) — Autenticación stateless
* [Spatie Laravel Permission](https://spatie.com/laravel-permission) — Roles y permisos
* [Spatie Activity Log](https://spatie.com/laravel-activitylog) — Auditoría inmutable
* [L5-Swagger](https://github.com/DarkaOnLine/L5-Swagger) — Documentación OpenAPI
* [PHPUnit](https://phpunit.de/) — Pruebas unitarias y de integración

**Frontend:**

* [Angular 21](https://angular.dev/) — Framework SPA
* [Tailwind CSS 4](https://tailwindcss.com/) — Utilidades de diseño
* [pnpm](https://pnpm.io/) — Gestor de paquetes
* [Vitest](https://vitest.dev/) — Pruebas unitarias
* [TypeScript 6](https://www.typescriptlanglang.org/) — Tipado estático

## Licencia 📄

Este proyecto está bajo la Licencia MIT — mira el archivo [LICENSE.md](LICENSE.md) para detalles.
