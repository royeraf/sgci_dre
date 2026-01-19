# Guía de Despliegue en cPanel - Sistema de Control DRE

## Problema Actual
El error ocurre porque Laravel 10 (versiones recientes) y sus dependencias requieren PHP 8.2 o superior, pero el servidor cPanel tiene PHP 8.1.34.

## SOLUCIÓN RECOMENDADA: Actualizar PHP en cPanel

### Paso 1: Actualizar versión de PHP en cPanel
1. Inicia sesión en cPanel
2. Busca y abre **"MultiPHP Manager"** o **"Select PHP Version"**
3. Selecciona tu dominio/subdirectorio
4. Cambia la versión de PHP a **8.2** o **8.3** (la más reciente disponible)
5. Guarda los cambios

### Paso 2: Verificar la versión de PHP
En el terminal de cPanel, ejecuta:
```bash
php -v
```
Deberías ver PHP 8.2.x o superior.

### Paso 3: Instalar dependencias
```bash
cd ~/public_html  # o la ruta donde está tu proyecto
composer install --optimize-autoloader --no-dev
```

---

## ✅ SOLUCIÓN APLICADA: Versiones compatibles con PHP 8.1

El archivo `composer.json` ha sido actualizado para usar versiones compatibles con PHP 8.1.34:

### Cambios realizados:

```json
{
    "require": {
        "php": "^8.1",
        "barryvdh/laravel-dompdf": "^2.0",        // Downgrade de ^3.1
        "guzzlehttp/guzzle": "^7.2",
        "inertiajs/inertia-laravel": "^1.0",      // Downgrade de ^2.0
        "laravel/framework": "^10.48.22",         // Versión específica compatible
        "laravel/sanctum": "^3.3",
        "laravel/tinker": "^2.8",
        "pusher/pusher-php-server": "^7.2",
        "spatie/laravel-permission": "^6.0"       // Downgrade de ^6.24
    },
    "require-dev": {
        "fakerphp/faker": "^1.9.1",
        // laravel/pint removido (requiere PHP 8.2+)
        // laravel/reverb removido (requiere PHP 8.2+)
        "laravel/sail": "^1.18",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.1",
        "spatie/laravel-ignition": "^2.0"
    }
}
```

### Paquetes removidos:
- **laravel/reverb**: Requiere PHP 8.2+
- **laravel/pint**: Requiere PHP 8.2+

### Nota importante:
Si necesitas Laravel Reverb (WebSockets) o Laravel Pint (code styling), deberás actualizar a PHP 8.2+ en tu servidor.

---

## Pasos Completos para Despliegue en cPanel

### 1. Preparar archivos localmente

```bash
# En tu máquina local
cd /home/royer/Desktop/dre_sistema_control

# Limpiar archivos de desarrollo
rm -rf node_modules vendor

# Compilar assets de producción
npm install
npm run build

# Crear archivo .zip para subir
zip -r proyecto.zip . -x "node_modules/*" -x "vendor/*" -x ".git/*" -x "storage/logs/*"
```

### 2. Subir archivos a cPanel

1. Sube `proyecto.zip` mediante el **File Manager** de cPanel
2. Extrae el archivo en el directorio deseado (ej: `public_html`)

### 3. Configurar permisos

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

### 4. Configurar archivo .env

Copia `.env.example` a `.env` y configura:

```env
APP_NAME="Sistema de Control DRE"
APP_ENV=production
APP_KEY=  # Se generará en el siguiente paso
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nombre_base_datos
DB_USERNAME=usuario_db
DB_PASSWORD=contraseña_db

# Configurar otros servicios según necesites
```

### 5. Generar clave de aplicación

```bash
php artisan key:generate
```

### 6. Instalar dependencias de Composer

```bash
composer install --optimize-autoloader --no-dev
```

### 7. Ejecutar migraciones

```bash
php artisan migrate --force
```

### 8. Ejecutar seeders (si es necesario)

```bash
php artisan db:seed --force
```

### 9. Optimizar aplicación

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 10. Configurar el Document Root

En cPanel, configura el **Document Root** para que apunte a la carpeta `public` de tu proyecto Laravel:

- Si tu proyecto está en `~/laravel_app`
- El Document Root debe ser `~/laravel_app/public`

### 11. Configurar .htaccess (si es necesario)

Asegúrate de que el archivo `public/.htaccess` existe y contiene:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

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
```

---

## Troubleshooting

### Error: "Your lock file does not contain a compatible set of packages"

```bash
rm composer.lock
composer install --optimize-autoloader --no-dev
```

### Error: "Class not found"

```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Error de permisos en storage

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Limpiar caché si hay problemas

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

## Notas Importantes

1. **Nunca** subas la carpeta `vendor` o `node_modules` - siempre instálalas en el servidor
2. **Nunca** subas el archivo `.env` - créalo directamente en el servidor
3. Asegúrate de que `APP_DEBUG=false` en producción
4. Mantén actualizadas las dependencias de seguridad
5. Realiza backups regulares de la base de datos

---

## Comandos útiles para mantenimiento

```bash
# Ver logs de errores
tail -f storage/logs/laravel.log

# Limpiar todo el caché
php artisan optimize:clear

# Poner la aplicación en mantenimiento
php artisan down

# Sacar la aplicación de mantenimiento
php artisan up
```
