#!/usr/bin/env bash
# ============================================
# APIDIAN - Entrypoint para despliegue Dokploy
# Automatiza lo que hace install.sh:
#   .env, APP_KEY, composer install, firma DIAN,
#   migraciones, seed, storage:link y permisos
# ============================================
set -e

cd /var/www/html

echo "=============================================="
echo "  APIDIAN - Inicializando contenedor PHP"
echo "=============================================="

# --------------------------------------------
# 1. Directorios de escritura requeridos
# --------------------------------------------
mkdir -p \
    storage/app/certificates \
    storage/app/public \
    storage/app/xml \
    storage/app/zip \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# --------------------------------------------
# 1b. Compartir estáticos con nginx (volumen /app_public)
# --------------------------------------------
if [ -d public ] && [ -d /app_public ]; then
    echo "==> Copiando archivos estáticos a /app_public"
    cp -a public/. /app_public/ 2>/dev/null || true
fi

# --------------------------------------------
# 2. Restaurar estructura de storage (storage.zip)
# --------------------------------------------
if [ -f storage.zip ] && [ ! -d storage/fonts ]; then
    echo "==> Extrayendo storage.zip"
    unzip -o storage.zip >/dev/null 2>&1 || true
fi

# --------------------------------------------
# 3. Generar archivo .env si no existe
# --------------------------------------------
if [ ! -f .env ]; then
    echo "==> Generando .env"
    cat > .env <<EOF
APP_NAME="${APP_NAME:-APIDIAN}"
APP_VERSION="${APP_VERSION:- v2.1}"
APP_ENV=${APP_ENV:-production}
APP_KEY=
APP_DEBUG=${APP_DEBUG:-false}
APP_PORT=${APP_PORT:-80}
APP_URL=${APP_URL:-http://localhost}
FORCE_HTTPS=${FORCE_HTTPS:-false}

LOG_CHANNEL=${LOG_CHANNEL:-stack}

DB_CONNECTION=${DB_CONNECTION:-mysql}
DB_HOST=${DB_HOST:-mariadb}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-apidian}
DB_USERNAME=${DB_USERNAME:-apidian}
DB_PASSWORD=${DB_PASSWORD}

BROADCAST_DRIVER=${BROADCAST_DRIVER:-log}
CACHE_DRIVER=${CACHE_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
SESSION_DRIVER=${SESSION_DRIVER:-file}
SESSION_LIFETIME=${SESSION_LIFETIME:-120}

REDIS_HOST=${REDIS_HOST:-redis}
REDIS_PASSWORD=${REDIS_PASSWORD:-null}
REDIS_PORT=${REDIS_PORT:-6379}

MAIL_DRIVER=${MAIL_DRIVER:-smtp}
MAIL_HOST=${MAIL_HOST:-smtp.mailtrap.io}
MAIL_PORT=${MAIL_PORT:-2525}
MAIL_USERNAME=${MAIL_USERNAME:-null}
MAIL_PASSWORD=${MAIL_PASSWORD:-null}
MAIL_ENCRYPTION=${MAIL_ENCRYPTION:-null}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-null}
MAIL_FROM_NAME=${MAIL_FROM_NAME:-}

AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID:-}
AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY:-}
AWS_DEFAULT_REGION=${AWS_DEFAULT_REGION:-us-east-1}
AWS_BUCKET=${AWS_BUCKET:-}

PUSHER_APP_ID=${PUSHER_APP_ID:-}
PUSHER_APP_KEY=${PUSHER_APP_KEY:-}
PUSHER_APP_SECRET=${PUSHER_APP_SECRET:-}
PUSHER_APP_CLUSTER=${PUSHER_APP_CLUSTER:-mt1}

MIX_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
MIX_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

ALLOW_PUBLIC_DOWNLOAD=${ALLOW_PUBLIC_DOWNLOAD:-true}
APPLY_SEND_CUSTORMER_CREDENTIALS=${APPLY_SEND_CUSTORMER_CREDENTIALS:-true}
GRAPHIC_REPRESENTATION_TEMPLATE=${GRAPHIC_REPRESENTATION_TEMPLATE:-2}
ALLOW_PUBLIC_REGISTER=${ALLOW_PUBLIC_REGISTER:-true}
VALIDATE_BEFORE_SENDING=${VALIDATE_BEFORE_SENDING:-true}
EOF
fi

# --------------------------------------------
# 4. APP_KEY (sin depender de artisan/vendor)
# --------------------------------------------
if [ -n "${APP_KEY:-}" ]; then
    if grep -qE '^APP_KEY=' .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
fi

if ! grep -qE '^APP_KEY=base64:' .env; then
    echo "==> Generando APP_KEY"
    NEW_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    if grep -qE '^APP_KEY=' .env; then
        sed -i "s|^APP_KEY=.*|APP_KEY=${NEW_KEY}|" .env
    else
        echo "APP_KEY=${NEW_KEY}" >> .env
    fi
fi

# Exportar la clave real para PHP-FPM. Si el contenedor trae APP_KEY vacío,
# Laravel (Dotenv inmutable) ignoraría el valor de .env y fallaría con
# "No application encryption key has been specified".
export APP_KEY="$(grep -E '^APP_KEY=' .env | head -1 | cut -d= -f2-)"

# --------------------------------------------
# 5. Dependencias de Composer
# --------------------------------------------
# Se considera vendor completo si existen el autoloader y un archivo de control
# (si falta cualquiera, el vendor quedó a medias y hay que reinstalar limpio).
if [ -f vendor/autoload.php ] && [ -f vendor/vlucas/phpdotenv/src/Exception/InvalidPathException.php ]; then
    echo "==> Dependencias ya instaladas"
