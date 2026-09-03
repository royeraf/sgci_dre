<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Etiquetas Térmicas - TD-402S</title>
    @php
        // Escala de tamaños tipográficos y gráficos según la altura y columnas
        if ($labelHeight <= 26) {
            // Etiquetas compactas de 25.4mm (1 pulgada / 2x1) o 20mm
            $fontSizeEntity = '5.5pt';
            $fontSizeSub = '4.5pt';
            $fontSizeCode = '6.5pt';
            $fontSizeName = '5pt';
            $fontSizeExtra = '4.5pt';
            $barcodeH = 7.0; // mm
            $qrSize = ($columns === 2) ? 13.5 : 14.5; // mm
            $qrColWidth = ($columns === 2) ? 15.0 : 16.0; // mm
            $cellPadding = '0.5mm 1mm';
        } elseif ($labelHeight <= 35) {
            // Etiquetas de 30mm o 35mm
            $fontSizeEntity = '6.5pt';
            $fontSizeSub = '5pt';
            $fontSizeCode = '7.5pt';
            $fontSizeName = '6pt';
            $fontSizeExtra = '5pt';
            $barcodeH = 9.5; // mm
            $qrSize = ($columns === 2) ? 16.5 : 18.0; // mm
            $qrColWidth = ($columns === 2) ? 18.0 : 20.0; // mm
            $cellPadding = '0.8mm 1.5mm';
        } else {
            // Etiquetas de 40mm, 50mm o mayores
            $fontSizeEntity = '8pt';
            $fontSizeSub = '6.5pt';
            $fontSizeCode = '9.5pt';
            $fontSizeName = '7.5pt';
            $fontSizeExtra = '6.5pt';
            $barcodeH = 14.0; // mm
            $qrSize = ($columns === 2) ? 22.0 : 25.0; // mm
            $qrColWidth = ($columns === 2) ? 24.0 : 28.0; // mm
            $cellPadding = '1.5mm 2mm';
        }
    @endphp
    <style>
        @page {
            size: {{ $paperWidthPt }}pt {{ $paperHeightPt }}pt;
            margin: 0px;
        }
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        html, body {
            margin: 0px;
            padding: 0px;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #fff;
            color: #000;
        }
        .break-before {
            page-break-before: always;
        }
        .page-container-1col {
            padding: {{ $cellPadding }};
            text-align: center;
        }
        .row-container-2col {
            width: 100%;
        }
        table.grid-2col {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        td.lbl-2col {
            width: {{ $labelWidth }}mm;
            vertical-align: top;
            padding: {{ $cellPadding }};
            text-align: center;
            overflow: hidden;
        }
        td.gap-2col {
            width: {{ $gap }}mm;
            padding: 0;
        }
        td.side-2col {
            width: {{ $sideMargins }}mm;
            padding: 0;
        }
        /* Tipografías legibles y nítidas */
        .text-entity {
            font-size: {{ $fontSizeEntity }};
            font-weight: bold;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            color: #000;
        }
        .text-sub {
            font-size: {{ $fontSizeSub }};
            font-weight: bold;
            line-height: 1;
            text-transform: uppercase;
            color: #444;
            margin-top: 0.2mm;
        }
        .barcode-img {
            display: block;
            margin: 0.3mm auto;
            max-width: 95%;
            height: {{ $barcodeH }}mm;
        }
        .text-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: {{ $fontSizeCode }};
            font-weight: bold;
            line-height: 1.1;
            letter-spacing: 0.5px;
            color: #000;
            margin: 0.2mm 0;
        }
        .text-name {
            font-size: {{ $fontSizeName }};
            font-weight: 600;
            line-height: 1.1;
            color: #111;
            overflow: hidden;
        }
        .text-extra {
            font-size: {{ $fontSizeExtra }};
            line-height: 1;
            color: #555;
            margin-top: 0.2mm;
        }
        /* QR Horizontal */
        table.qr-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0;
            padding: 0;
        }
        td.qr-col {
            width: {{ $qrColWidth }}mm;
            vertical-align: middle;
            text-align: center;
            padding-right: 0.5mm;
        }
        td.info-col {
            vertical-align: middle;
            text-align: left;
            padding-left: 0.5mm;
            overflow: hidden;
        }
        .qr-image {
            width: {{ $qrSize }}mm;
            height: {{ $qrSize }}mm;
            display: block;
            margin: 0 auto;
        }
    </style>
</head>
<body>

