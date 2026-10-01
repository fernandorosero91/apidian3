# ============================================
# DOCKERFILE PHP 7.3 - OPTIMIZADO PARA MÁXIMO RENDIMIENTO
# Compatible con DIAN Colombia - Sin problemas de Composer
# ============================================
FROM php:7.3-fpm

# Debian bullseye está EOL: sus repos fueron movidos a archive.debian.org.
# bullseye-security ya no existe (ni en security.debian.org ni en el archivo),
# el repo principal de archivo ya incluye las actualizaciones finales.
RUN printf '%s\n' \
        'deb http://archive.debian.org/debian bullseye main' \
        'deb http://archive.debian.org/debian bullseye-updates main' \
        > /etc/apt/sources.list \
    && rm -f /etc/apt/sources.list.d/*.list /etc/apt/sources.list.d/*.sources \
    && printf 'Acquire::Check-Valid-Until "false";\nAcquire::AllowInsecureRepositories "true";\n' > /etc/apt/apt.conf.d/99archive

# Instalar dependencias del sistema (EXACTAS según guía DIAN)
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
    && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensiones PHP (EXACTAS según guía original)
# mbstring, soap, zip, mysql, curl, gd, xml, intl, imap, imagick
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

# Instalar imagick via PECL (versión específica compatible con PHP 7.3)
RUN pecl install imagick-3.4.4 && docker-php-ext-enable imagick

# Instalar Composer 2.2 (versión específica compatible con PHP 7.3)
# Esta versión NO tiene comandos de auditoría problemáticos
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# Configurar Composer para evitar problemas de plataforma
RUN composer config --global platform-check false

# Configurar PHP para rendimiento (según guía original DIAN)
RUN echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=1024M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_input_vars=3000" >> /usr/local/etc/php/conf.d/custom.ini

# Configurar OPcache para máximo rendimiento
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=512" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=64" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/opcache.ini

# Configurar realpath cache (CRÍTICO para rendimiento en Docker)
RUN echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/custom.ini

# Optimizaciones adicionales de PHP-FPM para máximo rendimiento
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_children = 50" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.start_servers = 10" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.min_spare_servers = 5" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_spare_servers = 15" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.max_requests = 1000" >> /usr/local/etc/php-fpm.d/zz-performance.conf \
    && echo "pm.process_idle_timeout = 60s" >> /usr/local/etc/php-fpm.d/zz-performance.conf

# Configurar timezone para Colombia
RUN echo "date.timezone = America/Bogota" >> /usr/local/etc/php/conf.d/custom.ini

# No limpiar variables de entorno (necesario para Traefik/Dokploy)
RUN echo "clear_env = no" >> /usr/local/etc/php-fpm.d/zz-performance.conf

# Entrypoint que automatiza el despliegue (.env, migraciones, seed, admin)
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# Código de la aplicación dentro de la imagen
COPY . /var/www/html

# Instalar dependencias en el build: el vendor queda completo y fijo en la imagen
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_PROCESS_TIMEOUT=900
RUN mkdir -p /root/.composer \
    && printf '%s' '{"config":{"platform-check":false,"allow-plugins":{"*":true}}}' > /root/.composer/config.json \
    && (composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction \
        || (rm -f composer.lock && composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction))

# Archivos de firma DIAN
RUN cp -f resources/templates/xml/urn/*.* resources/templates/xml/ 2>/dev/null || true
RUN if [ -d vendor/ubl21dian/torresoftware/src/XAdES/urn ]; then cp -f vendor/ubl21dian/torresoftware/src/XAdES/urn/*.* vendor/ubl21dian/torresoftware/src/XAdES/ 2>/dev/null || true; fi
RUN cp -f resources/templates/xml/urn/Request.php vendor/laravel/framework/src/Illuminate/Http/Request.php 2>/dev/null || true

# Estructura de storage + permisos
RUN mkdir -p storage/app/certificates storage/app/public storage/app/xml storage/app/zip \
        storage/framework/cache/data storage/framework/sessions storage/framework/views \
        storage/logs bootstrap/cache \
    && (unzip -o storage.zip >/dev/null 2>&1 || true) \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
