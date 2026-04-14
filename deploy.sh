#!/bin/bash

# ╔════════════════════════════════════════════════════════════╗
# ║  SGCI DRE Huánuco — Script de Despliegue                  ║
# ╠════════════════════════════════════════════════════════════╣
# ║  DESDE TU PC LOCAL:                                        ║
# ║    ./deploy.sh              → rsync + build VPS + config   ║
# ║    ./deploy.sh --sync-code  → solo rsync código PHP        ║
# ║    ./deploy.sh --upload-assets → build local + subir zip   ║
# ║    ./deploy.sh --build-only → solo compilar local          ║
# ║    ./deploy.sh --remote     → solo config remota VPS       ║
# ║                                                            ║
# ║  DESDE EL TERMINAL DEL VPS (cPanel):                       ║
# ║    bash deploy.sh --vps     → git pull + build + artisan   ║
# ╚════════════════════════════════════════════════════════════╝

set -euo pipefail

# ─── Detectar modo VPS ───────────────────────────────────────
VPS_MODE=false
for arg in "$@"; do
    [[ "$arg" == "--vps" ]] && VPS_MODE=true
done

# ─── Guardia: evitar ejecución accidental en el VPS ──────────
if [ "$VPS_MODE" = false ]; then
    if [ "$(whoami)" = "drehua5" ] || [ "$(hostname)" = "drehuanuco.gob.pe" ]; then
        echo "ERROR: Para ejecutar en el VPS usa: bash deploy.sh --vps"
        exit 1
    fi
fi

# ─── Configuración (solo relevante en modo local) ────────────
VPS_USER="drehua5"
VPS_HOST="drehuanuco.gob.pe"
VPS_PORT="22"                    # Cambiar si el VPS usa otro puerto SSH
VPS_PATH="~/documentos.drehuanuco.gob.pe"
SSH_KEY="${HOME}/.ssh/dre_vps"
VPS_SSH="${VPS_USER}@${VPS_HOST}"
if [ -f "${SSH_KEY}" ]; then
    SSH_OPTS="-i ${SSH_KEY} -p ${VPS_PORT} -o StrictHostKeyChecking=no -o BatchMode=yes"
else
    SSH_OPTS="-p ${VPS_PORT} -o StrictHostKeyChecking=no"
fi
DOMAIN="https://documentos.drehuanuco.gob.pe"
LOCAL_PROJECT_DIR="$(cd "$(dirname "$0")" && pwd)"

# ─── Colores ─────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
BLUE='\033[0;34m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

print_header() {
    echo ""
    echo -e "${BLUE}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║${NC}  ${BOLD}🚀 SGCI DRE Huánuco — Despliegue${NC}              ${BLUE}║${NC}"
    echo -e "${BLUE}║${NC}  $(date '+%Y-%m-%d %H:%M:%S')                       ${BLUE}║${NC}"
    echo -e "${BLUE}╚════════════════════════════════════════════════╝${NC}"
    echo ""
}
print_step() { echo -e "${CYAN}━━━ $1${NC}"; }
print_ok()   { echo -e "  ${GREEN}✓${NC} $1"; }
print_warn() { echo -e "  ${YELLOW}⚠${NC} $1"; }
print_error(){ echo -e "  ${RED}✗${NC} $1"; }
confirm() {
    echo -e "${YELLOW}❓ $1 (s/n)${NC}"
    read -r respuesta
    [[ "$respuesta" =~ ^[sS]$ ]]
}

