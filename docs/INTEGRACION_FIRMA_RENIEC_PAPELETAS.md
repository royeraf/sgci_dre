# Integración: firma PFX RENIEC y papeletas con QR

Este módulo implementa una papeleta de salida con estas etapas:

1. El servidor solicita la papeleta y firma con su certificado PFX.
2. El jefe inmediato revisa, aprueba y firma.
3. Recursos Humanos revisa, aprueba y firma.
4. Portería escanea el QR para registrar salida y retorno reales.
5. En comisión, la entidad destino registra nombre, cargo y firma táctil.

## Seguridad del certificado

- El usuario carga voluntariamente su archivo `.pfx` o `.p12`.
- El archivo y su contraseña original se almacenan cifrados en el disco privado.
- La clave personal de firma no se guarda: solo se guarda un hash para verificarla.
- Al firmar, el PFX se descifra temporalmente, se usa en memoria/archivo temporal y se elimina al finalizar.
- Nunca subir archivos PFX, contraseñas, `.env` o `storage/app/private` al repositorio.

## Dependencias de servidor

PHP 8.1+, Laravel 10, OpenSSL, Python 3 y un entorno virtual con:

```bash
python3 -m venv storage/app/signing-venv
storage/app/signing-venv/bin/pip install pyhanko pillow
```

Configuración necesaria en `.env`:

```dotenv
SERVER_SIGNING_PYTHON=/ruta/al/proyecto/storage/app/signing-venv/bin/python
SERVER_SIGNING_SCRIPT=/ruta/al/proyecto/tools/server_sign_pdf.py
```

Después de desplegar:

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

## Rutas que se deben integrar

Agregar los controladores `DigitalCertificateController` y
`PapeletaQrControlController` a `routes/web.php`. Dentro del grupo autenticado
de papeletas se requieren las rutas para registrar el certificado, solicitar,
aprobar, rechazar, descargar el PDF y obtener el QR. El control físico se expone
fuera de la autenticación con límite de peticiones:

```php
Route::middleware('throttle:30,1')->prefix('control-papeleta')->name('papeletas.qr.')->group(function () {
    Route::get('/{token}', [PapeletaQrControlController::class, 'show'])->name('show');
    Route::post('/{token}/salida', [PapeletaQrControlController::class, 'salida'])->name('salida');
    Route::post('/{token}/retorno', [PapeletaQrControlController::class, 'retorno'])->name('retorno');
    Route::post('/{token}/destino', [PapeletaQrControlController::class, 'destino'])->name('destino');
});
```

## PDF final de una sola página

Para papeletas nuevas, el sistema reserva antes de la primera firma los campos
AcroForm de salida, retorno, firma de destino y las tres firmas institucionales.
La primera firma certifica el documento con política `DocMDP / FillForms`.
Luego los hitos QR se agregan como actualizaciones incrementales de esos campos,
sin regenerar el PDF ni alterar los bytes firmados.

Los documentos anteriores que ya fueron firmados sin campos reservados no deben
convertirse ni editarse: deben conservarse como evidencia original.

## Archivos principales

- `app/Services/DigitalCertificateVault.php`: cifrado, validación y desbloqueo temporal del PFX.
- `app/Services/PapeletaRequestSigningService.php`: secuencia Servidor → Jefe → RR. HH.
- `app/Services/PapeletaExecutionPdfService.php`: agrega los datos QR al PDF final.
- `tools/server_sign_pdf.py`: firma PAdES con el PFX.
- `tools/prepare_papeleta_qr_fields.py`: reserva los campos antes de firmar.
- `tools/update_papeleta_qr_fields.py`: completa los campos QR de forma incremental.
- `app/Http/Controllers/PapeletaQrControlController.php`: controla salida, retorno y conformidad de destino.

## Prueba recomendada

Crear una papeleta nueva con tres usuarios que tengan PFX válidos. Completar las
tres firmas, abrir el QR, marcar salida y retorno, y descargar `Papeleta firmada`.
El PDF debe mostrar ambos horarios reales y conservar las tres firmas digitales.
