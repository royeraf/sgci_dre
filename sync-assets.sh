#!/bin/bash

# Script para sincronizar SOLO los assets modificados vía RSYNC/SCP
# Requiere acceso SSH al servidor

echo "========================================="
echo "  SINCRONIZACIÓN DIRECTA - SSH"
echo "========================================="
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# CONFIGURACIÓN - MODIFICA ESTOS VALORES
SERVER_USER="tu_usuario_cpanel"
SERVER_HOST="documentos.drehuanuco.gob.pe"
SERVER_PATH="~/public_html/documentos/public"
# PUERTO SSH (normalmente 22, pero cPanel puede usar otro)
SSH_PORT="22"

echo -e "${YELLOW}Configuración actual:${NC}"
echo "  Usuario: $SERVER_USER"
echo "  Servidor: $SERVER_HOST"
echo "  Puerto SSH: $SSH_PORT"
echo "  Ruta remota: $SERVER_PATH/build/"
echo ""

# Preguntar si continuar
read -p "¿Continuar con esta configuración? (s/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo -e "${YELLOW}Cancelado. Edita el script para cambiar la configuración.${NC}"
    exit 0
fi

# Verificar que npm build está actualizado
echo -e "${YELLOW}¿Ya ejecutaste 'npm run build'?${NC}"
read -p "Continuar (s/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Ss]$ ]]; then
    echo "Ejecuta: npm run build"
    echo "Luego vuelve a ejecutar este script"
    exit 0
fi

# Verificar que existe el directorio build
if [ ! -d "public/build" ]; then
    echo -e "${RED}Error: No existe public/build/${NC}"
    echo "Ejecuta: npm run build"
    exit 1
fi

echo -e "${YELLOW}Sincronizando archivos...${NC}"
echo ""

# Opción 1: RSYNC (más eficiente, solo sube lo que cambió)
if command -v rsync &> /dev/null; then
    echo -e "${BLUE}Usando RSYNC (solo archivos modificados)...${NC}"
    rsync -avz --progress -e "ssh -p $SSH_PORT" \
        public/build/ \
        ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/build/

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Sincronización completada${NC}"
    else
        echo ""
        echo -e "${RED}✗ Error en la sincronización${NC}"
        echo -e "${YELLOW}Verifica:${NC}"
        echo "  1. Usuario y contraseña SSH"
        echo "  2. Puerto SSH correcto"
        echo "  3. Ruta del servidor correcta"
        exit 1
    fi
else
    # Opción 2: SCP (sube todo)
    echo -e "${BLUE}Usando SCP (subiendo todos los archivos)...${NC}"
    scp -P $SSH_PORT -r public/build/ \
        ${SERVER_USER}@${SERVER_HOST}:${SERVER_PATH}/

    if [ $? -eq 0 ]; then
        echo ""
        echo -e "${GREEN}✓ Archivos subidos${NC}"
    else
        echo ""
        echo -e "${RED}✗ Error al subir archivos${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${YELLOW}Limpiando caché en el servidor...${NC}"

ssh -p $SSH_PORT ${SERVER_USER}@${SERVER_HOST} << 'ENDSSH'
cd ~/public_html/documentos
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo "✓ Caché limpiado"
ENDSSH

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ DESPLIEGUE COMPLETADO${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "Verifica en: https://documentos.drehuanuco.gob.pe"
echo "Recuerda hacer Ctrl+Shift+R para limpiar caché del navegador"
echo ""
