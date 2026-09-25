<?php

namespace App\Services\Planilla;

use App\Models\DreConfiguracion;
use App\Models\PlanillaDetalle;
use App\Models\PlanillaPeriodo;
use App\Models\PlanillaTardanza;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Arma la boleta de pago de un trabajador a partir de la planilla ya
 * calculada (`planilla_detalles` + `planilla_detalle_items`).
 *
 * Es la única fuente de verdad del documento: tanto el JSON que consume la
 * vista previa en pantalla como la plantilla Blade del PDF se construyen
 * con {@see self::componer()}, de modo que nunca se desincronizan.
 *
 * No persiste nada: el código de boleta se deriva de la posición del
 * trabajador en el detalle del periodo, así que recalcular la planilla
 * reordena los códigos sin dejar registros huérfanos.
 */
class BoletaService
{
    /**
     * Jornada mensual de referencia. Es el mismo divisor que usa
     * {@see TardanzaService::calcular()} para el valor del día (ingresos / 30).
     */
    public const DIAS_MES = 30;

    /**
     * El módulo administra exclusivamente planillas CAS mensuales
     * (ver PlanillaController::REGIMEN_PLANILLA).
     */
    public const PERIODICIDAD = 'Mensual';

    public const CONTRATO_INDETERMINADO = 'INDETERMINADO';

    public const CONTRATO_FIJO = 'FIJO';

    /**
     * Ancho en píxeles de la copia reducida del logo usada en el PDF. El
     * original mide 1728×1537 y DomPDF tarda ~1.2 s solo en decodificarlo,
     * lo que domina el costo de una descarga masiva.
     */
    private const LOGO_ANCHO_PDF = 300;

    /**
     * Logo de la entidad en base64, listo para incrustar en la plantilla.
     *
     * Reduce el PNG original a {@see self::LOGO_ANCHO_PDF} de ancho y cachea
     * el resultado en disco: la boleta mide 62 px de ancho, así que la copia
     * reducida es visualmente idéntica pero unas 30 veces más barata de
     * decodificar. Devuelve null si el logo no existe.
     */
    public function logoBase64(): ?string
    {
        $original = public_path('images/logo.png');

        if (!is_file($original)) {
            return null;
        }

        $reducido = $this->logoReducido($original);

        return base64_encode(file_get_contents($reducido));
    }

    /**
     * Copia reducida del logo, regenerada solo si el original cambió.
     *
     * El canal alfa se aplana contra blanco a propósito: DomPDF guarda las
     * imágenes con alfa en un SMask aparte, lo que triplica el peso del PDF
     * (381 KB frente a 25 KB) sin mejorar nada sobre papel blanco.
     */
    private function logoReducido(string $original): string
    {
        $destino = storage_path('app/planillas/logo-boleta.png');

        if (is_file($destino) && filemtime($destino) >= filemtime($original)) {
            return $destino;
        }

        $directorio = dirname($destino);

        if (!is_dir($directorio)) {
            mkdir($directorio, 0755, true);
        }

        $fuente = @imagecreatefrompng($original);

        if (!$fuente) {
            // Sin GD no se puede reducir: se usa el original tal cual.
            return $original;
        }

        $ancho = imagesx($fuente);
        $alto = imagesy($fuente);
        $nuevoAncho = min(self::LOGO_ANCHO_PDF, $ancho);
        $nuevoAlto = (int) round($alto * ($nuevoAncho / $ancho));

        // Fondo blanco opaco: fuerza la salida a truecolor sin alfa.
        $reducido = imagecreatetruecolor($nuevoAncho, $nuevoAlto);
        $blanco = imagecolorallocate($reducido, 255, 255, 255);
        imagefilledrectangle($reducido, 0, 0, $nuevoAncho, $nuevoAlto, $blanco);
        imagealphablending($reducido, true);
        imagecopyresampled($reducido, $fuente, 0, 0, 0, 0, $nuevoAncho, $nuevoAlto, $ancho, $alto);
        imagepng($reducido, $destino, 9);

        imagedestroy($fuente);
        imagedestroy($reducido);

        return $destino;
    }

