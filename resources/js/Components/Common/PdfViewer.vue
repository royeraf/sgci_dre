<script setup>
import { ref, watch, nextTick, onBeforeUnmount } from 'vue';
import axios from 'axios';
import {
    FileText,
    Download,
    X,
    ChevronUp,
    ChevronDown,
    ZoomIn,
    ZoomOut,
    Loader2,
    AlertCircle,
} from 'lucide-vue-next';
import { usePdfViewer } from '@/composables/usePdfViewer';

const { state, closePdf } = usePdfViewer();

// pdf.js es una dependencia pesada: se carga de forma perezosa (dynamic import)
// solo cuando el usuario abre un PDF, para no engordar el bundle inicial.
let pdfjsLib = null;
const getPdfjs = async () => {
    if (pdfjsLib) return pdfjsLib;
    const [lib, workerUrl] = await Promise.all([
        import('pdfjs-dist'),
        import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
    ]);
    lib.GlobalWorkerOptions.workerSrc = workerUrl.default;
    pdfjsLib = lib;
    return pdfjsLib;
};

const loading = ref(false);
const error = ref(null);
const numPages = ref(0);
const currentPage = ref(1);
const scale = ref(1.2);

const MIN_SCALE = 0.5;
const MAX_SCALE = 3;

const pagesContainer = ref(null);
const canvasRefs = ref([]);

let pdfDoc = null;
let loadingTask = null;
let pdfBytes = null;
let objectUrl = null;
let renderToken = 0;
let pageObserver = null;

const setCanvasRef = (el, index) => {
    if (el) canvasRefs.value[index] = el;
};

const cleanupDocument = () => {
    if (pageObserver) {
        pageObserver.disconnect();
        pageObserver = null;
    }
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
    if (loadingTask) {
        loadingTask.destroy().catch(() => {});
        loadingTask = null;
    }
    pdfDoc = null;
    pdfBytes = null;
    canvasRefs.value = [];
    numPages.value = 0;
    currentPage.value = 1;
};

const renderAllPages = async (token) => {
    if (!pdfDoc) return;

    for (let i = 1; i <= pdfDoc.numPages; i++) {
        if (token !== renderToken) return; // se abrió/cerró/cambió mientras renderizaba

        const page = await pdfDoc.getPage(i);
        const viewport = page.getViewport({ scale: scale.value });
        const canvas = canvasRefs.value[i - 1];
        if (!canvas) continue;

        const context = canvas.getContext('2d');
        const outputScale = window.devicePixelRatio || 1;

        canvas.width = Math.floor(viewport.width * outputScale);
        canvas.height = Math.floor(viewport.height * outputScale);
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;

        const transform = outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null;

        await page.render({ canvasContext: context, viewport, transform }).promise;
    }
};

const setupPageObserver = () => {
    if (pageObserver) pageObserver.disconnect();
    if (!pagesContainer.value) return;

    pageObserver = new IntersectionObserver(
        (entries) => {
            const visible = entries
                .filter((e) => e.isIntersecting)
                .sort((a, b) => b.intersectionRatio - a.intersectionRatio)[0];
            if (visible) {
                const index = Number(visible.target.dataset.pageIndex);
                if (!Number.isNaN(index)) currentPage.value = index + 1;
            }
        },
        { root: pagesContainer.value, threshold: [0.25, 0.5, 0.75] }
    );

    canvasRefs.value.forEach((canvas) => {
        if (canvas) pageObserver.observe(canvas);
    });
};

const loadPdf = async (src) => {
    cleanupDocument();
    error.value = null;
    loading.value = true;

    const token = ++renderToken;

    try {
        const [lib, response] = await Promise.all([
            getPdfjs(),
            axios.get(src, { responseType: 'arraybuffer' }),
        ]);
        if (token !== renderToken) return;

        pdfBytes = response.data;
        loadingTask = lib.getDocument({ data: pdfBytes.slice(0) });
        pdfDoc = await loadingTask.promise;
        if (token !== renderToken) return;

        numPages.value = pdfDoc.numPages;
        canvasRefs.value = new Array(pdfDoc.numPages).fill(null);

        loading.value = false;
        await nextTick();

        await renderAllPages(token);
        if (token === renderToken) setupPageObserver();
    } catch (e) {
        if (token !== renderToken) return;
        console.error('Error al cargar el PDF:', e);
        error.value = 'No se pudo cargar el documento PDF.';
        loading.value = false;
    }
};

watch(
    () => state.src,
    (src) => {
        if (src) loadPdf(src);
    }
);

watch(scale, async () => {
    if (!pdfDoc) return;
    const token = ++renderToken;
    await renderAllPages(token);
    if (token === renderToken) setupPageObserver();
});

const zoomIn = () => {
    scale.value = Math.min(MAX_SCALE, Math.round((scale.value + 0.2) * 10) / 10);
};

const zoomOut = () => {
    scale.value = Math.max(MIN_SCALE, Math.round((scale.value - 0.2) * 10) / 10);
};

