#!/bin/bash

# ============================================
# APIDIAN - Script de Verificación
# Verifica que la instalación esté correcta
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
echo "   APIDIAN - Verificación de Instalación"
echo "============================================"
echo -e "${NC}"

# Verificar Docker
echo -e "${YELLOW}[1/6] Verificando Docker...${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}✗ Docker no está instalado${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker instalado${NC}"

# Verificar Docker Compose
echo -e "${YELLOW}[2/6] Verificando Docker Compose...${NC}"
if ! docker compose version &> /dev/null; then
    echo -e "${RED}✗ Docker Compose no está disponible${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker Compose disponible${NC}"

# Verificar contenedores
echo -e "${YELLOW}[3/6] Verificando contenedores...${NC}"
CONTAINERS_RUNNING=$(docker compose ps --services --filter "status=running" | wc -l)
TOTAL_CONTAINERS=$(docker compose ps --services | wc -l)

if [ "$CONTAINERS_RUNNING" -eq "$TOTAL_CONTAINERS" ] && [ "$CONTAINERS_RUNNING" -gt 0 ]; then
    echo -e "${GREEN}✓ Todos los contenedores están corriendo ($CONTAINERS_RUNNING/$TOTAL_CONTAINERS)${NC}"
else
    echo -e "${RED}✗ Algunos contenedores no están corriendo ($CONTAINERS_RUNNING/$TOTAL_CONTAINERS)${NC}"
    docker compose ps
fi

# Verificar PHP
echo -e "${YELLOW}[4/6] Verificando PHP...${NC}"
PHP_VERSION=$(docker compose exec -T php php -v | head -n 1 | grep -o "PHP 7\.[0-9]")
if [[ "$PHP_VERSION" == "PHP 7.3" ]]; then
    echo -e "${GREEN}✓ PHP 7.3 correcto${NC}"
else
    echo -e "${YELLOW}⚠ PHP versión: $PHP_VERSION (esperado: PHP 7.3)${NC}"
fi

# Verificar extensiones PHP críticas
echo -e "${YELLOW}[5/6] Verificando extensiones PHP...${NC}"
REQUIRED_EXTENSIONS=("mbstring" "soap" "zip" "mysql" "curl" "gd" "xml" "intl" "imap" "imagick")
MISSING_EXTENSIONS=()

for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if docker compose exec -T php php -m | grep -q "^$ext$"; then
        echo -e "${GREEN}  ✓ $ext${NC}"
    else
        echo -e "${RED}  ✗ $ext${NC}"
        MISSING_EXTENSIONS+=("$ext")
    fi
done

if [ ${#MISSING_EXTENSIONS[@]} -eq 0 ]; then
    echo -e "${GREEN}✓ Todas las extensiones PHP están instaladas${NC}"
else
    echo -e "${RED}✗ Extensiones faltantes: ${MISSING_EXTENSIONS[*]}${NC}"
fi

# Verificar base de datos
echo -e "${YELLOW}[6/6] Verificando base de datos...${NC}"
if docker compose exec -T php php artisan migrate:status > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Base de datos conectada y migrada${NC}"
else
    echo -e "${RED}✗ Error de conexión a base de datos${NC}"
fi

echo ""
echo -e "${BLUE}Estado de contenedores:${NC}"
docker compose ps

echo ""
echo -e "${BLUE}Logs recientes (últimas 10 líneas):${NC}"
echo -e "${YELLOW}PHP:${NC}"
docker compose logs --tail=10 php

echo -e "${YELLOW}Nginx:${NC}"
docker compose logs --tail=10 nginx

echo ""
echo -e "${GREEN}Verificación completada${NC}"