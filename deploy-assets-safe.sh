#!/bin/bash

# Script de despliegue SEGURO para cPanel
# Crea un ZIP con nombre único para evitar conflictos

echo "========================================="
echo "  DESPLIEGUE SEGURO - cPanel"
echo "========================================="
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar directorio
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: Ejecuta desde la raíz del proyecto Laravel${NC}"
    exit 1
fi

# Generar nombre único con timestamp
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
PROJECT_NAME="sgci_dre"
ZIP_NAME="${PROJECT_NAME}_assets_${TIMESTAMP}.zip"

echo -e "${YELLOW}Paso 1:${NC} Compilando assets..."
npm run build

if [ $? -ne 0 ]; then
    echo -e "${RED}Error al compilar${NC}"
    exit 1
fi

echo -e "${GREEN}✓${NC} Assets compilados"
echo ""

echo -e "${YELLOW}Paso 2:${NC} Creando paquete con nombre único..."

# Crear ZIP con nombre único
cd public
zip -r "$ZIP_NAME" build/ -q

if [ $? -ne 0 ]; then
    echo -e "${RED}Error al crear ZIP${NC}"
    cd ..
    exit 1
fi

ZIP_SIZE=$(du -h "$ZIP_NAME" | cut -f1)
echo -e "${GREEN}✓${NC} Paquete creado: ${BLUE}${ZIP_NAME}${NC} (${ZIP_SIZE})"
cd ..
echo ""

# Crear archivo con instrucciones específicas
INSTRUCTIONS_FILE="INSTRUCCIONES_DEPLOY_${TIMESTAMP}.txt"
cat > "$INSTRUCTIONS_FILE" << EOF
═══════════════════════════════════════════════════════════════
  INSTRUCCIONES DE DESPLIEGUE - $(date +"%Y-%m-%d %H:%M:%S")
═══════════════════════════════════════════════════════════════

ARCHIVO A SUBIR: public/${ZIP_NAME}

═══════════════════════════════════════════════════════════════
PASO 1: SUBIR ARCHIVO
═══════════════════════════════════════════════════════════════

1. Abre File Manager en cPanel
2. Ve a: public_html/documentos/ (o donde esté tu proyecto)
3. Sube el archivo: ${ZIP_NAME}

═══════════════════════════════════════════════════════════════
PASO 2: EXTRAER EN UBICACIÓN CORRECTA (IMPORTANTE)
═══════════════════════════════════════════════════════════════

¡ATENCIÓN! NO extraer directamente en public_html

Opción A - Usando Terminal SSH (RECOMENDADO):
--------------------------------------------
cd ~/public_html/documentos/
unzip -o ${ZIP_NAME}
rm ${ZIP_NAME}

Opción B - Usando File Manager:
--------------------------------
1. Haz clic derecho en ${ZIP_NAME}
2. Selecciona "Extract"
3. IMPORTANTE: Verificar que se extrae en la ubicación correcta
4. Elimina ${ZIP_NAME} después

RESULTADO ESPERADO:
  ~/public_html/documentos/build/assets/app-xDDkCbkB.css
  ~/public_html/documentos/build/assets/Login-B4sBJbNn.js
  ~/public_html/documentos/build/assets/... (otros archivos)

═══════════════════════════════════════════════════════════════
PASO 3: VERIFICAR ESTRUCTURA
═══════════════════════════════════════════════════════════════

Verifica que existan estos archivos en el servidor:

  documentos/build/manifest.json
  documentos/build/assets/app-xDDkCbkB.css
  documentos/build/assets/app-9DE5_l2D.js
  documentos/build/assets/Login-B4sBJbNn.js

Comando para verificar (SSH):
  ls -lh ~/public_html/documentos/build/assets/ | head -20

═══════════════════════════════════════════════════════════════
PASO 4: CONFIGURAR .env (SI AÚN NO LO HICISTE)
═══════════════════════════════════════════════════════════════

Edita: ~/public_html/documentos/.env

Cambiar estas líneas:
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://documentos.drehuanuco.gob.pe
  ASSET_URL=https://documentos.drehuanuco.gob.pe

COMENTAR/ELIMINAR estas líneas:
  # VITE_REVERB_APP_KEY=
  # VITE_REVERB_HOST=localhost
  # VITE_REVERB_PORT=8080
  # VITE_REVERB_SCHEME=

═══════════════════════════════════════════════════════════════
PASO 5: LIMPIAR CACHÉ
═══════════════════════════════════════════════════════════════

Ejecuta en Terminal SSH:
  cd ~/public_html/documentos
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear

═══════════════════════════════════════════════════════════════
PASO 6: VERIFICAR EN EL NAVEGADOR
═══════════════════════════════════════════════════════════════

1. Abre: https://documentos.drehuanuco.gob.pe
2. Presiona Ctrl+Shift+R (hard refresh)
3. Abre DevTools (F12) → pestaña Network
4. Recarga la página
5. Verifica que NO haya errores 404

Si aún ves errores 404:
  - Verifica que la carpeta build/ esté en la ubicación correcta
  - Verifica que APP_URL en .env sea correcto
  - Limpia caché del navegador completamente

═══════════════════════════════════════════════════════════════
INFORMACIÓN TÉCNICA
═══════════════════════════════════════════════════════════════

Paquete generado: $(date +"%Y-%m-%d %H:%M:%S")
Archivos incluidos: $(cd public && find build -type f | wc -l) archivos
Tamaño total: ${ZIP_SIZE}

EOF

echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✓ PREPARACIÓN COMPLETA${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Archivo para subir:${NC}"
echo -e "  📦 public/${ZIP_NAME} (${ZIP_SIZE})"
echo ""
echo -e "${BLUE}Instrucciones detalladas:${NC}"
echo -e "  📄 ${INSTRUCTIONS_FILE}"
echo ""
echo -e "${YELLOW}Lee el archivo de instrucciones para el paso a paso completo${NC}"
echo ""
