# Guía de Despliegue — Recaudify

Guía completa para replicar el despliegue de Recaudify (monorepo Laravel + Angular) en un VPS nuevo.

---

## Prerequisitos

- VPS con Ubuntu 24.04 LTS, acceso root SSH, IP dedicada
- Dominio con acceso al panel DNS (Hostinger u otro)
- Repositorio GitHub privado
- Windows 11 con Git y PowerShell

---

## 1. Configurar SSH desde Windows

Genera un par de llaves SSH en PowerShell:

```powershell
ssh-keygen -t ed25519 -C "recaudify-deploy"
```

Acepta el path por defecto (`C:\Users\<usuario>\.ssh\id_ed25519`), deja passphrase vacío.

Conéctate al VPS por primera vez:

```powershell
ssh root@<IP-DEL-VPS>
```

Si aparece error de `known_hosts` por cambio de servidor:

```powershell
ssh-keygen -R <IP-DEL-VPS>
ssh root@<IP-DEL-VPS>
```

---

## 2. Preparar el servidor

Actualiza el sistema:

```bash
apt update && apt upgrade -y
```

Instala todo el stack en un solo bloque:

```bash
apt install -y software-properties-common curl git unzip && \
add-apt-repository ppa:ondrej/php -y && \
apt update && \
apt install -y nginx php8.4 php8.4-fpm php8.4-mysql php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-bcmath php8.4-tokenizer mysql-server certbot python3-certbot-nginx && \
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

---

## 3. Crear usuario deploy

```bash
adduser --disabled-password --gecos "" deploy && \
usermod -aG www-data deploy && \
mkdir -p /home/deploy/.ssh && \
chmod 700 /home/deploy/.ssh && \
touch /home/deploy/.ssh/authorized_keys && \
chmod 600 /home/deploy/.ssh/authorized_keys && \
chown -R deploy:deploy /home/deploy/.ssh
```

Agrega tu llave pública de Windows al usuario deploy (ejecuta en Windows para obtenerla):

```powershell
cat C:\Users\<usuario>\.ssh\id_ed25519.pub
```

Pega el output en el VPS:

```bash
echo "<CONTENIDO-DE-TU-LLAVE-PUB>" >> /home/deploy/.ssh/authorized_keys
```

Verifica desde una nueva ventana de PowerShell:

```powershell
ssh deploy@<IP-DEL-VPS>
```

---

## 4. Configurar llave SSH del VPS para GitHub

Genera una llave SSH en el VPS para el usuario deploy:

```bash
sudo -u deploy ssh-keygen -t ed25519 -C "vps-recaudify-deploy" -f /home/deploy/.ssh/github_deploy -N ""
```

Muestra la llave pública y agrégala en GitHub como Deploy Key:

```bash
cat /home/deploy/.ssh/github_deploy.pub
```

Ve a `https://github.com/<usuario>/<repo>/settings/keys` → **Add deploy key** (solo lectura).

Configura SSH para usar esa llave con GitHub:

```bash
cat > /home/deploy/.ssh/config << 'EOF'
Host github.com
  HostName github.com
  User git
  IdentityFile /home/deploy/.ssh/github_deploy
EOF
chown deploy:deploy /home/deploy/.ssh/config
chmod 600 /home/deploy/.ssh/config
```

Verifica la conexión con GitHub:

```bash
sudo -u deploy ssh -T git@github.com
```

---

## 5. Crear directorios y bases de datos

```bash
mkdir -p /var/www/recaudify-staging /var/www/recaudify-prod && \
chown -R deploy:www-data /var/www/recaudify-staging /var/www/recaudify-prod && \
chmod -R 775 /var/www/recaudify-staging /var/www/recaudify-prod
```

Crea las bases de datos en MySQL:

```bash
mysql -u root
```

```sql
CREATE DATABASE recaudify_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE recaudify_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'recaudify_staging'@'localhost' IDENTIFIED BY '<password_staging>';
CREATE USER 'recaudify_prod'@'localhost' IDENTIFIED BY '<password_prod>';

GRANT ALL PRIVILEGES ON recaudify_staging.* TO 'recaudify_staging'@'localhost';
GRANT ALL PRIVILEGES ON recaudify_prod.* TO 'recaudify_prod'@'localhost';

FLUSH PRIVILEGES;
EXIT;
```

---

## 6. Clonar el repositorio

```bash
sudo -u deploy git clone git@github.com:<usuario>/<repo>.git /var/www/recaudify-staging && \
sudo -u deploy git clone git@github.com:<usuario>/<repo>.git /var/www/recaudify-prod
```

Apunta staging a la rama `develop`:

```bash
sudo -u deploy git -C /var/www/recaudify-staging checkout develop && \
sudo -u deploy git -C /var/www/recaudify-staging pull origin develop
```

---

## 7. Instalar dependencias PHP

```bash
cd /var/www/recaudify-staging/recaudify-api && sudo -u deploy composer install --no-dev --optimize-autoloader && \
cd /var/www/recaudify-prod/recaudify-api && sudo -u deploy composer install --no-dev --optimize-autoloader
```

---

## 8. Configurar archivos .env

Copia el `.env.example` como base:

```bash
cp /var/www/recaudify-staging/recaudify-api/.env.example /var/www/recaudify-staging/recaudify-api/.env && \
cp /var/www/recaudify-prod/recaudify-api/.env.example /var/www/recaudify-prod/recaudify-api/.env
```

Edita staging:

```bash
nano /var/www/recaudify-staging/recaudify-api/.env
```

