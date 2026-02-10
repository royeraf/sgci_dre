<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiquetas - Códigos de Barra</title>
    @php
        // A4: 210mm x 297mm — Márgenes: 10mm → área útil: 190mm x 277mm
        // Cálculo: área útil - gaps totales / columnas
        if ($size === 'small') {
            $cols = 4;
            $gap = 2;
            // 190mm - (5 gaps × 2mm) = 180mm / 4 = 45mm → ajustar a 43mm
            $labelWidth = 43;
            $barcodeH = 14;
            $headerFont = 7;
            $subFont = 6;
            $codeFont = 7;
            $nameFont = 6;
            $padding = 2;
            $truncate = 18;
        } elseif ($size === 'large') {
            $cols = 2;
            $gap = 4;
            // 190mm - (3 gaps × 4mm) = 178mm / 2 = 89mm → ajustar a 88mm
            $labelWidth = 88;
            $barcodeH = 24;
            $headerFont = 11;
            $subFont = 9;
            $codeFont = 10;
            $nameFont = 8;
            $padding = 5;
            $truncate = 45;
        } else {
            // medium (default)
            $cols = 3;
            $gap = 3;
            // 190mm - (4 gaps × 3mm) = 178mm / 3 ≈ 59mm → ajustar a 58mm
            $labelWidth = 58;
            $barcodeH = 18;
            $headerFont = 9;
            $subFont = 7;
            $codeFont = 8;
            $nameFont = 7;
            $padding = 3;
            $truncate = 28;
        }
        $assetsList = $assets->values();
        $totalRows = (int) ceil($assetsList->count() / $cols);
    @endphp
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .wrapper {
            display: inline-block;
            text-align: center;
        }
        table.labels-table {
            border-collapse: separate;
            border-spacing: {{ $gap }}mm;
        }
        td.label-cell {
            width: {{ $labelWidth }}mm;
            border: 1px dashed #999;
            padding: {{ $padding }}mm;
            text-align: center;
            vertical-align: top;
        }
        td.empty-cell {
            border: none;
        }
        .label-header {
            font-weight: bold;
            font-size: {{ $headerFont }}pt;
            color: #333;
            margin-bottom: 1mm;
            letter-spacing: 0.5px;
        }
        .label-subheader {
            font-size: {{ $subFont }}pt;
            color: #555;
            margin-bottom: 1mm;
        }
        .label-barcode {
            margin: 2mm 0;
            text-align: center;
        }
        .label-barcode img {
            width: 90%;
            height: {{ $barcodeH }}mm;
        }
        .label-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: {{ $codeFont }}pt;
            color: #222;
            margin: 1mm 0;
        }
        .label-name {
            font-size: {{ $nameFont }}pt;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="wrapper">
    <table class="labels-table">
        @for($row = 0; $row < $totalRows; $row++)
        <tr>
            @for($col = 0; $col < $cols; $col++)
                @php $index = $row * $cols + $col; @endphp
                @if($index < $assetsList->count())
                    @php $asset = $assetsList[$index]; @endphp
                    <td class="label-cell">
                        <div class="label-header">DRE HUÁNUCO</div>
                        <div class="label-subheader">Inventario 2026</div>
                        <div class="label-barcode">
                            <img src="{{ $barcodes[$asset->id] }}" alt="barcode">
                        </div>
                        <div class="label-code">{{ $asset->codigo_barras ?: $asset->codigo_completo }}</div>
                        <div class="label-name">{{ Str::limit($asset->denominacion, $truncate) }}</div>
                    </td>
                @else
                    <td class="empty-cell"></td>
                @endif
            @endfor
        </tr>
        @endfor
    </table>
    </div>
</body>
</html>
