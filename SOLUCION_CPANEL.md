# Solución de Problemas en Despliegue cPanel

## Problemas Encontrados

### 1. Error 404 en Assets (CSS/JS)
**Error:**
```
GET https://documentos.drehuanuco.gob.pe/build/assets/app-xDDkCbkB.css net::ERR_ABORTED 404 (Not Found)
GET https://documentos.drehuanuco.gob.pe/build/assets/Login-B4sBJbNn.js net::ERR_ABORTED 404 (Not Found)
```

**Causa:**
- Laravel no está generando las URLs correctas para los assets compilados por Vite
- APP_URL no coincide con la URL real de producción
- Falta configuración de ASSET_URL

### 2. Error WebSocket (Reverb/Laravel Echo)
**Error:**
```
WebSocket connection to 'wss://localhost:8080/app/lxodj7kbdwavnd47pdvs...' failed
```

**Causa:**
- Laravel Echo intenta conectarse a `localhost:8080` en producción
- Variables VITE_REVERB_HOST configuradas para desarrollo

## Soluciones Aplicadas

### ✅ Solución 1: Configuración de URLs en Producción

**Archivo modificado:** `app/Providers/AppServiceProvider.php`

Se agregó forzado de HTTPS en producción:

```php
public function boot(): void
{
    Schema::defaultStringLength(191);

    // Force HTTPS in production (cPanel deployment)
    if (env('APP_ENV') === 'production') {
        URL::forceScheme('https');
    }
}
```

### ✅ Solución 2: Variables de Entorno Correctas

**Archivo creado:** `.env.production.example`

Configuración necesaria para producción en cPanel:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://documentos.drehuanuco.gob.pe
ASSET_URL=https://documentos.drehuanuco.gob.pe

# NO definir variables VITE_REVERB_* para evitar WebSocket en producción
```

## ⚠️ PROBLEMA IDENTIFICADO

Los archivos compilados (`app-xDDkCbkB.css`, `Login-B4sBJbNn.js`, etc.) **NO están en el servidor**.

Vite genera estos archivos con hashes únicos cada vez que ejecutas `npm run build`. Los archivos que tienes en el servidor son de una compilación anterior con hashes diferentes.

## ✅ SOLUCIÓN INMEDIATA

**Debes subir el directorio `public/build/` completo al servidor.**

### 🚀 Método Recomendado: Script Automático

Ejecuta el script de despliegue:

```bash
./deploy-to-cpanel.sh
```

Este script:
- ✅ Compila los assets con `npm run build`
- ✅ Crea `public/build.zip` automáticamente
- ✅ Muestra instrucciones claras paso a paso

Luego solo subes `public/build.zip` a cPanel y lo extraes.

### Opción 1: Usando File Manager de cPanel

1. **Comprimir** el directorio build localmente:
   ```bash
   cd public/
   zip -r build.zip build/
   ```

2. **Subir** `build.zip` al servidor mediante File Manager de cPanel en la carpeta `public/`

3. **Descomprimir** en el servidor (desde File Manager o SSH):
   ```bash
   cd public/
   unzip -o build.zip
   rm build.zip
   ```

### Opción 2: Usando FTP/SFTP

1. Conectar con FileZilla o tu cliente FTP favorito
2. Navegar a la carpeta `public/` en el servidor
3. **Eliminar** la carpeta `build/` antigua del servidor
4. **Subir** la carpeta `build/` completa desde tu local a `public/build/`

### Opción 3: Usando SCP (si tienes SSH)

```bash
# Desde tu máquina local
scp -r public/build/ usuario@documentos.drehuanuco.gob.pe:~/public_html/public/
```

## Pasos para Solucionar en el Servidor

### 1. Actualizar archivo .env en producción

Conectarse por SSH o File Manager de cPanel y editar el archivo `.env`:

```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://documentos.drehuanuco.gob.pe
ASSET_URL=https://documentos.drehuanuco.gob.pe
```

**IMPORTANTE:** Eliminar o comentar estas líneas:
```bash
# NO usar estas variables en producción cPanel:
# VITE_REVERB_APP_KEY=
# VITE_REVERB_HOST=
# VITE_REVERB_PORT=
# VITE_REVERB_SCHEME=
```

### 2. Limpiar cachés

Ejecutar en el servidor:

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 3. Recompilar assets (si es necesario)

Si los archivos en `/public/build/` no existen o están desactualizados:

```bash
npm run build
```

Esto generará los archivos en `public/build/assets/` con los hashes correctos.

### 4. Verificar permisos

Asegurar que el directorio `public/build/` tenga permisos 755:

```bash
chmod -R 755 public/build/
```

### 5. Verificar .htaccess

El archivo `public/.htaccess` debe tener esta configuración (ya está correcta):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

## Verificación

Después de aplicar los cambios:

1. **Verificar que los assets cargan:**
   - Abrir DevTools (F12)
   - Ir a la pestaña Network
   - Recargar la página
   - Todos los archivos en `/build/assets/` deben retornar 200 OK

2. **Verificar que no hay errores de WebSocket:**
   - En Console no deben aparecer errores de conexión a localhost:8080

3. **Verificar que la aplicación funciona:**
   - Login debe funcionar correctamente
   - Todas las páginas deben cargar con estilos

## Troubleshooting Adicional

### Si los assets siguen dando 404:

1. Verificar que existe el directorio `public/build/assets/`
2. Ejecutar `npm run build` en local
3. Subir todo el contenido de `public/build/` al servidor
4. Verificar que los nombres de archivo coincidan con los del manifiesto

### Si aparece página en blanco:

1. Activar temporalmente `APP_DEBUG=true`
2. Ver el error completo en pantalla
3. Revisar logs en `storage/logs/laravel.log`
4. Desactivar debug después: `APP_DEBUG=false`

### Si sigue conectándose a WebSocket:

1. Verificar que el archivo `.env` en producción NO tiene variables `VITE_REVERB_*`
2. Limpiar caché: `php artisan config:clear`
3. Hard refresh en el navegador (Ctrl+F5)
4. Limpiar caché del navegador

## Archivos Modificados

- ✅ `app/Providers/AppServiceProvider.php` - Agregado forzado de HTTPS
- ✅ `.env.production.example` - Plantilla de configuración para producción

## Notas Importantes

1. **NUNCA** subir el archivo `.env` a Git (ya está en .gitignore)
2. **SIEMPRE** usar `APP_ENV=production` y `APP_DEBUG=false` en producción
3. **NO** usar Reverb/WebSockets en cPanel sin un servidor WebSocket configurado
4. **VERIFICAR** que `APP_URL` y `ASSET_URL` coincidan con el dominio real

## Comandos útiles en cPanel

Si tienes acceso SSH en cPanel:

```bash
# Limpiar todos los cachés
php artisan optimize:clear

# Ver configuración actual
php artisan config:show

# Verificar rutas
php artisan route:list

# Ver información del entorno
php artisan about
```
