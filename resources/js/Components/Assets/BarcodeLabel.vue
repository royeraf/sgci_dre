<script setup>
import { computed } from 'vue';

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

const containerClasses = {
    small: 'w-[160px] p-1.5',
    medium: 'w-[240px] p-2.5',
    large: 'w-[320px] p-3.5',
};

const imgHeight = {
    small: 'h-[35px]',
    medium: 'h-[50px]',
    large: 'h-[70px]',
};

const barcodeUrl = computed(() => `/assets/${props.asset.id}/barcode-image`);
const barcodeText = computed(() => props.asset.codigo_barras || props.asset.codigo_completo || props.asset.codigo_patrimonio);
</script>

<template>
    <div
        :class="['barcode-label border border-slate-300 rounded-lg bg-white flex flex-col items-center text-center', containerClasses[size]]">
        <p class="font-bold text-slate-700 tracking-wide leading-tight"
            :class="size === 'small' ? 'text-[7px]' : size === 'medium' ? 'text-[9px]' : 'text-[11px]'">
            DRE HUÁNUCO
        </p>
        <p class="font-semibold text-slate-500 leading-tight"
            :class="size === 'small' ? 'text-[6px]' : size === 'medium' ? 'text-[7px]' : 'text-[9px]'">
            Inventario 2026
        </p>

        <img :src="barcodeUrl" :alt="barcodeText" :class="['w-full my-0.5', imgHeight[size]]" />

        <p class="font-mono font-bold text-slate-800 leading-tight"
            :class="size === 'small' ? 'text-[8px]' : size === 'medium' ? 'text-[10px]' : 'text-[12px]'">
            {{ barcodeText }}
        </p>

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