# ════════════════════════════════════════════════════════════
# MODO VPS — se ejecuta directamente en el servidor
# ════════════════════════════════════════════════════════════
do_vps_deploy() {
    APP_DIR="${HOME}/documentos.drehuanuco.gob.pe"
    cd "$APP_DIR"

    # ── Git pull ──────────────────────────────────────────
    print_step "Descargando cambios (git pull)"
    git pull origin main || { print_error "git pull falló. Revisa conflictos."; exit 1; }
    print_ok "Código actualizado"

    # ── Install + build (npm preferido en VPS ploop/OpenVZ) ──
    print_step "Instalando dependencias y compilando assets"
    if command -v npm &>/dev/null; then
        npm install --prefer-offline 2>/dev/null || npm install
        print_ok "Dependencias instaladas (npm)"
        npm run build
    else
        BUN_BIN="${HOME}/.bun/bin/bun"
        [ ! -f "$BUN_BIN" ] && BUN_BIN="$(command -v bun 2>/dev/null || echo '')"
        [ -z "$BUN_BIN" ] && { print_error "Ni npm ni bun encontrados."; exit 1; }
        "$BUN_BIN" install
        print_ok "Dependencias instaladas (bun)"
        "$BUN_BIN" run build
    fi
    print_ok "Assets compilados"

    # ── Caché Laravel ─────────────────────────────────────
    print_step "Configurando Laravel"
    php artisan config:clear -q && php artisan cache:clear -q
    php artisan route:clear -q  && php artisan view:clear -q
    php artisan config:cache -q && php artisan route:cache -q && php artisan view:cache -q
    print_ok "Caché regenerada"

    # ── Migraciones ───────────────────────────────────────
    if confirm "¿Ejecutar migraciones?"; then
        print_step "Ejecutando migraciones"
        php artisan migrate --force
        print_ok "Migraciones ejecutadas"
    fi

    # ── Permisos ──────────────────────────────────────────
    chmod -R 755 storage bootstrap/cache 2>/dev/null || true
    chmod -R 775 storage/logs 2>/dev/null || true
    : > error_log 2>/dev/null || true
    print_ok "Permisos y logs configurados"

    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅ Deploy VPS completado                      ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}"
    echo ""
}

# ════════════════════════════════════════════════════════════
# MODO LOCAL — rsync + SSH al VPS
# ════════════════════════════════════════════════════════════
do_build() {
    print_step "PASO 1 — Compilando assets localmente"
    cd "$LOCAL_PROJECT_DIR"
    [ ! -d "node_modules" ] && npm install
    npm run build && print_ok "Assets compilados en public/build/" || { print_error "Error al compilar."; exit 1; }
}

