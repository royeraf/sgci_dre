<script setup>
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';
import axios from 'axios';
import { acquireResolutionUploadSlot } from '@/Composables/useResolutionUploads';
import { isAuxiliaryResolutionFile } from '@/Composables/resolutionFileRules';
import ResolutionCoverage from './ResolutionCoverage.vue';
import { Check, CircleAlert, FileText, FolderOpen, Loader2, Minus, Pause, Play, RotateCcw, Trash2, Upload, X } from 'lucide-vue-next';

const props = defineProps({ open: Boolean, maxMb: Number, currentYear: Number, jobId: Number, label: String });
// Keep this queue alive on an expired session instead of the global login redirect.
const uploadHttp = axios.create();
const emit = defineEmits(['close', 'busy', 'finished', 'reveal', 'dismiss', 'label']);
const dialog = ref(null);
const fileInput = ref(null);
const folderInput = ref(null);
const retryInput = ref(null), retryTarget = ref(null);
const queue = ref([]);
const busy = ref(false);
const paused = ref(false);
const year = ref('');
const message = ref('');
const dragging = ref(false);
const minimized = ref(false);
const motionEnabled = ref(false);
const dropped = ref(0);
const auxiliaryNames = ref([]);
const sequenceReport = ref(null);
const coverageMessage = ref('');
const catalogDirty = ref(false);

const queuePage = ref(1);
const pageSize = 50;
let previousFocus;
let windowAnimation;
let windowVersion = 0;
let runController;
watch(paused, value => { if (value) runController?.abort(); }, { flush: 'sync' });
const counts = computed(() => {
    const result = { done: 0, duplicate: 0, error: 0, pending: 0, processed: 0, bytes: 0, processedBytes: 0 };
    for (const item of queue.value) {
        result.bytes += item.file.size;
        if (['done', 'duplicate', 'error'].includes(item.status)) {
            result[item.status]++; result.processed++; result.processedBytes += item.file.size;
        }
        if (['pending', 'error'].includes(item.status) && item.file.size <= props.maxMb * 1024 ** 2) result.pending++;
    }
    return result;
});
const activeItems = computed(() => queue.value.filter(item => item.status === 'uploading'));
const transmitting = computed(() => activeItems.value.filter(item => !item.waitingForSlot).length);
const overallProgress = computed(() => {
    if (!queue.value.length) return 0;
    const fraction = activeItems.value.reduce((sum, item) => sum + Math.min(item.progress || 0, 99) / 100, 0);
    return Math.floor((counts.value.processed + fraction) / queue.value.length * 1000) / 10;
});
const pageCount = computed(() => Math.max(1, Math.ceil(queue.value.length / pageSize)));
const visibleQueue = computed(() => queue.value.slice((queuePage.value - 1) * pageSize, queuePage.value * pageSize));
const lastFailure = computed(() => queue.value.findLast(item => item.status === 'error'));
const format = value => new Intl.NumberFormat('es-PE').format(value);
const bytes = value => value < 1024 ** 2 ? `${(value / 1024).toFixed(0)} KB` : value < 1024 ** 3 ? `${(value / 1024 ** 2).toFixed(1)} MB` : `${(value / 1024 ** 3).toFixed(2)} GB`;
watch(pageCount, count => queuePage.value = Math.min(queuePage.value, count));
const labels = { pending: 'Listo para cargar', uploading: 'Cargando', done: 'Registrado', duplicate: 'Ya existía', error: 'Requiere atención' };

