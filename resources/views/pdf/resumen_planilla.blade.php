<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen Planilla CAS - {{ $periodo->nombre_periodo }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 14px; }
        .header h1 { margin: 0; font-size: 16px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 2px 0; color: #475569; font-size: 11px; }
        .header .periodo { font-weight: bold; font-size: 12px; color: #0f766e; text-transform: uppercase; }
        .bloque { margin-bottom: 18px; }
        .bloque h2 {
            margin: 0 0 6px 0;
            font-size: 12px;
            text-transform: uppercase;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 5px 8px;
        }
        .bloque h2 .essalud { float: right; font-weight: normal; text-transform: none; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; }
        th { background: #334155; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 9px; }
        td.monto, th.monto { text-align: right; font-family: monospace; }
        tr.total td { font-weight: bold; background: #f8fafc; }
        .neto { margin-top: -4px; }
        .neto .caja {
            display: inline-block;
            background: #ccfbf1;
            border: 1px solid #14b8a6;
            font-weight: bold;
            padding: 5px 10px;
        }
        .neto .caja .etiqueta { font-weight: normal; }
        .totales { width: 100%; margin-top: 6px; }
        .totales td { border: none; padding: 3px 0; }
        .totales .etiqueta { font-weight: bold; }
        .totales .valor { text-align: right; font-family: monospace; font-weight: bold; }
        .totales .par { width: 50%; padding-right: 24px; }
        .firmas { margin-top: 34px; text-align: center; font-size: 10px; color: #475569; }
        .firmas div { display: inline-block; margin: 0 40px; }
        .firmas .linea { border-top: 1px solid #94a3b8; padding-top: 4px; width: 180px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Resumen Planilla CAS</h1>
        <p>Dirección Regional de Educación Huánuco</p>
        <p class="periodo">{{ $periodo->nombre_periodo }} · {{ number_format($periodo->total_empleados, 0) }} empleados</p>
    </div>

    @foreach ($bloques as $clave => $bloque)
    <div class="bloque">
        <h2>
            Génerica de gasto {{ $bloque['generica'] }}
            <span class="essalud">Essalud (2.1.31.1 15): S/ {{ number_format($bloque['essalud'], 2) }}</span>
        </h2>

        <table>
            <thead>
                <tr>
                    <th style="width: 90px;">Esp. Gasto</th>
                    <th>Concepto</th>
                    <th class="monto" style="width: 90px;">Monto S/</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bloque['ingresos'] as $ingreso)
                <tr>
                    <td>{{ $ingreso['esp_gasto'] }}</td>
                    <td>{{ $ingreso['nombre'] }}</td>
                    <td class="monto">{{ number_format($ingreso['monto'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td colspan="2">Total Ingresos</td>
                    <td class="monto">{{ number_format($bloque['total_ingresos'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th class="monto" style="width: 90px;">Monto S/</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($bloque['descuentos'] as $descuento)
                <tr>
                    <td>{{ $descuento['nombre'] }}</td>
                    <td class="monto">{{ number_format($descuento['monto'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td>Total Descuentos</td>
                    <td class="monto">{{ number_format($bloque['total_descuentos'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        <div class="neto">
            <span class="caja"><span class="etiqueta">Neto a Pagar:</span> S/ {{ number_format($bloque['neto'], 2) }}</span>
        </div>
    </div>
    @endforeach

    <table class="totales">
        <tr>
            <td class="par"><span class="etiqueta">Total Líquido:</span></td>
            <td class="valor">S/ {{ number_format($totales['liquido'], 2) }}</td>
            <td class="par"><span class="etiqueta">Total Descuento:</span></td>
            <td class="valor">S/ {{ number_format($totales['descuento'], 2) }}</td>
        </tr>
        <tr>
            <td class="par"><span class="etiqueta">Total Aporte (Essalud):</span></td>
            <td class="valor">S/ {{ number_format($totales['aporte'], 2) }}</td>
            <td class="par"><span class="etiqueta">Total Planilla:</span></td>
            <td class="valor">S/ {{ number_format($totales['planilla'], 2) }}</td>
        </tr>
    </table>

    <div class="firmas">
        <div><div class="linea">Elaborado por</div></div>
        <div><div class="linea">Revisado por</div></div>
        <div><div class="linea">Aprobado por</div></div>
    </div>
</body>
</html>
