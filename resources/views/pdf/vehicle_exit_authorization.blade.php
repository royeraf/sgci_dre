<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Autorización Salida de Vehículos Nº {{ $commission->numero }}-{{ $commission->anio }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 8mm;
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 10px;
            color: #000;
            line-height: 1.3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-table td { border: none; vertical-align: middle; }
        .header-title {
            font-size: 13px;
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
        }
        .sub-title {
            font-size: 8px;
            text-align: center;
        }
        .numero-box {
            border: 1px solid #000;
            text-align: center;
            padding: 4px;
        }
        .numero-box .label { font-size: 8px; font-weight: bold; }
        .numero-box .value { font-size: 14px; font-weight: bold; color: #dc2626; }
        .field-line {
            margin: 6px 0;
        }
        .field-label { font-weight: bold; }
        .field-value {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-height: 12px;
            padding: 0 4px;
        }
        .grid-2 { width: 100%; }
        .grid-2 td { border: none; padding: 3px 0; vertical-align: bottom; }
        .signature-area { margin-top: 26px; }
        .signature-area td {
            border: none;
            padding-top: 18px;
            text-align: center;
            width: 33.33%;
        }
        .signature-line {
            display: block;
            border-top: 1px solid #000;
            margin: 0 auto 2px auto;
            width: 85%;
        }
        .signature-label { font-size: 8px; font-weight: bold; text-transform: uppercase; }
        .footer-note {
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
            margin-top: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <table class="header-table">
        <tr>
            <td style="width: 20%; text-align: center;">
                @php
                    $logoPath = public_path('images/logo.png');
                    $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : null;
                @endphp
                @if($logoData)
                    <img src="data:image/png;base64,{{ $logoData }}" style="width: 45px; height: auto;">
                @endif
            </td>
            <td style="width: 55%;">
                <div class="sub-title">DIRECCIÓN REGIONAL DE EDUCACIÓN HUÁNUCO</div>
                <div class="header-title">Autorización Salida de Vehículos</div>
            </td>
            <td style="width: 25%;">
                <div class="numero-box">
                    <div class="label">Nº</div>
                    <div class="value">{{ str_pad($commission->numero, 3, '0', STR_PAD_LEFT) }}-{{ $commission->anio }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="grid-2">
        <tr>
            <td style="width: 65%;">
                <span class="field-label">Vehículo:</span>
                <span class="field-value" style="width: 90%; display: block;">
                    {{ $commission->vehicle?->tipo }} {{ $commission->vehicle?->marca }} {{ $commission->vehicle?->modelo }}
                    Placa Nº {{ $commission->vehicle?->placa ?? 'N/A' }}
                </span>
            </td>
            <td style="width: 35%;">
                <span class="field-label">Conductor:</span>
                <span class="field-value" style="width: 90%; display: block;">{{ $commission->conductor_nombre }}</span>
                @if($commission->conductor_licencia)
                    <span class="field-label">Licencia:</span>
                    <span class="field-value" style="width: 90%; display: block;">{{ $commission->conductor_licencia }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">Lugar y motivo de la comisión:</span>
                <span class="field-value" style="width: 100%; display: block;">{{ $commission->lugar }}{{ $commission->motivo ? ' - '.$commission->motivo : '' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">Referencia:</span>
                <span class="field-value" style="width: 90%; display: block;">{{ $commission->referencia ?? '' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">Servidor o funcionario que solicita:</span>
                <span class="field-value" style="width: 70%; display: block;">{{ $commission->solicitante_nombre }}</span>
            </td>
        </tr>
        <tr>
            <td style="width: 50%;">
                <span class="field-label">Hora de Salida:</span>
                <span class="field-value" style="width: 40%;">{{ substr($commission->hora_salida ?? $commission->hora ?? '', 0, 5) }}</span>
            </td>
            <td style="width: 50%;">
                <span class="field-label">Hora de retorno:</span>
                <span class="field-value" style="width: 40%;">{{ substr($commission->hora_regreso ?? '', 0, 5) }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="field-label">Km de salida:</span>
                <span class="field-value" style="width: 40%;">{{ $commission->km_salida ?? '' }}</span>
            </td>
            <td>
                <span class="field-label">Km de retorno:</span>
                <span class="field-value" style="width: 40%;">{{ $commission->km_retorno ?? '' }}</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="field-label">Combustible:</span>
                <span class="field-value" style="width: 40%;">{{ $commission->combustible ?? '' }}</span>
            </td>
            <td>
                <span class="field-label">P/Nº:</span>
                <span class="field-value" style="width: 40%;">{{ $commission->pnro ?? '' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="field-label">Total Km. Recorrido:</span>
                <span class="field-value" style="width: 30%;">{{ $commission->total_km_recorrido ?? '' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: right; padding-top: 10px;">
                Huánuco, {{ \Carbon\Carbon::parse($commission->dia)->locale('es')->translatedFormat('d \d\e F \d\e\l Y') }}
            </td>
        </tr>
    </table>

    <table class="signature-area">
        <tr>
            <td>
                <span class="signature-line"></span>
                <span class="signature-label">Servidor o funcionario que solicita</span>
            </td>
            <td>
                <span class="signature-line"></span>
                <span class="signature-label">Funcionario que autoriza</span>
                @if($commission->autorizado_por_nombre)
                    <br><span style="font-size: 8px;">{{ $commission->autorizado_por_nombre }}</span>
                    @if($commission->fecha_autorizacion)
                        <br><span style="font-size: 8px;">{{ $commission->fecha_autorizacion->format('d/m/Y H:i') }}</span>
                    @endif
                @endif
            </td>
            <td>
                <span class="signature-line"></span>
                <span class="signature-label">Conductor</span>
                @if($commission->conductor_nombre)
                    <br><span style="font-size: 8px;">{{ $commission->conductor_nombre }}</span>
                    @if($commission->conductor_licencia)
                        <br><span style="font-size: 8px;">Lic. {{ $commission->conductor_licencia }}</span>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Sistema de Control Vehicular - DRE Huánuco | Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
