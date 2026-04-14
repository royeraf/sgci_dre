#!/bin/bash

# ╔════════════════════════════════════════════════════════════╗
# ║  SGCI DRE Huánuco — Script de Despliegue                  ║
# ║  Uso: Se ejecuta LOCALMENTE para preparar + subir cambios ║
# ║  al VPS en documentos.drehuanuco.gob.pe                   ║
# ╚════════════════════════════════════════════════════════════╝
#
# USO:
#   ./deploy.sh              → Despliegue completo (build + subir + configurar)
#   ./deploy.sh --build-only → Solo compilar assets localmente
#   ./deploy.sh --remote     → Solo ejecutar configuración en el VPS
#   ./deploy.sh --help       → Mostrar ayuda
#
# REQUISITOS LOCALES: node, npm, git, scp/rsync, ssh
# REQUISITOS VPS:     php 8.1+, composer, mysql

set -euo pipefail

# ─── Configuración ───────────────────────────────────────────
VPS_USER="drehua5"
VPS_HOST="drehuanuco.gob.pe"
VPS_PATH="~/documentos.drehuanuco.gob.pe"
VPS_SSH="${VPS_USER}@${VPS_HOST}"
DOMAIN="https://documentos.drehuanuco.gob.pe"
LOCAL_PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Colores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Funciones ───────────────────────────────────────────────
print_header() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}  ${BOLD}🚀 SGCI DRE Huánuco — Despliegue${NC}              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}  $(date '+%Y-%m-%d %H:%M:%S')                       ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_step() {
    echo -e "${CYAN}━━━ $1${NC}"
}

print_ok() {
    echo -e "  ${GREEN}✓${NC} $1"
}

print_warn() {
    echo -e "  ${YELLOW}⚠${NC} $1"
}

print_error() {
    echo -e "  ${RED}✗${NC} $1"
}

print_help() {
    echo "USO: ./deploy.sh [opción]"
    echo ""
    echo "Opciones:"
    echo "  (sin argumentos)   Despliegue completo: build + subir + configurar"
    echo "  --build-only       Solo compilar assets localmente (npm run build)"
    echo "  --remote           Solo ejecutar configuración remota en el VPS"
    echo "  --sync-code        Sincronizar código PHP/Blade al VPS (sin build)"
    echo "  --help             Mostrar esta ayuda"
    echo ""
}

confirm() {
    echo -e "${YELLOW}❓ $1 (s/n)${NC}"
    read -r respuesta
    [[ "$respuesta" =~ ^[sS]$ ]]
}

# ─── 1. Build local ─────────────────────────────────────────
do_build() {
    print_step "PASO 1 — Compilando assets localmente"

    cd "$LOCAL_PROJECT_DIR"

    # Verificar que node_modules existe
    if [ ! -d "node_modules" ]; then
        echo "  Instalando dependencias npm..."
        npm install
    fi

    # Compilar assets
    echo "  Compilando con Vite..."
    npm run build

    if [ $? -eq 0 ]; then
        print_ok "Assets compilados en public/build/"
    else
        print_error "Error al compilar. Revisa los errores de Vite."
        exit 1
    fi


}

# ─── 2. Sincronizar código al VPS ───────────────────────────
do_sync_code() {
    print_step "PASO 2 — Sincronizando código al VPS"

    cd "$LOCAL_PROJECT_DIR"

    # Archivos y carpetas a sincronizar
    echo "  Subiendo código PHP, rutas, config..."
    rsync -avz --delete \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='.git' \
        --exclude='.env' \
        --exclude='storage/logs/*' \
        --exclude='storage/framework/cache/*' \
        --exclude='storage/framework/sessions/*' \
        --exclude='storage/framework/views/*' \
        --exclude='public/hot' \
        --exclude='public/build' \
        --exclude='*.xlsx' \
        --exclude='*.csv' \
        --exclude='*.pdf' \
        --exclude='composer.phar' \
        --exclude='.user.ini' \
        --exclude='error_log' \
        --exclude='.htaccess' \
        --exclude='public/.htaccess' \
        --exclude='package-lock.json' \
        --exclude='bun.lock' \
        --exclude='bun.lockb' \
        ./ "${VPS_SSH}:${VPS_PATH}/"

    print_ok "Código sincronizado"
    echo ""
}



