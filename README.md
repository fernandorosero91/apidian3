# APIDIAN - API Facturación Electrónica DIAN Colombia

API REST para facturación electrónica según normativa DIAN Colombia UBL 2.1

## Características

- ✅ Laravel 5.8 + PHP 7.3 (versión exacta según DIAN)
- ✅ Facturación Electrónica UBL 2.1
- ✅ Firma Digital XAdES-EPES
- ✅ Integración completa con DIAN
- ✅ **Docker ultra-optimizado para máximo rendimiento**
- ✅ **PHP OPcache + Realpath cache optimizado**
- ✅ **Nginx con FastCGI cache y compresión**
- ✅ **MariaDB 10.3 con configuración de alto rendimiento**
- ✅ **Redis para cache y sesiones**
- ✅ SSL automático con Let's Encrypt
- ✅ Instalación automatizada
- ✅ Configuración de dominio personalizado
- ✅ **Rate limiting y seguridad avanzada**

## Instalación Rápida con Docker

### Instalación Básica (HTTP)
```bash
# Clonar repositorio
git clone https://github.com/elierrosero-del/apidian.git
cd apidian

# Ejecutar instalación automatizada
chmod +x install.sh
sudo ./install.sh
```

### Instalación con SSL (HTTPS)
```bash
# Clonar repositorio
git clone https://github.com/elierrosero-del/apidian.git
cd apidian

# Ejecutar instalación automatizada
chmod +x install.sh
sudo ./install.sh

# Durante la instalación:
# - Dominio: apidian.clipers.pro
# - Email SSL: tu-email@dominio.com
# - Puerto HTTP: 80
# - Puerto HTTPS: 443
```

**Nota**: Para SSL, asegúrate de que el dominio apunte a tu servidor antes de ejecutar el script.

## Requisitos

- Ubuntu 20.04 LTS o superior
- Docker y Docker Compose
- Dominio apuntando al servidor (para SSL)
- Certificado digital DIAN (.p12)
- Resolución de facturación DIAN

## Configuración

1. Acceder a la API: `http://tu-servidor`
2. Configurar empresa DIAN
3. Subir certificado digital
4. Configurar resoluciones
5. ¡Listo para facturar!

## Verificación de Rendimiento

```bash
# Verificar optimizaciones después de la instalación
chmod +x verify.sh
./verify.sh

# Monitorear rendimiento en tiempo real
docker compose exec php php -i | grep opcache
docker compose exec nginx nginx -t
docker compose logs -f --tail=100
```

## Documentación

- [Comandos de instalación manual](Comandos%20Instalacion%20API%202024%20Linux%20Ubuntu%2020.txt)
- [Colección Postman](ApiDianV2.1.postman_collection.json)

## Soporte

Para soporte técnico, contactar al desarrollador.

---

**Desarrollado para facturación electrónica en Colombia según normativa DIAN**