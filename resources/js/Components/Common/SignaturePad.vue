<script setup>
import { ref, onMounted } from 'vue';
import { Eraser } from 'lucide-vue-next';

defineProps({
    label: { type: String, default: 'Firma táctil' },
    invalid: { type: Boolean, default: false },
});

const canvasRef = ref(null);
const hasInk = ref(false);
let ctx = null;
let drawing = false;

const configureContext = () => {
    const canvas = canvasRef.value;
    if (!canvas) return;
    const rect = canvas.getBoundingClientRect();
    const ratio = window.devicePixelRatio || 1;
    canvas.width = rect.width * ratio;
    canvas.height = rect.height * ratio;
    ctx = canvas.getContext('2d');
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    ctx.lineWidth = 2.2;
    ctx.lineCap = 'round';
    ctx.strokeStyle = '#0f172a';
};

const pointFromEvent = (e) => {
    const rect = canvasRef.value.getBoundingClientRect();
    return { x: e.clientX - rect.left, y: e.clientY - rect.top };
};

const onPointerDown = (e) => {
    if (!ctx) configureContext();
    drawing = true;
    hasInk.value = true;
    canvasRef.value.setPointerCapture(e.pointerId);
    const p = pointFromEvent(e);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y);
    e.preventDefault();
};

const onPointerMove = (e) => {
    if (!drawing) return;
    const p = pointFromEvent(e);
    ctx.lineTo(p.x, p.y);
    ctx.stroke();
    e.preventDefault();
};

const stopDrawing = () => {
    drawing = false;
};

const clear = () => {
    if (!ctx || !canvasRef.value) return;
    ctx.clearRect(0, 0, canvasRef.value.width, canvasRef.value.height);
    hasInk.value = false;
};

const getDataUrl = () => canvasRef.value?.toDataURL('image/png');

onMounted(configureContext);

defineExpose({ clear, getDataUrl, hasInk });
</script>

<template>
    <div>
        <div class="mb-1.5 flex items-center justify-between">
            <label class="block text-sm font-bold text-slate-700">{{ label }} <span class="text-red-500">*</span></label>
            <button type="button" @click="clear" :disabled="!hasInk"
                class="cursor-pointer inline-flex items-center gap-1 text-xs font-bold text-teal-700 transition-colors hover:text-teal-900 disabled:cursor-not-allowed disabled:opacity-40">
                <Eraser class="h-3.5 w-3.5" />
                Rehacer firma
            </button>
        </div>
        <canvas ref="canvasRef" aria-label="Área de firma táctil"
            class="block h-36 w-full touch-none rounded-xl border-2 border-dashed bg-teal-50/40 transition-colors"
            :class="invalid ? 'border-red-400' : 'border-teal-600'"
            @pointerdown="onPointerDown" @pointermove="onPointerMove" @pointerup="stopDrawing"
            @pointercancel="stopDrawing"></canvas>
    </div>
</template>