    /**
     * Listado de boletas de un periodo para la tabla de la interfaz.
     * Cada fila ya trae el código de boleta y los totales del detalle.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(PlanillaPeriodo $periodo): array
    {
        $dias = $this->diasLaboradosPorEmpleado($periodo);

        return $this->detallesOrdenados($periodo)
            ->map(fn (PlanillaDetalle $detalle, int $indice) => [
                'detalle_id' => $detalle->id,
                'codigo_boleta' => $this->codigoBoleta($periodo, $indice),
                'dni' => $detalle->employee?->dni,
                'apellidos' => $detalle->employee?->apellidos,
                'nombres' => $detalle->employee?->nombres,
                'apellidos_nombres' => $detalle->employee?->nombre_completo,
                'cargo' => $detalle->employee?->cargo,
                'dias_laborados' => $dias[$detalle->employee_id] ?? self::DIAS_MES,
                'total_ingresos' => (float) $detalle->total_ingresos,
                'total_descuentos' => (float) $detalle->total_descuentos,
                'neto_pagar' => (float) $detalle->neto_pagar,
            ])
            ->all();
    }

    /**
     * View-model completo de la boleta de un trabajador.
     *
     * @return array<string, mixed>
     */
    public function armar(PlanillaDetalle $detalle): array
    {
        $detalle->loadMissing('periodo');

        $periodo = $detalle->periodo;

        $indice = $this->detallesOrdenados($periodo)
            ->search(fn (PlanillaDetalle $d) => $d->id === $detalle->id);

        return $this->componer(
            $detalle,
            $indice === false ? 0 : $indice,
            $this->diasLaboradosPorEmpleado($periodo)
        );
    }

    /**
     * Boletas de todo el periodo, en el orden en que se asignan los códigos.
     * Resuelve el orden y los días laborados una sola vez, así que usarla
     * para el ZIP evita una consulta por trabajador.
     *
     * @return array<int, array<string, mixed>>
     */
    public function armarTodas(PlanillaPeriodo $periodo): array
    {
        $dias = $this->diasLaboradosPorEmpleado($periodo);

        return $this->detallesOrdenados($periodo)
            ->map(fn (PlanillaDetalle $detalle, int $indice) => $this->componer($detalle, $indice, $dias))
            ->all();
    }

    /**
     * @return array{ruc: string, razon_social: string, nombre_abreviado: ?string, direccion: string}
     */
    public function empresa(): array
    {
        $config = DreConfiguracion::actual();

        return [
            'ruc' => (string) $config->ruc,
            'razon_social' => (string) $config->razon_social,
            'nombre_abreviado' => $config->nombre_abreviado,
            'direccion' => (string) $config->direccion,
        ];
    }

    /**
     * Código correlativo dentro del periodo: BOL-2026-09-0001.
     * El correlativo es la posición del trabajador en el detalle del periodo.
     */
    public function codigoBoleta(PlanillaPeriodo $periodo, int $indice): string
    {
        return sprintf(
            'BOL-%d-%02d-%04d',
            (int) $periodo->anio,
            (int) $periodo->mes,
            $indice + 1
        );
    }

    /**
     * Días laborados del mes: la jornada de referencia menos las faltas no
     * justificadas registradas en el periodo. Los minutos de tardanza no
     * descuentan días, solo minutos (ver PlanillaGenerador::registroTardanzas).
     */
    public function diasLaborados(PlanillaPeriodo $periodo, ?string $employeeId): int
    {
        if (!$employeeId) {
            return self::DIAS_MES;
        }

        return $this->diasLaboradosPorEmpleado($periodo)[$employeeId] ?? self::DIAS_MES;
    }

    /**
     * Un contrato con fecha de fin es fijo; sin ella, indeterminado.
     */
    public function tipoContrato(?Carbon $fechaFin): string
    {
        return $fechaFin ? self::CONTRATO_FIJO : self::CONTRATO_INDETERMINADO;
    }

