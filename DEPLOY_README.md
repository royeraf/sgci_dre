# Guía Rápida de Despliegue en cPanel

## ✅ Configuración Actual
- **PHP Version**: 8.1+ compatible
- **Laravel Version**: 10.50.0
- **Dependencias**: Optimizadas para PHP 8.1.34

## 📦 Preparación Local

### 1. Compilar assets de producción
```bash
npm install
npm run build
```

### 2. Crear archivo comprimido para subir
```bash
# Excluir archivos innecesarios
zip -r proyecto.zip . \
  -x "node_modules/*" \
  -x "vendor/*" \
  -x ".git/*" \
  -x "storage/logs/*" \
  -x ".env"
```

## 🚀 Despliegue en cPanel

### 1. Subir archivos
1. Accede al **File Manager** de cPanel
2. Sube `proyecto.zip` a tu directorio (ej: `public_html` o `laravel_app`)
3. Extrae el archivo ZIP

### 2. Ejecutar script de despliegue
```bash
# Conecta por SSH o usa el Terminal de cPanel
cd ~/public_html  # o tu directorio

# Ejecutar script automático
bash deploy-cpanel.sh
```

### 3. Configurar .env
Edita el archivo `.env` con tus credenciales:

```env
APP_NAME="Sistema de Control DRE"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=tu_base_datos
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña

# Pusher/Broadcasting (si aplica)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=
```

### 4. Configurar Document Root
En cPanel > **Domains** > **Domains**:
- Selecciona tu dominio
- Cambia el **Document Root** a: `~/laravel_app/public`
- Guarda cambios

## 🔧 Comandos Útiles

### Limpiar caché
```bash
php artisan optimize:clear
```

### Optimizar para producción
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Ver logs de errores
```bash
tail -f storage/logs/laravel.log
```

### Modo mantenimiento
```bash
# Activar
php artisan down

# Desactivar
php artisan up
```

## ⚠️ Checklist Post-Despliegue

- [ ] Verificar que `APP_ENV=production` en `.env`
- [ ] Verificar que `APP_DEBUG=false` en `.env`
- [ ] Document Root apunta a la carpeta `public`
- [ ] Permisos de `storage` y `bootstrap/cache` configurados (775)
- [ ] Base de datos creada y credenciales correctas
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados (si es necesario)
- [ ] Caché optimizada
- [ ] SSL/HTTPS configurado

## 🐛 Solución de Problemas

### Error 500
```bash
# Revisar logs
tail -f storage/logs/laravel.log

# Limpiar caché
php artisan optimize:clear
```

### Error de permisos
```bash
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs
```

### Error de composer
```bash
# Reinstalar dependencias
rm -rf vendor composer.lock
composer install --optimize-autoloader --no-dev
```

### Página en blanco
- Verifica que el Document Root apunte a `public`
- Revisa los logs de Apache/Nginx
- Verifica que `.htaccess` existe en `public/`

## 📝 Notas Importantes

1. **Nunca** subas las carpetas `vendor` o `node_modules`
2. **Nunca** subas el archivo `.env` - créalo en el servidor
3. Mantén `APP_DEBUG=false` en producción
4. Realiza backups regulares de la base de datos
5. Monitorea los logs regularmente

## 🔄 Actualizaciones Futuras

Para actualizar la aplicación:

```bash
# 1. Hacer backup de la base de datos
# 2. Poner en modo mantenimiento
php artisan down

# 3. Subir nuevos archivos
# 4. Instalar dependencias
composer install --optimize-autoloader --no-dev

# 5. Ejecutar migraciones
php artisan migrate --force

# 6. Limpiar y optimizar
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 7. Salir de modo mantenimiento
php artisan up
```

## 📞 Soporte

Si encuentras problemas durante el despliegue:
1. Revisa los logs: `storage/logs/laravel.log`
2. Verifica la configuración de PHP en cPanel
3. Consulta la documentación completa en `DEPLOYMENT_CPANEL.md`
