<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import ResolutionSkeleton from './ResolutionSkeleton.vue';
import { ChevronLeft, ChevronRight, Download, ExternalLink, X, ZoomIn, ZoomOut } from 'lucide-vue-next';

const props = defineProps({ record: { type: Object, default: null }, position: { type: Number, default: 0 }, total: { type: Number, default: 1 } });
const emit = defineEmits(['close', 'previous', 'next']);
const dialog = ref(null);
const viewport = ref(null);
const canvas = ref(null);
const loading = ref(false);
const error = ref('');
const pageNumber = ref(1);
const pageInput = ref('1');
const pageCount = ref(0);
const zoom = ref(1);
let previousFocus = null;
let pdf = null;
let task = null;
let renderTask = null;
let observer = null;
let resizeTimer = null;
let documentToken = 0;
let renderToken = 0;
let library = null;
let availabilityRequest = null;

async function getLibrary() {
    if (library) return library;
    const [lib, worker] = await Promise.all([import('pdfjs-dist'), import('pdfjs-dist/build/pdf.worker.min.mjs?url')]);
    lib.GlobalWorkerOptions.workerSrc = worker.default;
    library = lib;
    return lib;
}

function cleanup() {
    documentToken++; renderToken++;
    availabilityRequest?.abort(); availabilityRequest = null;
    observer?.disconnect(); observer = null;
    clearTimeout(resizeTimer);
    renderTask?.cancel(); renderTask = null;
    task?.destroy().catch(() => {}); task = null;
    pdf = null;
    loading.value = false;
    pageCount.value = 0;
}

async function renderPage() {
    if (!pdf || !canvas.value || !viewport.value) return;
    if (!Number.isInteger(pageNumber.value) || pageNumber.value < 1 || pageNumber.value > pdf.numPages) return;
    const token = ++renderToken;
    loading.value = true;
    error.value = '';
    const previousRender = renderTask;
    previousRender?.cancel();
    if (previousRender) await previousRender.promise.catch(() => {});
    try {
        const page = await pdf.getPage(pageNumber.value);
        if (token !== renderToken || !canvas.value || !viewport.value) return;
        const original = page.getViewport({ scale: 1 });
        const fit = Math.max(0.2, (viewport.value.clientWidth - 32) / original.width);
        const view = page.getViewport({ scale: fit * zoom.value });
        const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);
        const target = canvas.value;
        target.width = Math.floor(view.width * pixelRatio);
        target.height = Math.floor(view.height * pixelRatio);
        target.style.width = `${view.width}px`;
        target.style.height = `${view.height}px`;
        renderTask = page.render({ canvasContext: target.getContext('2d'), viewport: view, transform: pixelRatio === 1 ? null : [pixelRatio, 0, 0, pixelRatio, 0, 0] });
        await renderTask.promise;
        if (token === renderToken) renderTask = null;
    } catch (e) {
        if (token === renderToken && e.name !== 'RenderingCancelledException') error.value = 'No se pudo mostrar esta página. Puede descargar el PDF o abrirlo en otra pestaña.';
    } finally { if (token === renderToken) loading.value = false; }
}

async function load(record) {
    cleanup();
    if (viewport.value) { viewport.value.scrollTop = 0; viewport.value.scrollLeft = 0; }
    const token = documentToken;
    loading.value = true; error.value = ''; pageNumber.value = 1; pageInput.value = '1'; zoom.value = 1;
    try {
        availabilityRequest = new AbortController();
        const response = await fetch(`/resoluciones/${record.id}/archivo`, { method: 'HEAD', credentials: 'same-origin', headers: { Accept: 'application/pdf' }, signal: availabilityRequest.signal });
        if (token !== documentToken) return;
        if (response.redirected || [401, 403, 419].includes(response.status)) throw new Error('Su sesión terminó o ya no tiene acceso. Ingrese al sistema y vuelva a abrir el documento.');
        if (response.status === 404) throw new Error('El archivo no está disponible. Compruebe que la unidad del archivo esté conectada y vuelva a intentarlo.');
        if (!response.ok || !response.headers.get('content-type')?.toLowerCase().includes('application/pdf')) throw new Error('El servidor no devolvió un PDF. Vuelva a intentarlo o abra el archivo en otra pestaña.');
        const lib = await getLibrary();
        if (token !== documentToken) return;
        // Request pages as needed, instead of drawing every page of a bound volume.
        task = lib.getDocument({ url: `/resoluciones/${record.id}/archivo`, withCredentials: true, disableAutoFetch: true, disableStream: true, isEvalSupported: false });
        const document = await task.promise;
        if (token !== documentToken) return;
        pdf = document; pageCount.value = pdf.numPages;
        await nextTick();
        await renderPage();
        if (token !== documentToken) return;
        observer = new ResizeObserver(() => { clearTimeout(resizeTimer); resizeTimer = setTimeout(renderPage, 120); });
        if (viewport.value) observer.observe(viewport.value);
    } catch (e) {
        if (token === documentToken) { loading.value = false; error.value = e.name === 'Error' && e.message ? e.message : 'No se pudo abrir el PDF. Verifique que el archivo esté disponible o intente descargarlo.'; }
    }
}

