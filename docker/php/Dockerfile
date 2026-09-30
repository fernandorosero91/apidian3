# ============================================
# DOCKERFILE PHP 7.3 - OPTIMIZADO PARA MÁXIMO RENDIMIENTO
# Compatible con DIAN Colombia - Sin problemas de Composer
# ============================================
FROM php:7.3-fpm

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

WORKDIR /var/www/html
