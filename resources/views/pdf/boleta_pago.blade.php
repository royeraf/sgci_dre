<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Boleta de Pago {{ $boleta['trabajador']['codigo_boleta'] }}</title>
    <style>
        @page {
            size: A5 portrait;
            margin: 7mm 6mm;
        }
        /* Source Sans 3 (SIL OFL), regular y bold. La familia se fija en
           todos los descendientes: si un elemento se queda sin heredar,
           DomPDF cae a Times. No usar `font-weight: 900`: no hay archivo
           para ese peso y DomPDF sustituye la familia. */
        @font-face {
            font-family: 'Source Sans 3';
            font-style: normal;
            font-weight: normal;
            src: url('{{ str_replace('\\', '/', resource_path('fonts/SourceSans3-Regular.ttf')) }}') format('truetype');
        }
        @font-face {
            font-family: 'Source Sans 3';
            font-style: normal;
            font-weight: bold;
            src: url('{{ str_replace('\\', '/', resource_path('fonts/SourceSans3-Bold.ttf')) }}') format('truetype');
        }
        body, body * {
            font-family: 'Source Sans 3', sans-serif;
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
            border: 0.8px solid #000;
        }
        .grid td {
            border: 0.4px solid #000;
        }
        /* Etiqueta de campo en formato vertical: una sola columna de
           etiquetas a la izquierda (~34%) y valor a la derecha. */
        .label {
            width: 34%;
            background-color: #eceff3;
            font-weight: bold;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
        }
        /* Etiqueta apilada (Datos de la empresa): nombre arriba a ancho
           completo y valor debajo, en filas separadas. */
        .label-top {
            background-color: #eceff3;
            font-weight: bold;
            font-size: 6.8px;
            text-transform: uppercase;
            letter-spacing: 0.1px;
            text-align: center;
        }
        /* Valores de los bloques de datos en columnas: letra menor para que
           los textos largos encajen sin alargar la hoja. */
        .empresa-valor {
            font-size: 6.6px;
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
            font-size: 8px;
            letter-spacing: 0.4px;
            color: #dc2626;
        }
        .codigo-label {
            background-color: #eceff3;
            font-size: 6.2px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
        }

        /* Conceptos apilados en vertical */
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
            border-top: 0.6px solid #000;
            padding: 2px 4px;
            font-size: 7.6px;
            text-align: left;
            white-space: nowrap;
        }
        .total-label {
            font-size: 6.2px;
            text-transform: uppercase;
            color: #475569;
        }

        /* Neto */
        .neto {
            border: 0.8px solid #000;
            background-color: #eceff3;
            font-size: 7.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .neto-valor {
            border: 0.8px solid #000;
            font-size: 11px;
            font-weight: bold;
            text-align: right;
            white-space: nowrap;
            padding: 2px 6px;
        }

        /* Marca de agua con el logo de la DRE: imagen fija centrada,
           tenue para no restar legibilidad. DomPDF no soporta
           `transform`, así que se centra con márgenes negativos. */
        .marca-agua {
            position: fixed;
            top: 50%;
            left: 50%;
            width: 90mm;
            margin-left: -45mm;
            margin-top: -45mm;
            opacity: 0.07;
            z-index: -1;
        }
    </style>
</head>
<body>
@if($logo)
<div class="marca-agua">
    <img src="data:image/png;base64,{{ $logo }}" style="width: 90mm; height: auto;">
</div>
@endif
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
    // Fila superior: Remuneraciones + Retenciones lado a lado; la columna
    // más larga fija la altura de ambas para que los totales queden parejos.
    $filaSuperior = [$columnas[0], $columnas[1]];
    $aportaciones = $columnas[2];
    $maxFila = max(count($filaSuperior[0]['items']), count($filaSuperior[1]['items']));

    // Formato vertical A5 retrato (148 x 210 mm): área útil aprox.
    // 136 mm de ancho x 196 mm de alto con márgenes de 7/6 mm.
    // Los datos ocupan ~172 mm y la banda inferior del QR el resto,
    // todo en una sola página. Si el contenido supera esa altura, la
    // celda crece sin cortarse y la banda del QR baja.
    $altoDatos = 172;
@endphp

<!-- Todo el contenido en un bloque de altura fija -->
<table style="width: 100%; border: none;">
    <tr>
        <td style="border: none; padding: 0; height: {{ $altoDatos }}mm; vertical-align: top;">

<!-- Encabezado: bloque institucional a la izquierda, título centrado, QR a la derecha -->
<table style="border: none; margin-bottom: 2px;">
    <tr>
        <td style="width: 32%; border: none; vertical-align: middle;">
            <table style="border: none;">
                <tr>
                    <td style="width: 26%; border: none; vertical-align: middle;">
                        @if($logo)
                            <img src="data:image/png;base64,{{ $logo }}" style="display: block; width: 30px; height: auto;">
                        @endif
                    </td>
                    <td style="border: none; vertical-align: middle;">
                        <div style="font-size: 7.5px; font-weight: bold; line-height: 1.2;">Dirección Regional de Educación</div>
                        <div style="font-size: 7px;">Huánuco</div>
                    </td>
                </tr>
            </table>
        </td>
        <td style="width: 40%; border: none; text-align: center; vertical-align: middle;">
            <div class="header-title">BOLETA DE PAGO</div>
            <div class="header-subtitle">Planilla de pago personal CAS</div>
            <div class="header-period">{{ $periodo['nombre_mes'] }} - {{ $periodo['anio'] }}</div>
        </td>
        <td style="width: 28%; border: none; text-align: center; vertical-align: middle;">
            <img src="{{ $boleta['qr'] }}" style="display: block; margin: 0 auto; width: 22mm; height: 22mm;">
        </td>
    </tr>
</table>
<table class="grid" style="margin-bottom: 2px;">
    <tr>
        <td class="codigo-label" style="width: 34%;">Código de boleta</td>
        <td class="codigo text-center">{{ $trabajador['codigo_boleta'] }}</td>
    </tr>
</table>

<!-- Empresa en 3 columnas (etiqueta arriba, valor debajo) + resto en rejilla de 2 columnas -->
<table class="grid">
    <tr>
        <td class="sep" colspan="3">Datos de la empresa</td>
    </tr>
    <tr>
        <td class="label-top" style="width: 22%;">RUC</td>
        <td class="label-top" style="width: 38%;">Razón Social</td>
        <td class="label-top">Dirección</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $sinDato($empresa['ruc']) }}</td>
        <td class="text-center empresa-valor">{{ $sinDato($empresa['razon_social']) }}</td>
        <td class="text-center empresa-valor">{{ $sinDato($empresa['direccion']) }}</td>
    </tr>
