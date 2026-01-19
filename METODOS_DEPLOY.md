# Métodos de Despliegue para cPanel

## 🎯 Tu Situación

Ya existe una carpeta `build/` en `public_html/` con archivos de otro proyecto que **NO debes eliminar**.

Tu aplicación Laravel está en: `public_html/documentos/`

## ✅ Soluciones Disponibles

---

## 📦 MÉTODO 1: ZIP con Nombre Único (RECOMENDADO)

**Ventaja:** No sobrescribe nada, crea un archivo con timestamp único

### Uso:

```bash
./deploy-assets-safe.sh
```

**Qué hace:**
1. Compila los assets (`npm run build`)
2. Crea `sgci_dre_assets_YYYYMMDD_HHMMSS.zip` con nombre único
3. Genera instrucciones específicas en un archivo `.txt`

**Pasos después:**
1. Sube el ZIP generado a: `public_html/documentos/`
2. Extrae el ZIP en esa ubicación (NO en public_html raíz)
3. Resultado: `public_html/documentos/build/assets/...`

**⚠️ IMPORTANTE:** El ZIP se extrae en `documentos/`, NO en `public_html/` directamente.

---

## 🚀 MÉTODO 2: Sincronización SSH Directa (MÁS RÁPIDO)

**Ventaja:** Automático, solo sube archivos modificados

### Configuración inicial:

Edita `sync-assets.sh` y ajusta estos valores:

```bash
SERVER_USER="tu_usuario_cpanel"
SERVER_HOST="documentos.drehuanuco.gob.pe"
SERVER_PATH="~/public_html/documentos/public"
SSH_PORT="22"
```

### Uso:

```bash
# 1. Compilar assets
npm run build

# 2. Sincronizar directamente
./sync-assets.sh
```

**Requisitos:**
- Acceso SSH al servidor cPanel
- `rsync` instalado (recomendado) o `scp`

**Qué hace:**
1. Sube solo los archivos del directorio `build/` que cambiaron
2. Los coloca en `~/public_html/documentos/public/build/`
3. Limpia automáticamente el caché de Laravel

---

## 📂 MÉTODO 3: Manual con FTP/FileZilla

**Ventaja:** Control visual total de lo que subes

### Pasos:

1. **Compilar localmente:**
   ```bash
   npm run build
   ```

2. **Conectar con FileZilla:**
   - Host: `documentos.drehuanuco.gob.pe`
   - Usuario: Tu usuario cPanel
   - Contraseña: Tu contraseña
   - Puerto: 21 (FTP) o 22 (SFTP)

3. **Navegar en el servidor a:**
   ```
   /public_html/documentos/public/
   ```

4. **Eliminar solo la carpeta `build/` dentro de `documentos/public/`**
   - ⚠️ NO tocar `/public_html/build/` (esa es del otro proyecto)

5. **Subir la carpeta local:**
   ```
   Local: /tu/proyecto/public/build/
   Remoto: /public_html/documentos/public/build/
   ```

---

## 🔧 MÉTODO 4: Comando SSH Manual

Si tienes acceso SSH directo:

```bash
# En tu local
cd public
tar -czf build.tar.gz build/
scp build.tar.gz usuario@documentos.drehuanuco.gob.pe:~/

# En el servidor
ssh usuario@documentos.drehuanuco.gob.pe
cd ~/public_html/documentos/public/
rm -rf build/
tar -xzf ~/build.tar.gz
rm ~/build.tar.gz
php ../artisan config:clear
php ../artisan cache:clear
```

---

## 📋 Verificar la Estructura Correcta

Después de cualquier método, verifica en el servidor:

```
public_html/
├── build/                    ← Este es del OTRO proyecto (NO TOCAR)
│   └── ...
└── documentos/              ← TU aplicación Laravel
    ├── app/
    ├── public/
    │   ├── build/           ← AQUÍ deben estar TUS assets
    │   │   ├── manifest.json
    │   │   └── assets/
    │   │       ├── app-xDDkCbkB.css
    │   │       ├── app-9DE5_l2D.js
    │   │       ├── Login-B4sBJbNn.js
    │   │       └── ... (otros)
    │   └── index.php
    ├── routes/
    └── .env
```

---

## ✅ Configuración .env (IMPORTANTE)

Después de subir los assets, edita: `documentos/.env`

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://documentos.drehuanuco.gob.pe
ASSET_URL=https://documentos.drehuanuco.gob.pe
```

**Eliminar/Comentar:**
```env
# VITE_REVERB_APP_KEY=
# VITE_REVERB_HOST=localhost
# VITE_REVERB_PORT=8080
```

---

## 🧹 Limpiar Caché (OBLIGATORIO)

Después de cada despliegue:

```bash
cd ~/public_html/documentos
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

---

## 🔍 Verificación Final

1. Abre: https://documentos.drehuanuco.gob.pe
2. Presiona `Ctrl + Shift + R` (hard refresh)
3. Abre DevTools (`F12`) → pestaña **Network**
4. Verifica que estos archivos retornen **200 OK**:
   - `app-xDDkCbkB.css`
   - `app-9DE5_l2D.js`
   - `Login-B4sBJbNn.js`

---

## 🆘 Troubleshooting

### ❌ Siguen apareciendo errores 404

**Causa probable:** Los archivos están en la ubicación incorrecta

**Solución:**
```bash
# Verifica en SSH
ls -la ~/public_html/documentos/public/build/assets/

# Debe mostrar app-xDDkCbkB.css y otros archivos
```

### ❌ Error "Unable to preload CSS"

**Causa:** El navegador tiene caché antiguo

**Solución:**
1. `Ctrl + Shift + Delete` → Limpiar caché
2. `Ctrl + Shift + R` → Hard refresh
3. Probar en ventana incógnito

### ❌ WebSocket sigue intentando conectar

**Causa:** Variables VITE_REVERB en .env

**Solución:**
```bash
# Editar .env y comentar
# VITE_REVERB_APP_KEY=
# VITE_REVERB_HOST=

# Limpiar caché
php artisan config:clear
```

---

## 📊 Comparación de Métodos

| Método | Velocidad | Dificultad | Requiere SSH | Automático |
|--------|-----------|------------|--------------|------------|
| ZIP Único | ⭐⭐⭐ | Fácil | No | Parcial |
| SSH Sync | ⭐⭐⭐⭐⭐ | Media | Sí | Total |
| FTP Manual | ⭐⭐ | Fácil | No | No |
| SSH Manual | ⭐⭐⭐⭐ | Media | Sí | No |

---

## 🎯 Recomendación

- **Primera vez:** Usa **MÉTODO 1** (ZIP con nombre único)
- **Después:** Usa **MÉTODO 2** (SSH Sync) para deploys rápidos
- **Sin SSH:** Usa **MÉTODO 3** (FTP)

