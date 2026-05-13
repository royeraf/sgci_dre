<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\MarcaAsistencia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AsistenciaController extends Controller
{
    /**
     * Página principal del módulo de asistencia.
     */
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee?->load('person', 'direction', 'office', 'position');

        // Para admin/RRHH, listar empleados disponibles para filtrar
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        $employees = $isAdmin
            ? Employee::with('person')->where('estado', 'ACTIVO')->get()->map(fn($e) => [
                'id'             => $e->id,
                'nombre_completo'=> trim($e->full_name),
                'dni'            => $e->dni,
            ])->sortBy('nombre_completo')->values()
            : collect();

        return Inertia::render('Asistencia/Index', [
            'myEmployee' => $employee,
            'isAdmin'    => $isAdmin,
            'employees'  => $employees,
        ]);
    }

    /**
     * API: marcas del empleado autenticado (o de otro si admin).
     */
    public function getMarcas(Request $request)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        // Determinar el empleado a consultar
        if ($isAdmin && $request->filled('employee_id')) {
            $employee = Employee::findOrFail($request->employee_id);
        } else {
            $employee = $user->employee;
        }

        if (!$employee) {
            return response()->json([]);
        }

        $year  = $request->input('year',  now()->year);
        $month = $request->input('month', now()->month);

        $marcas = MarcaAsistencia::where('employee_id', $employee->id)
            ->delMes($year, $month)
            ->orderBy('fecha')
            ->get();

        return response()->json($marcas);
    }

    /**
     * API: guardar o actualizar marca de un día (solo admin/RRHH).
     */
    public function storeMarca(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos para registrar marcas.'], 403);
        }

        $validated = $request->validate([
            'employee_id'      => 'required|exists:employees,id',
            'fecha'            => 'required|date',
            'entrada'          => 'nullable|date_format:H:i',
            'salida_mediodia'  => 'nullable|date_format:H:i',
            'retorno_mediodia' => 'nullable|date_format:H:i',
            'salida'           => 'nullable|date_format:H:i',
            'entrada_3'        => 'nullable|date_format:H:i',
            'salida_3'         => 'nullable|date_format:H:i',
            'entrada_4'        => 'nullable|date_format:H:i',
            'salida_4'         => 'nullable|date_format:H:i',
            'observaciones'    => 'nullable|string|max:500',
        ]);

        $validated['registrado_por'] = $user->id;

        $marca = MarcaAsistencia::updateOrCreate(
            ['employee_id' => $validated['employee_id'], 'fecha' => $validated['fecha']],
            $validated
        );

        return response()->json($marca->fresh(), 200);
    }

    /**
     * API: eliminar marca de un día (solo admin/RRHH).
     */
    public function deleteMarca(MarcaAsistencia $marca)
    {
        $user = Auth::user();

        if (!in_array($user->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos para eliminar marcas.'], 403);
        }

        $marca->delete();
        return response()->json(['message' => 'Marca eliminada correctamente.']);
    }

    // ===== FERIADOS =====

    public function getFeriados(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        return response()->json(
            Feriado::delAnio($year)->orderBy('fecha')->get()
        );
    }

    public function storeFeriado(Request $request)
    {
        if (!in_array(Auth::user()->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos.'], 403);
        }

        $validator = \Validator::make($request->all(), [
            'fecha'  => 'required|date|unique:feriados,fecha',
            'nombre' => 'required|string|max:200',
            'tipo'   => 'required|in:FERIADO,NO_LABORABLE',
        ], [
            'fecha.required'  => 'La fecha es obligatoria.',
            'fecha.date'      => 'La fecha no es válida.',
            'fecha.unique'    => 'Esta fecha ya está registrada como día no laborable.',
            'nombre.required' => 'El nombre o motivo es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 200 caracteres.',
            'tipo.required'   => 'El tipo es obligatorio.',
            'tipo.in'         => 'El tipo seleccionado no es válido.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Los datos ingresados no son válidos.',
                'errors'  => $validator->errors()->toArray(),
            ], 422);
        }

        $data = $validator->validated();
        $data['creado_por'] = Auth::id();

        return response()->json(Feriado::create($data), 201);
    }

    public function deleteFeriado(Feriado $feriado)
    {
        if (!in_array(Auth::user()->rol_id, ['ROL001', 'ROL009'])) {
            return response()->json(['message' => 'Sin permisos.'], 403);
        }

        $feriado->delete();
        return response()->json(['message' => 'Feriado eliminado.']);
    }

    /**
     * PDF: Reporte mensual de marcaciones por empleado.
     */
    public function reporteMensual(Request $request)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        if ($isAdmin && $request->filled('employee_id')) {
            $employee = Employee::with('person', 'direction', 'office', 'position', 'contractType', 'horario')->findOrFail($request->employee_id);
        } else {
            $employee = $user->employee?->load('person', 'direction', 'office', 'position', 'contractType', 'horario');
        }

        if (!$employee) {
            abort(404, 'Empleado no encontrado.');
        }

        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        $marcasRaw = MarcaAsistencia::where('employee_id', $employee->id)
            ->delMes($year, $month)
            ->get()
            ->keyBy(fn($m) => $m->fecha->format('Y-m-d'));

        // Mapa fecha => tipo  (para mostrar FER / NO LAB en el reporte)
        $feriadosSet = Feriado::delAnio($year)
            ->get()
            ->mapWithKeys(fn($f) => [$f->fecha->format('Y-m-d') => $f->tipo])
            ->toArray();

        // Hora de referencia para tardanza según horario del empleado
        $horario = $employee->horario_efectivo ?? Horario::getDefault();
        $refTime = $horario ? substr($horario->entrada_manana, 0, 5) : '08:00';

        $dias    = $this->buildDiasMes($year, $month, $marcasRaw, $feriadosSet, $refTime);
        $resumen = $this->calcularResumen($dias);

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        $totalDias  = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $data = [
            'employee' => $employee,
            'horario'  => $horario,
            'periodo'  => [
                'desde' => sprintf('01/%02d/%d', $month, $year),
                'hasta' => sprintf('%02d/%02d/%d', $totalDias, $month, $year),
                'mes'   => $monthNames[$month - 1],
                'year'  => $year,
            ],
            'dias'     => $dias,
            'resumen'  => $resumen,
        ];

        $pdf = Pdf::loadView('pdf.asistencia_mensual', $data)
            ->setPaper('a4', 'landscape');

        $filename = "marcaciones_{$employee->dni}_{$year}_{$month}.pdf";
        return $pdf->download($filename);
    }

    /**
     * PDF: Reporte anual de marcaciones por empleado.
     */
    public function reporteAnual(Request $request)
    {
        $user = Auth::user();
        $isAdmin = in_array($user->rol_id, ['ROL001', 'ROL009']);

        if ($isAdmin && $request->filled('employee_id')) {
            $employee = Employee::with('person', 'direction', 'office', 'position', 'contractType', 'horario')->findOrFail($request->employee_id);
        } else {
            $employee = $user->employee?->load('person', 'direction', 'office', 'position', 'contractType', 'horario');
        }

        if (!$employee) {
            abort(404, 'Empleado no encontrado.');
        }

        $year = (int) $request->input('year', now()->year);

        $marcasAnuales = MarcaAsistencia::where('employee_id', $employee->id)
            ->whereYear('fecha', $year)
            ->get()
            ->keyBy(fn($m) => $m->fecha->format('Y-m-d'));

        $feriadosSet = Feriado::delAnio($year)
            ->get()
            ->mapWithKeys(fn($f) => [$f->fecha->format('Y-m-d') => $f->tipo])
            ->toArray();

        $horario = $employee->horario_efectivo ?? Horario::getDefault();
        $refTime = $horario ? substr($horario->entrada_manana, 0, 5) : '08:00';

        $monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                       'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

        $meses   = [];
        $totales = ['dias_laborables' => 0, 'dias_asistidos' => 0, 'dias_sin_marca' => 0,
                    'total_tardanza_min' => 0, 'total_horas_normales' => 0.0, 'total_min_extras' => 0];

        for ($m = 1; $m <= 12; $m++) {
            $diasMes   = $this->buildDiasMes($year, $m, $marcasAnuales, $feriadosSet, $refTime);
            $resumenMes = $this->calcularResumen($diasMes);
            $tieneDatos = $resumenMes['dias_asistidos'] > 0;

            $meses[] = [
                'nombre'             => $monthNames[$m - 1],
                'numero'             => $m,
                'tiene_datos'        => $tieneDatos,
                'dias'               => $diasMes,
                'dias_laborables'    => $resumenMes['dias_laborables'],
                'dias_asistidos'     => $resumenMes['dias_asistidos'],
                'dias_sin_marca'     => $resumenMes['dias_sin_marca'],
                'total_tardanza_min' => $resumenMes['total_tardanza_min'],
                'total_horas_normales' => $resumenMes['total_horas_normales'],
                'total_min_extras'   => $resumenMes['total_min_extras'],
                'observacion'        => $resumenMes['total_tardanza_min'] > 0 ? 'Con tardanzas' : '',
            ];

            $totales['dias_laborables']      += $resumenMes['dias_laborables'];
            $totales['dias_asistidos']        += $resumenMes['dias_asistidos'];
            $totales['dias_sin_marca']        += $resumenMes['dias_sin_marca'];
            $totales['total_tardanza_min']    += $resumenMes['total_tardanza_min'];
            $totales['total_horas_normales']  += $resumenMes['total_horas_normales'];
            $totales['total_min_extras']      += $resumenMes['total_min_extras'];
        }

        $data = [
            'employee' => $employee,
            'year'     => $year,
            'meses'    => $meses,
            'totales'  => $totales,
        ];

        $pdf = Pdf::loadView('pdf.asistencia_anual', $data)
            ->setPaper('a4', 'portrait');

        $filename = "marcaciones_anual_{$employee->dni}_{$year}.pdf";
        return $pdf->download($filename);
    }

    // ===== HELPERS PRIVADOS =====

    /**
     * Construye el array de días del mes con marcas y cálculos.
     */
    private function buildDiasMes(int $year, int $month, $marcasIndexadas, array $feriadosSet = [], string $refTime = '08:00'): array
    {
        $dowLabels  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
        $totalDias  = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $dias       = [];

        for ($d = 1; $d <= $totalDias; $d++) {
            $fechaStr  = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $dow       = (int) date('w', strtotime($fechaStr));
            $esFin     = $dow === 0 || $dow === 6;
            $esFeriado   = array_key_exists($fechaStr, $feriadosSet);
            $feriadoTipo = $feriadosSet[$fechaStr] ?? null; // 'FERIADO' | 'NO_LABORABLE'
            $marca       = $marcasIndexadas->get($fechaStr);

            $calculo = ['tardanza' => 0, 'horas_normales' => 0.0, 'min_extras' => 0];
            if ($marca && !$esFin && !$esFeriado) {
                $calculo = $this->calcularMarca($marca, $refTime);
            }

            $dias[] = [
                'fecha'        => $fechaStr,
                'fecha_label'  => sprintf('%02d-%02d', $d, $month),
                'dow'          => $dow,
                'dow_label'    => $dowLabels[$dow],
                'es_fin_semana'=> $esFin,
                'es_domingo'   => $dow === 0,
                'es_feriado'   => $esFeriado,
                'feriado_tipo' => $feriadoTipo,
                'marca'        => $marca ? $marca->toArray() : null,
                'calculo'      => $calculo,
            ];
        }

        return $dias;
    }

    /**
     * Calcula tardanza, horas normales y minutos extras de una marca.
     * Hora de referencia: 07:30.
     */
    private function calcularMarca(MarcaAsistencia $marca, string $refTime = '08:00'): array
    {
        $refMin = $this->timeToMinutes($refTime);

        // Tardanza: basada en la primera entrada vs. hora de inicio del horario
        $tardanzaMin = 0;
        if ($marca->entrada) {
            $tardanzaMin = max(0, $this->timeToMinutes($marca->entrada) - $refMin);
        }

        // Sumar todos los pares entrada/salida disponibles
        $pairs = [
            [$marca->entrada,          $marca->salida_mediodia],
            [$marca->retorno_mediodia, $marca->salida],
            [$marca->entrada_3,        $marca->salida_3],
            [$marca->entrada_4,        $marca->salida_4],
        ];

        $totalMin = 0;
        $paresCompletos = 0;
        foreach ($pairs as [$e, $s]) {
            if ($e && $s) {
                $diff = $this->timeToMinutes($s) - $this->timeToMinutes($e);
                if ($diff > 0) { $totalMin += $diff; $paresCompletos++; }
            } elseif ($e && !$s) {
                // Par incompleto: sólo entrada, no sumar
            }
        }

        // Si solo hay Entrada 1 sin Salida 1 (par incompleto) y no hay más pares,
        // no podemos calcular horas (jornada en curso o datos incompletos)
        if ($paresCompletos === 0 && $marca->entrada && !$marca->salida_mediodia) {
            $totalMin = 0;
        }

        return [
            'tardanza'       => $tardanzaMin,
            'horas_normales' => min($totalMin, 480) / 60,
            'min_extras'     => max(0, $totalMin - 480),
        ];
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = explode(':', substr($time, 0, 5));
        return (int)$h * 60 + (int)$m;
    }

    private function calcularResumen(array $dias): array
    {
        $laborables      = 0;
        $asistidos       = 0;
        $sinMarca        = 0;
        $totalTardanza   = 0;
        $totalHorasNorm  = 0.0;
        $totalExtras     = 0;

        foreach ($dias as $dia) {
            if ($dia['es_fin_semana'] || $dia['es_feriado']) continue;
            $laborables++;
            if ($dia['marca']) {
                $asistidos++;
                $totalTardanza  += $dia['calculo']['tardanza'];
                $totalHorasNorm += $dia['calculo']['horas_normales'];
                $totalExtras    += $dia['calculo']['min_extras'];
            } else {
                $sinMarca++;
            }
        }

        return [
            'dias_laborables'    => $laborables,
            'dias_asistidos'     => $asistidos,
            'dias_sin_marca'     => $sinMarca,
            'total_tardanza_min' => $totalTardanza,
            'total_horas_normales' => $totalHorasNorm,
            'total_min_extras'   => $totalExtras,
        ];
    }
}
