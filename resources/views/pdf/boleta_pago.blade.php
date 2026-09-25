<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Boleta de Pago {{ $boleta['trabajador']['codigo_boleta'] }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 6mm 7mm;
        }
        /* La familia se fija en todos los descendientes: si un elemento se
           queda sin heredar, DomPDF cae a la fuente serif por defecto
           (Times) y la boleta se imprime con dos tipografías distintas.
           Tampoco usar `font-weight: 900`, que DomPDF no sabe resolver. */
        body, body * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            font-size: 7.6px;
            color: #000;
            line-height: 1.16;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        td, th {
            padding: 1.2px 4px;
            vertical-align: middle;
            word-wrap: break-word;
        }
        .grid {
            border: 1.2px solid #000;
        }
        .grid td {
            border: 0.6px solid #000;
        }
        /* Etiqueta de campo: fondo gris, versalitas, occupies ~26% de la celda */
        .label {
            width: 24%;
            background-color: #eceff3;
            font-weight: bold;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
        }
        /* Separador de sección dentro de la rejilla de datos */
        .sep {
            background-color: #d5dbe3;
            font-weight: bold;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 2px 4px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }
        .v { width: 26%; }

        /* Encabezado */
        .header-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1.2px;
        }
        .header-subtitle { font-size: 7.5px; }
        .header-period {
            font-size: 10px;
            font-weight: bold;
            margin-top: 1px;
        }
        .codigo {
            font-size: 11px;
            font-weight: bold;
            letter-spacing: 0.4px;
        }
        .codigo-label {
            background-color: #eceff3;
            font-size: 6.2px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }

        /* Conceptos */
        .conceptos { font-size: 7.6px; }
        .conceptos td {
            border: none;
            padding: 1.1px 4px;
        }
        .conceptos .monto {
            text-align: right;
            font-weight: bold;
            white-space: nowrap;
        }
        .total-row td {
            border-top: 1px solid #000;
            padding: 2px 4px;
            font-size: 7.6px;
            text-align: right;
            white-space: nowrap;
        }
        .total-label {
            font-size: 6.2px;
            text-transform: uppercase;
            color: #475569;
        }

        /* Neto */
        .neto {
            border: 1.2px solid #000;
            background-color: #eceff3;
            font-size: 8.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .neto-valor {
            border: 1.2px solid #000;
            font-size: 13px;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            padding: 2px 6px;
        }

        /* Firmas */
        .firmas td {
            border: none;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
            height: 15mm;
        }
        .firma-linea {
            display: block;
            border-top: 0.8px solid #000;
            margin: 0 4px 2px 4px;
        }
        .firma-nombre {
            font-size: 6.5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .firma-detalle { font-size: 6.2px; color: #475569; }
    </style>
</head>
<body>
@php
    $empresa = $boleta['empresa'];
    $periodo = $boleta['periodo'];
    $trabajador = $boleta['trabajador'];
    $laboral = $boleta['relacion_laboral'];
    $totales = $boleta['totales'];
    $esIndeterminado = $trabajador['contrato'] === \App\Services\Planilla\BoletaService::CONTRATO_INDETERMINADO;
    $sinDato = fn ($valor) => ($valor === null || $valor === '') ? '—' : $valor;
    $fecha = fn ($valor) => $valor ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '—';
    $pct = fn ($valor) => $valor === null ? '' : ' (' . number_format($valor * 100, 2) . '%)';
    $columnas = [
        ['titulo' => 'Remuneraciones', 'items' => $boleta['remuneraciones'], 'total' => $totales['remuneraciones']],
        ['titulo' => 'Retenciones / Descuentos', 'items' => $boleta['retenciones'], 'total' => $totales['retenciones']],
        ['titulo' => 'Aportaciones del Empleador', 'items' => $boleta['aportaciones'], 'total' => $totales['aportaciones']],
    ];
    $maxConceptos = max(array_map(fn ($c) => count($c['items']), $columnas));

    // Altura reservada a los datos, para que el bloque de firmas caiga siempre
    // en la misma posición haya uno o siete conceptos. La caja útil del A5
    // apaisado es 148 - 6 - 6 = 136 mm; 120 mm de datos dejan 15 mm de firma
    // más un pequeño aire. Si algún dia el contenido no cabe en 120 mm, la
    // celda crece y las firmas bajan (nunca se cortan ni salen a otra pagina).
    $altoDatos = 120;
@endphp

<!-- Todo lo anterior a las firmas, en un bloque de altura fija -->
<table style="width: 100%; border: none;">
    <tr>
        <td style="border: none; padding: 0; height: {{ $altoDatos }}mm; vertical-align: top;">

<!-- Encabezado: logo a la izquierda, título al centro, código a la derecha -->
<table style="border: none; margin-bottom: 2px;">
    <tr>
        <td style="width: 13%; border: none; text-align: left;">
            @if($logo)
                <img src="data:image/png;base64,{{ $logo }}" style="width: 34px; height: auto;">
            @endif
        </td>
        <td style="width: 62%; border: none; text-align: center;">
            <div class="header-title">BOLETA DE PAGO</div>
            <div class="header-subtitle">Planilla de pago personal CAS</div>
            <div class="header-period">{{ $periodo['nombre_mes'] }} - {{ $periodo['anio'] }}</div>
        </td>
        <td style="width: 25%; border: none;">
            <table class="grid">
                <tr>
                    <td class="codigo-label" colspan="2">Código de boleta</td>
                </tr>
                <tr>
                    <td class="codigo text-center" colspan="2">{{ $trabajador['codigo_boleta'] }}</td>
                </tr>
                <tr>
                    <td class="codigo-label" style="width: 40%;">DNI</td>
                    <td class="text-bold text-center">{{ $sinDato($trabajador['dni']) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Empresa + trabajador + relación laboral en una sola rejilla -->
<table class="grid">
    <tr>
        <td class="sep" colspan="4">Datos de la empresa</td>
    </tr>
    <tr>
        <td class="label">RUC</td>
        <td class="text-bold" style="width: 26%;">{{ $sinDato($empresa['ruc']) }}</td>
        <td class="label" style="width: 24%;">Razón Social</td>
        <td class="text-bold">{{ $sinDato($empresa['razon_social']) }}</td>
    </tr>
    <tr>
        <td class="label">Dirección</td>
        <td colspan="3">{{ $sinDato($empresa['direccion']) }}</td>
    </tr>

    <tr>
        <td class="sep" colspan="4">Datos del trabajador</td>
    </tr>
    <tr>
        <td class="label">Apellidos y Nombres</td>
        <td colspan="3" class="text-bold">{{ $sinDato($trabajador['apellidos_nombres']) }}</td>
    </tr>
    <tr>
        <td class="label">DNI</td>
        <td style="width: 26%;">{{ $sinDato($trabajador['dni']) }}</td>
        <td class="label" style="width: 24%;">Código de boleta</td>
        <td>{{ $trabajador['codigo_boleta'] }}</td>
    </tr>
    <tr>
        <td class="label">Fecha de Ingreso</td>
        <td>{{ $fecha($trabajador['fecha_ingreso']) }}</td>
        <td class="label">Fecha Inicio Contrato</td>
        <td>{{ $fecha($trabajador['fecha_inicio_contrato']) }}</td>
    </tr>
    <tr>
        <td class="label">{{ $esIndeterminado ? 'Contrato' : 'Fecha Fin Contrato' }}</td>
        <td colspan="3">
            @if($esIndeterminado)
                <span class="text-bold">Indeterminado</span>
            @else
                {{ $fecha($trabajador['fecha_fin_contrato']) }}
                <span class="text-bold">(Contrato Fijo)</span>
            @endif
        </td>
    </tr>

    <tr>
        <td class="sep" colspan="4">Datos vinculados a la relación laboral</td>
    </tr>
    <tr>
        <td class="label">Cargo</td>
        <td style="width: 26%;" class="text-bold">{{ $sinDato($laboral['cargo']) }}</td>
        <td class="label" style="width: 24%;">Régimen</td>
        <td class="text-bold">{{ $sinDato($laboral['regimen']) }}</td>
    </tr>
    <tr>
        <td class="label">Periodicidad</td>
        <td class="text-bold">{{ $laboral['periodicidad'] }}</td>
        <td class="label">Sistema de Pensiones</td>
        <td class="text-bold">{{ $sinDato($laboral['sistema_pensiones']) }}</td>
    </tr>
    <tr>
        <td class="label">CUSPP</td>
        <td>{{ $sinDato($laboral['cuspp']) }}</td>
        <td class="label">Días Laborados</td>
        <td class="text-bold">{{ $laboral['dias_laborados'] }} / {{ \App\Services\Planilla\BoletaService::DIAS_MES }}</td>
    </tr>
    <tr>
        <td class="label">Banco</td>
        <td>{{ $sinDato($laboral['banco']) }}</td>
        <td class="label">Cuenta de Ahorro</td>
        <td>{{ $sinDato($laboral['cuenta_ahorro']) }}</td>
    </tr>
</table>

<!-- Conceptos: la columna más larga fija la altura de las tres -->
<table class="grid" style="margin-top: 2px;">
    <tr>
        @foreach($columnas as $columna)
            <th class="sep text-center" style="width: 33.33%;">{{ $columna['titulo'] }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach($columnas as $columna)
            <td style="padding: 0; vertical-align: top;">
                <table class="conceptos">
                    @foreach($columna['items'] as $item)
                        <tr>
                            <td style="width: 66%;">
                                {{ $item['descripcion'] }}<span style="color: #64748b;">{{ $pct($item['porcentaje']) }}</span>
                            </td>
                            <td class="monto">{{ number_format($item['monto'], 2) }}</td>
                        </tr>
                    @endforeach
                    @for($i = count($columna['items']); $i < $maxConceptos; $i++)
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                    @endfor
                    @if($maxConceptos === 0)
                        <tr>
                            <td colspan="2" class="text-center" style="color: #94a3b8;">—</td>
                        </tr>
                    @endif
                </table>
            </td>
        @endforeach
    </tr>
    <tr class="total-row">
        @foreach($columnas as $columna)
            <td>
                <span class="total-label">{{ $columna['titulo'] }}</span>
                {{ number_format($columna['total'], 2) }}
            </td>
        @endforeach
    </tr>
</table>

<!-- Neto y fecha de emisión -->
<table style="border: none; margin-top: 2px;">
    <tr>
        <td class="neto" style="width: 26%;">NETO A PAGAR</td>
        <td class="neto-valor" style="width: 24%;">S/ {{ number_format($totales['neto_pagar'], 2) }}</td>
        <td style="width: 30%; font-size: 6.5px;">Fecha de emisión:
            <span class="text-bold">{{ \Carbon\Carbon::parse($boleta['fecha_emision'])->format('d/m/Y H:i') }}</span>
        </td>
        <td style="font-size: 6.5px; text-align: right;">
            Periodo {{ $periodo['nombre_periodo'] }}
        </td>
    </tr>
</table>

        </td>
    </tr>
</table>

<!-- Firmas: banda fija de 15 mm anclada al fondo de la caja de contenido -->
<table class="firmas" style="margin-top: 2px;">
    <tr>
        <td style="width: 50%;">
            <span class="firma-linea"></span>
            <span class="firma-nombre">Firma del trabajador</span><br>
            <span class="firma-detalle">{{ $sinDato($trabajador['apellidos_nombres']) }}</span>
        </td>
        <td style="width: 50%;">
            <span class="firma-linea"></span>
            <span class="firma-nombre">Firma del empleador</span><br>
            <span class="firma-detalle">{{ $sinDato($empresa['razon_social']) }}</span>
        </td>
    </tr>
</table>
</body>
</html>