# ─── 4. Configurar el VPS ───────────────────────────────────
do_remote_config() {
    print_step "PASO 4 — Configurando el VPS"

    ssh "${VPS_SSH}" bash -s << 'REMOTE_SCRIPT'
        set -e
        cd ~/documentos.drehuanuco.gob.pe

        echo "  [VPS] PHP $(php -v | head -1 | awk '{print $2}')"
        echo "  [VPS] Laravel $(php artisan --version | awk '{print $NF}')"

        echo "  [VPS] Preparando repo para el git pull..."
        git checkout -- public/build.zip 2>/dev/null || true
        git stash 2>/dev/null || true
        
        echo "  [VPS] Descargando últimos cambios (git pull)..."
        git pull || echo "  [VPS] ⚠ Advertencia: No se pudo hacer git pull (revisa manualmente si hay más conflictos)."
        
        git stash pop 2>/dev/null || true

        echo "  [VPS] Instalando Node Modules con Bun (borrando caché previa)..."
        rm -rf node_modules bun.lock bun.lockb
        ~/.bun/bin/bun install || bun install --verbose

        echo "  [VPS] Compilando Assets con Vite..."
        ~/.bun/bin/bun run build || bun run build

        # ── Asegurar .user.ini con memory_limit ──
        if ! grep -q "memory_limit" .user.ini 2>/dev/null; then
            echo "memory_limit = 256M" > .user.ini
            echo "  [VPS] ✓ .user.ini creado con memory_limit=256M"
        fi
        if ! grep -q "memory_limit" public/.user.ini 2>/dev/null; then
            echo "memory_limit = 256M" > public/.user.ini
            echo "  [VPS] ✓ public/.user.ini creado con memory_limit=256M"
        fi

        # ── Asegurar .htaccess raíz (rewrite a public/) ──
        if [ ! -f .htaccess ]; then
            cat > .htaccess << 'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# php -- BEGIN cPanel-generated handler, do not edit
# Set the "ea-php81" package as the default "PHP" programming language.
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php81 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
HTACCESS
            echo "  [VPS] ✓ .htaccess raíz creado"
        fi

        # ── Asegurar public/.htaccess (SIN Options) ──
        cat > public/.htaccess << 'HTACCESS_PUBLIC'
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS_PUBLIC
        echo "  [VPS] ✓ public/.htaccess configurado (sin Options)"

        # ── Permisos ──
        chmod -R 755 storage bootstrap/cache 2>/dev/null
        chmod -R 775 storage/logs 2>/dev/null
        echo "  [VPS] ✓ Permisos configurados"

        # ── Limpiar caché ──
        php artisan config:clear -q
        php artisan cache:clear -q
        php artisan route:clear -q
        php artisan view:clear -q
        echo "  [VPS] ✓ Caché limpiada"

        # ── Regenerar caché de producción ──
        php artisan config:cache -q
        php artisan route:cache -q
        php artisan view:cache -q
        echo "  [VPS] ✓ Caché de producción generada"

        # ── Limpiar error_log antiguo ──
        : > error_log 2>/dev/null
        echo "  [VPS] ✓ error_log limpiado"

        echo ""
        echo "  [VPS] 🎉 Configuración completada"
REMOTE_SCRIPT

    print_ok "VPS configurado exitosamente"
    echo ""
}

# ─── 5. Migraciones (opcional) ───────────────────────────────
do_migrations() {
    if confirm "¿Ejecutar migraciones en el VPS?"; then
        print_step "Ejecutando migraciones..."
        ssh "${VPS_SSH}" "cd ${VPS_PATH} && php artisan migrate --force"
        print_ok "Migraciones ejecutadas"
    fi
    echo ""
}

# ─── 6. Verificación ────────────────────────────────────────
do_verify() {
    print_step "VERIFICACIÓN FINAL"

    # Verificar que el sitio responde
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${DOMAIN}/login" 2>/dev/null || echo "000")

    if [ "$HTTP_STATUS" = "200" ]; then
        print_ok "Sitio respondiendo correctamente (HTTP ${HTTP_STATUS})"
    elif [ "$HTTP_STATUS" = "302" ]; then
        print_ok "Sitio respondiendo con redirección (HTTP ${HTTP_STATUS}) — OK"
    elif [ "$HTTP_STATUS" = "000" ]; then
        print_warn "No se pudo conectar al sitio. Verifica manualmente: ${DOMAIN}"
    else
        print_error "Sitio respondió con HTTP ${HTTP_STATUS}. Revisa los logs."
        echo "  Comando para revisar: ssh ${VPS_SSH} 'tail -20 ${VPS_PATH}/storage/logs/laravel.log'"
    fi

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅ DESPLIEGUE COMPLETADO                      ║${NC}"
    echo -e "${GREEN}║  ${DOMAIN}          ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}"
    echo ""
}

# ─── Main ────────────────────────────────────────────────────
main() {
    print_header

    case "${1:-}" in
        --help)
            print_help
            ;;
        --build-only)
            do_build
            print_ok "Build completado. Usa './deploy.sh' para despliegue completo."
            ;;
        --remote)
            do_remote_config
            do_migrations
            do_verify
            ;;
        --sync-code)
            do_sync_code
            do_remote_config
            do_verify
            ;;
        *)
            # Despliegue completo (el build se hace en el VPS)
            do_sync_code
            do_remote_config
            do_migrations
            do_verify
            ;;
    esac
}

main "$@"
