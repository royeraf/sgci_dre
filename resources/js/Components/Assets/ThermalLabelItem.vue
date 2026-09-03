<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import JsBarcode from 'jsbarcode';
import QRCode from 'qrcode';

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
    codeType: {
        type: String,
        default: 'barcode', // 'barcode' | 'qr'
    },
    labelWidth: {
        type: Number,
        default: 50.8, // mm
    },
    labelHeight: {
        type: Number,
        default: 25.4, // mm
    },
    qrLayout: {
        type: String,
        default: 'horizontal', // 'horizontal' | 'vertical'
    },
    options: {
        type: Object,
        default: () => ({
            showEntity: true,
            showSubtitle: true,
            showCode: true,
            showName: true,
            showSeries: true,
            showOffice: false,
            entityText: 'DRE HUÁNUCO',
            subtitleText: 'INVENTARIO 2026',
        }),
    },
    scale: {
        type: Number,
        default: 1.0, // Multiplicador de escala para previsualización
    },
    isPrintMode: {
        type: Boolean,
        default: false,
    },
});

const barcodeSvgRef = ref(null);
const qrDataUrl = ref('');

const assetCode = computed(() => {
    return props.asset.codigo_barras ||
           props.asset.codigo_completo ||
           props.asset.codigo_patrimonio ||
           '00000000';
});

const assetName = computed(() => {
    return props.asset.denominacion || 'BIEN PATRIMONIAL';
});

const assetSeries = computed(() => {
    return props.asset.numero_serie || '';
});

const assetOffice = computed(() => {
    if (typeof props.asset.oficina_actual === 'object' && props.asset.oficina_actual) {
        return props.asset.oficina_actual.nombre || '';
    }
    return props.asset.oficina_actual || '';
});

// Renderizar códigos según el tipo
const renderCodes = async () => {
    await nextTick();

    if (props.codeType === 'barcode') {
        if (barcodeSvgRef.value) {
            try {
                JsBarcode(barcodeSvgRef.value, assetCode.value, {
                    format: 'CODE128',
                    displayValue: false,
                    margin: 0,
                    height: Math.max(22, Math.round(props.labelHeight * 1.5)),
                    width: props.labelWidth < 40 ? 1 : 1.3,
                });
            } catch (err) {
                console.warn('Error rendering barcode:', err);
            }
        }
    } else {
        try {
            const url = await QRCode.toDataURL(assetCode.value, {
                margin: 0,
                width: 160,
                errorCorrectionLevel: 'M',
            });
            qrDataUrl.value = url;
        } catch (err) {
            console.warn('Error rendering QR code:', err);
        }
    }
};

watch([() => props.asset, () => props.codeType, () => props.labelWidth, () => props.labelHeight], () => {
    renderCodes();
});

onMounted(() => {
    renderCodes();
});

// Estilos dinámicos calculados en milímetros
const containerStyle = computed(() => {
    if (props.isPrintMode) {
        return {
            width: `${props.labelWidth}mm`,
            height: `${props.labelHeight}mm`,
            padding: '1mm 1.5mm',
        };
    }
    // En previsualización en pantalla, usamos píxeles basados en escala (3.78px = 1mm aprox a 96dpi)
    const pxPerMm = 3.78 * props.scale;
    return {
        width: `${Math.round(props.labelWidth * pxPerMm)}px`,
        height: `${Math.round(props.labelHeight * pxPerMm)}px`,
        padding: `${Math.max(2, Math.round(1.5 * props.scale))}px ${Math.max(3, Math.round(2 * props.scale))}px`,
    };
});

// Fuentes calculadas según el alto de la etiqueta y la escala
const fontSizes = computed(() => {
    const isSmall = props.labelHeight <= 26;
    const baseMultiplier = props.isPrintMode ? 1 : props.scale;

    return {
        entity: isSmall ? `${Math.round(8 * baseMultiplier)}px` : `${Math.round(9.5 * baseMultiplier)}px`,
        sub: isSmall ? `${Math.round(6.5 * baseMultiplier)}px` : `${Math.round(7.5 * baseMultiplier)}px`,
        code: isSmall ? `${Math.round(9 * baseMultiplier)}px` : `${Math.round(10.5 * baseMultiplier)}px`,
        name: isSmall ? `${Math.round(7 * baseMultiplier)}px` : `${Math.round(8.5 * baseMultiplier)}px`,
        extra: isSmall ? `${Math.round(6.5 * baseMultiplier)}px` : `${Math.round(7.5 * baseMultiplier)}px`,
        qrSize: isSmall
            ? (props.isPrintMode ? '16mm' : `${Math.round(16 * 3.78 * props.scale)}px`)
            : (props.isPrintMode ? '19mm' : `${Math.round(19 * 3.78 * props.scale)}px`),
    };
});
</script>