    /**
     * Construye el view-model de la boleta a partir de un detalle, su posición
     * dentro del periodo y el mapa de días laborados ya resuelto.
     *
     * @param  array<string, int>  $dias  días laborados indexados por employee_id
     * @return array<string, mixed>
     */
    private function componer(PlanillaDetalle $detalle, int $indice, array $dias): array
    {
        $detalle->loadMissing([
            'items',
            'employee.person',
            'employee.position',
            'employee.contractType',
            'employee.payrollProfile.regimenPensionario',
            'employee.payrollProfile.banco',
        ]);

        $periodo = $detalle->periodo;
        $empleado = $detalle->employee;
        $perfil = $empleado?->payrollProfile;

        return [
            'empresa' => $this->empresa(),
            'periodo' => [
                'id' => $periodo->id,
                'anio' => (int) $periodo->anio,
                'mes' => (int) $periodo->mes,
                'nombre_mes' => PlanillaPeriodo::MESES[$periodo->mes] ?? (string) $periodo->mes,
                'nombre_periodo' => $periodo->nombre_periodo,
                'estado' => $periodo->estado,
            ],
            'trabajador' => [
                'codigo_boleta' => $this->codigoBoleta($periodo, $indice),
                'dni' => $empleado?->dni,
                'apellidos' => $empleado?->apellidos,
                'nombres' => $empleado?->nombres,
                'apellidos_nombres' => $empleado?->nombre_completo,
                'fecha_ingreso' => $empleado?->fecha_ingreso?->toDateString(),
                'fecha_inicio_contrato' => $empleado?->fecha_inicio_contrato?->toDateString(),
                'fecha_fin_contrato' => $empleado?->fecha_fin_contrato?->toDateString(),
                'contrato' => $this->tipoContrato($empleado?->fecha_fin_contrato),
            ],
            'relacion_laboral' => [
                'cargo' => $empleado?->cargo,
                'regimen' => $empleado?->tipo_contrato,
                'periodicidad' => self::PERIODICIDAD,
                'sistema_pensiones' => $perfil?->regimenPensionario?->nombre,
                'cuspp' => $perfil?->cuspp,
                'dias_laborados' => $dias[$detalle->employee_id] ?? self::DIAS_MES,
                'cuenta_ahorro' => $perfil?->cuenta_ahorro,
                'banco' => $perfil?->banco?->nombre,
            ],
            'remuneraciones' => $this->itemsPorTipo($detalle, 'INGRESO'),
            'retenciones' => $this->itemsPorTipo($detalle, 'DESCUENTO'),
            'aportaciones' => $this->itemsPorTipo($detalle, 'APORTACION'),
            'totales' => [
                'remuneraciones' => (float) $detalle->total_ingresos,
                'retenciones' => (float) $detalle->total_descuentos,
                'aportaciones' => (float) $detalle->total_aportaciones,
                'neto_pagar' => (float) $detalle->neto_pagar,
            ],
            'fecha_emision' => Carbon::now()->toDateTimeString(),
        ];
    }

    /**
     * Días laborados de todos los trabajadores del periodo en una sola
     * consulta: jornada de referencia menos las faltas no justificadas.
     *
     * @return array<string, int> indexado por employee_id
     */
    private function diasLaboradosPorEmpleado(PlanillaPeriodo $periodo): array
    {
        $faltas = PlanillaTardanza::where('periodo_id', $periodo->id)
            ->noJustificadas()
            ->groupBy('employee_id')
            ->selectRaw('employee_id, SUM(dias) as total_dias')
            ->pluck('total_dias', 'employee_id');

        $mapa = [];

        foreach ($faltas as $employeeId => $totalDias) {
            $mapa[$employeeId] = max(0, self::DIAS_MES - (int) $totalDias);
        }

        return $mapa;
    }

    /**
     * Conceptos de un tipo (INGRESO / DESCUENTO / APORTACION) listos para
     * pintar, conservando el orden de cálculo.
     *
     * @return array<int, array<string, mixed>>
     */
    private function itemsPorTipo(PlanillaDetalle $detalle, string $tipo): array
    {
        return $detalle->items
            ->where('tipo', $tipo)
            ->map(fn ($item) => [
                'descripcion' => $item->descripcion,
                'porcentaje' => $item->porcentaje !== null ? (float) $item->porcentaje : null,
                'monto' => (float) $item->monto,
            ])
            ->values()
            ->all();
    }

    /**
     * Detalle del periodo en el orden en que se asignan los códigos de boleta:
     * alfabético por apellidos, igual que el detalle de planilla y el Excel.
     *
     * @return Collection<int, PlanillaDetalle>
     */
    private function detallesOrdenados(PlanillaPeriodo $periodo): Collection
    {
        return $periodo->detalles()
            ->with(['periodo', 'employee.person', 'employee.position'])
            ->get()
            ->sortBy(fn (PlanillaDetalle $d) => $d->employee?->apellidos ?? '', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }
}