@if($columns === 1)
    {{-- ===== MODO 1 COLUMNA ===== --}}
    @foreach($items as $idx => $item)
        <div class="page-container-1col @if($idx > 0) break-before @endif">
            @if($codeType === 'qr' && $qrLayout === 'horizontal')
                <table class="qr-table">
                    <tr>
                        <td class="qr-col">
                            <img src="{{ $item['image'] }}" class="qr-image" alt="QR">
                        </td>
                        <td class="info-col">
                            @if($showEntity)
                                <div class="text-entity">{{ $entityText }}</div>
                            @endif
                            @if($showSubtitle)
                                <div class="text-sub">{{ $subtitleText }}</div>
                            @endif
                            @if($showCode)
                                <div class="text-code">{{ $item['code'] }}</div>
                            @endif
                            @if($showName)
                                <div class="text-name">{{ Str::limit($item['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                            @endif
                            @if($showSeries && !empty($item['asset']['numero_serie']))
                                <div class="text-extra">S/N: {{ $item['asset']['numero_serie'] }}</div>
                            @endif
                            @if($showOffice && !empty($item['asset']['oficina_actual']))
                                <div class="text-extra">Of: {{ is_array($item['asset']['oficina_actual']) ? ($item['asset']['oficina_actual']['nombre'] ?? '') : $item['asset']['oficina_actual'] }}</div>
                            @endif
                        </td>
                    </tr>
                </table>
            @elseif($codeType === 'qr')
                @if($showEntity)
                    <div class="text-entity">{{ $entityText }}</div>
                @endif
                @if($showSubtitle)
                    <div class="text-sub">{{ $subtitleText }}</div>
                @endif
                <div style="margin: 0.3mm 0;">
                    <img src="{{ $item['image'] }}" style="width: {{ $qrSize }}mm; height: {{ $qrSize }}mm; display: inline-block;" alt="QR">
                </div>
                @if($showCode)
                    <div class="text-code">{{ $item['code'] }}</div>
                @endif
                @if($showName)
                    <div class="text-name">{{ Str::limit($item['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                @endif
                @if($showSeries && !empty($item['asset']['numero_serie']))
                    <div class="text-extra">S/N: {{ $item['asset']['numero_serie'] }}</div>
                @endif
            @else
                @if($showEntity)
                    <div class="text-entity">{{ $entityText }}</div>
                @endif
                @if($showSubtitle)
                    <div class="text-sub">{{ $subtitleText }}</div>
                @endif
                <img src="{{ $item['image'] }}" class="barcode-img" alt="Barcode">
                @if($showCode)
                    <div class="text-code">{{ $item['code'] }}</div>
                @endif
                @if($showName)
                    <div class="text-name">{{ Str::limit($item['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                @endif
                @if($showSeries && !empty($item['asset']['numero_serie']))
                    <div class="text-extra">S/N: {{ $item['asset']['numero_serie'] }}</div>
                @endif
            @endif
        </div>
    @endforeach

@else
    {{-- ===== MODO 2 COLUMNAS ===== --}}
    @foreach(array_chunk($items, 2) as $rowIdx => $rowItems)
        <div class="row-container-2col @if($rowIdx > 0) break-before @endif">
            <table class="grid-2col">
                <tr>
                    @if($sideMargins > 0)
                        <td class="side-2col"></td>
                    @endif

                    {{-- Etiqueta Izquierda --}}
                    @php $item1 = $rowItems[0]; @endphp
                    <td class="lbl-2col">
                        @if($codeType === 'qr' && $qrLayout === 'horizontal')
                            <table class="qr-table">
                                <tr>
                                    <td class="qr-col">
                                        <img src="{{ $item1['image'] }}" class="qr-image" alt="QR">
                                    </td>
                                    <td class="info-col">
                                        @if($showEntity)
                                            <div class="text-entity">{{ $entityText }}</div>
                                        @endif
                                        @if($showSubtitle)
                                            <div class="text-sub">{{ $subtitleText }}</div>
                                        @endif
                                        @if($showCode)
                                            <div class="text-code">{{ $item1['code'] }}</div>
                                        @endif
                                        @if($showName)
                                            <div class="text-name">{{ Str::limit($item1['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                                        @endif
                                        @if($showSeries && !empty($item1['asset']['numero_serie']))
                                            <div class="text-extra">S/N: {{ $item1['asset']['numero_serie'] }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        @elseif($codeType === 'qr')
                            @if($showEntity)
                                <div class="text-entity">{{ $entityText }}</div>
                            @endif
                            @if($showSubtitle)
                                <div class="text-sub">{{ $subtitleText }}</div>
                            @endif
                            <div style="margin: 0.3mm 0;">
                                <img src="{{ $item1['image'] }}" style="width: {{ $qrSize }}mm; height: {{ $qrSize }}mm; display: inline-block;" alt="QR">
                            </div>
                            @if($showCode)
                                <div class="text-code">{{ $item1['code'] }}</div>
                            @endif
                            @if($showName)
                                <div class="text-name">{{ Str::limit($item1['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                            @endif
                            @if($showSeries && !empty($item1['asset']['numero_serie']))
                                <div class="text-extra">S/N: {{ $item1['asset']['numero_serie'] }}</div>
                            @endif
                        @else
                            @if($showEntity)
                                <div class="text-entity">{{ $entityText }}</div>
                            @endif
                            @if($showSubtitle)
                                <div class="text-sub">{{ $subtitleText }}</div>
                            @endif
                            <img src="{{ $item1['image'] }}" class="barcode-img" alt="Barcode">
                            @if($showCode)
                                <div class="text-code">{{ $item1['code'] }}</div>
                            @endif
                            @if($showName)
                                <div class="text-name">{{ Str::limit($item1['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                            @endif
                            @if($showSeries && !empty($item1['asset']['numero_serie']))
                                <div class="text-extra">S/N: {{ $item1['asset']['numero_serie'] }}</div>
                            @endif
                        @endif
                    </td>

                    {{-- Brecha/Gap central entre columnas --}}
                    <td class="gap-2col"></td>

                    {{-- Etiqueta Derecha (si existe) --}}
                    @if(isset($rowItems[1]))
                        @php $item2 = $rowItems[1]; @endphp
                        <td class="lbl-2col">
                            @if($codeType === 'qr' && $qrLayout === 'horizontal')
                                <table class="qr-table">
                                    <tr>
                                        <td class="qr-col">
                                            <img src="{{ $item2['image'] }}" class="qr-image" alt="QR">
                                        </td>
                                        <td class="info-col">
                                            @if($showEntity)
                                                <div class="text-entity">{{ $entityText }}</div>
                                            @endif
                                            @if($showSubtitle)
                                                <div class="text-sub">{{ $subtitleText }}</div>
                                            @endif
                                            @if($showCode)
                                                <div class="text-code">{{ $item2['code'] }}</div>
                                            @endif
                                            @if($showName)
                                                <div class="text-name">{{ Str::limit($item2['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                                            @endif
                                            @if($showSeries && !empty($item2['asset']['numero_serie']))
                                                <div class="text-extra">S/N: {{ $item2['asset']['numero_serie'] }}</div>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                            @elseif($codeType === 'qr')
                                @if($showEntity)
                                    <div class="text-entity">{{ $entityText }}</div>
                                @endif
                                @if($showSubtitle)
                                    <div class="text-sub">{{ $subtitleText }}</div>
                                @endif
                                <div style="margin: 0.3mm 0;">
                                    <img src="{{ $item2['image'] }}" style="width: {{ $qrSize }}mm; height: {{ $qrSize }}mm; display: inline-block;" alt="QR">
                                </div>
                                @if($showCode)
                                    <div class="text-code">{{ $item2['code'] }}</div>
                                @endif
                                @if($showName)
                                    <div class="text-name">{{ Str::limit($item2['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                                @endif
                                @if($showSeries && !empty($item2['asset']['numero_serie']))
                                    <div class="text-extra">S/N: {{ $item2['asset']['numero_serie'] }}</div>
                                @endif
                            @else
                                @if($showEntity)
                                    <div class="text-entity">{{ $entityText }}</div>
                                @endif
                                @if($showSubtitle)
                                    <div class="text-sub">{{ $subtitleText }}</div>
                                @endif
                                <img src="{{ $item2['image'] }}" class="barcode-img" alt="Barcode">
                                @if($showCode)
                                    <div class="text-code">{{ $item2['code'] }}</div>
                                @endif
                                @if($showName)
                                    <div class="text-name">{{ Str::limit($item2['asset']['denominacion'] ?? '', $nameLimit) }}</div>
                                @endif
                                @if($showSeries && !empty($item2['asset']['numero_serie']))
                                    <div class="text-extra">S/N: {{ $item2['asset']['numero_serie'] }}</div>
                                @endif
                            @endif
                        </td>
                    @else
                        <td class="lbl-2col" style="border: none;"></td>
                    @endif

                    @if($sideMargins > 0)
                        <td class="side-2col"></td>
                    @endif
                </tr>
            </table>
        </div>
    @endforeach
@endif

</body>
</html>