<template>
    <div
        :style="containerStyle"
        class="thermal-label bg-white border border-slate-300 rounded shadow-sm flex flex-col justify-between items-center text-center overflow-hidden select-none box-border"
    >
        <!-- CASO 1: CÓDIGO QR HORIZONTAL (QR Izquierda, Texto Derecha) -->
        <template v-if="codeType === 'qr' && qrLayout === 'horizontal'">
            <div class="w-full h-full flex items-center justify-between gap-1.5 text-left">
                <!-- QR Image -->
                <div class="flex-shrink-0 flex items-center justify-center">
                    <img
                        v-if="qrDataUrl"
                        :src="qrDataUrl"
                        :style="{ width: fontSizes.qrSize, height: fontSizes.qrSize }"
                        alt="QR"
                        class="object-contain"
                    />
                </div>

                <!-- Info derecha -->
                <div class="flex-1 min-w-0 flex flex-col justify-center overflow-hidden">
                    <div
                        v-if="options.showEntity"
                        :style="{ fontSize: fontSizes.entity }"
                        class="font-black text-slate-900 leading-tight uppercase truncate"
                    >
                        {{ options.entityText || 'DRE HUÁNUCO' }}
                    </div>

                    <div
                        v-if="options.showSubtitle"
                        :style="{ fontSize: fontSizes.sub }"
                        class="font-semibold text-slate-500 leading-none uppercase truncate"
                    >
                        {{ options.subtitleText || 'INVENTARIO 2026' }}
                    </div>

                    <div
                        v-if="options.showCode"
                        :style="{ fontSize: fontSizes.code }"
                        class="font-mono font-bold text-black tracking-tight leading-tight mt-0.5"
                    >
                        {{ assetCode }}
                    </div>

                    <div
                        v-if="options.showName"
                        :style="{ fontSize: fontSizes.name }"
                        class="font-semibold text-slate-700 leading-tight line-clamp-2"
                        :title="assetName"
                    >
                        {{ assetName }}
                    </div>

                    <div
                        v-if="options.showSeries && assetSeries"
                        :style="{ fontSize: fontSizes.extra }"
                        class="font-mono text-slate-500 truncate leading-none mt-0.5"
                    >
                        S/N: {{ assetSeries }}
                    </div>

                    <div
                        v-if="options.showOffice && assetOffice"
                        :style="{ fontSize: fontSizes.extra }"
                        class="text-slate-500 truncate leading-none mt-0.5"
                    >
                        Of: {{ assetOffice }}
                    </div>
                </div>
            </div>
        </template>

        <!-- CASO 2: CÓDIGO QR VERTICAL (Centrado) -->
        <template v-else-if="codeType === 'qr'">
            <div class="w-full flex flex-col items-center">
                <div
                    v-if="options.showEntity"
                    :style="{ fontSize: fontSizes.entity }"
                    class="font-black text-slate-900 leading-tight uppercase truncate w-full"
                >
                    {{ options.entityText || 'DRE HUÁNUCO' }}
                </div>
                <div
                    v-if="options.showSubtitle"
                    :style="{ fontSize: fontSizes.sub }"
                    class="font-semibold text-slate-500 leading-none uppercase truncate w-full"
                >
                    {{ options.subtitleText || 'INVENTARIO 2026' }}
                </div>
            </div>

            <div class="my-0.5 flex-1 flex items-center justify-center">
                <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    :style="{ width: fontSizes.qrSize, height: fontSizes.qrSize }"
                    alt="QR"
                    class="object-contain"
                />
            </div>

            <div class="w-full flex flex-col items-center">
                <div
                    v-if="options.showCode"
                    :style="{ fontSize: fontSizes.code }"
                    class="font-mono font-bold text-black tracking-wider leading-tight"
                >
                    {{ assetCode }}
                </div>
                <div
                    v-if="options.showName"
                    :style="{ fontSize: fontSizes.name }"
                    class="font-semibold text-slate-700 leading-tight truncate w-full"
                    :title="assetName"
                >
                    {{ assetName }}
                </div>
                <div
                    v-if="options.showSeries && assetSeries"
                    :style="{ fontSize: fontSizes.extra }"
                    class="font-mono text-slate-500 truncate w-full leading-none"
                >
                    S/N: {{ assetSeries }}
                </div>
            </div>
        </template>

        <!-- CASO 3: CÓDIGO DE BARRAS (Code 128) -->
        <template v-else>
            <div class="w-full flex flex-col items-center">
                <div
                    v-if="options.showEntity"
                    :style="{ fontSize: fontSizes.entity }"
                    class="font-black text-slate-900 leading-tight uppercase truncate w-full"
                >
                    {{ options.entityText || 'DRE HUÁNUCO' }}
                </div>
                <div
                    v-if="options.showSubtitle"
                    :style="{ fontSize: fontSizes.sub }"
                    class="font-semibold text-slate-500 leading-none uppercase truncate w-full"
                >
                    {{ options.subtitleText || 'INVENTARIO 2026' }}
                </div>
            </div>

            <!-- Contenedor del Barcode SVG -->
            <div class="w-full my-0.5 flex-1 flex items-center justify-center overflow-hidden">
                <svg ref="barcodeSvgRef" class="w-full max-h-full"></svg>
            </div>

            <div class="w-full flex flex-col items-center">
                <div
                    v-if="options.showCode"
                    :style="{ fontSize: fontSizes.code }"
                    class="font-mono font-bold text-black tracking-wider leading-tight"
                >
                    {{ assetCode }}
                </div>
                <div
                    v-if="options.showName"
                    :style="{ fontSize: fontSizes.name }"
                    class="font-semibold text-slate-700 leading-tight truncate w-full"
                    :title="assetName"
                >
                    {{ assetName }}
                </div>
                <div
                    v-if="options.showSeries && assetSeries"
                    :style="{ fontSize: fontSizes.extra }"
                    class="font-mono text-slate-500 truncate w-full leading-none"
                >
                    S/N: {{ assetSeries }}
                </div>
                <div
                    v-if="options.showOffice && assetOffice"
                    :style="{ fontSize: fontSizes.extra }"
                    class="text-slate-500 truncate w-full leading-none"
                >
                    Of: {{ assetOffice }}
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.thermal-label {
    box-sizing: border-box;
    break-inside: avoid;
    page-break-inside: avoid;
}
</style>
