<?php

namespace App\Http\Controllers;

use App\Models\PapeletaRequest;
use App\Services\PapeletaExecutionPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
        return view('papeletas.qr-control', ['papeleta' => $this->papeleta($token), 'token' => $token]);
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

    public function destino(Request $request, string $token)
    {
        $papeleta = $this->papeleta($token);
        abort_unless($papeleta->reason?->tipo === 'comision', 422, 'La constancia de destino solo aplica a comisiones.');
        abort_unless($papeleta->salida_real_at, 422, 'Primero debe registrar la salida.');
        $data = $request->validate([
            'nombre' => 'required|string|max:150',
            'cargo' => 'required|string|max:150',
            'firma' => 'required|string|max:700000',
        ]);
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
            'destino_firmante_nombre' => $data['nombre'], 'destino_firmante_cargo' => $data['cargo'],
            'destino_firma_path' => $path, 'destino_firmado_at' => now(),
        ]);
        $this->executionPdf->refresh($papeleta->fresh());
        return back()->with('ok', 'Constancia de la entidad destino registrada.');
    }
}
