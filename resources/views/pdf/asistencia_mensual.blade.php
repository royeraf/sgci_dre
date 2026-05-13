<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Listado de Marcaciones</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 6mm 5mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 6.5px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* ── Cabecera institucional ── */
        .inst-header {
            text-align: center;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 1px;
        }
        .periodo-titulo {
            text-align: center;
            font-size: 7px;
            margin-bottom: 3px;
        }

        /* ── Datos del empleado ── */
        .emp-bar {
            background: #1e3a5f;
            color: #fff;
            padding: 2px 5px;
            margin-bottom: 3px;
            font-size: 7px;
        }
        .emp-bar table { width: 100%; border: none; border-collapse: collapse; }
        .emp-bar td    { border: none; padding: 1px 3px; color: #fff; font-size: 7px; }
        .emp-bar .sep  { color: #93c5fd; }

        /* ── Tabla principal ── */
        table.marcas {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        table.marcas th,
        table.marcas td {
            border: 1px solid #888;
            padding: 1px 0;
            text-align: center;
            overflow: hidden;
            white-space: nowrap;
            font-size: 6px;
            height: 10px;
        }

        /* Fila nombres de día */
        .r-dia th               { background: #1e3a5f; color: #fff; font-size: 6.5px; font-weight: bold; height: 9px; }
        .r-dia .h-fin           { background: #374151; color: #d1d5db; }
        .r-dia .h-fer           { background: #d97706; color: #fff; }

        /* Fila fechas */
        .r-fecha td             { background: #dbeafe; font-weight: bold; font-size: 6px; height: 8px; }
        .r-fecha .d-fin         { background: #e5e7eb; color: #9ca3af; }
        .r-fecha .d-fer         { background: #fef3c7; color: #92400e; font-weight: bold; }

        /* Filas de marcas */
        .r-marca td             { height: 10px; font-size: 6.5px; }
        .r-marca .m-fin         { background: #f9fafb; }
        .r-marca .m-fer-lbl     { background: #fef9c3; color: #92400e; font-weight: bold; font-size: 6px; }
        .r-marca .m-fer-empty   { background: #fef9c3; }
        .r-marca .m-obs         { font-size: 5.5px; color: #4b5563; background: #f8fafc; }

        /* Fila tardanza */
        .r-tard td              { height: 8px; font-size: 6px; background: #fff7ed; }
        .r-tard .tard-val       { color: #dc2626; font-weight: bold; }

        /* ── Resumen inferior ── */
        .summary-bar {
            margin-top: 4px;
            display: table;
            width: 100%;
        }
        .summary-left {
            display: table-cell;
            font-size: 7px;
            vertical-align: bottom;
            padding-left: 2px;
            color: #374151;
        }
        .summary-right {
            display: table-cell;
            text-align: right;
            vertical-align: bottom;
        }
        .total-box {
            display: inline-block;
            border: 1.5px solid #000;
            padding: 2px 8px 2px 4px;
            font-size: 7.5px;
            font-weight: bold;
        }
        .total-box .lbl { font-size: 6.5px; font-weight: bold; }
        .total-box .val { font-size: 11px; font-weight: bold; margin-left: 6px; }

        .footer {
            text-align: center;
            font-size: 5.5px;
            color: #6b7280;
            margin-top: 4px;
            border-top: 1px solid #e5e7eb;
            padding-top: 2px;
        }
    </style>
</head>
<body>

{{-- ── Cabecera ── --}}
<div class="inst-header">Dirección Regional de Educación Huánuco</div>
<div class="periodo-titulo">
    Listado de Marcaciones &mdash; Del {{ $periodo['desde'] }} al {{ $periodo['hasta'] }}
</div>

{{-- ── Barra del empleado ── --}}
<div class="emp-bar">
    <table>
        <tr>
            <td style="width:10%"><b>{{ $employee->dni }}</b></td>
            <td style="width:28%"><b>{{ strtoupper($employee->apellidos . ', ' . $employee->nombres) }}</b></td>
            <td style="width:27%">{{ strtoupper($employee->direction?->nombre ?? '-') }}</td>
            <td style="width:20%">{{ strtoupper($employee->contractType?->nombre ?? '-') }}</td>
            <td style="width:15%; text-align:right; color:#93c5fd; font-size:6px">
                @if($horario)
                    {{ substr($horario->entrada_manana,0,5) }}-{{ substr($horario->salida_manana,0,5) }}
                    @if($horario->entrada_tarde)
                        / {{ substr($horario->entrada_tarde,0,5) }}-{{ substr($horario->salida_tarde,0,5) }}
                    @endif
                @endif
            </td>
        </tr>
    </table>
</div>

{{-- ── Tabla de marcaciones ── --}}
<table class="marcas">

    {{-- Fila 1: Nombres de día --}}
    <tr class="r-dia">
        @foreach($dias as $dia)
        <th class="{{ $dia['es_fin_semana'] ? 'h-fin' : ($dia['es_feriado'] ? 'h-fer' : '') }}">
            {{ strtoupper(substr($dia['dow_label'], 0, 3)) }}
        </th>
        @endforeach
    </tr>

    {{-- Fila 2: Fechas dd-mm --}}
    <tr class="r-fecha">
        @foreach($dias as $dia)
        <td class="{{ $dia['es_fin_semana'] ? 'd-fin' : ($dia['es_feriado'] ? 'd-fer' : '') }}">
            {{ $dia['fecha_label'] }}
        </td>
        @endforeach
    </tr>

    {{-- Filas 3-10: Hasta 4 pares entrada/salida --}}
    @php
        $campos = [
            'entrada', 'salida_mediodia',
            'retorno_mediodia', 'salida',
            'entrada_3', 'salida_3',
            'entrada_4', 'salida_4',
        ];
        // Determinar cuántas filas mostrar: solo hasta el último campo con algún dato
        $maxFila = 4; // mínimo 4 filas (2 pares)
        foreach($dias as $dia) {
            if (!$dia['marca']) continue;
            foreach([4,5,6,7] as $idx) {
                if (!empty($dia['marca'][$campos[$idx]])) {
                    $maxFila = max($maxFila, $idx + 1);
                }
            }
        }
    @endphp
    @for($fi = 0; $fi < $maxFila; $fi++)
    <tr class="r-marca">
        @foreach($dias as $dia)
            @if($dia['es_fin_semana'])
                <td class="m-fin"></td>
            @elseif($dia['es_feriado'])
                <td class="{{ $fi === 0 ? 'm-fer-lbl' : 'm-fer-empty' }}">
                    @if($fi === 0){{ $dia['feriado_tipo'] === 'NO_LABORABLE' ? 'NO LAB' : 'FER' }}@endif
                </td>
            @else
                @php $t = $dia['marca'] ? ($dia['marca'][$campos[$fi]] ?? null) : null; @endphp
                <td>{{ $t ? substr($t, 0, 5) : '' }}</td>
            @endif
        @endforeach
    </tr>
    @endfor

    {{-- Fila 7: Observaciones --}}
    <tr class="r-marca">
        @foreach($dias as $dia)
            @if($dia['es_fin_semana'])
                <td class="m-fin"></td>
            @elseif($dia['es_feriado'])
                <td class="m-fer-empty"></td>
            @else
                <td class="m-obs">
                    {{ ($dia['marca']['observaciones'] ?? '') ? mb_substr($dia['marca']['observaciones'], 0, 9) : '' }}
                </td>
            @endif
        @endforeach
    </tr>

    {{-- Fila 8: Tardanza (minutos) --}}
    <tr class="r-tard">
        @foreach($dias as $dia)
            @php $tard = (!$dia['es_fin_semana'] && !$dia['es_feriado']) ? $dia['calculo']['tardanza'] : 0; @endphp
            <td class="{{ $tard > 0 ? 'tard-val' : '' }}">
                {{ $tard > 0 ? $tard : '' }}
            </td>
        @endforeach
    </tr>

</table>

{{-- ── Resumen ── --}}
<div class="summary-bar">
    <div class="summary-left">
        Días laborables: <b>{{ $resumen['dias_laborables'] }}</b> &nbsp;|&nbsp;
        Con asistencia: <b>{{ $resumen['dias_asistidos'] }}</b> &nbsp;|&nbsp;
        Sin marca: <b>{{ $resumen['dias_sin_marca'] }}</b> &nbsp;|&nbsp;
        H. Normales: <b>{{ number_format($resumen['total_horas_normales'], 2) }}</b> &nbsp;|&nbsp;
        Min. Extras: <b>{{ $resumen['total_min_extras'] }}</b>
    </div>
    <div class="summary-right">
        <div class="total-box">
            <span class="lbl">TOTAL TARDANZAS</span>
            <span class="val">{{ $resumen['total_tardanza_min'] }}</span>
        </div>
    </div>
</div>

<div class="footer">
    Generado el {{ now()->format('d/m/Y H:i:s') }} &nbsp;|&nbsp; Sistema de Control &mdash; DRE Huánuco
</div>

</body>
</html>