else
    echo "==> Instalando dependencias con Composer (puede tardar varios minutos)"
    export COMPOSER_ALLOW_SUPERUSER=1
    export COMPOSER_PROCESS_TIMEOUT=900
    mkdir -p /root/.composer
    echo '{"config":{"platform-check":false,"allow-plugins":{"*":true}}}' > /root/.composer/config.json
    composer config --global platform-check false || true

    # Instalación limpia: se elimina cualquier vendor incompleto previo
    rm -rf vendor

    COMPOSER_OK=0
    for i in 1 2 3; do
        if composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction; then
            COMPOSER_OK=1
            echo "==> Dependencias instaladas"
            break
        fi
        echo "==> Composer falló (intento $i/3), reintentando en 10s..."
        sleep 10
    done

    # Fallback: si el composer.lock está desactualizado, se regenera
    if [ "$COMPOSER_OK" != "1" ]; then
        echo "==> Reintentando sin composer.lock..."
        rm -f composer.lock
        if composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction; then
            COMPOSER_OK=1
        fi
    fi

    if [ "$COMPOSER_OK" != "1" ] || [ ! -f vendor/autoload.php ]; then
        echo "!!! ERROR CRÍTICO: no se pudieron instalar las dependencias de Composer"
        exit 1
    fi
fi

# --------------------------------------------
# 6. Archivos de firma DIAN (post composer)
# --------------------------------------------
if [ -d resources/templates/xml/urn ]; then
    echo "==> Configurando archivos de firma DIAN"
    cp -f resources/templates/xml/urn/*.* resources/templates/xml/ 2>/dev/null || true
    if [ -d vendor/ubl21dian/torresoftware/src/XAdES/urn ]; then
        cp -f vendor/ubl21dian/torresoftware/src/XAdES/urn/*.* vendor/ubl21dian/torresoftware/src/XAdES/ 2>/dev/null || true
    fi
    if [ -d vendor/stenfrank/ubl21dian/src/XAdES/urn ]; then
        cp -f vendor/stenfrank/ubl21dian/src/XAdES/urn/*.* vendor/stenfrank/ubl21dian/src/XAdES/ 2>/dev/null || true
    fi
    cp -f resources/templates/xml/urn/Request.php vendor/laravel/framework/src/Illuminate/Http/Request.php 2>/dev/null || true
fi

# --------------------------------------------
# 7. Permisos
# --------------------------------------------
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 777 storage bootstrap/cache 2>/dev/null || true
touch storage/logs/laravel.log 2>/dev/null || true
chown www-data:www-data storage/logs/laravel.log 2>/dev/null || true
mkdir -p vendor/mpdf/mpdf/tmp 2>/dev/null || true
chmod -R 777 vendor/mpdf 2>/dev/null || true

# --------------------------------------------
# 8. Esperar base de datos y migrar
# --------------------------------------------
echo "==> Esperando conexión a la base de datos..."
DB_READY=0
for i in $(seq 1 30); do
    if php -r '
        $h = getenv("DB_HOST") ?: "mariadb";
        $p = getenv("DB_PORT") ?: "3306";
        $d = getenv("DB_DATABASE") ?: "apidian";
        $u = getenv("DB_USERNAME") ?: "apidian";
        $pw = getenv("DB_PASSWORD");
        try {
            new PDO("mysql:host=$h;port=$p;dbname=$d", $u, $pw);
            exit(0);
        } catch (Throwable $e) {
            exit(1);
        }
    ' >/dev/null 2>&1; then
        DB_READY=1
        break
    fi
    echo "   Base de datos no disponible ($i/30)..."
    sleep 5
done

if [ "$DB_READY" = "1" ]; then
    echo "==> Ejecutando migraciones"
    php artisan migrate --force || true

    if [ ! -f storage/.seeded ]; then
        echo "==> Ejecutando seed inicial (solo una vez)"
        if php artisan db:seed --force; then
            touch storage/.seeded
        fi
    fi
else
    echo "!!! ADVERTENCIA: base de datos no disponible, se omite migración"
fi

# --------------------------------------------
# 8b. Usuario administrador inicial (login web)
# --------------------------------------------
export ADMIN_EMAIL="${ADMIN_EMAIL:-admin@gmail.com}"
export ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin123}"

if [ "$DB_READY" = "1" ]; then
    echo "==> Asegurando usuario administrador ${ADMIN_EMAIL}"
    php -r '
        require "vendor/autoload.php";
        $app = require "bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        $email = getenv("ADMIN_EMAIL");
        $pass  = getenv("ADMIN_PASSWORD");
        if (!App\User::where("email", $email)->exists()) {
            App\User::create([
                "name" => "Administrador",
                "email" => $email,
                "password" => bcrypt($pass),
            ]);
            echo "Usuario administrador creado\n";
        } else {
            echo "El usuario administrador ya existe\n";
        }
    ' || true
fi

# --------------------------------------------
# 9. Optimización Laravel
# --------------------------------------------
echo "==> Optimizando Laravel"
php artisan storage:link 2>/dev/null || true

# Enlace de storage en el volumen compartido con nginx
if [ -d /app_public ]; then
    ln -sfn /var/www/html/storage/app/public /app_public/storage 2>/dev/null || true
fi

php artisan config:cache 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 777 storage bootstrap/cache 2>/dev/null || true

echo "==> APIDIAN listo. Iniciando PHP-FPM"
exec docker-php-entrypoint php-fpm
