#!/bin/bash

# Script de despliegue automático para cPanel
# Uso: ./deploy-to-cpanel.sh

echo "========================================="
echo "  SCRIPT DE DESPLIEGUE - cPanel"
echo "========================================="
echo ""

# Colores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: No se encuentra el archivo artisan${NC}"
    echo "Por favor ejecuta este script desde la raíz del proyecto Laravel"
    exit 1
fi

echo -e "${YELLOW}Paso 1:${NC} Compilando assets con Vite..."
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}Error al compilar assets${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Assets compilados exitosamente"
echo ""

echo -e "${YELLOW}Paso 2:${NC} Comprimiendo directorio build..."
cd public

# Eliminar build.zip anterior si existe
if [ -f "build.zip" ]; then
    rm build.zip
fi

zip -r build.zip build/ -q

if [ $? -ne 0 ]; then
    echo -e "${RED}Error al comprimir build${NC}"
    cd ..
    exit 1
fi

echo -e "${GREEN}✓${NC} Archivo build.zip creado ($(du -h build.zip | cut -f1))"
cd ..
echo ""

echo -e "${YELLOW}Paso 3:${NC} Preparando archivos para subir..."
echo ""
echo "Los siguientes archivos están listos para subir a cPanel:"
echo ""
echo "  📦 public/build.zip"
echo ""
echo -e "${YELLOW}═══════════════════════════════════════${NC}"
echo -e "${GREEN}INSTRUCCIONES PARA cPanel:${NC}"
echo -e "${YELLOW}═══════════════════════════════════════${NC}"
echo ""
echo "1. Abre File Manager en cPanel"
echo "2. Navega a: public_html/public/ (o el directorio public de tu instalación)"
echo "3. ELIMINA la carpeta 'build' antigua (si existe)"
echo "4. Sube el archivo: public/build.zip"
echo "5. Haz clic derecho en build.zip → Extract"
echo "6. Elimina el archivo build.zip después de extraer"
echo ""
echo -e "${YELLOW}CONFIGURACIÓN .env:${NC}"
echo "7. Edita el archivo .env en el servidor con estos valores:"
echo ""
echo "   APP_ENV=production"
echo "   APP_DEBUG=false"
echo "   APP_URL=https://documentos.drehuanuco.gob.pe"
echo "   ASSET_URL=https://documentos.drehuanuco.gob.pe"
echo ""
echo "8. COMENTAR o eliminar estas líneas del .env:"
echo "   # VITE_REVERB_APP_KEY="
echo "   # VITE_REVERB_HOST="
echo "   # VITE_REVERB_PORT="
echo ""
echo -e "${YELLOW}LIMPIAR CACHÉ:${NC}"
echo "9. Ejecuta desde Terminal en cPanel:"
echo "   cd ~/public_html"
echo "   php artisan config:clear"
echo "   php artisan cache:clear"
echo "   php artisan route:clear"
echo "   php artisan view:clear"
echo ""
echo -e "${GREEN}✓ Preparación completa${NC}"
echo ""
echo "Archivo listo en: $(pwd)/public/build.zip"
echo ""
