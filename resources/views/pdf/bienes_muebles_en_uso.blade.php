<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Bienes Muebles en Uso</title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 15mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 2px solid #2D3748;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 14px;
            color: #2D3748;
            margin: 3px 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header h2 {
            font-size: 10px;
            color: #4A5568;
            margin: 2px 0;
            font-weight: normal;
        }
        .header p {
            margin: 2px 0;
            font-size: 9px;
            color: #A0AEC0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        thead th {
            background-color: #2D3748;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
            padding: 6px 4px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #1A202C;
        }
        thead th.left {
            text-align: left;
        }
        tbody td {
            padding: 4px 5px;
            border: 1px solid #CBD5E0;
            vertical-align: middle;
            font-size: 8.5px;
        }
        tbody tr:nth-child(even) {
            background-color: #F7FAFC;
        }
        tbody tr:hover {
            background-color: #EBF4FF;
        }
        .num-col {
            text-align: center;
            width: 6%;
            color: #718096;
            font-weight: bold;
        }
        .denom-col {
            text-align: left;
            width: 52%;
            color: #2D3748;
        }
        .count-col {
            text-align: center;
            width: 10%;
            font-weight: bold;
        }
        .count-bueno {
            color: #276749;
        }
        .count-regular {
            color: #975A16;
        }
        .count-malo {
            color: #C53030;
        }
        .count-total {
            color: #2D3748;
            font-weight: bold;
        }

        tfoot td {
            padding: 6px 5px;
            border: 1px solid #1A202C;
            font-weight: bold;
            font-size: 9px;
            background-color: #EDF2F7;
        }
        tfoot .label {
            text-align: right;
            color: #2D3748;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #A0AEC0;
            border-top: 1px solid #E2E8F0;
            padding-top: 8px;
        }

        .zero {
            color: #CBD5E0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>DIRECCI&Oacute;N REGIONAL DE EDUCACI&Oacute;N HU&Aacute;NUCO</h2>
        <h1>Bienes Muebles Registrados - M&oacute;dulo SIGA Patrimonio</h1>
        <h2>Bienes Muebles en Uso - A&ntilde;o {{ now()->year }}</h2>
        <p>Generado el {{ now()->format('d/m/Y H:i A') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>N&deg;</th>
                <th class="left">Descripci&oacute;n del Bien</th>
                <th>Bueno</th>
                <th>Regular</th>
                <th>Malo</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $row)
            <tr>
                <td class="num-col">{{ $index + 1 }}</td>
                <td class="denom-col">{{ $row['denominacion'] }}</td>
                <td class="count-col {{ $row['bueno'] > 0 ? 'count-bueno' : 'zero' }}">{{ $row['bueno'] }}</td>
                <td class="count-col {{ $row['regular'] > 0 ? 'count-regular' : 'zero' }}">{{ $row['regular'] }}</td>
                <td class="count-col {{ $row['malo'] > 0 ? 'count-malo' : 'zero' }}">{{ $row['malo'] }}</td>
                <td class="count-col count-total">{{ $row['total'] }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" class="label">Totales</td>
                <td class="count-col count-bueno" style="background-color: #E6FFFA;">{{ $totals['bueno'] }}</td>
                <td class="count-col count-regular" style="background-color: #FFFFF0;">{{ $totals['regular'] }}</td>
                <td class="count-col count-malo" style="background-color: #FFF5F5;">{{ $totals['malo'] }}</td>
                <td class="count-col count-total" style="background-color: #E2E8F0;">{{ $totals['total'] }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        <p>Este documento es un reporte generado por el sistema y tiene car&aacute;cter informativo.</p>
    </div>
</body>
</html>
