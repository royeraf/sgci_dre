<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario de Bienes Asignados - {{ $responsible->full_name }}</title>
    <style>
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; margin: 0; padding: 20px; color: #333; }
        .header { text-align: center; margin-bottom: 25px; border-bottom: 2px solid #ddd; padding-bottom: 15px; }
        .header h1 { font-size: 18px; color: #2D3748; margin: 5px 0; font-weight: bold; text-transform: uppercase; }
        .header h2 { font-size: 11px; color: #718096; margin: 2px 0; font-weight: normal; }
        .header p { margin: 2px 0; font-size: 10px; color: #A0AEC0; }
        
        .info-section { margin-bottom: 20px; background-color: #F7FAFC; padding: 15px; border-radius: 5px; border: 1px solid #E2E8F0; }
        .info-row { display: table; width: 100%; margin-bottom: 5px; }
        .info-cell { display: table-cell; width: 50%; vertical-align: top; }
        .info-label { font-weight: bold; color: #4A5568; display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { color: #2D3748; margin-top: 2px; font-size: 12px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { background-color: #EDF2F7; color: #4A5568; font-weight: bold; text-align: left; padding: 8px 10px; border-bottom: 2px solid #CBD5E0; font-size: 10px; text-transform: uppercase; }
        td { padding: 10px; border-bottom: 1px solid #E2E8F0; vertical-align: top; color: #2D3748; }
        tr:nth-child(even) { background-color: #F8FAFC; }
        
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #718096; }
        .signatures { margin-top: 80px; width: 100%; display: table; }
        .signature-box { display: table-cell; width: 33%; text-align: center; vertical-align: top; padding: 0 20px; }
        .signature-line { border-top: 1px solid #718096; width: 80%; margin: 0 auto 5px auto; }
        
        .status-badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .status-bueno { background: #E6FFFA; color: #2C7A7B; border: 1px solid #B2F5EA; }
        .status-regular { background: #FFFFF0; color: #975A16; border: 1px solid #FEEBC8; }
        .status-malo { background: #FFF5F5; color: #C53030; border: 1px solid #FED7D7; }
        
        .code-box { font-family: monospace; font-weight: bold; background: #F7FAFC; padding: 2px 4px; border-radius: 3px; border: 1px solid #E2E8F0; color: #4A5568; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Inventario Individual</h1>
        <h2>DIRECCIÓN REGIONAL DE EDUCACIÓN HUÁNUCO</h2>
        <p>Sistema de Gestión y Control de Inventarios — Generado el {{ now()->format('d/m/Y H:i A') }}</p>
    </div>

    <div class="info-section">
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Responsable</span>
                <div class="info-value">{{ strtoupper($responsible->full_name) }}</div>
            </div>
            <div class="info-cell">
                <span class="info-label">DNI / Código</span>
                <div class="info-value">{{ $responsible->dni }}</div>
            </div>
        </div>
        <div class="info-row">
            <div class="info-cell">
                <span class="info-label">Oficina / Área</span>
                <div class="info-value">{{ $responsible->area_office ?? 'NO ESPECIFICADO' }}</div>
            </div>
            <div class="info-cell">
                <span class="info-label">Total Bienes Asignados</span>
                <div class="info-value">{{ count($assets) }}</div>
            </div>
        </div>
    </div>

    @if(count($assets) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 15%;">Código Patrimonial</th>
                <th style="width: 10%;">Cód. Interno</th>
                <th style="width: 35%;">Descripción del Bien</th>
                <th style="width: 15%;">Marca / Serie</th>
                <th style="width: 10%;">Estado</th>
                <th style="width: 10%;">Fecha Asign.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $index => $asset)
            <tr>
                <td style="text-align: center; color: #718096;">{{ $index + 1 }}</td>
                <td>
                    <span class="code-box">{{ $asset->codigo_patrimonio }}</span>
                </td>
                <td style="text-align: center;">
                    @if($asset->codigo_interno)
                        <span style="color: #718096; font-family: monospace;">{{ $asset->codigo_interno }}</span>
                    @else
                        -
                    @endif
                </td>
                <td>
                    <div style="font-weight: bold; margin-bottom: 2px;">{{ $asset->denominacion }}</div>
                    <div style="font-size: 9px; color: #718096;">{{ Str::limit($asset->descripcion, 60) }}</div>
                </td>
                <td>
                    <div>{{ $asset->brand->nombre ?? '-' }}</div>
                    <div style="font-size: 9px; color: #718096; font-family: monospace;">{{ $asset->numero_serie ?? '-' }}</div>
                </td>
                <td style="text-align: center;">
                    @php
                        $estado = optional($asset->latestMovement->state)->nombre ?? 'DESCONOCIDO';
                        $class = match($estado) {
                            'BUENO' => 'status-bueno',
                            'REGULAR' => 'status-regular',
                            'MALO' => 'status-malo',
                            default => 'status-regular'
                        };
                    @endphp
                    <span class="status-badge {{ $class }}">{{ $estado }}</span>
                </td>
                <td style="font-size: 10px; text-align: center;">
                    {{ $asset->latestMovement->fecha ? \Carbon\Carbon::parse($asset->latestMovement->fecha)->format('d/m/Y') : '-' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div style="padding: 40px; text-align: center; color: #718096; border: 1px dashed #CBD5E0; margin-top: 20px; border-radius: 5px;">
        No hay bienes asignados actualmente a este responsable.
    </div>
    @endif

    <div class="footer">
        <p>Este documento es un reporte generado por el sistema y tiene carácter informativo.</p>
        <p>Página 1 de 1</p>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>Control Patrimonial</strong><br>
            <span style="font-size: 9px; color: #718096;">Entregué Conforme</span>
        </div>
        <div class="signature-box"></div> <!-- Espacio central -->
        <div class="signature-box">
            <div class="signature-line"></div>
            <strong>{{ strtoupper($responsible->full_name) }}</strong><br>
            <span style="font-size: 9px; color: #718096;">DNI: {{ $responsible->dni }}</span><br>
            <span style="font-size: 9px; color: #718096;">Recibí Conforme</span>
        </div>
    </div>
</body>
</html>