watch(() => props.open, async open => {
    if (open) { previousFocus = document.activeElement; minimized.value = false; await nextTick(); await showWindow(true); }
    else { if (queue.value.length) minimized.value = true; await showWindow(false); }
}, { immediate: true });
async function showWindow(open, animated = motionEnabled.value) {
    const element = dialog.value;
    if (!element) return;
    const version = ++windowVersion;
    const wasOpen = element.open;
    const current = wasOpen ? getComputedStyle(element) : null;
    const from = current ? { opacity: current.opacity, transform: current.transform } : { opacity: 0, transform: 'scale(0.96)' };
    windowAnimation?.cancel();
    if (open && !wasOpen) element.showModal();
    if (!open && !wasOpen) return;
    if (animated) {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const frames = reduce ? [{ opacity: from.opacity }, { opacity: open ? 1 : 0 }] : [from, { opacity: open ? 1 : 0, transform: open ? 'scale(1)' : 'scale(0.96)' }];
        const animation = element.animate(frames, { duration: reduce ? 100 : 200, easing: 'cubic-bezier(0.23, 1, 0.32, 1)', fill: 'both' });
        windowAnimation = animation;
        try { await animation.finished; } catch {}
        if (version !== windowVersion) return;
        if (!open) element.close();
        animation.cancel();
    } else if (!open) element.close();
}
async function close(event) {
    if (busy.value || counts.value.pending || counts.value.error) { await minimize(event); return; }
    motionEnabled.value = event?.detail > 0;
    await showWindow(false); emit('close'); previousFocus?.focus?.();
    if (catalogDirty.value) { catalogDirty.value = false; emit('finished'); }
    emit('dismiss');
}
async function minimize(event) { motionEnabled.value = event?.detail > 0; await showWindow(false); minimized.value = true; emit('close'); previousFocus?.focus?.(); if (!queue.value.length) emit('dismiss'); }
function restore(event) { motionEnabled.value = event?.detail > 0; emit('reveal'); }
function addFiles(files) {
    message.value = '';
    const input = Array.from(files || []);
    dropped.value = input.filter(f => !/\.pdf$/i.test(f.name)).length;
    const existing = new Set(queue.value.map(i => i.key));
    const additions = [];
    for (const file of input.filter(f => /\.pdf$/i.test(f.name))) {
        const key = file.webkitRelativePath || file.name;
        if (isAuxiliaryResolutionFile(file.name)) { if (!auxiliaryNames.value.includes(key)) auxiliaryNames.value.push(key); continue; }
        if (existing.has(key)) continue;
        existing.add(key);
        const folder = key.includes('/') ? key.slice(0, key.lastIndexOf('/')) : '';
        additions.push({ key, folder, file, sourceUnreadable: false, status: file.size > props.maxMb * 1024 ** 2 ? 'error' : 'pending', progress: 0, retrySeconds: 0, retryReason: '', batchYear: year.value, yearLocked: busy.value, error: file.size > props.maxMb * 1024 ** 2 ? `Supera el límite de ${props.maxMb} MB.` : '' });
    }
    // Keep the whole folder; only the visible list is paginated, never the upload queue.
    queue.value = queue.value.concat(additions);
    if (queue.value.length) emit('label', queue.value[0].key.includes('/') ? queue.value[0].key.split('/')[0] : props.label);
    if (!queue.value.length && !auxiliaryNames.value.length) message.value = 'No se encontraron PDF. Seleccione archivos con extensión .pdf.';
    else nextTick(() => dialog.value?.querySelector('.upload-progress')?.scrollIntoView({ block: 'nearest' }));
}
function selected(event) { addFiles(event.target.files); event.target.value = ''; }
function chooseRetrySource(item) { retryTarget.value = item; retryInput.value?.click(); }
async function replaceRetrySource(event) {
    const file = event.target.files?.[0], item = retryTarget.value;
    event.target.value = ''; retryTarget.value = null;
    if (!file || !item || busy.value || !queue.value.includes(item)) return;
    if (file.name !== item.file.name) { message.value = 'Seleccione el mismo PDF, con su nombre original.'; return; }
    if (file.size > props.maxMb * 1024 ** 2) { message.value = `Supera el límite de ${props.maxMb} MB.`; return; }
    try { if ((await file.slice(0, 5).arrayBuffer()).byteLength < 5) throw new Error(); }
    catch { message.value = 'No se pudo leer el archivo. Copie ese PDF a una carpeta corta, por ejemplo C:\\PDF, y vuelva a elegirlo.'; return; }
    // Replace the local source only; the queue keeps its original folder, name and year.
    item.file = file; item.status = 'pending'; item.error = ''; item.progress = 0; item.sourceUnreadable = false;
    message.value = 'Archivo listo para reintentar en su carpeta original.';
}
function drop(event) { dragging.value = false; addFiles(event.dataTransfer.files); }
function showFailure() {
    const index = queue.value.findIndex(item => item === lastFailure.value);
    if (index < 0) return;
    queuePage.value = Math.floor(index / pageSize) + 1;
    nextTick(() => dialog.value?.querySelector('.queue-list .has-error')?.scrollIntoView({ block: 'nearest' }));
}
const transient = error => !error.localRead && (!error.response || [408, 425, 500, 502, 503, 504].includes(error.response.status));
function failureText(error) {
    const status = error.response?.status;
    const detail = Object.values(error.response?.data?.errors || {}).flat().join(' ') || error.response?.data?.message;
    if (status === 401 || status === 419) return `La sesión venció. Ingrese de nuevo en otra pestaña y continúe aquí. (HTTP ${status})`;
    if (status === 403) return 'Su usuario no tiene permiso para cargar. (HTTP 403)';
    if (error.localRead) return 'El navegador no pudo leer este PDF del disco. Pruebe seleccionarlo desde una carpeta con una ruta más corta. (' + error.code + ')';
    if (error.unconfirmed) return error.message;
    if (!error.response) return `No se pudo leer o enviar el archivo. Compruebe que siga disponible en su disco. (${error.code || 'sin respuesta'})`;
    return `${typeof detail === 'string' ? detail : 'No se pudo registrar este PDF.'} (HTTP ${status})`;
}
async function sendFile(data, item, fromArchive = false) {
    let failures = 0;
    while (!paused.value) {
        try {
            item.waitingForSlot = true;
            const release = await acquireResolutionUploadSlot(runController.signal);
            try {
                item.waitingForSlot = false;
                if (paused.value) { const error = new Error('Carga pausada'); error.uploadPaused = true; throw error; }
                return await uploadHttp.post(fromArchive ? '/resoluciones/desde-archivo-local' : '/resoluciones', data, { headers: { Accept: 'application/json' }, onUploadProgress: e => item.progress = e.total ? Math.round(e.loaded * 100 / e.total) : 0 });
            } finally { item.waitingForSlot = false; release(); }
        } catch (error) {
            if (error.uploadPaused) throw error;
            const limited = error.response?.status === 429;
            if (!limited && (!transient(error) || ++failures >= 3)) throw error;
            const header = error.response?.headers?.['retry-after'];
            const delay = Number(header) || Math.ceil((Date.parse(header) - Date.now()) / 1000) || 60;
            item.retrySeconds = limited ? Math.min(3600, Math.max(1, Math.ceil(delay))) : 2 ** failures;
            item.retryReason = limited ? 'El servidor pidió una espera.' : `Reintentando el archivo (${failures + 1}/3).`;
            item.progress = 0;
            try {
                while (item.retrySeconds > 0 && !paused.value) {
                    await new Promise(resolve => setTimeout(resolve, 1000));
                    item.retrySeconds--;
                }
            } finally { item.retrySeconds = 0; item.retryReason = ''; }
        }
    }
    const error = new Error('Carga pausada');
    error.uploadPaused = true;
    throw error;
}
async function start() {
    if (busy.value) return;
    if (year.value && (!Number.isInteger(Number(year.value)) || Number(year.value) < 1900 || Number(year.value) > props.currentYear)) {
        message.value = `Ingrese un año entre 1900 y ${props.currentYear}, o déjelo vacío para detectarlo.`; return;
    }
    // Freeze metadata per item so other folders can be added while these five workers run.
    for (const item of queue.value) if (['pending', 'error'].includes(item.status) && !item.yearLocked) { item.batchYear = year.value; item.yearLocked = true; }
    busy.value = true; emit('busy', true); paused.value = false; message.value = '';
    sequenceReport.value = null; coverageMessage.value = '';
    runController = new AbortController();
    let cursor = 0, consecutiveFailures = 0;
    async function worker() {
        while (!paused.value) {
            const index = cursor++;
            if (index >= queue.value.length) return;
            const item = queue.value[index];
            if (!['pending', 'error'].includes(item.status) || item.file.size > props.maxMb * 1024 ** 2) continue;
            item.status = 'uploading'; item.error = ''; item.progress = 0;
            try {
                let fromArchive = false;
                try {
                    const probe = await item.file.slice(0, 5).arrayBuffer();
                    if (probe.byteLength < 5) { const error = new Error('Archivo vacío o inaccesible'); error.name = 'EmptyOrUnreadableFile'; throw error; }
                }
                catch (cause) { fromArchive = true; }
                item.fromArchive = fromArchive;
                item.sourceUnreadable = fromArchive;
                const data = new FormData();
                if (fromArchive) data.append('filename', item.file.name);
                else data.append('file', item.file);
                data.append('folder', item.folder);
                if (item.batchYear) data.append('year', item.batchYear);
                const response = await sendFile(data, item, fromArchive);
                if (response.status !== 201 || !Number.isInteger(Number(response.data?.id)) || Number(response.data.id) < 1) {
                    const error = new Error('El servidor no confirmó el registro. Verifique su sesión en otra pestaña antes de reintentar.'); error.unconfirmed = true; throw error;
                }
                item.status = 'done'; item.progress = 100; consecutiveFailures = 0; catalogDirty.value = true;
            } catch (error) {
                if (error.uploadPaused) { item.status = 'pending'; continue; }
                if (error.response?.status === 409) { item.status = 'duplicate'; consecutiveFailures = 0; continue; }
                item.status = 'error'; item.error = failureText(error);
                consecutiveFailures = transient(error) ? consecutiveFailures + 1 : 0;
                if (error.unconfirmed || [401, 403, 419].includes(error.response?.status) || consecutiveFailures >= 3) {
                    paused.value = true; message.value = `Carga pausada: ${item.error} Los archivos registrados se conservan.`;
                }
            }
        }
    }
    try { await Promise.all(Array.from({ length: 5 }, () => worker())); }
    finally {
        busy.value = false; emit('busy', false);
        if (catalogDirty.value && !paused.value) { catalogDirty.value = false; emit('finished'); }
        if (!paused.value) {
            const years = [...new Set(queue.value.map(item=>item.batchYear).filter(Boolean))];
            try {
                const response = await uploadHttp.get('/resoluciones/cobertura', { params: years.length === 1 ? { year: years[0] } : {} });
                if (!Array.isArray(response.data?.groups)) throw new Error('Unconfirmed coverage');
                sequenceReport.value = response.data;
            } catch { coverageMessage.value = 'No se pudo comprobar la numeración. Consulte el control desde el resumen.'; }
        }
    }
}
const preventClose = event => { if (busy.value) { event.preventDefault(); event.returnValue = ''; } };
window.addEventListener('beforeunload', preventClose);
onBeforeUnmount(() => { windowVersion++; windowAnimation?.cancel(); window.removeEventListener('beforeunload', preventClose); });
</script>