watch(() => props.record, async record => {
    if (record) {
        if (!dialog.value?.open) previousFocus = document.activeElement;
        await nextTick();
        if (dialog.value && !dialog.value.open) dialog.value.showModal();
        await load(record);
    } else { cleanup(); if (dialog.value?.open) dialog.value.close(); }
});
watch(pageNumber, value => {
    pageInput.value = String(value);
    if (viewport.value) { viewport.value.scrollTop = 0; viewport.value.scrollLeft = 0; }
});
watch([pageNumber, zoom], () => { if (pdf) renderPage(); });
function goToPage() {
    pageNumber.value = Math.max(1, Math.min(pageCount.value || 1, Math.trunc(Number(pageInput.value)) || 1));
    pageInput.value = String(pageNumber.value);
}
function close() {
    dialog.value?.close();
    emit('close');
    previousFocus?.focus?.();
}
onBeforeUnmount(() => { cleanup(); dialog.value?.close(); });
</script>

<template>
    <dialog ref="dialog" class="resolution-preview" aria-labelledby="resolution-preview-title" @cancel.prevent="close">
        <div v-if="record" class="flex h-full flex-col">
            <header class="preview-header flex shrink-0 flex-wrap items-start justify-between gap-3 border-b border-slate-200 bg-white p-3 sm:p-4">
                <div class="min-w-0 flex-1"><p class="mb-1 text-xs font-semibold text-blue-700">{{ record.series || 'Resolución' }}<template v-if="record.number_start"> N.º {{ record.number_start }}<template v-if="record.number_end">–{{ record.number_end }}</template></template> · {{ record.year || 'Año por identificar' }}</p><h2 id="resolution-preview-title" class="break-words text-sm font-semibold text-slate-900">{{ record.title || record.filename }}</h2><p v-if="record.folder" class="mt-1 text-xs text-slate-500">{{ record.folder }}</p></div>
                <button type="button" class="preview-action" autofocus aria-label="Cerrar vista previa" @click="close"><X class="h-5 w-5" /></button>
                <nav class="flex w-full flex-wrap gap-2 text-sm" aria-label="Acciones del documento">
                    <a :href="`/resoluciones/${record.id}/archivo?download=1`" class="preview-action"><Download class="h-4 w-4" /> Descargar PDF</a>
                    <a :href="`/resoluciones/${record.id}/archivo`" target="_blank" rel="noopener" class="preview-action"><ExternalLink class="h-4 w-4" /> Abrir en otra pestaña</a>
                </nav>
            </header>
            <div class="flex shrink-0 flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2 text-sm">
                <nav class="flex items-center gap-1" aria-label="Páginas del PDF"><button class="preview-action" :disabled="pageNumber <= 1 || !pageCount" aria-label="Página anterior" @click="pageNumber--"><ChevronLeft class="h-4 w-4" /></button><form class="flex items-center gap-2" @submit.prevent="goToPage"><label for="resolution-page-input">Página</label><input id="resolution-page-input" v-model="pageInput" aria-label="Número de página" class="w-16 rounded border border-slate-300 bg-white px-2 py-1 tabular-nums" type="number" min="1" :max="pageCount || 1" :disabled="!pageCount" @change="goToPage" /> de {{ pageCount || '—' }}</form><button class="preview-action" :disabled="pageNumber >= pageCount || !pageCount" aria-label="Página siguiente" @click="pageNumber++"><ChevronRight class="h-4 w-4" /></button></nav>
                <div class="flex items-center gap-1"><button class="preview-action" :disabled="zoom <= 0.5 || !pageCount" aria-label="Reducir zoom" @click="zoom = Math.max(0.5, zoom - 0.25)"><ZoomOut class="h-4 w-4" /></button><span class="w-12 text-center tabular-nums">{{ Math.round(zoom * 100) }} %</span><button class="preview-action" :disabled="zoom >= 2 || !pageCount" aria-label="Aumentar zoom" @click="zoom = Math.min(2, zoom + 0.25)"><ZoomIn class="h-4 w-4" /></button></div>
            </div>
            <div ref="viewport" class="relative min-h-0 w-full flex-1 overflow-auto bg-slate-200 p-4" :aria-busy="loading">
                <ResolutionSkeleton v-if="loading && !error" variant="document" />
                <div v-if="error" role="alert" class="rounded-lg bg-white p-5 text-sm text-red-800"><p>{{ error }}</p><button class="preview-action mt-3" @click="load(record)">Reintentar</button></div>
                <canvas v-show="!error && !loading && pageCount > 0" ref="canvas" class="mx-auto bg-white" role="img" :aria-label="`Página ${pageNumber} de ${record.filename}`" />
            </div>
            <footer v-if="total > 1" class="document-navigation"><button class="preview-action" :disabled="position <= 0" @click="emit('previous')"><ChevronLeft :size="16" /><span>Resolución anterior</span></button><span class="navigation-position">Documento {{ position + 1 }} / {{ total }}</span><button class="preview-action" :disabled="position >= total - 1" @click="emit('next')"><span>Siguiente resolución</span><ChevronRight :size="16" /></button></footer>
        </div>
    </dialog>
