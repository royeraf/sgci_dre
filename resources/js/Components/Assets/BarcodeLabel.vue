<script setup>
import { onMounted, watch, useTemplateRef } from 'vue';
import JsBarcode from 'jsbarcode';

const props = defineProps({
    asset: {
        type: Object,
        required: true
    },
    size: {
        type: String,
        default: 'medium',
        validator: (v) => ['small', 'medium', 'large'].includes(v)
    }
});

const svgRef = useTemplateRef('barcodeSvg');

const sizeConfig = {
    small: { width: 1.2, height: 35, fontSize: 10, labelFontSize: 7 },
    medium: { width: 1.5, height: 50, fontSize: 12, labelFontSize: 9 },
    large: { width: 2, height: 70, fontSize: 14, labelFontSize: 11 },
};

const containerClasses = {
    small: 'w-[160px] p-1.5',
    medium: 'w-[240px] p-2.5',
    large: 'w-[320px] p-3.5',
};

const generateBarcode = () => {
    if (!svgRef.value || !props.asset.codigo_patrimonio) return;

    const config = sizeConfig[props.size];
    try {
        JsBarcode(svgRef.value, props.asset.codigo_patrimonio, {
            format: 'CODE128',
            width: config.width,
            height: config.height,
            fontSize: config.fontSize,
            displayValue: true,
            margin: 2,
            textMargin: 2,
            font: 'monospace',
            fontOptions: 'bold',
        });
    } catch (e) {
        console.error('Error generating barcode:', e);
    }
};

onMounted(generateBarcode);

watch(() => [props.asset.codigo_patrimonio, props.size], generateBarcode);
</script>

<template>
    <div :class="['barcode-label border border-slate-300 rounded-lg bg-white flex flex-col items-center text-center', containerClasses[size]]">
        <p class="font-bold text-slate-700 tracking-wide leading-tight"
           :class="size === 'small' ? 'text-[7px]' : size === 'medium' ? 'text-[9px]' : 'text-[11px]'">
            DRE APURÍMAC
        </p>

        <svg ref="barcodeSvg" class="w-full"></svg>

        <p class="font-semibold text-slate-600 leading-tight truncate w-full"
           :class="size === 'small' ? 'text-[6px]' : size === 'medium' ? 'text-[8px]' : 'text-[10px]'"
           :title="asset.denominacion">
            {{ asset.denominacion }}
        </p>
    </div>
</template>

<style scoped>
.barcode-label {
    break-inside: avoid;
    page-break-inside: avoid;
}
</style>
