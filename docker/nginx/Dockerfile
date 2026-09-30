# ============================================
# NGINX OPTIMIZADO PARA MÁXIMO RENDIMIENTO
# ============================================
FROM nginx:alpine

# Instalar herramientas adicionales
RUN apk add --no-cache \
    curl \
    wget

# Configuración Nginx optimizada para máximo rendimiento
RUN echo "# NGINX OPTIMIZADO PARA MÁXIMO RENDIMIENTO" > /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "user nginx;" >> /etc/nginx/nginx.conf \
    && echo "worker_processes auto;" >> /etc/nginx/nginx.conf \
    && echo "worker_rlimit_nofile 65535;" >> /etc/nginx/nginx.conf \
    && echo "error_log /var/log/nginx/error.log warn;" >> /etc/nginx/nginx.conf \
    && echo "pid /var/run/nginx.pid;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "events {" >> /etc/nginx/nginx.conf \
    && echo "    worker_connections 4096;" >> /etc/nginx/nginx.conf \
    && echo "    use epoll;" >> /etc/nginx/nginx.conf \
    && echo "    multi_accept on;" >> /etc/nginx/nginx.conf \
    && echo "}" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "http {" >> /etc/nginx/nginx.conf \
    && echo "    include /etc/nginx/mime.types;" >> /etc/nginx/nginx.conf \
    && echo "    default_type application/octet-stream;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Logging optimizado" >> /etc/nginx/nginx.conf \
    && echo "    log_format main '\$remote_addr - \$remote_user [\$time_local] \"\$request\" '" >> /etc/nginx/nginx.conf \
    && echo "                    '\$status \$body_bytes_sent \"\$http_referer\" '" >> /etc/nginx/nginx.conf \
    && echo "                    '\"\$http_user_agent\" \"\$http_x_forwarded_for\" \$request_time';" >> /etc/nginx/nginx.conf \
    && echo "    access_log /var/log/nginx/access.log main;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Optimizaciones de rendimiento" >> /etc/nginx/nginx.conf \
    && echo "    sendfile on;" >> /etc/nginx/nginx.conf \
    && echo "    tcp_nopush on;" >> /etc/nginx/nginx.conf \
    && echo "    tcp_nodelay on;" >> /etc/nginx/nginx.conf \
    && echo "    keepalive_timeout 65;" >> /etc/nginx/nginx.conf \
    && echo "    keepalive_requests 1000;" >> /etc/nginx/nginx.conf \
    && echo "    types_hash_max_size 2048;" >> /etc/nginx/nginx.conf \
    && echo "    server_tokens off;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Buffer sizes optimizados" >> /etc/nginx/nginx.conf \
    && echo "    client_body_buffer_size 128k;" >> /etc/nginx/nginx.conf \
    && echo "    client_max_body_size 100m;" >> /etc/nginx/nginx.conf \
    && echo "    client_header_buffer_size 1k;" >> /etc/nginx/nginx.conf \
    && echo "    large_client_header_buffers 4 4k;" >> /etc/nginx/nginx.conf \
    && echo "    output_buffers 1 32k;" >> /etc/nginx/nginx.conf \
    && echo "    postpone_output 1460;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Timeouts optimizados" >> /etc/nginx/nginx.conf \
    && echo "    client_header_timeout 3m;" >> /etc/nginx/nginx.conf \
    && echo "    client_body_timeout 3m;" >> /etc/nginx/nginx.conf \
    && echo "    send_timeout 3m;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Compresión optimizada" >> /etc/nginx/nginx.conf \
    && echo "    gzip on;" >> /etc/nginx/nginx.conf \
    && echo "    gzip_vary on;" >> /etc/nginx/nginx.conf \
    && echo "    gzip_min_length 1024;" >> /etc/nginx/nginx.conf \
    && echo "    gzip_proxied any;" >> /etc/nginx/nginx.conf \
    && echo "    gzip_comp_level 6;" >> /etc/nginx/nginx.conf \
    && echo "    gzip_types" >> /etc/nginx/nginx.conf \
    && echo "        text/plain" >> /etc/nginx/nginx.conf \
    && echo "        text/css" >> /etc/nginx/nginx.conf \
    && echo "        text/xml" >> /etc/nginx/nginx.conf \
    && echo "        text/javascript" >> /etc/nginx/nginx.conf \
    && echo "        application/json" >> /etc/nginx/nginx.conf \
    && echo "        application/javascript" >> /etc/nginx/nginx.conf \
    && echo "        application/xml+rss" >> /etc/nginx/nginx.conf \
    && echo "        application/atom+xml" >> /etc/nginx/nginx.conf \
    && echo "        image/svg+xml;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Cache de archivos estáticos" >> /etc/nginx/nginx.conf \
    && echo "    open_file_cache max=200000 inactive=20s;" >> /etc/nginx/nginx.conf \
    && echo "    open_file_cache_valid 30s;" >> /etc/nginx/nginx.conf \
    && echo "    open_file_cache_min_uses 2;" >> /etc/nginx/nginx.conf \
    && echo "    open_file_cache_errors on;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    # Rate limiting" >> /etc/nginx/nginx.conf \
    && echo "    limit_req_zone \$binary_remote_addr zone=api:10m rate=10r/s;" >> /etc/nginx/nginx.conf \
    && echo "" >> /etc/nginx/nginx.conf \
    && echo "    include /etc/nginx/conf.d/*.conf;" >> /etc/nginx/nginx.conf \
    && echo "}" >> /etc/nginx/nginx.conf

# Healthcheck optimizado
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Exponer puertos
EXPOSE 80 443

# Comando por defecto
CMD ["nginx", "-g", "daemon off;"]