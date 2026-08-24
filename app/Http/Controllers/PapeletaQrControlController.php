<?php

namespace App\Http\Controllers;

use App\Models\PapeletaRequest;
use App\Models\Person;
use App\Services\PapeletaExecutionPdfService;
use App\Services\ReniecService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PapeletaQrControlController extends Controller
{
    public function __construct(private readonly PapeletaExecutionPdfService $executionPdf)
    {
    }

    private function papeleta(string $token): PapeletaRequest
    {
        return PapeletaRequest::with(['employee.person', 'reason'])->where('qr_token', $token)->firstOrFail();
    }

    public function show(string $token)
    {
        $papeleta = $this->papeleta($token);

        return Inertia::render('Papeletas/QrControl', [
            'token' => $token,
            'papeleta' => [
                'numero_papeleta' => $papeleta->numero_papeleta,
                'empleado_nombre' => $papeleta->employee?->full_name,
                'motivo_nombre' => $papeleta->reason?->nombre,
                'motivo_tipo' => $papeleta->reason?->tipo,
                'salida_real_at' => $papeleta->salida_real_at?->format('d/m/Y H:i'),
                'retorno_real_at' => $papeleta->retorno_real_at?->format('d/m/Y H:i'),
                'destino_firmado_at' => $papeleta->destino_firmado_at?->format('d/m/Y H:i'),
                'destino_firmante_nombre' => $papeleta->destino_firmante_nombre,
                'destino_firmante_cargo' => $papeleta->destino_firmante_cargo,
            ],
        ]);
    }

    /** Public, token-scoped DNI lookup used only by the destination QR screen. */
    public function consultarDni(Request $request, string $token, ReniecService $reniec)
    {
        $this->papeleta($token);
        $data = $request->validate(['dni' => ['required', 'regex:/^\d{8}$/']]);

        // Prefer the institutional registry, then use the configured RENIEC provider.
        $local = Person::findByDni($data['dni']);
        if ($local) {
            return response()->json(['success' => true, 'data' => [
                'dni' => $local->dni,
                'nombre_completo' => $local->nombre_full,
            ]]);
        }

        return response()->json($reniec->consultarDni($data['dni']));
    }

    public function salida(Request $request, string $token)
    {
        $papeleta = $this->papeleta($token);
        abort_unless($papeleta->estado === 'APROBADO', 422, 'La papeleta aún no está aprobada.');
        abort_if($papeleta->salida_real_at, 422, 'La salida ya fue registrada.');
        $papeleta->update(['salida_real_at' => now()]);
        $this->executionPdf->refresh($papeleta->fresh());
        return back()->with('ok', 'Salida registrada a las '.now()->format('H:i').'.');
    }

    public function retorno(Request $request, string $token)
    {
        $papeleta = $this->papeleta($token);
        abort_unless($papeleta->salida_real_at, 422, 'Primero debe registrar la salida.');
        abort_if($papeleta->retorno_real_at, 422, 'El retorno ya fue registrado.');
        $papeleta->update(['retorno_real_at' => now()]);
        $this->executionPdf->refresh($papeleta->fresh());
        return back()->with('ok', 'Retorno registrado a las '.now()->format('H:i').'.');
    }

    public function destino(Request $request, string $token, ReniecService $reniec)
    {
        $papeleta = $this->papeleta($token);
        abort_unless($papeleta->reason?->tipo === 'comision', 422, 'La constancia de destino solo aplica a comisiones.');
        abort_unless($papeleta->salida_real_at, 422, 'Primero debe registrar la salida.');
        abort_if($papeleta->destino_firmado_at, 422, 'La constancia de destino ya fue registrada.');
        $data = $request->validate([
            'dni' => ['required', 'regex:/^\d{8}$/'],
            'cargo' => 'required|string|max:150',
            'firma' => 'required|string|max:700000',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:10000',
        ]);

        // Never trust the name sent by the browser: resolve it again on the server.
        $local = Person::findByDni($data['dni']);
        $resultado = $local
            ? ['success' => true, 'data' => ['nombre_completo' => $local->nombre_full]]
            : $reniec->consultarDni($data['dni']);
        if (!($resultado['success'] ?? false) || empty($resultado['data']['nombre_completo'])) {
            return back()->withErrors(['dni' => 'No se pudo validar el DNI del responsable de destino.']);
        }
        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $data['firma'])) {
            return back()->withErrors(['firma' => 'La firma táctil no tiene un formato válido.']);
        }
        [, $encoded] = explode(',', $data['firma'], 2);
        $binary = base64_decode($encoded, true);
        if ($binary === false || strlen($binary) < 100) {
            return back()->withErrors(['firma' => 'No se pudo procesar la firma táctil.']);
        }
        $path = 'papeletas/destino/'.$papeleta->id.'.png';
        Storage::disk('local')->put($path, $binary);
        $papeleta->update([
            'destino_firmante_dni' => $data['dni'],
            'destino_firmante_nombre' => $resultado['data']['nombre_completo'],
            'destino_firmante_cargo' => $data['cargo'],
            'destino_latitude' => $data['latitude'],
            'destino_longitude' => $data['longitude'],
            'destino_gps_accuracy_m' => $data['accuracy'] ?? null,
            'destino_gps_at' => now(),
            'destino_firma_path' => $path, 'destino_firmado_at' => now(),
        ]);
        $this->executionPdf->refresh($papeleta->fresh());
        return back()->with('ok', 'Constancia de la entidad destino registrada.');
    }
}