</table>
<table class="grid" style="margin-top: 2px;">

    <tr>
        <td class="sep text-center" colspan="3">Datos del trabajador</td>
    </tr>
    <tr>
        <td class="label-top">Apellidos y Nombres</td>
        <td class="label-top">DNI</td>
        <td class="label-top">Fecha de Ingreso</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $sinDato($trabajador['apellidos_nombres']) }}</td>
        <td class="text-center empresa-valor">{{ $sinDato($trabajador['dni']) }}</td>
        <td class="text-center empresa-valor">{{ $fecha($trabajador['fecha_ingreso']) }}</td>
    </tr>
    <tr>
        <td class="label-top">Fecha Inicio Contrato</td>
        <td class="label-top" colspan="2">{{ $esIndeterminado ? 'Contrato' : 'Fecha Fin Contrato' }}</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $fecha($trabajador['fecha_inicio_contrato']) }}</td>
        <td class="text-center empresa-valor" colspan="2">
            @if($esIndeterminado)
                <span>Indeterminado</span>
            @else
                {{ $fecha($trabajador['fecha_fin_contrato']) }}
                <span>(Contrato Fijo)</span>
            @endif
        </td>
    </tr>

    <tr>
        <td class="sep text-center" colspan="3">Datos vinculados a la relación laboral</td>
    </tr>
    <tr>
        <td class="label-top">Cargo</td>
        <td class="label-top">Régimen</td>
        <td class="label-top">Periodicidad</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $sinDato($laboral['cargo']) }}</td>
        <td class="text-center empresa-valor">{{ $sinDato($laboral['regimen']) }}</td>
        <td class="text-center empresa-valor">{{ $laboral['periodicidad'] }}</td>
    </tr>
    <tr>
        <td class="label-top">Sistema de Pensiones</td>
        <td class="label-top">CUSPP</td>
        <td class="label-top">Días Laborados</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $sinDato($laboral['sistema_pensiones']) }}</td>
        <td class="text-center empresa-valor">{{ $sinDato($laboral['cuspp']) }}</td>
        <td class="text-center empresa-valor">{{ $laboral['dias_laborados'] }} / {{ \App\Services\Planilla\BoletaService::DIAS_MES }}</td>
    </tr>
    <tr>
        <td class="label-top">Banco</td>
        <td class="label-top" colspan="2">Cuenta de Ahorro</td>
    </tr>
    <tr>
        <td class="text-center empresa-valor">{{ $sinDato($laboral['banco']) }}</td>
        <td class="text-center empresa-valor" colspan="2">{{ $sinDato($laboral['cuenta_ahorro']) }}</td>
    </tr>
