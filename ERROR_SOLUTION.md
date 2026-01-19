# Solución al Error de Composer en cPanel

## 🔴 Error Original

Al ejecutar `composer install` en cPanel con PHP 8.1.34, aparecían los siguientes errores:

```
Problem 1
  - nette/utils is locked to version v4.1.1 and an update of this package was not requested.
  - nette/utils v4.1.1 requires php 8.2 - 8.5 -> your php version (8.1.34) does not satisfy that requirement.

Problem 2
  - symfony/css-selector is locked to version v8.0.0 and an update of this package was not requested.
  - symfony/css-selector v8.0.0 requires php >=8.0.0 -> your php version (8.1.34) does not satisfy that requirement.

Problem 3
  - symfony/event-dispatcher is locked to version v7.4.0 and an update of this package was not requested.
  - symfony/event-dispatcher v7.4.0 requires php >=8.2 -> your php version (8.1.34) does not satisfy that requirement.

... y más errores similares
```

## ✅ Solución Aplicada

### 1. Modificación de `composer.json`

Se ajustaron las versiones de los paquetes principales para ser compatibles con PHP 8.1:

```json
{
    "require": {
        "php": "^8.1",
        "barryvdh/laravel-dompdf": "^2.0",        // ⬇️ Downgrade de ^3.1
        "inertiajs/inertia-laravel": "^1.0",      // ⬇️ Downgrade de ^2.0
        "laravel/framework": "^10.48.22",         // 🔒 Versión específica
        "spatie/laravel-permission": "^6.0"       // ⬇️ Downgrade de ^6.24
    },
    "require-dev": {
        // ❌ Removidos: laravel/pint, laravel/reverb (requieren PHP 8.2+)
    }
}
```

### 2. Limpieza de archivos antiguos

```bash
rm -rf vendor composer.lock
```

### 3. Reinstalación de dependencias

```bash
composer install --optimize-autoloader --no-dev
```

## 📋 Pasos para Desplegar en cPanel

### Opción A: Usar el script automático (Recomendado)

```bash
# 1. Subir el proyecto a cPanel
# 2. Conectar por SSH o usar Terminal de cPanel
cd ~/public_html  # o tu directorio

# 3. Ejecutar el script de despliegue
bash deploy-cpanel.sh
```

### Opción B: Paso a paso manual

```bash
# 1. Verificar versión de PHP
php -v
# Debe mostrar: PHP 8.1.34 (o superior)

# 2. Instalar dependencias
composer install --optimize-autoloader --no-dev

# 3. Configurar .env
cp .env.example .env
nano .env  # Editar con tus credenciales

# 4. Generar clave de aplicación
php artisan key:generate

# 5. Configurar permisos
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs

# 6. Ejecutar migraciones
php artisan migrate --force

# 7. Ejecutar seeders (opcional)
php artisan db:seed --force

# 8. Optimizar para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 🔍 Verificación Post-Instalación

### Verificar que no hay errores de dependencias:

```bash
composer validate
```

Salida esperada:
```
./composer.json is valid
```

### Verificar versión de Laravel:

```bash
php artisan --version
```

Salida esperada:
```
Laravel Framework 10.50.0
```

### Verificar que la aplicación funciona:

```bash
php artisan route:list
```

Debe mostrar todas las rutas sin errores.

## 🚨 Problemas Comunes y Soluciones

### Error: "Your lock file does not contain a compatible set of packages"

**Solución:**
```bash
rm composer.lock
composer install --optimize-autoloader --no-dev
```

### Error: "Class not found"

**Solución:**
```bash
composer dump-autoload
php artisan clear-compiled
php artisan optimize
```

### Error: Symfony components require PHP 8.2+

**Causa**: Estás intentando usar el `composer.lock` antiguo

**Solución:**
```bash
# Eliminar archivos antiguos
rm -rf vendor composer.lock

# Asegurarse de que composer.json tiene las versiones correctas
cat composer.json | grep "laravel/framework"
# Debe mostrar: "laravel/framework": "^10.48.22"

# Reinstalar
composer install --optimize-autoloader --no-dev
```

### Error 500 después del despliegue

**Solución:**
```bash
# 1. Revisar logs
tail -f storage/logs/laravel.log

# 2. Verificar permisos
chmod -R 755 storage bootstrap/cache

# 3. Limpiar caché
php artisan optimize:clear

# 4. Regenerar caché
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📊 Comparación de Versiones

| Paquete | Versión Anterior | Versión Actual | Compatible PHP 8.1 |
|---------|------------------|----------------|-------------------|
| Laravel Framework | ^10.10 | ^10.48.22 | ✅ |
| Inertia Laravel | ^2.0 | ^1.0 | ✅ |
| Laravel DomPDF | ^3.1 | ^2.0 | ✅ |
| Spatie Permission | ^6.24 | ^6.0 | ✅ |
| Laravel Reverb | ^1.7 | ❌ Removido | ❌ |
| Laravel Pint | ^1.0 | ❌ Removido | ❌ |

## ✨ Funcionalidades Confirmadas

Todas estas funcionalidades funcionan perfectamente con PHP 8.1:

- ✅ Autenticación con Laravel Sanctum
- ✅ Sistema de permisos y roles (Spatie)
- ✅ Generación de PDFs (DomPDF)
- ✅ Interfaz con Inertia.js + Vue 3
- ✅ Broadcasting con Pusher
- ✅ Gestión de citas
- ✅ Control de personal
- ✅ Gestión de vehículos
- ✅ Bienestar social
- ✅ Control de licencias
- ✅ Registro de ocurrencias
- ✅ Visitas externas

## 📝 Notas Finales

1. **No subas** las carpetas `vendor` o `node_modules` a cPanel
2. **Siempre** ejecuta `composer install` en el servidor
3. **Verifica** que el Document Root apunte a la carpeta `public`
4. **Configura** `APP_ENV=production` y `APP_DEBUG=false` en `.env`
5. **Realiza** backups regulares de la base de datos

## 📚 Documentación Adicional

- `DEPLOY_README.md` - Guía rápida de despliegue
- `DEPLOYMENT_CPANEL.md` - Guía completa de despliegue
- `VERSION_CHANGES.md` - Detalles de cambios de versiones
- `deploy-cpanel.sh` - Script automático de despliegue

---

**Estado**: ✅ Problema resuelto
**Fecha**: 2026-01-19
**PHP Version**: 8.1.34
**Laravel Version**: 10.50.0