<template>
    <dialog ref="dialog" class="upload-dialog" :aria-labelledby="`upload-title-${jobId}`" @cancel.prevent="close">
        <header class="upload-header"><div><h2 :id="`upload-title-${jobId}`">Subir resoluciones</h2><p>{{ label }}</p></div><div class="upload-window-actions"><button class="icon-btn" aria-label="Minimizar carga" @click="minimize"><Minus :size="20" /></button><button class="icon-btn" aria-label="Cerrar carga de archivos" @click="close"><X :size="20" /></button></div></header>
        <div class="upload-body">
            <input ref="retryInput" class="sr-only" tabindex="-1" type="file" accept="application/pdf,.pdf" aria-label="Volver a elegir el PDF fallido" @change="replaceRetrySource" />
            <div class="drop-zone" :class="{ dragging, locked: busy }" @dragover.prevent="dragging = !busy" @dragleave.prevent="dragging = false" @drop.prevent="drop">
                <Upload :size="28" class="mx-auto text-blue-600" /><h3>Arrastre sus PDF aquí</h3>
                <div class="flex flex-wrap justify-center gap-2 mt-4"><button class="upload-primary" @click="fileInput.click()"><FileText :size="16" /> Elegir PDF</button><button class="upload-secondary" @click="folderInput.click()"><FolderOpen :size="16" /> Elegir carpeta</button></div>
                <input ref="fileInput" class="sr-only" tabindex="-1" type="file" accept="application/pdf,.pdf" multiple @change="selected" /><input ref="folderInput" class="sr-only" tabindex="-1" type="file" webkitdirectory multiple @change="selected" />
                <small>Sin límite de archivos · Hasta {{ maxMb }} MB por PDF</small>

            </div>
            <label class="year-label">Año <span>Opcional</span><input v-model="year" type="number" min="1900" :max="currentYear" placeholder="Detectar del nombre o carpeta" /><small>Vacío: se detecta del nombre o carpeta. No cambia cargas ya iniciadas.</small></label>
            <p v-if="dropped" class="upload-notice">Se omitieron {{ dropped }} archivos que no son PDF.</p>
            <p v-if="message" class="upload-notice" role="alert">{{ message }}</p>
            <details v-if="auxiliaryNames.length" class="upload-notice"><summary>{{ format(auxiliaryNames.length) }} archivos auxiliares omitidos</summary><p>Se omiten actuados, notificaciones, archivos temporales y nombres genéricos c.pdf/cx.pdf.</p><ul><li v-for="name in auxiliaryNames" :key="name">{{ name }}</li></ul></details>
            <section v-if="queue.length" class="queue" aria-label="Archivos seleccionados">
                <div class="queue-heading"><h3>{{ format(queue.length) }} archivos · {{ bytes(counts.bytes) }}</h3><button :disabled="busy" @click="queue = []; queuePage = 1; message = ''"><Trash2 :size="14" /> Vaciar lista</button></div>
                <div class="queue-summary" role="status"><span v-if="counts.done"><Check :size="14" /> {{ counts.done }} registrados</span><span v-if="counts.duplicate">{{ counts.duplicate }} ya existían</span><span v-if="counts.error" class="text-red-700">{{ counts.error }} con error</span></div>
                <ResolutionCoverage v-if="sequenceReport" :report="sequenceReport" />
                <p v-if="coverageMessage" class="upload-notice">{{ coverageMessage }}</p>
                <div v-if="lastFailure" class="failure-detail" role="status"><strong>{{ lastFailure.key }}</strong><p>{{ lastFailure.error }}</p><button :disabled="busy" @click="showFailure">Ver archivo con error</button><button v-if="lastFailure.sourceUnreadable" :disabled="busy" @click="chooseRetrySource(lastFailure)">Volver a elegir PDF</button><small v-if="busy">La carga continúa con los demás PDF.</small></div>
                <div v-if="busy || counts.processed || paused" class="upload-progress">
                    <div class="progress-heading"><strong>{{ busy ? 'Cargando'  : paused ? 'Carga pausada' : counts.processed === queue.length ? 'Lote procesado' : 'Progreso general' }}</strong><b>{{ overallProgress }} %</b></div>
                    <progress :value="overallProgress" max="100" aria-label="Progreso general de la carpeta" :aria-valuetext="`${format(counts.processed)} de ${format(queue.length)} archivos procesados; ${format(counts.done)} registrados`" />
                    <p>{{ format(counts.processed) }} / {{ format(queue.length) }} PDF</p>
                    <p v-if="busy">{{ transmitting }} en curso · Máximo 5 simultáneos</p>
                    <div v-for="item in activeItems" :key="item.key" class="current-upload"><p>{{ item.key }}</p><div><span>{{ item.waitingForSlot ? 'En cola' : item.retrySeconds ? `${item.retryReason} ${item.retrySeconds} s` : item.progress < 100 ? 'Enviando archivo' : 'Confirmando registro…' }}</span><strong>{{ item.progress }} %</strong></div><progress :value="item.progress" max="100" :aria-label="`Progreso de ${item.file.name}`" /></div>
                </div>
                <ul class="queue-list"><li v-for="item in visibleQueue" :key="item.key" :class="{ 'has-error': item.status === 'error' }"><Check v-if="item.status === 'done'" :size="17" class="text-emerald-700 shrink-0" /><CircleAlert v-else-if="item.status === 'error'" :size="17" class="text-red-600 shrink-0" /><Loader2 v-else-if="item.status === 'uploading'" :size="17" class="animate-spin text-blue-600 shrink-0" /><FileText v-else :size="17" class="text-slate-400 shrink-0" /><div class="min-w-0 flex-1"><p class="break-words text-sm text-slate-800">{{ item.key }}</p><small v-if="item.status !== 'pending' || item.error" :class="item.error ? 'text-red-700' : 'text-slate-500'">{{ item.error || labels[item.status] }}</small></div><button class="icon-btn" :disabled="busy || ['done', 'duplicate'].includes(item.status)" :aria-label="`Quitar ${item.file.name} del lote`" @click="queue = queue.filter(i => i !== item)"><X :size="15" /></button></li></ul>
                <nav v-if="pageCount > 1" class="queue-pagination" aria-label="Lista de archivos del lote"><button :disabled="queuePage === 1 || busy" @click="queuePage--">Anterior</button><span>{{ format(queuePage) }} / {{ format(pageCount) }}</span><button :disabled="queuePage === pageCount || busy" @click="queuePage++">Siguiente</button></nav>
            </section>
        </div>
        <footer class="upload-footer" :class="{ 'justify-end': !busy }"><p v-if="busy">Puede minimizar esta ventana.</p><button v-if="busy" class="upload-secondary" :disabled="paused" @click="paused = true"><Pause :size="16" /> {{ paused ? 'Pausando…' : 'Pausar' }}</button><button v-else class="upload-primary" :disabled="!counts.pending" @click="start"><Check v-if="queue.length && !counts.pending && !counts.error" :size="16" /><RotateCcw v-else-if="counts.error" :size="16" /><Play v-else :size="16" /> {{ queue.length && !counts.pending ? (counts.error ? 'Revisar archivos' : 'Lote procesado') : counts.error ? 'Continuar / reintentar' : counts.done || counts.duplicate ? 'Continuar carga' : 'Iniciar carga' }}</button></footer>
    </dialog>
    <Transition name="upload-dock" :css="motionEnabled">
    <aside v-if="minimized && queue.length" class="upload-dock" aria-label="Cargas de resoluciones en segundo plano">
        <button class="dock-open" :aria-label="`Abrir carga ${label}: ${format(counts.processed)} de ${format(queue.length)} PDF, ${overallProgress} por ciento`" @click="restore"></button>
        <header><div><Upload :size="18" /><strong>{{ label }}</strong></div></header>
        <div class="dock-body"><div class="dock-count"><span>{{ format(counts.processed) }} / {{ format(queue.length) }} PDF</span><strong>{{ overallProgress }} %</strong></div><progress :value="overallProgress" max="100" aria-label="Progreso de cargas en segundo plano" /><p>{{ busy ? `${transmitting} en curso` : paused ? 'En pausa' : counts.pending ? `${format(counts.pending)} pendientes` : `${format(counts.done)} registrados · ${format(counts.duplicate)} existentes` }}<span v-if="counts.error"> · {{ counts.error }} con error</span></p><div v-if="busy || counts.pending" class="dock-actions"><button v-if="busy" :disabled="paused" @click.stop="paused = true">{{ paused ? 'Pausando…' : 'Pausar' }}</button><button v-else-if="counts.pending" @click.stop="start">Continuar</button></div></div>
    </aside>
    </Transition>