do_sync_code() {
    print_step "PASO 2 — Sincronizando código al VPS"
    cd "$LOCAL_PROJECT_DIR"
    echo "  Subiendo código PHP, rutas, config..."
    rsync -az --delete -e "ssh ${SSH_OPTS}" \
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

do_upload_build() {
    print_step "PASO 3 — Subiendo build de assets al VPS"
    cd "$LOCAL_PROJECT_DIR"
    echo "  Comprimiendo build..."
    cd public && rm -f build.zip && zip -r build.zip build/ -q
    BUILD_SIZE=$(du -h build.zip | cut -f1)
    cd "$LOCAL_PROJECT_DIR"
    print_ok "build.zip creado (${BUILD_SIZE})"
    echo "  Subiendo al VPS..."
    scp -i "${SSH_KEY}" -P "${VPS_PORT}" public/build.zip "${VPS_SSH}:${VPS_PATH}/public/"
    ssh ${SSH_OPTS} "${VPS_SSH}" "cd ${VPS_PATH}/public && rm -rf build && unzip -o build.zip -q && rm build.zip"
    print_ok "Assets desplegados en public/build/"
    echo ""
}

do_remote_config() {
    print_step "PASO 4 — Configurando el VPS vía SSH"
    ssh ${SSH_OPTS} "${VPS_SSH}" bash -s << 'REMOTE_SCRIPT'
        set -e
        cd ~/documentos.drehuanuco.gob.pe
        echo "  [VPS] PHP $(php -v | head -1 | awk '{print $2}')"
        echo "  [VPS] Laravel $(php artisan --version | awk '{print $NF}')"

        echo "  [VPS] Instalando dependencias y compilando assets..."
        if command -v npm &>/dev/null; then
            npm install --prefer-offline 2>/dev/null || npm install
            npm run build
        else
            BUN_BIN="${HOME}/.bun/bin/bun"
            [ ! -f "$BUN_BIN" ] && BUN_BIN="$(command -v bun 2>/dev/null || echo '')"
            [ -z "$BUN_BIN" ] && { echo "  [VPS] ✗ Ni npm ni bun encontrados."; exit 1; }
            "$BUN_BIN" install
            "$BUN_BIN" run build
        fi
        echo "  [VPS] ✓ Assets compilados"

        grep -q "memory_limit" .user.ini 2>/dev/null || echo "memory_limit = 256M" > .user.ini
        grep -q "memory_limit" public/.user.ini 2>/dev/null || echo "memory_limit = 256M" > public/.user.ini

        [ ! -f .htaccess ] && cat > .htaccess << 'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
# php -- BEGIN cPanel-generated handler, do not edit
<IfModule mime_module>
  AddHandler application/x-httpd-ea-php81 .php .php8 .phtml
</IfModule>
# php -- END cPanel-generated handler, do not edit
HTACCESS

        cat > public/.htaccess << 'HTACCESS_PUBLIC'
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
HTACCESS_PUBLIC

        chmod -R 755 storage bootstrap/cache 2>/dev/null
        chmod -R 775 storage/logs 2>/dev/null
        php artisan config:clear -q && php artisan cache:clear -q
        php artisan route:clear -q  && php artisan view:clear -q
        php artisan config:cache -q && php artisan route:cache -q && php artisan view:cache -q
        : > error_log 2>/dev/null
        echo "  [VPS] ✓ Configuración completada"
REMOTE_SCRIPT
    print_ok "VPS configurado exitosamente"
    echo ""
}

do_migrations() {
    if confirm "¿Ejecutar migraciones en el VPS?"; then
        print_step "Ejecutando migraciones..."
        ssh ${SSH_OPTS} "${VPS_SSH}" "cd ${VPS_PATH} && php artisan migrate --force"
        print_ok "Migraciones ejecutadas"
    fi
    echo ""
}

do_verify() {
    print_step "VERIFICACIÓN FINAL"
    HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "${DOMAIN}/login" 2>/dev/null || echo "000")
    if   [ "$HTTP_STATUS" = "200" ]; then print_ok "Sitio OK (HTTP 200)"
    elif [ "$HTTP_STATUS" = "302" ]; then print_ok "Sitio OK con redirección (HTTP 302)"
    elif [ "$HTTP_STATUS" = "000" ]; then print_warn "No se pudo conectar. Verifica: ${DOMAIN}"
    else print_error "Sitio respondió HTTP ${HTTP_STATUS}. Revisa: storage/logs/laravel.log"
    fi
    echo ""
    echo -e "${GREEN}╔════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║  ✅ DESPLIEGUE COMPLETADO                      ║${NC}"
    echo -e "${GREEN}║  ${DOMAIN}          ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════╝${NC}"
    echo ""
}

print_help() {
    echo "USO: ./deploy.sh [opción]"
    echo ""
    echo "  Desde tu PC local:"
    echo "  (sin argumentos)   rsync código + build en VPS + config + migraciones"
    echo "  --sync-code        Solo rsync código PHP al VPS"
    echo "  --upload-assets    Build local + subir zip al VPS (si SSH no puede ejecutar bun)"
    echo "  --remote           Solo config remota en VPS (bun build + artisan)"
    echo "  --build-only       Solo compilar assets localmente"
    echo ""
    echo "  Desde el terminal web del VPS (cPanel):"
    echo "  --vps              git pull + bun build + artisan cache + migraciones"
    echo ""
    echo "  --help             Mostrar esta ayuda"
    echo ""
}

# ─── Main ────────────────────────────────────────────────────
main() {
    print_header

    case "${1:-}" in
        --vps)
            do_vps_deploy
            ;;
        --help)
            print_help
            ;;
        --build-only)
            do_build
            print_ok "Build local completado."
            ;;
        --upload-assets)
            do_build
            do_upload_build
            print_ok "Assets subidos al VPS."
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
            do_sync_code
            do_remote_config
            do_migrations
            do_verify
            ;;
    esac
}

main "$@"
