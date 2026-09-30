#!/bin/bash

# ============================================
# APIDIAN - Script de Instalación Docker
# Facturación Electrónica DIAN Colombia
# Compatible con Laravel 5.8 / PHP 7.4
# ============================================
# Uso: chmod +x install.sh && sudo ./install.sh
# ============================================

set -e

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================"
echo "   APIDIAN - Instalación Docker"
echo "   Facturación Electrónica DIAN Colombia"
echo "============================================"
echo -e "${NC}"

# Verificar root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}Error: Ejecutar como root (sudo ./install.sh)${NC}"
    exit 1
fi

# Directorio actual
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Generar contraseñas seguras
DB_PASSWORD=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)
DB_ROOT_PASSWORD=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)

# Configuración
echo -e "${YELLOW}Configuración:${NC}"
read -p "Dominio (ej: apidian.clipers.pro) [localhost]: " DOMAIN
DOMAIN=${DOMAIN:-localhost}

read -p "Email para certificado SSL (requerido si usa dominio): " SSL_EMAIL

read -p "Puerto MySQL externo [3306]: " MYSQL_PORT
MYSQL_PORT=${MYSQL_PORT:-3306}

# ============================================
# DETECTAR SI PUERTO 80 ESTÁ EN USO
# ============================================
HTTP_PORT=80
HTTPS_PORT=443
USE_PROXY=false

if ss -tuln | grep -q ':80 '; then
    echo ""
    echo -e "${YELLOW}⚠ Puerto 80 está en uso por otro servicio.${NC}"
    echo ""
    echo "Opciones:"
    echo "  1) Usar puerto 8081 (configurar proxy manualmente después)"
    echo "  2) Liberar puerto 80 (detener el servicio que lo usa)"
    echo ""
    read -p "Selecciona opción [1]: " port_option
    port_option=${port_option:-1}
    
    if [ "$port_option" = "1" ]; then
        HTTP_PORT=8081
        HTTPS_PORT=8443
        USE_PROXY=true
        echo -e "${GREEN}✓ APIDIAN usará puerto ${HTTP_PORT}${NC}"
    else
        echo -e "${YELLOW}Intentando liberar puerto 80...${NC}"
        # Intentar detener contenedores Docker que usan puerto 80
        for container in $(docker ps -q 2>/dev/null); do
            if docker port "$container" 2>/dev/null | grep -q "0.0.0.0:80"; then
                container_name=$(docker inspect --format '{{.Name}}' "$container" | sed 's/\///')
                echo "Deteniendo contenedor: $container_name"
                docker stop "$container"
            fi
        done
        sleep 2
        
        # Verificar si se liberó
        if ss -tuln | grep -q ':80 '; then
            echo -e "${RED}No se pudo liberar puerto 80. Usando puerto 8081.${NC}"
            HTTP_PORT=8081
            HTTPS_PORT=8443
            USE_PROXY=true
        else
            echo -e "${GREEN}✓ Puerto 80 liberado${NC}"
        fi
    fi
fi

# Determinar si usar SSL (solo posible con puerto 80)
USE_SSL=false
if [[ "$DOMAIN" != "localhost" && -n "$SSL_EMAIL" && "$HTTP_PORT" = "80" ]]; then
    USE_SSL=true
elif [[ "$DOMAIN" != "localhost" && -n "$SSL_EMAIL" && "$HTTP_PORT" != "80" ]]; then
    echo -e "${YELLOW}⚠ SSL automático no disponible en puerto ${HTTP_PORT}. Configúralo en tu proxy.${NC}"
fi

