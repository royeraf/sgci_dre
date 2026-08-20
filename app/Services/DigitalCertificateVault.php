<?php

namespace App\Services;

use App\Models\DigitalCertificate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DigitalCertificateVault
{
    private const AAD = 'dreh-reniec-pfx-v1';

    public function register(UploadedFile $file, string $pfxPassword, string $pin, string $dni, ?int $userId): DigitalCertificate
    {
        $binary = file_get_contents($file->getRealPath());
        $items = [];
        if ($binary === false || !openssl_pkcs12_read($binary, $items, $pfxPassword)) {
            throw ValidationException::withMessages(['pfx_password' => 'La contraseña actual del PFX es incorrecta o el archivo está dañado.']);
        }
        if (empty($items['cert']) || empty($items['pkey']) || !openssl_x509_check_private_key($items['cert'], $items['pkey'])) {
            throw ValidationException::withMessages(['pfx_file' => 'El PFX no contiene una clave privada válida.']);
        }

        $details = openssl_x509_parse($items['cert']);
        $issuer = $this->formatName($details['issuer'] ?? []);
        $subject = $this->formatName($details['subject'] ?? []);
        if (!preg_match('/RENIEC|ECEP/i', $issuer)) {
            throw ValidationException::withMessages(['pfx_file' => 'El certificado no fue emitido por RENIEC/ECEP.']);
        }
        preg_match_all('/(?<!\d)\d{8}(?!\d)/', $subject, $dniMatches);
        if (!empty($dniMatches[0]) && !in_array($dni, $dniMatches[0], true)) {
            throw ValidationException::withMessages(['signer_dni' => 'El DNI ingresado no coincide con el titular indicado en el certificado.']);
        }

        $validFrom = (int) ($details['validFrom_time_t'] ?? 0);
        $validTo = (int) ($details['validTo_time_t'] ?? 0);
        if ($validFrom > time() || $validTo < time()) {
            throw ValidationException::withMessages(['pfx_file' => 'El certificado todavía no es válido o ya venció.']);
        }

        $salt = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $key = $this->deriveKey($pin, $salt);
        $nonce = random_bytes(SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES);
        $payload = json_encode([
            'pfx' => base64_encode($binary),
            'password' => $pfxPassword,
        ], JSON_THROW_ON_ERROR);
        $ciphertext = sodium_crypto_aead_xchacha20poly1305_ietf_encrypt($payload, self::AAD, $nonce, $key);
        sodium_memzero($key);

        $id = (string) Str::uuid();
        $path = 'digital-certificates/'.$id.'.vault';
        Storage::disk('local')->put($path, $nonce.$ciphertext);
        chmod(Storage::disk('local')->path($path), 0600);

        $certificate = DigitalCertificate::where('signer_dni', $dni)->first();
        $oldPath = $certificate?->vault_path;
        $values = [
            'vault_path' => $path,
            'salt' => base64_encode($salt),
            'pin_hash' => Hash::make($pin),
            'certificate_subject' => $subject,
            'certificate_issuer' => $issuer,
            'certificate_serial' => strtoupper((string) ($details['serialNumberHex'] ?? $details['serialNumber'] ?? '')),
            'certificate_thumbprint' => strtoupper(openssl_x509_fingerprint($items['cert'], 'sha256') ?: ''),
            'valid_from' => date('Y-m-d H:i:s', $validFrom),
            'valid_to' => date('Y-m-d H:i:s', $validTo),
            'is_active' => true,
            'created_by' => $userId,
        ];
        if ($certificate) {
            $certificate->forceFill($values)->save();
        } else {
            $certificate = DigitalCertificate::create(['id' => $id, 'signer_dni' => $dni] + $values);
        }
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        return $certificate;
    }

    public function unlock(DigitalCertificate $certificate, string $pin): array
    {
        if (!Hash::check($pin, $certificate->pin_hash)) {
            throw ValidationException::withMessages(['signing_pin' => 'El PIN de firma es incorrecto.']);
        }
        $blob = Storage::disk('local')->get($certificate->vault_path);
        $nonceLength = SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_NPUBBYTES;
        $nonce = substr($blob, 0, $nonceLength);
        $ciphertext = substr($blob, $nonceLength);
        $salt = base64_decode($certificate->salt, true);
        if ($salt === false) {
            throw new \RuntimeException('La bóveda del certificado no es válida.');
        }
        $key = $this->deriveKey($pin, $salt);
        $plaintext = sodium_crypto_aead_xchacha20poly1305_ietf_decrypt($ciphertext, self::AAD, $nonce, $key);
        sodium_memzero($key);
        if ($plaintext === false) {
            throw ValidationException::withMessages(['signing_pin' => 'No se pudo abrir el certificado con ese PIN.']);
        }
        $payload = json_decode($plaintext, true, flags: JSON_THROW_ON_ERROR);
        sodium_memzero($plaintext);
        return [
            'pfx' => base64_decode($payload['pfx'], true),
            'password' => (string) $payload['password'],
        ];
    }

    private function deriveKey(string $pin, string $salt): string
    {
        return sodium_crypto_pwhash(
            SODIUM_CRYPTO_AEAD_XCHACHA20POLY1305_IETF_KEYBYTES,
            $pin,
            $salt,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE
        );
    }

    private function formatName(array $name): string
    {
        return collect($name)->map(fn ($value, $key) => $key.'='.(is_array($value) ? implode('+', $value) : $value))->implode(', ');
    }
}
