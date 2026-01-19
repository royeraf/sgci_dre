#!/bin/bash

# Script de despliegue para cPanel - Sistema de Control DRE
# Compatible con PHP 8.1

echo "=========================================="
echo "  Despliegue en cPanel - PHP 8.1"
echo "=========================================="
echo ""

# Verificar versión de PHP
echo "1. Verificando versión de PHP..."
php -v | head -n 1
echo ""

# Instalar dependencias de Composer
echo "2. Instalando dependencias de Composer..."
composer install --optimize-autoloader --no-dev
echo ""

# Verificar si existe .env
if [ ! -f .env ]; then
    echo "3. Creando archivo .env..."
    cp .env.example .env
    echo "   ⚠️  IMPORTANTE: Configura las variables de entorno en .env"
    echo ""
fi

# Generar clave de aplicación si no existe
if ! grep -q "APP_KEY=base64:" .env; then
    echo "4. Generando clave de aplicación..."
    php artisan key:generate
    echo ""
else
    echo "4. La clave de aplicación ya existe"
    echo ""
fi

# Configurar permisos
echo "5. Configurando permisos..."
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
echo "   ✓ Permisos configurados"
echo ""

# Ejecutar migraciones
echo "6. ¿Deseas ejecutar las migraciones? (s/n)"
read -r ejecutar_migraciones
if [ "$ejecutar_migraciones" = "s" ] || [ "$ejecutar_migraciones" = "S" ]; then
    php artisan migrate --force
    echo ""
fi

# Ejecutar seeders
echo "7. ¿Deseas ejecutar los seeders? (s/n)"
read -r ejecutar_seeders
if [ "$ejecutar_seeders" = "s" ] || [ "$ejecutar_seeders" = "S" ]; then
    php artisan db:seed --force
    echo ""
fi

# Limpiar y optimizar caché
echo "8. Limpiando y optimizando caché..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo ""

echo "9. Optimizando aplicación para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
echo ""

echo "=========================================="
echo "  ✓ Despliegue completado exitosamente"
echo "=========================================="
echo ""
echo "Pasos siguientes:"
echo "1. Verifica que el Document Root apunte a la carpeta 'public'"
echo "2. Configura las variables de entorno en .env"
echo "3. Asegúrate de que APP_ENV=production y APP_DEBUG=false"
echo ""