echo ""
echo -e "${GREEN}Configuración seleccionada:${NC}"
echo "  Dominio: $DOMAIN"
echo "  SSL: $([ "$USE_SSL" = true ] && echo "Sí (Let's Encrypt)" || echo "No")"
echo "  Puerto HTTP: $HTTP_PORT"
echo "  Puerto HTTPS: $HTTPS_PORT"
echo "  Puerto MySQL: $MYSQL_PORT"
if [ "$USE_PROXY" = true ]; then
    echo ""
    echo -e "${YELLOW}  NOTA: Después de instalar, configura tu proxy para redirigir${NC}"
    echo -e "${YELLOW}        ${DOMAIN} -> http://127.0.0.1:${HTTP_PORT}${NC}"
fi
echo ""

read -p "¿Continuar? (s/n): " confirm
[[ "$confirm" != "s" && "$confirm" != "S" ]] && exit 0

# ============================================
# 1. INSTALAR DOCKER Y DEPENDENCIAS
# ============================================
echo -e "${YELLOW}[1/9] Verificando Docker y dependencias...${NC}"

# Configurar timezone de Colombia (requerido para firmas DIAN)
echo "Configurando timezone America/Bogota..."
timedatectl set-timezone America/Bogota 2>/dev/null || ln -sf /usr/share/zoneinfo/America/Bogota /etc/localtime
echo -e "${GREEN}✓ Timezone configurado: America/Bogota${NC}"

# Instalar unzip si no existe
if ! command -v unzip &> /dev/null; then
    apt-get update
    apt-get install -y unzip
fi

if ! command -v docker &> /dev/null; then
    echo "Instalando Docker..."
    apt-get update
    apt-get install -y apt-transport-https ca-certificates curl gnupg lsb-release
    
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
    
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
    
    apt-get update
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    
    systemctl enable docker
    systemctl start docker
fi
echo -e "${GREEN}✓ Docker instalado${NC}"

# ============================================
# 2. CREAR DOCKERFILE PHP 7.3
# ============================================
echo -e "${YELLOW}[2/9] Creando Dockerfile PHP 7.3...${NC}"

mkdir -p docker/php docker/nginx docker/mariadb

# Crear configuración optimizada de MariaDB
cat > docker/mariadb/my.cnf << 'MARIADBCONF'
# ============================================
# MARIADB OPTIMIZADO PARA MÁXIMO RENDIMIENTO
# ============================================

[mysqld]
user = mysql
pid-file = /var/run/mysqld/mysqld.pid
socket = /var/run/mysqld/mysqld.sock
port = 3306
basedir = /usr
datadir = /var/lib/mysql
tmpdir = /tmp
lc-messages-dir = /usr/share/mysql

character-set-server = utf8
collation-server = utf8_spanish_ci
init-connect = 'SET NAMES utf8'

innodb_buffer_pool_size = 512M
innodb_log_file_size = 128M
innodb_log_buffer_size = 32M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_open_files = 400
innodb_io_capacity = 400
innodb_read_io_threads = 8
innodb_write_io_threads = 8
innodb_thread_concurrency = 16

query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

max_connections = 200
thread_cache_size = 50
table_open_cache = 4000

sort_buffer_size = 4M
read_buffer_size = 2M
join_buffer_size = 8M
tmp_table_size = 64M
max_heap_table_size = 64M

wait_timeout = 600
interactive_timeout = 600

skip-name-resolve
skip-external-locking
max_allowed_packet = 64M

[mysql]
default-character-set = utf8

[client]
default-character-set = utf8
MARIADBCONF

cat > docker/php/Dockerfile << 'DOCKERFILE'
FROM php:7.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libicu-dev \
    libc-client-dev \
    libkrb5-dev \
    zip \
    unzip \
    libssl-dev \
    libmagickwand-dev \
    imagemagick \
    && rm -r /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        soap \
        xml \
        intl \
        imap \
        opcache

RUN pecl install imagick-3.4.4 && docker-php-ext-enable imagick

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini

RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

RUN echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone=America/Bogota" >> /usr/local/etc/php/conf.d/custom.ini

# Configurar timezone del sistema
ENV TZ=America/Bogota
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_children = 20" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.start_servers = 4" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.min_spare_servers = 2" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_spare_servers = 6" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-performance.conf

WORKDIR /var/www/html
DOCKERFILE

echo -e "${GREEN}✓ Dockerfile creado${NC}"

# ============================================
# 3. CREAR CONFIGURACIÓN NGINX
# ============================================
echo -e "${YELLOW}[3/9] Creando configuración Nginx...${NC}"

mkdir -p docker/nginx/sites-available

if [ "$USE_SSL" = true ]; then
    cat > docker/nginx/sites-available/default.conf << NGINXCONF
server {
    listen 80;
    server_name ${DOMAIN};

    location /health {
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    location /.well-known/acme-challenge/ {
        root /var/www/certbot;
    }

    location / {
        return 301 https://\$host\$request_uri;
    }
}

server {
    listen 443 ssl;
    server_name ${DOMAIN};
    root /var/www/html/public;
    index index.php index.html;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;

    client_max_body_size 100M;

    location /health {
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINXCONF
else
    cat > docker/nginx/sites-available/default.conf << NGINXCONF
server {
    listen 80;
    server_name ${DOMAIN} localhost;
    root /var/www/html/public;
    index index.php index.html;

    client_max_body_size 100M;

    location /health {
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        try_files \$uri =404;
        fastcgi_pass php:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINXCONF
fi

echo -e "${GREEN}✓ Nginx configurado${NC}"

# ============================================
# 4. ACTUALIZAR DOCKER-COMPOSE CON PUERTO
# ============================================
echo -e "${YELLOW}[4/9] Configurando docker-compose.yml...${NC}"

if [ ! -f "docker-compose.yml" ]; then
    echo -e "${RED}Error: docker-compose.yml no encontrado${NC}"
    exit 1
fi

# Actualizar puerto en docker-compose.yml
sed -i "s/\${HTTP_PORT:-80}/${HTTP_PORT}/g" docker-compose.yml
sed -i "s/\${HTTP_PORT:-8081}/${HTTP_PORT}/g" docker-compose.yml

echo -e "${GREEN}✓ docker-compose.yml configurado (puerto ${HTTP_PORT})${NC}"

# ============================================
# 5. CREAR ARCHIVO .ENV
# ============================================
echo -e "${YELLOW}[5/9] Creando archivo .env...${NC}"

APP_KEY="base64:$(openssl rand -base64 32)"
SERVER_IP=$(curl -s ifconfig.me 2>/dev/null || hostname -I | awk '{print $1}')

if [ "$USE_SSL" = true ]; then
    APP_URL="https://${DOMAIN}"
elif [ "$HTTP_PORT" = "80" ]; then
    APP_URL="http://${DOMAIN}"
else
    APP_URL="http://${DOMAIN}:${HTTP_PORT}"
fi

cat > .env << ENVFILE
APP_NAME="APIDIAN"
APP_VERSION=" v2.1"
APP_ENV=production
APP_KEY=${APP_KEY}
APP_DEBUG=false
APP_PORT=${HTTP_PORT}
APP_URL=${APP_URL}
FORCE_HTTPS=$([ "$USE_SSL" = true ] && echo "true" || echo "false")

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST=mariadb
DB_PORT=3306
DB_DATABASE=apidian
DB_USERNAME=apidian
DB_PASSWORD=${DB_PASSWORD}

CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=null
MAIL_FROM_NAME=

ALLOW_PUBLIC_DOWNLOAD=true
APPLY_SEND_CUSTORMER_CREDENTIALS=true
GRAPHIC_REPRESENTATION_TEMPLATE=2
ALLOW_PUBLIC_REGISTER=true
VALIDATE_BEFORE_SENDING=true

# Docker Compose vars
MYSQL_USER=apidian
MYSQL_PASSWORD=${DB_PASSWORD}
MYSQL_DATABASE=apidian
MYSQL_ROOT_PASSWORD=${DB_ROOT_PASSWORD}
HTTP_PORT=${HTTP_PORT}

# SSL Configuration
DOMAIN=${DOMAIN}
SSL_EMAIL=${SSL_EMAIL}
USE_SSL=${USE_SSL}
ENVFILE

echo -e "${GREEN}✓ .env creado${NC}"

# ============================================
# 6. CONSTRUIR E INICIAR CONTENEDORES
# ============================================
echo -e "${YELLOW}[6/9] Construyendo contenedores...${NC}"

mkdir -p docker/mariadb/init
cat > docker/mariadb/init/01-init.sql << INITSQL
CREATE DATABASE IF NOT EXISTS apidian CHARACTER SET utf8 COLLATE utf8_spanish_ci;
CREATE USER IF NOT EXISTS 'apidian'@'%' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'apidian'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
CREATE USER IF NOT EXISTS 'apidian'@'172.%' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON apidian.* TO 'apidian'@'%';
GRANT ALL PRIVILEGES ON apidian.* TO 'apidian'@'localhost';
GRANT ALL PRIVILEGES ON apidian.* TO 'apidian'@'172.%';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'%' IDENTIFIED BY '${DB_ROOT_PASSWORD}' WITH GRANT OPTION;
FLUSH PRIVILEGES;
INITSQL

echo "Limpiando instalación anterior..."
docker compose down 2>/dev/null || true
docker volume rm apidian_apidian_mysql_data 2>/dev/null || true

docker compose build --no-cache --parallel

docker compose up -d mariadb redis
echo "Esperando a que MariaDB inicialice (60 segundos)..."
sleep 60

echo "Verificando MariaDB..."
for i in 1 2 3 4 5 6 7 8 9 10; do
    echo "Verificando MariaDB (intento $i/10)..."
    if docker compose exec -T mariadb mysqladmin ping -h localhost --silent 2>/dev/null; then
        echo -e "${GREEN}✓ MariaDB está listo${NC}"
        if docker compose exec -T mariadb mysql -u apidian -p"${DB_PASSWORD}" -e "SELECT 1;" apidian 2>/dev/null; then
            echo -e "${GREEN}✓ Usuario apidian puede conectarse${NC}"
            break
        fi
    fi
    sleep 10
done

docker compose up -d php nginx
sleep 10

echo -e "${GREEN}✓ Contenedores iniciados${NC}"

# ============================================
# 7. CONFIGURAR SSL SI ES NECESARIO
# ============================================
if [ "$USE_SSL" = true ]; then
    echo -e "${YELLOW}[7/9] Configurando SSL con Let's Encrypt...${NC}"
    
    mkdir -p /var/www/certbot
    
    docker compose run --rm certbot certonly --webroot --webroot-path=/var/www/certbot --email ${SSL_EMAIL} --agree-tos --no-eff-email -d ${DOMAIN}
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Certificado SSL obtenido${NC}"
        docker compose restart nginx
        (crontab -l 2>/dev/null | grep -v certbot; echo "0 12 * * * /usr/bin/docker compose -f ${SCRIPT_DIR}/docker-compose.yml run --rm certbot renew --quiet && /usr/bin/docker compose -f ${SCRIPT_DIR}/docker-compose.yml restart nginx") | crontab -
        echo -e "${GREEN}✓ Renovación automática configurada${NC}"
    else
        echo -e "${RED}Error obteniendo certificado SSL. Continuando sin SSL...${NC}"
        USE_SSL=false
    fi
else
    echo -e "${YELLOW}[7/9] Saltando configuración SSL...${NC}"
fi

# ============================================
# 8. INSTALAR DEPENDENCIAS Y CONFIGURAR
# ============================================
echo -e "${YELLOW}[8/9] Instalando dependencias...${NC}"

if [ -f "storage.zip" ]; then
    unzip -o storage.zip
fi

rm -f composer.lock
rm -rf vendor

chmod -R 777 storage bootstrap/cache

echo "Instalando dependencias con Composer..."
docker compose exec -T php mkdir -p /root/.composer
docker compose exec -T php bash -c 'echo "{\"config\":{\"platform-check\":false,\"allow-plugins\":{\"*\":true}}}" > /root/.composer/config.json'

export COMPOSER_PROCESS_TIMEOUT=600

for i in 1 2 3; do
    echo "Intento $i de instalación de dependencias..."
    if docker compose exec -T -e COMPOSER_PROCESS_TIMEOUT=600 php composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction; then
        echo -e "${GREEN}✓ Dependencias instaladas correctamente${NC}"
        break
    else
        echo -e "${YELLOW}⚠ Error en intento $i, reintentando...${NC}"
        if [ $i -eq 3 ]; then
            echo -e "${RED}✗ Error: No se pudieron instalar las dependencias después de 3 intentos${NC}"
            exit 1
        fi
        sleep 10
    fi
done

echo "Ejecutando comandos de firma DIAN..."
docker compose exec -T php bash -c '
    if [ -d "vendor/ubl21dian/torresoftware/src/XAdES/urn" ]; then
        cp resources/templates/xml/urn/*.* resources/templates/xml/ 2>/dev/null || true
        cp vendor/ubl21dian/torresoftware/src/XAdES/urn/*.* vendor/ubl21dian/torresoftware/src/XAdES/ 2>/dev/null || true
        cp resources/templates/xml/urn/Request.php vendor/laravel/framework/src/Illuminate/Http/Request.php 2>/dev/null || true
        echo "Archivos de firma DIAN copiados"
    elif [ -d "vendor/stenfrank/ubl21dian/src/XAdES/urn" ]; then
        cp resources/templates/xml/urn/*.* resources/templates/xml/ 2>/dev/null || true
        cp vendor/stenfrank/ubl21dian/src/XAdES/urn/*.* vendor/stenfrank/ubl21dian/src/XAdES/ 2>/dev/null || true
        cp resources/templates/xml/urn/Request.php vendor/laravel/framework/src/Illuminate/Http/Request.php 2>/dev/null || true
        echo "Archivos de firma DIAN copiados (stenfrank)"
    fi
'

echo "Configurando Laravel..."
docker compose exec -T php php artisan key:generate --force
docker compose exec -T php php artisan config:clear || true
docker compose exec -T php php artisan cache:clear || true
docker compose exec -T php php artisan config:cache || true
docker compose exec -T php php artisan storage:link || true

echo "Verificando conexión a base de datos..."
for i in {1..6}; do
    if docker compose exec -T php php artisan migrate:status > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Conexión a base de datos establecida${NC}"
        break
    fi
    echo "Esperando conexión (intento $i/6)..."
    sleep 10
done

echo "Ejecutando migraciones..."
docker compose exec -T php php artisan migrate --seed --force || echo -e "${YELLOW}⚠ Error en migraciones${NC}"

docker compose exec -T php chmod -R 777 /var/www/html/storage
docker compose exec -T php chmod -R 777 /var/www/html/bootstrap/cache

# Permisos para mPDF (generación de PDFs)
docker compose exec -T php mkdir -p /var/www/html/vendor/mpdf/mpdf/tmp
docker compose exec -T php chmod -R 777 /var/www/html/vendor/mpdf || true

docker compose exec -T php php artisan config:cache
docker compose exec -T php php artisan config:clear
docker compose exec -T php php artisan cache:clear

echo -e "${GREEN}✓ Dependencias instaladas${NC}"

# ============================================
# 9. MOSTRAR INFORMACIÓN
# ============================================
echo -e "${YELLOW}[9/9] Finalizando...${NC}"

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}   ¡INSTALACIÓN COMPLETADA!${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo -e "${BLUE}URL de la API:${NC}"
if [ "$USE_SSL" = true ]; then
    echo "  https://${DOMAIN}"
elif [ "$HTTP_PORT" = "80" ]; then
    echo "  http://${DOMAIN}"
else
    echo "  http://${DOMAIN}:${HTTP_PORT}"
    echo ""
    echo -e "${YELLOW}Para acceder sin puerto, configura tu proxy:${NC}"
    echo "  ${DOMAIN} -> http://127.0.0.1:${HTTP_PORT}"
fi
echo ""
echo -e "${BLUE}Base de datos:${NC}"
echo "  Host: localhost:${MYSQL_PORT}"
echo "  Database: apidian"
echo "  Usuario: apidian"
echo "  Password: ${DB_PASSWORD}"
echo "  Root Password: ${DB_ROOT_PASSWORD}"
echo ""
if [ "$USE_SSL" = true ]; then
    echo -e "${BLUE}SSL:${NC}"
    echo "  Certificado: Let's Encrypt"
    echo "  Dominio: ${DOMAIN}"
    echo "  Renovación: Automática (cron)"
    echo ""
fi
echo -e "${BLUE}Comandos útiles:${NC}"
echo "  Ver logs:     docker compose logs -f"
echo "  Reiniciar:    docker compose restart"
echo "  Detener:      docker compose down"
echo "  Estado:       docker compose ps"
echo ""

cat > CREDENCIALES.txt << CREDS
============================================
CREDENCIALES APIDIAN - $(date)
============================================

URL API: ${APP_URL}

Base de Datos:
  Host: localhost:${MYSQL_PORT} (externo) / mariadb:3306 (interno)
  Database: apidian
  Usuario: apidian
  Password: ${DB_PASSWORD}
  Root Password: ${DB_ROOT_PASSWORD}

$([ "$USE_SSL" = true ] && echo "SSL:
  Certificado: Let's Encrypt
  Dominio: ${DOMAIN}
  Email: ${SSL_EMAIL}")

$([ "$USE_PROXY" = true ] && echo "PROXY:
  Configura tu proxy para redirigir:
  ${DOMAIN} -> http://127.0.0.1:${HTTP_PORT}")

============================================
CREDS
chmod 600 CREDENCIALES.txt

echo -e "${YELLOW}Credenciales guardadas en: CREDENCIALES.txt${NC}"
echo ""

echo -e "${YELLOW}Verificando instalación...${NC}"
sleep 3

docker compose ps

echo ""
echo -e "${GREEN}¡Listo! Verifica con: curl http://127.0.0.1:${HTTP_PORT}/health${NC}"
echo ""
