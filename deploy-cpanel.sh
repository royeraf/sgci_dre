#!/bin/bash

# ============================================
# Script de Despliegue para cPanel - PHP 8.1
# Sistema de Control DRE
# ============================================

echo ""
echo "=========================================="
echo "  🚀 Despliegue en cPanel - PHP 8.1"
echo "=========================================="
echo ""

# Verificar versión de PHP
echo "📌 1. Verificando versión de PHP..."
php -v | head -n 1
echo ""

# IMPORTANTE: NO ejecutar composer install con el lock file antiguo
# Ejecutar composer update para obtener versiones compatibles
echo "📦 2. Instalando dependencias compatibles con PHP 8.1..."
echo "   (Esto puede tomar unos minutos...)"
echo ""

# Usar composer update para resolver dependencias según la plataforma PHP actual
composer update --optimize-autoloader --no-dev --no-interaction

if [ $? -ne 0 ]; then
    echo ""
    echo "❌ Error al instalar dependencias."
    echo "   Intentando solución alternativa..."
    echo ""
    rm -f composer.lock
    composer install --optimize-autoloader --no-dev --no-interaction
fi

echo ""

# Verificar si existe .env
if [ ! -f .env ]; then
    echo "📝 3. Creando archivo .env..."
    cp .env.example .env
    echo "   ⚠️  IMPORTANTE: Configura las variables de entorno en .env"
    echo ""
else
    echo "📝 3. Archivo .env ya existe"
    echo ""
fi

# Generar clave de aplicación si no existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "🔑 4. Generando clave de aplicación..."
    php artisan key:generate --force
    echo ""
else
    echo "🔑 4. La clave de aplicación ya existe"
    echo ""
fi

# Configurar permisos
echo "🔒 5. Configurando permisos..."
chmod -R 755 storage bootstrap/cache 2>/dev/null
chmod -R 775 storage/logs 2>/dev/null
echo "   ✓ Permisos configurados"
echo ""

# Preguntar por migraciones
echo "❓ 6. ¿Deseas ejecutar las migraciones? (s/n)"
read -r ejecutar_migraciones
if [ "$ejecutar_migraciones" = "s" ] || [ "$ejecutar_migraciones" = "S" ]; then
    php artisan migrate --force
    echo ""
fi

# Preguntar por seeders
echo "❓ 7. ¿Deseas ejecutar los seeders? (s/n)"
read -r ejecutar_seeders
if [ "$ejecutar_seeders" = "s" ] || [ "$ejecutar_seeders" = "S" ]; then
    php artisan db:seed --force
    echo ""
fi

# Optimizar aplicación
echo "⚡ 8. Optimizando aplicación para producción..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo ""

echo "🔧 9. Generando caché optimizada..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo ""

echo "=========================================="
echo "  ✅ Despliegue completado exitosamente"
echo "=========================================="
echo ""
echo "📋 Pasos siguientes:"
echo "   1. Verifica que el Document Root apunte a 'public'"
echo "   2. Configura las variables en .env"
echo "   3. Asegúrate de que APP_ENV=production"
echo "   4. Asegúrate de que APP_DEBUG=false"
echo ""