const scrollToPage = (pageNumber) => {
    const canvas = canvasRefs.value[pageNumber - 1];
    if (canvas) canvas.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const goToPrevPage = () => {
    if (currentPage.value > 1) scrollToPage(currentPage.value - 1);
};

const goToNextPage = () => {
    if (currentPage.value < numPages.value) scrollToPage(currentPage.value + 1);
};

const downloadPdf = () => {
    if (!pdfBytes) return;
    if (objectUrl) URL.revokeObjectURL(objectUrl);

    const blob = new Blob([pdfBytes], { type: 'application/pdf' });
    objectUrl = URL.createObjectURL(blob);

    const link = document.createElement('a');
    link.href = objectUrl;
    link.download = state.filename || 'documento.pdf';
    document.body.appendChild(link);
    link.click();
    link.remove();
};

const handleClose = () => {
    closePdf();
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') handleClose();
};

watch(
    () => state.isOpen,
    (isOpen) => {
        if (isOpen) {
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', handleKeydown);
        } else {
            document.body.style.overflow = '';
            window.removeEventListener('keydown', handleKeydown);
            renderToken++; // invalida renders/cargas en curso
            cleanupDocument();
            scale.value = 1.2;
        }
    }
);

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', handleKeydown);
    renderToken++;
    cleanupDocument();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="state.isOpen" class="fixed inset-0 z-[100] flex flex-col bg-slate-900">
                <!-- Header -->
                <div class="flex-shrink-0 flex items-center justify-between gap-4 px-4 sm:px-6 py-3 bg-slate-950/80 border-b border-slate-800">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="bg-white/10 p-2 rounded-lg flex-shrink-0">
                            <FileText class="w-5 h-5 text-white" />
                        </div>
                        <p class="text-sm font-semibold text-white truncate">{{ state.filename }}</p>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button
                            @click="downloadPdf"
                            :disabled="!pdfBytes"
                            class="cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white text-sm font-semibold transition-colors duration-150"
                            title="Descargar"
                        >
                            <Download class="w-4 h-4" />
                            <span class="hidden sm:inline">Descargar</span>
                        </button>
                        <button
                            @click="handleClose"
                            class="cursor-pointer p-2 rounded-lg bg-white/10 hover:bg-red-600 text-white transition-colors duration-150"
                            title="Cerrar"
                        >
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="relative flex-1 min-h-0">
                    <!-- Loading -->
                    <div v-if="loading" class="absolute inset-0 flex flex-col items-center justify-center gap-3">
                        <Loader2 class="w-8 h-8 text-white animate-spin" />
                        <p class="text-sm text-slate-300">Cargando documento...</p>
                    </div>

                    <!-- Error -->
                    <div v-else-if="error" class="absolute inset-0 flex flex-col items-center justify-center gap-3 px-6 text-center">
                        <AlertCircle class="w-10 h-10 text-red-400" />
                        <p class="text-sm text-slate-300">{{ error }}</p>
                    </div>

                    <!-- Pages -->
                    <div
                        v-show="!loading && !error"
                        ref="pagesContainer"
                        class="h-full overflow-y-auto py-6 px-4 flex flex-col items-center gap-4"
                    >
                        <canvas
                            v-for="(page, index) in numPages"
                            :key="index"
                            :ref="(el) => setCanvasRef(el, index)"
                            :data-page-index="index"
                            class="bg-white shadow-2xl rounded-sm max-w-full"
                        ></canvas>
                    </div>

                    <!-- Floating controls -->
                    <div
                        v-if="!loading && !error && numPages > 0"
                        class="absolute right-3 sm:right-6 top-1/2 -translate-y-1/2 flex flex-col items-center gap-1 bg-slate-950/90 border border-slate-800 rounded-2xl p-2 shadow-2xl"
                    >
                        <div class="flex flex-col items-center px-2 py-1.5 text-white text-xs font-bold">
                            <span>{{ currentPage }}</span>
                            <div class="w-4 border-t border-slate-700 my-1"></div>
                            <span class="text-slate-400">{{ numPages }}</span>
                        </div>

                        <button
                            @click="goToPrevPage"
                            :disabled="currentPage <= 1"
                            class="cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed p-2 rounded-xl text-white hover:bg-white/10 transition-colors duration-150"
                            title="Página anterior"
                        >
                            <ChevronUp class="w-4 h-4" />
                        </button>
                        <button
                            @click="goToNextPage"
                            :disabled="currentPage >= numPages"
                            class="cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed p-2 rounded-xl text-white hover:bg-white/10 transition-colors duration-150"
                            title="Página siguiente"
                        >
                            <ChevronDown class="w-4 h-4" />
                        </button>

                        <div class="w-6 border-t border-slate-700 my-1"></div>

                        <button
                            @click="zoomIn"
                            :disabled="scale >= MAX_SCALE"
                            class="cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed p-2 rounded-xl text-white hover:bg-white/10 transition-colors duration-150"
                            title="Acercar"
                        >
                            <ZoomIn class="w-4 h-4" />
                        </button>
                        <button
                            @click="zoomOut"
                            :disabled="scale <= MIN_SCALE"
                            class="cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed p-2 rounded-xl text-white hover:bg-white/10 transition-colors duration-150"
                            title="Alejar"
                        >
                            <ZoomOut class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
