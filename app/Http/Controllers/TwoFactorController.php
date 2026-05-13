<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─── Login verification ───────────────────────────────────────────────────

    public function showVerify(Request $request)
    {
        if (!$request->session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactor');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ], [
            'code.required' => 'El código es obligatorio.',
        ]);

        $userId = $request->session()->get('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $code = trim($request->code);

        // Try TOTP first (6 digits)
        if (preg_match('/^\d{6}$/', $code)) {
            $valid = $this->google2fa->verifyKey(
                decrypt($user->two_factor_secret),
                $code,
                2
            );

            if ($valid) {
                return $this->finalizeLogin($request, $user);
            }
        }

        // Try recovery code (any format)
        if ($user->useRecoveryCode($code)) {
            return $this->finalizeLogin($request, $user);
        }

        return back()->withErrors(['code' => 'El código ingresado es incorrecto o ha expirado.']);
    }

    private function finalizeLogin(Request $request, User $user)
    {
        $remember = $request->session()->pull('2fa:remember', false);
        $request->session()->forget('2fa:user_id');

        return app(AuthController::class)->completeLogin($request, $user, $remember);
    }

    // ─── Setup (from profile) ─────────────────────────────────────────────────

    public function setup(Request $request)
    {
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            return back()->withErrors(['2fa' => 'El 2FA ya está activado en su cuenta.']);
        }

        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->dni ?? $user->email ?? 'usuario',
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeSvg = base64_encode($writer->writeString($qrCodeUrl));

        return back()->with([
            '2fa_secret' => $secret,
            '2fa_qr' => $qrCodeSvg,
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $user = Auth::user();

        if (!$user->two_factor_secret || $user->hasTwoFactorEnabled()) {
            return back()->withErrors(['code' => 'Inicie el proceso de configuración primero.']);
        }

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code,
            2
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'El código es incorrecto. Escanee el QR nuevamente.']);
        }

        $plainCodes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn($c) => hash('sha256', $c), $plainCodes);

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ])->save();

        return back()->with([
            'recovery_codes' => $plainCodes,
            'success' => 'La autenticación de dos factores ha sido activada correctamente.',
        ]);
    }

    public function disable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return back()->withErrors(['code' => 'El 2FA no está activado en su cuenta.']);
        }

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code,
            2
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'El código es incorrecto. No se pudo desactivar el 2FA.']);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return back()->with('success', 'La autenticación de dos factores ha sido desactivada.');
    }

    public function regenerateCodes(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ], [
            'code.required' => 'El código es obligatorio.',
            'code.digits' => 'El código debe tener 6 dígitos.',
        ]);

        $user = Auth::user();

        if (!$user->hasTwoFactorEnabled()) {
            return back()->withErrors(['code' => 'El 2FA no está activado.']);
        }

        $valid = $this->google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->code,
            2
        );

        if (!$valid) {
            return back()->withErrors(['code' => 'El código es incorrecto.']);
        }

        $plainCodes = $this->generateRecoveryCodes();
        $hashedCodes = array_map(fn($c) => hash('sha256', $c), $plainCodes);

        $user->forceFill([
            'two_factor_recovery_codes' => json_encode($hashedCodes),
        ])->save();

        return back()->with([
            'recovery_codes' => $plainCodes,
            'success' => 'Nuevos códigos de recuperación generados.',
        ]);
    }

    // ─── Admin override ───────────────────────────────────────────────────────

    public function adminReset(Request $request, User $user)
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return response()->json([
            'message' => 'El 2FA del usuario ha sido desactivado correctamente.',
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function generateRecoveryCodes(int $count = 8): array
    {
        return array_map(
            fn() => strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)),
            range(1, $count)
        );
    }
}