</template>

<style scoped>
.upload-dialog{width:min(700px,94vw);max-width:94vw;max-height:92dvh;margin:auto;padding:0;border:0;border-radius:16px;color:#0f172a;background:#fff;font-family:'Inter',ui-sans-serif,system-ui,sans-serif;}.upload-dialog[open]{display:flex;flex-direction:column}.upload-dialog::backdrop{background:rgb(15 23 42/.6)}.upload-header{display:flex;justify-content:space-between;gap:16px;padding:24px;border-bottom:1px solid #344660;background:#13243d;color:#f1f5ff}.upload-header h2{font-size:20px;font-weight:750}.upload-header p{font-size:13px;color:#b4c3da;margin-top:5px}.upload-header .icon-btn{color:#dce7f8;background:#253752}.upload-header .icon-btn:hover{background:#344a6b}.upload-header .icon-btn:focus-visible{outline-color:#a9c6ff}.upload-body{padding:24px;overflow:auto}.drop-zone{padding:28px 20px;text-align:center;border:1px dashed #9bb1d5;border-radius:12px;background:#f0f5ff}.drop-zone.dragging{background:#eff6ff;border-color:#2563eb}.drop-zone h3{margin-top:12px;font-size:16px;font-weight:650}.drop-zone p{font-size:13px;color:#64748b;margin-top:5px}.drop-zone small{display:block;font-size:12px;color:#64748b;margin-top:14px}.upload-primary,.upload-secondary{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:42px;padding:9px 15px;border-radius:12px;font-size:13px;font-weight:650}.upload-primary{background:#2563eb;color:white}.upload-primary:hover{background:#1d4ed8}.upload-secondary{border:1px solid #cbd5e1;color:#334155;background:#fff}.upload-secondary:hover{background:#f1f5f9}.icon-btn{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;flex-shrink:0;color:#64748b}.icon-btn:hover{background:#f1f5f9}.year-label{display:block;margin-top:20px;font-size:13px;font-weight:600}.year-label>span{font-weight:400;color:#64748b;margin-left:5px}.year-label input{display:block;width:100%;min-height:42px;margin-top:8px;padding:9px 12px;border:1px solid #cbd5e1;border-radius:10px;font-size:14px;font-weight:400}.year-label small{display:block;margin-top:6px;font-size:12px;font-weight:400;color:#64748b}.upload-notice{margin-top:16px;padding:12px;border-radius:8px;background:#fffbeb;color:#92400e;font-size:13px}.queue{margin-top:24px}.queue-heading{display:flex;align-items:center;justify-content:space-between;gap:10px}.queue-heading h3{font-size:14px;font-weight:650}.queue-heading button{display:flex;align-items:center;gap:6px;color:#64748b;font-size:12px;min-height:40px}.queue-summary{display:flex;flex-wrap:wrap;gap:12px;font-size:12px;color:#64748b;margin:4px 0 10px}.queue-summary span{display:flex;gap:4px;align-items:center}.queue-list{max-height:240px;overflow:auto;border-top:1px solid #e2e8f0}.queue-list li{display:flex;align-items:center;gap:10px;padding:12px 0;border-bottom:1px solid #f1f5f9}.queue-list small{font-size:12px}.upload-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 24px;border-top:1px solid #e2e8f0;background:#f8fafc}.upload-footer p{font-size:12px;color:#64748b;max-width:330px}button:disabled{opacity:.5;cursor:not-allowed}:is(button,input):focus-visible{outline:2px solid #2563eb;outline-offset:3px}@media(max-width:639px){.upload-header,.upload-body{padding:18px}.upload-footer{padding:14px 18px;flex-wrap:wrap}.upload-footer p{width:100%;max-width:none}.upload-footer button{width:100%}.drop-zone{padding:20px 12px}}
.upload-progress{position:sticky;top:0;z-index:2;background:#f1f6ff;border:1px solid #d6e5ff;border-radius:10px;padding:16px;margin:14px 0 18px;box-shadow:0 3px 9px #23457708}.progress-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px;color:#24466f;font-size:13px}.progress-heading strong{font-weight:600}.progress-heading b{font-size:23px;font-weight:650;color:#1d5ec3;font-variant-numeric:tabular-nums}.upload-progress progress{display:block;width:100%;height:12px;appearance:none;border:0;border-radius:8px;overflow:hidden;background:#dae5f7;accent-color:#2468d1}.upload-progress progress::-webkit-progress-bar{background:#dae5f7;border-radius:8px}.upload-progress progress::-webkit-progress-value{background:#2468d1;border-radius:8px}.upload-progress progress::-moz-progress-bar{background:#2468d1;border-radius:8px}.upload-progress>p{font-size:12px;color:#526b8b;margin-top:9px}.current-upload{padding-top:12px;margin-top:12px;border-top:1px solid #d9e5f7}.current-upload>p{font-size:12px;color:#3b577a;overflow-wrap:anywhere;line-height:1.6}.current-upload>div{display:flex;justify-content:space-between;gap:10px;margin:7px 0;color:#5f7693;font-size:11px}.current-upload progress{height:5px}.queue-pagination{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:11px;color:#64748b;padding-top:10px}.queue-pagination button{min-height:40px;padding:7px;color:#245dae}.queue-pagination span{text-align:center}.queue-list{overflow-anchor:none}@media(max-width:639px){.upload-progress{padding:13px}.progress-heading{font-size:12px}.progress-heading b{font-size:21px}.queue-pagination{font-size:10px;gap:5px}}
.failure-detail{margin:12px 0;padding:13px 15px;border:1px solid #f2ccbf;border-radius:8px;background:#fff7f2;font-size:12px;color:#994124;overflow-wrap:anywhere}.failure-detail strong{font-weight:600}.failure-detail p{margin-top:7px;line-height:1.7}.failure-detail button{display:block;color:#245dae;text-decoration:underline;text-underline-offset:3px;min-height:36px}.failure-detail small{display:block;margin-top:8px;color:#79654f}.queue-list .has-error{background:#fff7f2;padding-inline:8px}.upload-window-actions{display:flex;gap:7px}.upload-dock{position:fixed;right:22px;bottom:20px;width:350px;max-width:calc(100vw - 24px);z-index:1000;border:1px solid #d8e2ef;border-radius:12px;background:#fff;box-shadow:0 8px 35px #172c5040;font-family:Inter,system-ui,sans-serif;color:#2d4665}.upload-dock>header{display:flex;justify-content:space-between;align-items:center;background:#153155;color:#fff;border-radius:11px 11px 0 0;padding:12px 15px;gap:10px}.upload-dock>header>div{display:flex;align-items:center;gap:9px;font-size:13px}.upload-dock>header button{display:flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:5px;color:#d9e8ff}.upload-dock button:focus-visible{outline:2px solid #4285f4;outline-offset:3px}.dock-body{padding:16px}.dock-count{display:flex;justify-content:space-between;gap:12px;font-size:12px}.dock-count strong{color:#2468c8}.dock-body progress{width:100%;height:8px;display:block;margin-top:11px;accent-color:#2468c8}.dock-body>p{font-size:11px;color:#6b7d95;margin-top:9px}.dock-actions{display:flex;justify-content:space-between;gap:12px;margin-top:9px}.dock-actions button{font-size:11px;color:#2462b1;min-height:34px}.current-upload{padding-top:9px;margin-top:9px}.current-upload>p{font-size:11px;display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden}.upload-progress{position:static}.upload-dock .dock-body progress{border-radius:4px}@media(max-width:639px){.upload-dock{right:12px;bottom:12px;width:330px}.upload-window-actions{gap:3px}.upload-header{gap:8px}.upload-header h2{font-size:18px}.upload-header .icon-btn{width:33px;height:35px}}
.dock-body progress{appearance:none;border:0;overflow:hidden;background:#dae5f7}.dock-body progress::-webkit-progress-bar{background:#dae5f7;border-radius:4px}.dock-body progress::-webkit-progress-value{background:#2468c8;border-radius:4px}.dock-body progress::-moz-progress-bar{background:#2468c8;border-radius:4px}.upload-dock{position:static;right:auto;bottom:auto;width:100%;max-width:100%;flex-shrink:0;box-shadow:0 3px 12px #172c5025}.upload-dock>header>div{min-width:0}.upload-dock>header strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.upload-dock>header svg{flex-shrink:0}@media(max-width:639px){.upload-dock{position:static;width:100%;right:auto;bottom:auto}}.upload-dialog,.upload-dock{--ease-out:cubic-bezier(0.23,1,0.32,1)}.upload-dock{position:relative}.dock-open{position:absolute;inset:0;width:100%;height:100%;z-index:1;padding:0;border:0;border-radius:inherit;background:transparent;cursor:pointer}.dock-open:focus-visible{outline:2px solid #4585de;outline-offset:-3px}.dock-actions{position:relative;z-index:2;pointer-events:none}.dock-actions button{pointer-events:auto}.upload-dock-enter-active,.upload-dock-leave-active{transition:opacity 180ms var(--ease-out),transform 180ms var(--ease-out)}.upload-dock-enter-from,.upload-dock-leave-to{opacity:0;transform:translateY(10%)}@media(hover:hover) and (pointer:fine){.dock-open:hover{background:#3b82f608}.dock-open:active{background:#3b82f614}}@media(prefers-reduced-motion:reduce){.upload-dock-enter-active,.upload-dock-leave-active{transition:opacity 100ms var(--ease-out)}.upload-dock-enter-from,.upload-dock-leave-to{transform:none}}
</style>
