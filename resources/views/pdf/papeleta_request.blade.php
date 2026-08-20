<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Papeleta de autorización de salida #{{ $papeleta->numero_papeleta }}</title>
    <style>
        @page { size: A5 portrait; margin: 9mm 10mm; }
        body { font-family: Arial, sans-serif; color: #000; font-size: 8.4px; line-height: 1.15; }
        table { border-collapse: collapse; width: 100%; table-layout: fixed; }
        .text-center { text-align: center; }
        .text-bold { font-weight: bold; }
        .muted { color: #5b6474; }
        .header { height: 90px; margin-bottom: 7px; position: relative; }
        .regional-brand { left: 0; position: absolute; text-align: left; top: 0; }
        .regional-brand img { height: 43px; max-width: 47px; object-fit: contain; }
        .dre-brand { position: absolute; right: 62px; text-align: center; top: 0; }
        .dre-brand img { height: 34px; max-width: 42px; object-fit: contain; }
        .title-cell { left: 0; position: absolute; right: 0; text-align: center; top: 36px; }
        .title { border-bottom: 1px solid #000; display: inline-block; font-size: 10px; font-weight: bold; padding-bottom: 1px; }
        .number-cell { position: absolute; right: 0; top: 0; width: 56px; }
        .number-box { border: 1px solid #000; border-radius: 3px; font-size: 10px; font-weight: bold; min-height: 18px; padding: 4px 2px; text-align: center; }
        .date-row { position: absolute; right: 0; text-align: right; top: 55px; }
        .date-label { font-size: 9px; font-weight: bold; margin-right: 5px; }
        .date-boxes { display: inline-table; border-collapse: collapse; font-size: 6px; width: 84px; }
        .date-boxes td { border: 1px solid #000; height: 13px; padding: 1px; text-align: center; }
        .date-boxes .value { font-size: 7px; font-weight: bold; }
        .line-row { margin-bottom: 3px; min-height: 11px; }
        .field-label { font-weight: bold; }
        .dotted-value { border-bottom: 1px dotted #000; display: inline-block; min-height: 10px; vertical-align: bottom; }
        .motive-item { display: inline-block; margin-right: 8px; white-space: nowrap; }
        .check { border: 1px solid #000; display: inline-block; height: 9px; line-height: 9px; margin-right: 3px; position: relative; text-align: center; top: 1px; vertical-align: middle; width: 9px; }
        .check.active:after { content: 'X'; font-size: 8px; font-weight: bold; left: 1px; line-height: 9px; position: absolute; top: -1px; }
        .text-box { border-bottom: 1px dotted #000; min-height: 21px; padding: 2px 1px; }
        .duration-title { font-size: 9px; font-weight: bold; margin: 8px 0 3px; text-align: center; text-decoration: underline; }
        .duration { margin: 0 auto; width: 68%; }
        .duration td { border: 1px solid #000; padding: 2px; text-align: center; }
        .duration .label { font-size: 6.5px; font-weight: bold; }
        .duration .value { font-size: 10px; font-weight: bold; height: 18px; }
        .constancia { margin-top: 8px; }
        .constancia-title { border-bottom: 1px dotted #000; font-size: 7px; font-weight: bold; padding-bottom: 1px; text-align: center; }
        /* The lower block reserves room for the destination signature,
           identity data and the GPS map that is filled through QR. */
        .constancia-box { border: 1px dashed #000; height: 82px; margin: 4px 12px 0; position: relative; }
        /* This reserved vertical space keeps the PAdES appearance cards above
           the three official signature captions, just as in the DRE form. */
        .signature-area { margin-top: 79px; }
        .signature-area td { padding-top: 48px; text-align: center; vertical-align: top; }
        .signature-line { border-top: 1px dotted #000; display: block; margin: 0 auto 3px; width: 78%; }
        .signature-title { font-size: 8px; font-weight: bold; }
        .footer { border-top: 1px solid #ddd; color: #98a1b3; font-size: 6px; margin-top: 14px; padding-top: 4px; text-align: center; }
        .preview-notice { background: #fff7d6; border: 1px solid #c88900; color: #7c5800; font-size: 7px; font-weight: bold; margin-bottom: 5px; padding: 3px; text-align: center; }
    </style>
</head>
<body>
    @php
        $logoPath = public_path('images/logo.png');
        $goreLogoPath = public_path('images/logo-goreh.png');
        // DomPDF needs GD to decode embedded PNGs. On environments without GD
        // (for example a developer machine) the official logo is omitted only
        // from the preview instead of preventing the PDF from being generated.
        $logoData = function_exists('imagecreatefrompng') && file_exists($logoPath)
            ? base64_encode(file_get_contents($logoPath))
            : null;
        $goreLogoData = function_exists('imagecreatefrompng') && file_exists($goreLogoPath)
            ? base64_encode(file_get_contents($goreLogoPath))
            : null;
        $motivoSalida = $papeleta->motivo_salida ?? ($papeleta->reason?->tipo === 'comision' ? 'comision' : 'particular_compensable');
        $fechaCreacion = $papeleta->created_at ?? $papeleta->fecha_salida;
    @endphp

    @if($preview ?? false)
        <div class="preview-notice">VISTA PREVIA - PAPELETA PENDIENTE DE APROBACIÓN Y FIRMAS</div>
    @endif

    <div class="header">
        <div class="regional-brand">
            @if($goreLogoData)<img src="data:image/png;base64,{{ $goreLogoData }}" alt="Gobierno Regional Huánuco">
            @else <strong>Gobierno Regional<br>Huánuco</strong> @endif
        </div>
        <div class="dre-brand">@if($logoData)<img src="data:image/png;base64,{{ $logoData }}" alt="DRE Huánuco">@endif</div>
        <div class="title-cell"><span class="title">PAPELETA DE AUTORIZACIÓN DE SALIDA</span></div>
        <div class="number-cell"><div class="number-box">N° {{ $papeleta->numero_papeleta }}</div></div>
        <div class="date-row"><span class="date-label">FECHA:</span><table class="date-boxes"><tr><td>DÍA</td><td>MES</td><td>AÑO</td></tr><tr><td class="value">{{ $fechaCreacion?->format('d') }}</td><td class="value">{{ $fechaCreacion?->format('m') }}</td><td class="value">{{ $fechaCreacion?->format('Y') }}</td></tr></table></div>
    </div>

    <div class="line-row"><span class="field-label">APELLIDOS Y NOMBRES:</span> <span class="dotted-value" style="width: 73%;">{{ $papeleta->employee?->apellidos }}, {{ $papeleta->employee?->nombres }}</span></div>
    <div class="line-row"><span class="field-label">DNI:</span> <span class="dotted-value" style="width: 21%;">{{ $papeleta->employee?->dni }}</span> <span class="field-label">OFICINA:</span> <span class="dotted-value" style="width: 47%;">{{ $papeleta->employee?->office?->nombre ?? '-' }}</span></div>
    <div class="line-row"><span class="field-label">CONDICIÓN LABORAL:</span> <span class="dotted-value" style="width: 67%;">{{ $papeleta->employee?->contractType?->nombre ?? $papeleta->employee?->tipo_contrato ?? '-' }}</span></div>
    <div class="line-row"><span class="field-label">MOTIVO SALIDA:</span> <span class="motive-item"><span class="check {{ $motivoSalida === 'comision' ? 'active' : '' }}"></span>COMISIÓN SERVICIO</span><span class="motive-item"><span class="check {{ $motivoSalida === 'particular_compensable' ? 'active' : '' }}"></span>PARTICULAR COMPENSABLE</span><span class="motive-item"><span class="check {{ $motivoSalida === 'por_salud' ? 'active' : '' }}"></span>POR SALUD</span></div>

    <div class="line-row" style="margin-top: 8px;"><span class="field-label">DESTINO:</span> <span class="dotted-value" style="width: 84%;">{{ $papeleta->destino ?? '-' }}</span></div>
    <div class="field-label" style="margin-top: 5px;">JUSTIFICACIÓN:</div>
    <div class="text-box">{{ $papeleta->motivo }}</div>

    <div class="duration-title">DURACIÓN</div>
    <table class="duration"><tr><td class="label">HORA DE SALIDA (REAL)</td><td class="label">HORA DE RETORNO (REAL)</td></tr><tr><td class="value">@unless($papeleta->qr_form_enabled){{ $papeleta->salida_real_at?->format('H:i') ?? 'PENDIENTE QR' }}@endunless</td><td class="value">@unless($papeleta->qr_form_enabled){{ $papeleta->retorno_real_at?->format('H:i') ?? 'PENDIENTE QR' }}@endunless</td></tr></table>

    <table class="signature-area"><tr>
        <td style="width: 33%;"><span class="signature-line"></span><span class="signature-title">FIRMA DEL SERVIDOR</span><br><span style="font-size: 7px;">DNI: {{ $papeleta->employee?->dni }}</span></td>
        <td style="width: 33%;"><span class="signature-line"></span><span class="signature-title">JEFE INMEDIATO</span></td>
        <td style="width: 34%;"><span class="signature-line"></span><span class="signature-title">RECURSOS HUMANOS</span></td>
    </tr></table>

    {{-- En el formato oficial la constancia de comisión se coloca después de
         las tres firmas institucionales. Solo se llena al cerrar una comisión. --}}
    <div class="constancia"><div class="constancia-title">FIRMA, SELLO Y HORA DE LA JEFATURA O DIRECCIÓN DE LA DEPENDENCIA DONDE SE REALIZÓ LA COMISIÓN DE SERVICIO</div><div class="constancia-box">
        @if($papeleta->destino_firmado_at)
            <div style="font-size: 7px; padding: 4px;"><strong>Conformidad registrada:</strong> {{ $papeleta->destino_firmante_nombre }} - {{ $papeleta->destino_firmante_cargo }}<br>DNI: {{ $papeleta->destino_firmante_dni }} | {{ $papeleta->destino_firmado_at->format('d/m/Y H:i') }}</div>
            @php($firmaPath = $papeleta->destino_firma_path ? storage_path('app/private/'.$papeleta->destino_firma_path) : null)
            @if($firmaPath && file_exists($firmaPath))<img src="data:image/png;base64,{{ base64_encode(file_get_contents($firmaPath)) }}" style="height: 27px; max-width: 180px; margin: 0 5px;">@endif
        @elseif(!$papeleta->qr_form_enabled)
            <div class="muted" style="bottom: 4px; font-size: 7px; position: absolute; text-align: center; width: 100%;">FIRMA Y SELLO DE LA ENTIDAD DE DESTINO</div>
        @endif
    </div></div>

    <div class="footer">Sistema de Control de Asistencia - DRE Huánuco | Fecha de impresión: {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