```env
APP_NAME=Recaudify
APP_ENV=staging
APP_DEBUG=true
APP_URL=https://staging-api.recaudify.cloud

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=recaudify_staging
DB_USERNAME=recaudify_staging
DB_PASSWORD=<password_staging>

FRONTEND_URL=https://staging-app.recaudify.cloud
L5_SWAGGER_CONST_HOST=https://staging-api.recaudify.cloud
```

Edita prod:

```bash
nano /var/www/recaudify-prod/recaudify-api/.env
```

```env
APP_NAME=Recaudify
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.recaudify.cloud

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=recaudify_prod
DB_USERNAME=recaudify_prod
DB_PASSWORD=<password_prod>

FRONTEND_URL=https://app.recaudify.cloud
L5_SWAGGER_CONST_HOST=https://api.recaudify.cloud
```

---

## 9. Generar claves y permisos

```bash
cd /var/www/recaudify-staging/recaudify-api && php artisan key:generate && php artisan jwt:secret --force
cd /var/www/recaudify-prod/recaudify-api && php artisan key:generate && php artisan jwt:secret --force
```

Crea los directorios de storage y da permisos:

```bash
for DIR in /var/www/recaudify-staging /var/www/recaudify-prod; do
  mkdir -p $DIR/recaudify-api/storage/framework/views \
            $DIR/recaudify-api/storage/framework/cache \
            $DIR/recaudify-api/storage/framework/sessions \
            $DIR/recaudify-api/storage/logs
  chown -R deploy:www-data $DIR/recaudify-api/storage $DIR/recaudify-api/bootstrap/cache
  chmod -R 775 $DIR/recaudify-api/storage $DIR/recaudify-api/bootstrap/cache
done
```

---

## 10. Correr migraciones y seeders

```bash
cd /var/www/recaudify-staging/recaudify-api && php artisan migrate --seed --force && \
cd /var/www/recaudify-prod/recaudify-api && php artisan migrate --seed --force
```

---

## 11. Configurar DNS

En el panel DNS del dominio agrega estos registros:

| Tipo | Nombre | Apunta a |
|------|--------|----------|
| A | `staging-api` | `<IP-DEL-VPS>` |
| A | `api` | `<IP-DEL-VPS>` |
| CNAME | `staging-app` | `<valor-dado-por-vercel>` |
| CNAME | `app` | `<valor-dado-por-vercel>` |

---

## 12. Configurar Nginx

Crea el virtual host de staging:

```bash
nano /etc/nginx/sites-available/recaudify-staging
```

```nginx
server {
    listen 80;
    server_name staging-api.recaudify.cloud;
    root /var/www/recaudify-staging/recaudify-api/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Crea el virtual host de prod:

```bash
nano /etc/nginx/sites-available/recaudify-prod
```

```nginx
server {
    listen 80;
    server_name api.recaudify.cloud;
    root /var/www/recaudify-prod/recaudify-api/public;

    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activa los sitios y recarga Nginx:

```bash
ln -s /etc/nginx/sites-available/recaudify-staging /etc/nginx/sites-enabled/ && \
ln -s /etc/nginx/sites-available/recaudify-prod /etc/nginx/sites-enabled/ && \
nginx -t && \
systemctl reload nginx
```

---

## 13. Obtener SSL

```bash
certbot --nginx -d staging-api.recaudify.cloud -d api.recaudify.cloud \
  --non-interactive --agree-tos -m <tu-email>
```

---

## 14. Cachear configuración Laravel

```bash
cd /var/www/recaudify-staging/recaudify-api && php artisan config:cache && php artisan route:cache && php artisan view:cache && \
cd /var/www/recaudify-prod/recaudify-api && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## 15. Activar firewall

```bash
ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw --force enable
```

---

## 16. Configurar GitHub Actions

Agrega estos secrets en `https://github.com/<usuario>/<repo>/settings/secrets/actions`:

| Secret | Valor |
|--------|-------|
| `VPS_HOST` | IP del VPS |
| `VPS_USER` | `deploy` |
| `VPS_SSH_KEY` | Contenido de `C:\Users\<usuario>\.ssh\id_ed25519` |
| `VPS_STAGING_PATH` | `/var/www/recaudify-staging` |
| `VPS_PROD_PATH` | `/var/www/recaudify-prod` |

Crea `.github/workflows/deploy-staging.yml`:

```yaml
name: Deploy Staging

on:
  push:
    branches:
      - develop

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to VPS via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd ${{ secrets.VPS_STAGING_PATH }}/recaudify-api
            git pull origin develop
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
```

Crea `.github/workflows/deploy-prod.yml`:

```yaml
name: Deploy Production

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to VPS via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USER }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd ${{ secrets.VPS_PROD_PATH }}/recaudify-api
            git pull origin main
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
```

---

## 17. Configurar Vercel

1. Importa el repositorio en `vercel.com`
2. Vercel detecta el `vercel.json` automáticamente
3. En **Settings → Environment Variables** agrega:

| Variable | Valor | Entorno |
|----------|-------|---------|
| `BUILD_CONFIG` | `prod` | Production |
| `BUILD_CONFIG` | `staging` | Preview |

4. En **Settings → Domains** asigna:
   - `app.recaudify.cloud` → rama `main`
   - `staging-app.recaudify.cloud` → rama `develop`

---

## Flujo de trabajo diario

```
Desarrollo en rama agent
       ↓
   PR → develop
       ↓
GitHub Actions despliega Laravel a staging-api.recaudify.cloud
Vercel despliega Angular a staging-app.recaudify.cloud
       ↓
   Validar en staging
       ↓
   PR → main
       ↓
GitHub Actions despliega Laravel a api.recaudify.cloud
Vercel despliega Angular a app.recaudify.cloud
```