</table>

<!-- Conceptos: Remuneraciones y Retenciones en la misma fila,
     Aportaciones a ancho completo debajo -->
<table class="grid" style="margin-top: 2px;">
    <tr>
        @foreach($filaSuperior as $columna)
            <th class="sep text-center" style="width: 50%;">{{ $columna['titulo'] }}</th>
        @endforeach
    </tr>
    <tr>
        @foreach($filaSuperior as $columna)
            <td style="padding: 0; vertical-align: top;">
                <table class="conceptos">
                    @foreach($columna['items'] as $item)
                        <tr>
                            <td>
                                {{ $item['descripcion'] }}<span style="color: #64748b;">{{ $pct($item['porcentaje']) }}</span>
                            </td>
                            <td class="monto" style="width: 30%;">{{ number_format($item['monto'], 2) }}</td>
                        </tr>
                    @endforeach
                    @for($i = count($columna['items']); $i < $maxFila; $i++)
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                    @endfor
                    @if($maxFila === 0)
                        <tr>
                            <td colspan="2" class="text-center" style="color: #94a3b8;">—</td>
                        </tr>
                    @endif
                </table>
            </td>
        @endforeach
    </tr>
    <tr class="total-row">
        @foreach($filaSuperior as $columna)
            <td class="text-bold" style="padding: 0;">
                <table style="border: none;">
                    <tr>
                        <td style="border: none; text-align: left;">Total {{ $columna['titulo'] }}:</td>
                        <td style="border: none; text-align: right; white-space: nowrap;">{{ number_format($columna['total'], 2) }}</td>
                    </tr>
                </table>
            </td>
        @endforeach
    </tr>
</table>
<table class="grid" style="margin-top: 2px;">
    <tr>
        <th class="sep text-center">{{ $aportaciones['titulo'] }}</th>
    </tr>
    <tr>
        <td style="padding: 0; vertical-align: top;">
            <table class="conceptos">
                @forelse($aportaciones['items'] as $item)
                    <tr>
                        <td>
                            {{ $item['descripcion'] }}<span style="color: #64748b;">{{ $pct($item['porcentaje']) }}</span>
                        </td>
                        <td class="monto" style="width: 26%;">{{ number_format($item['monto'], 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">—</td>
                    </tr>
                @endforelse
            </table>
        </td>
    </tr>
    <tr class="total-row">
        <td class="text-bold" style="padding: 0;">
            <table style="border: none;">
                <tr>
                    <td style="border: none; text-align: left;">Total {{ $aportaciones['titulo'] }}:</td>
                    <td style="border: none; text-align: right; white-space: nowrap;">{{ number_format($aportaciones['total'], 2) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<!-- Neto y fecha de emisión apilados -->
<table style="border: none; margin-top: 2px;">
    <tr>
        <td class="neto" style="width: 34%;">NETO A PAGAR</td>
        <td class="neto-valor">S/ {{ number_format($totales['neto_pagar'], 2) }}</td>
    </tr>
</table>
<table style="border: none; margin-top: 1px;">
    <tr>
        <td style="font-size: 6.5px;">Fecha de emisión:
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
</body>
</html>
