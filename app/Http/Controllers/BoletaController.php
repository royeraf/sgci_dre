<?php

namespace App\Http\Controllers;

use App\Models\DreConfiguracion;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Services\Planilla\BoletaService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class BoletaController extends Controller
{
    public function __construct(private BoletaService $boletas)
    {
    }

    /**
     * Periodos que tienen planilla generada, para el selector de la pestaña.
     */
    public function index()
    {
        $periodos = PlanillaPeriodo::query()
            ->withCount('detalles')
            ->orderByDesc('anio')
            ->orderByDesc('mes')
            ->get()
            ->map(fn (PlanillaPeriodo $periodo) => [
                'id' => $periodo->id,
                'anio' => $periodo->anio,
                'mes' => $periodo->mes,
                'nombre_periodo' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
                'total_empleados' => $periodo->total_empleados,
                'total_neto' => (float) $periodo->total_neto,
                'boletas' => $periodo->detalles_count,
            ])
            ->values();

        return response()->json($periodos);
    }

    /**
     * Boletas de un periodo (tabla de trabajadores del módulo).
     */
    public function listar(string $periodoId)
    {
        $periodo = PlanillaPeriodo::withCount('detalles')->find($periodoId);

        if (!$periodo) {
            return response()->json(['message' => 'Periodo no encontrado'], 404);
        }

        if ($periodo->detalles_count === 0) {
            return response()->json([
                'message' => 'El periodo no tiene planilla generada. Genere la planilla desde la pestaña Planillas.',
            ], 422);
        }

        return response()->json([
            'periodo' => [
                'id' => $periodo->id,
                'nombre_periodo' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
                'total_empleados' => $periodo->total_empleados,
                'total_neto' => (float) $periodo->total_neto,
            ],
            'boletas' => $this->boletas->listar($periodo),
        ]);
    }

    /**
     * Datos de una boleta para la vista previa en pantalla.
     */
    public function show(PlanillaDetalle $detalle)
    {
        return response()->json($this->boletas->armar($detalle));
    }

    /**
     * Boleta en PDF de un trabajador.
     */
    public function pdf(PlanillaDetalle $detalle)
    {
        $boleta = $this->boletas->armar($detalle);

        $pdf = $this->render($boleta);

        return $pdf->stream('boleta_' . $boleta['trabajador']['codigo_boleta'] . '.pdf');
    }

    /**
     * Todas las boletas del periodo en un solo ZIP, un PDF por trabajador.
     */
    public function pdfZip(string $periodoId)
    {
        $periodo = PlanillaPeriodo::withCount('detalles')->find($periodoId);

        if (!$periodo) {
            return response()->json(['message' => 'Periodo no encontrado'], 404);
        }

        if ($periodo->detalles_count === 0) {
            return response()->json([
                'message' => 'El periodo no tiene planilla generada. Genere la planilla desde la pestaña Planillas.',
            ], 422);
        }

        $archivo = tempnam(sys_get_temp_dir(), 'boletas_zip_');

        $zip = new ZipArchive();

        if ($zip->open($archivo, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return response()->json(['message' => 'No se pudo crear el archivo ZIP'], 500);
        }

        $logo = $this->boletas->logoBase64();

        foreach ($this->boletas->armarTodas($periodo) as $boleta) {
            $pdf = $this->render($boleta, $logo);

            $zip->addFromString(
                'boleta_' . $boleta['trabajador']['codigo_boleta'] . '.pdf',
                $pdf->output()
            );
        }

        $zip->close();

        $nombre = Str::slug("boletas {$periodo->nombre_periodo}", '_') . '.zip';

        return response()->download($archivo, $nombre)->deleteFileAfterSend(true);
    }

    /**
     * Datos de la entidad emisora del encabezado de la boleta.
     */
    public function getConfiguracion()
    {
        return response()->json($this->boletas->empresa());
    }

    public function updateConfiguracion(Request $request)
    {
        $validated = $request->validate([
            'ruc' => ['required', 'string', 'regex:/^\d{11}$/'],
            'razon_social' => ['required', 'string', 'max:191'],
            'nombre_abreviado' => ['nullable', 'string', 'max:60'],
            'direccion' => ['required', 'string', 'max:255'],
        ], [
            'ruc.regex' => 'El RUC debe tener 11 dígitos.',
            'razon_social.required' => 'La razón social es obligatoria.',
            'direccion.required' => 'La dirección es obligatoria.',
        ]);

        $config = DreConfiguracion::actual();
        $config->update($validated);

        return response()->json([
            'message' => 'Datos de la empresa actualizados',
            'empresa' => $this->boletas->empresa(),
        ]);
    }

    /**
     * Renderiza una boleta en A4 vertical.
     *
     * El subsetting de fuentes se activa sobre la misma instancia que carga la
     * vista: sin él DomPDF embebe la fuente completa (~450 KB) en cada PDF.
     * Encadenar sobre una única instancia es lo que hace que la opción
     * sobreviva, porque el facade no la conserva entre llamadas estáticas.
     *
     * @param  array<string, mixed>  $boleta
     * @param  string|null  $logo  base64 del logo; se resuelve si no se pasa
     */
    private function render(array $boleta, ?string $logo = null)
    {
        $logo ??= $this->boletas->logoBase64();

        return Pdf::setOption('enable_font_subsetting', true)
            ->loadView('pdf.boleta_pago', compact('boleta', 'logo'))
            ->setPaper('a5', 'landscape');
    }
}
