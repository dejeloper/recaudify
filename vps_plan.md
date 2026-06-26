# VPS Deployment Plan — Recaudify

## Stack

| Capa       | Tecnología          | Hosting       |
| ---------- | ------------------- | ------------- |
| Frontend   | Angular 21          | Vercel (free) |
| Backend    | Laravel 13, PHP 8.3 | VPS propio    |
| Base datos | MySQL               | VPS propio    |

---

## VPS

- **OS:** Ubuntu 24.04 LTS
- **Spec:** 1 vCPU · 4 GB RAM · 50 GB NVMe · IP dedicada · acceso root SSH
- **IP:** 31.97.8.226
- **Proveedor:** Hostinger
- **Acceso local:** Windows 11 (PowerShell)

---

## Arquitectura de entornos

```
VPS (Ubuntu 24.04 LTS)
├── staging-api.recaudify.cloud   → Laravel staging  · DB: recaudify_staging
└── api.recaudify.cloud           → Laravel prod      · DB: recaudify_prod

Vercel
├── staging-app.recaudify.cloud   → Angular staging
└── app.recaudify.cloud           → Angular prod
```

---

## Stack del servidor (sin Docker)

- **Nginx** — reverse proxy + virtual hosts
- **PHP 8.3-FPM** — via `ondrej/php` PPA
- **MySQL 8** — dos bases de datos separadas
- **Certbot** — SSL automático con Let's Encrypt

Se eligió setup manual (sin Docker, sin Dokploy/Coolify) porque:

- Solo hay un proyecto en el VPS
- Angular va a Vercel, no se necesita PaaS
- Menos capas = menos puntos de falla para un demo
- Docker + panel consumiría ~500 MB RAM innecesarios

---

## Ramas → entornos

| Rama      | Rol        | Destino                       |
| --------- | ---------- | ----------------------------- |
| `local`   | Desarrollo | _(local, no despliega)_       |
| `develop` | Staging    | `staging-api.recaudify.cloud` |
| `main`    | Producción | `api.recaudify.cloud`         |

**Flujo:** trabajar en `local` → PR a `develop` → validar en staging → PR a `main` → prod

---

## CI/CD — GitHub Actions

**Backend (Laravel → VPS vía SSH):**

- Push a `develop` → deploy automático a staging
- Push a `main` → deploy automático a prod
- Pasos: `git pull` → `composer install --no-dev` → `php artisan migrate --force` → `php artisan config:cache`

**Frontend (Angular → Vercel):**

- Vercel tiene integración nativa con GitHub, no requiere Actions
- Variable de entorno `VITE_API_URL` configurada por entorno en el dashboard de Vercel

---

## Secrets requeridos en GitHub

| Secret             | Descripción                    |
| ------------------ | ------------------------------ |
| `VPS_HOST`         | IP del VPS (31.97.8.226)       |
| `VPS_USER`         | `deploy`                       |
| `VPS_SSH_KEY`      | Llave privada SSH (id_ed25519) |
| `VPS_STAGING_PATH` | `/var/www/recaudify-staging`   |
| `VPS_PROD_PATH`    | `/var/www/recaudify-prod`      |

---

## Checklist de setup inicial

### VPS (una sola vez)

- [x] Acceso SSH desde Windows confirmado
- [x] Actualizar sistema (`apt update && apt upgrade`)
- [x] Instalar Nginx, PHP 8.3-FPM, MySQL, Certbot, Composer, Git
- [x] Crear usuario `deploy` sin contraseña con clave SSH (evitar usar root en Actions)
- [x] Crear directorios `/var/www/recaudify-staging` y `/var/www/recaudify-prod`
- [x] Clonar repositorio en ambos directorios
- [x] Configurar `.env` de staging y `.env` de prod (a mano, una sola vez)
- [x] Configurar virtual hosts en Nginx para ambos dominios
- [x] Obtener SSL con Certbot para ambos subdominios
- [x] Dar permisos correctos a `storage/` y `bootstrap/cache/`
- [x] Configurar MySQL: crear usuarios y bases de datos para staging y prod
- [x] Activar firewall UFW (puertos 22, 80, 443)

### GitHub

- [x] Agregar los 5 secrets al repositorio
- [x] Crear workflow `deploy-staging.yml` (rama `develop`)
- [x] Crear workflow `deploy-prod.yml` (rama `main`)
- [x] Verificar deploy automático a prod funcionando
- [x] Verificar deploy automático a staging funcionando

### Vercel

- [x] Conectar repositorio
- [x] Configurar dominios staging-app.recaudify.cloud y app.recaudify.cloud
- [x] Configurar BUILD_CONFIG por entorno (prod/staging)
- [x] Verificar build del proyecto Angular en staging y prod

---

## Dominios

| Entorno | Backend                       | Frontend                      |
| ------- | ----------------------------- | ----------------------------- |
| Staging | `staging-api.recaudify.cloud` | `staging-app.recaudify.cloud` |
| Prod    | `api.recaudify.cloud`         | `app.recaudify.cloud`         |

---

## Comandos útiles en el VPS

### Como `root`

```bash
# Staging
cd /var/www/recaudify-staging
sudo -u deploy php artisan migrate --force
sudo -u deploy php artisan l5-swagger:generate
sudo -u deploy php artisan config:cache

# Prod
cd /var/www/recaudify-prod
sudo -u deploy php artisan migrate --force
sudo -u deploy php artisan l5-swagger:generate
sudo -u deploy php artisan config:cache
```

### Como `deploy`

```bash
# Migración fresca con seeders — staging
cd /var/www/recaudify-staging/recaudify-api && git pull origin develop && php artisan migrate:fresh --seed --force

# Migración fresca con seeders — prod
cd /var/www/recaudify-prod/recaudify-api && git pull origin main && php artisan migrate:fresh --seed --force

# Swagger + config cache — staging
cd /var/www/recaudify-staging/recaudify-api && php artisan l5-swagger:generate && php artisan config:cache

# Swagger + config cache — prod
cd /var/www/recaudify-prod/recaudify-api && php artisan l5-swagger:generate && php artisan config:cache
```
