# APIDIAN - Instalación con Docker

## Requisitos

- VPS con Ubuntu 20.04, 22.04 o 24.04
- Mínimo 2GB RAM, 20GB disco
- Acceso root (SSH)
- Dominio apuntando al servidor (opcional, para SSL)

## Proveedores VPS Recomendados

- Hetzner (hetzner.com)
- Contabo (contabo.com)
- DigitalOcean (digitalocean.com)
- AWS / Google Cloud

## Instalación Rápida

```bash
# 1. Conectarse al VPS por SSH
ssh root@IP_DEL_SERVIDOR

# 2. Clonar el repositorio
git clone https://github.com/elierrosero-del/apidian2.git /var/www/apidian

# 3. Entrar al directorio
cd /var/www/apidian

# 4. Dar permisos al script
chmod +x install.sh

# 5. Ejecutar instalación
sudo ./install.sh
```

## Durante la Instalación

El script te preguntará:

| Pregunta | Ejemplo | Descripción |
|----------|---------|-------------|
| Dominio | apidian.miempresa.com | Tu dominio o `localhost` |
| Email SSL | admin@miempresa.com | Para certificado Let's Encrypt |
| Puerto HTTP | 80 | Puerto web |
| Puerto HTTPS | 443 | Puerto SSL |
| Puerto MySQL | 3306 | Puerto base de datos |

## Qué Instala el Script

- Docker y Docker Compose
- PHP 7.3 con extensiones DIAN (soap, xml, gd, etc.)
- MariaDB 10.3
- Nginx
- Redis
- Certificado SSL Let's Encrypt (opcional)

## Después de la Instalación

Las credenciales se guardan en `CREDENCIALES.txt`:

```bash
cat /var/www/apidian/CREDENCIALES.txt
```

## Comandos Útiles

```bash
# Ver estado de contenedores
docker compose ps

# Ver logs en tiempo real
docker compose logs -f

# Reiniciar servicios
docker compose restart

# Detener todo
docker compose down

# Iniciar todo
docker compose up -d

# Entrar al contenedor PHP
docker compose exec php bash

# Ejecutar comandos artisan
docker compose exec php php artisan migrate
docker compose exec php php artisan cache:clear
```

## Acceso a la API

- Sin SSL: `http://IP_SERVIDOR:PUERTO`
- Con SSL: `https://tu-dominio.com`

## Acceso a Base de Datos

Desde el VPS:
```bash
docker compose exec mariadb mysql -u apidian -p apidian
```

Desde cliente externo (HeidiSQL, DBeaver):
- Host: IP del servidor
- Puerto: 3306 (o el configurado)
- Usuario: apidian
- Password: (ver CREDENCIALES.txt)
- Base de datos: apidian

## Solución de Problemas

### Error de conexión a base de datos
```bash
docker compose restart mariadb
sleep 30
docker compose restart php
```

### Limpiar caché
```bash
docker compose exec php php artisan config:clear
docker compose exec php php artisan cache:clear
docker compose exec php php artisan view:clear
```

### Ver logs de errores
```bash
docker compose logs php --tail=100
docker compose logs nginx --tail=100
docker compose logs mariadb --tail=100
```

### Reinstalar desde cero
```bash
cd /var/www/apidian
docker compose down -v
sudo ./install.sh
```

## Renovar Certificado SSL

El certificado se renueva automáticamente. Para renovar manualmente:

```bash
cd /var/www/apidian
docker compose run --rm certbot renew
docker compose restart nginx
```

## Actualizar APIDIAN

```bash
cd /var/www/apidian
git pull
docker compose exec php composer install --no-dev
docker compose exec php php artisan migrate
docker compose exec php php artisan cache:clear
docker compose restart
```
