<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiquetas - Códigos de Barra</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 10mm;
        }
        .labels-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 4mm;
            justify-content: center;
        }
        .label {
            border: 1px solid #ccc;
            border-radius: 4px;
            padding: {{ $size === 'small' ? '2mm' : ($size === 'large' ? '4mm' : '3mm') }};
            text-align: center;
            width: {{ $size === 'small' ? '40mm' : ($size === 'large' ? '80mm' : '60mm') }};
            page-break-inside: avoid;
        }
        .label-header {
            font-weight: bold;
            font-size: {{ $size === 'small' ? '7pt' : ($size === 'large' ? '11pt' : '9pt') }};
            color: #333;
            margin-bottom: 1mm;
            letter-spacing: 0.5px;
        }
        .label-subheader {
            font-size: {{ $size === 'small' ? '6pt' : ($size === 'large' ? '9pt' : '7pt') }};
            color: #666;
            margin-bottom: 1mm;
        }
        .label-barcode {
            margin: 1mm 0;
        }
        .label-barcode img {
            width: 100%;
            height: {{ $size === 'small' ? '12mm' : ($size === 'large' ? '22mm' : '16mm') }};
        }
        .label-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: {{ $size === 'small' ? '8pt' : ($size === 'large' ? '12pt' : '10pt') }};
            color: #222;
            margin: 1mm 0;
        }
        .label-name {
            font-size: {{ $size === 'small' ? '6pt' : ($size === 'large' ? '9pt' : '7pt') }};
            color: #555;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            max-width: 100%;
        }
    </style>
</head>
<body>
    <div class="labels-grid">
        @foreach($assets as $asset)
        <div class="label">
            <div class="label-header">DRE HUÁNUCO</div>
            <div class="label-subheader">Inventario 2026</div>
            <div class="label-barcode"><img src="{{ $barcodes[$asset->id] }}" alt="barcode"></div>
            <div class="label-code">{{ $asset->codigo_barras ?: $asset->codigo_completo }}</div>
            <div class="label-name" title="{{ $asset->denominacion }}">{{ $asset->denominacion }}</div>
        </div>
        @endforeach
    </div>
</body>
</html>