</template>

<style scoped>
.resolution-preview { width:min(1200px,96vw); height:94dvh; max-width:96vw; max-height:94dvh; margin:auto; padding:0; border:0; border-radius:12px; background:#e3eaf4; font-family:'Inter',ui-sans-serif,system-ui,sans-serif; }
.resolution-preview::backdrop { background:rgb(15 23 42 / .72); }
.preview-header { background:#13243d; border-color:#344660; padding:20px 22px; }
.preview-header h2 { color:#f1f5ff; font-size:14px; font-weight:550; line-height:1.65; }
.preview-header p:first-child { color:#a9c6ff; font-size:12px; }
.preview-header p:last-child { color:#b4c3da; font-size:11px; }
.preview-header .preview-action { background:#253752; color:#e7efff; border:1px solid #3b4f6c; font-size:12px; }
.preview-header .preview-action:hover { background:#344a6b; }
.resolution-preview canvas { box-shadow:0 4px 18px rgb(23 43 77 / .12); }

.preview-action { display:inline-flex; align-items:center; justify-content:center; gap:8px; min-height:40px; padding:8px 12px; border-radius:8px; color:#334155; background:#f8fafc; }
.preview-action:hover { background:#e2e8f0; }
.preview-action:disabled { opacity:.45; cursor:not-allowed; }
.preview-action:focus-visible { outline:2px solid #2563eb; outline-offset:2px; }
.preview-header .preview-action:focus-visible { outline-color:#a9c6ff; }
.document-navigation{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 16px;border-top:1px solid #e2e8f0;background:white;font-size:12px;flex-shrink:0}.navigation-position{text-align:center;font-variant-numeric:tabular-nums;color:#334155}.navigation-position small{display:block;font-size:10px;color:#64748b;margin-top:2px}
@media(max-width:639px) { .resolution-preview { width:100vw; max-width:100vw; height:100dvh; max-height:100dvh; border-radius:0; } }
@media(max-width:639px){.preview-header{padding:15px}.preview-header h2{font-size:12px}.preview-header .preview-action{font-size:11px}.document-navigation{padding:8px;gap:5px}.document-navigation .preview-action{padding:8px;font-size:11px}.document-navigation .preview-action span{max-width:66px;line-height:1.4}.navigation-position small{max-width:84px}.preview-action:focus-visible{outline-offset:0}}
</style>
