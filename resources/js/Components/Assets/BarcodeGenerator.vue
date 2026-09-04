<script setup>
import { ref, shallowRef, computed, watch, onMounted } from 'vue';
import {
    Search,
    Printer,
    CheckSquare,
    Square,
    Barcode,
    QrCode,
    ChevronLeft,
    ChevronRight,
    Loader2,
    PackageOpen,
    Sliders,
    Sparkles,
    Eye,
    RotateCcw,
    Layers,
    FileText,
    ZoomIn,
    ZoomOut,
} from 'lucide-vue-next';
import axios from 'axios';
import ThermalLabelItem from './ThermalLabelItem.vue';

// Estado de búsqueda y selección
const search = ref('');
const assets = ref([]);
const selectedIds = ref(new Set());
const loading = shallowRef(false);
const currentPage = shallowRef(1);
const lastPage = shallowRef(1);
const total = shallowRef(0);
const isSampleMode = ref(false);

// Configuración de la Impresora Térmica TD-402S
const config = ref({
    preset: '2x1_1col', // Preset actual
    mode: 'thermal', // 'thermal' | 'a4'
    columns: 1, // 1 | 2
    labelWidth: 50.8, // mm (2 pulgadas)
    labelHeight: 25.4, // mm (1 pulgada)
    gap: 2.0, // mm (brecha horizontal en 2 columnas)
    codeType: 'barcode', // 'barcode' | 'qr'
    qrLayout: 'horizontal', // 'horizontal' | 'vertical'
    showEntity: true,
    showSubtitle: true,
    showCode: true,
    showName: true,
    showSeries: true,
    showOffice: false,
    entityText: 'DIRECCIÓN REGIONAL DE EDUCACIÓN DE HUÁNUCO',
    subtitleText: 'INVENTARIO 2026',
    previewZoom: 1.25,
});

// Lista de Presets predefinidos para la TD-402S
const presets = [
    {
        id: '2x1_1col',
        name: '1 Columna — 50x25 mm (2" x 1")',
        columns: 1,
        width: 50.8,
        height: 25.4,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Rollo estándar individual más popular',
    },
    {
        id: '2x1_2col',
        name: '2 Columnas — 2x 50x25 mm (2" x 1")',
        columns: 2,
        width: 50.8,
        height: 25.4,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Rollo doble (ancho total 104 mm, doble velocidad)',
    },
    {
        id: '50x30_1col',
        name: '1 Columna — 50 x 30 mm',
        columns: 1,
        width: 50.0,
        height: 30.0,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Mayor altura para textos de 3 líneas',
    },
    {
        id: '70x30_1col',
        name: '1 Columna — 70 x 30 mm',
        columns: 1,
        width: 70.0,
        height: 30.0,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Etiqueta ancha de alta legibilidad',
    },
    {
        id: '80x40_1col',
        name: '1 Columna — 80 x 40 mm',
        columns: 1,
        width: 80.0,
        height: 40.0,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Etiqueta grande para bienes voluminosos',
    },
    {
        id: '30x20_2col',
        name: '2 Columnas — 2x 30 x 20 mm',
        columns: 2,
        width: 30.0,
        height: 20.0,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Rollo doble mini para equipos pequeños',
    },
    {
        id: 'custom',
        name: 'Personalizado (Medidas manuales)',
        columns: 1,
        width: 50.8,
        height: 25.4,
        gap: 2.0,
        mode: 'thermal',
        desc: 'Ingresar milímetros exactos del rollo',
    },
    {
        id: 'a4',
        name: 'Hoja A4 (Láminas adhesivas estándar)',
        columns: 3,
        width: 58.0,
        height: 30.0,
        gap: 3.0,
        mode: 'a4',
        desc: 'Para impresoras comunes de oficina',
    },
];

// Cargar configuración guardada de localStorage
const loadSavedConfig = () => {
    try {
        const saved = localStorage.getItem('dre_td402s_label_config');
        if (saved) {
            const parsed = JSON.parse(saved);
            // Migrar el texto de entidad corto guardado antes de usar el nombre completo
            if (parsed.entityText === 'DRE HUÁNUCO' || parsed.entityText === 'DRE HUANUCO') {
                parsed.entityText = 'DIRECCIÓN REGIONAL DE EDUCACIÓN DE HUÁNUCO';
            }
            config.value = { ...config.value, ...parsed };
        }
    } catch (e) {
        console.warn('Could not load label config from localStorage:', e);
    }
};

// Guardar configuración en localStorage al cambiar
watch(
    config,
    (newVal) => {
        try {
            localStorage.setItem('dre_td402s_label_config', JSON.stringify(newVal));
        } catch (e) {
            console.warn('Could not save label config to localStorage:', e);
        }
    },
    { deep: true }
);

// Aplicar preset
const applyPreset = (presetId) => {
    const selected = presets.find((p) => p.id === presetId);
    if (!selected) return;

    config.value.preset = selected.id;
    config.value.mode = selected.mode;
    config.value.columns = selected.columns;
    config.value.labelWidth = selected.width;
    config.value.labelHeight = selected.height;
    config.value.gap = selected.gap;
};

// Obtener bienes desde backend
const fetchAssets = async (page = 1) => {
    isSampleMode.value = false;
    loading.value = true;
    try {
        const response = await axios.get('/assets/list', {
            params: {
                search: search.value || undefined,
                per_page: 10,
                page,
            },
        });
        assets.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error fetching assets:', error);
    } finally {
        loading.value = false;
    }
};

// Cargar ejemplos de prueba simulados
const loadSampleAssets = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/assets/barcodes/samples');
        assets.value = response.data;
        selectedIds.value = new Set(response.data.map((a) => a.id));
        isSampleMode.value = true;
        total.value = response.data.length;
        currentPage.value = 1;
        lastPage.value = 1;
    } catch (error) {
        console.error('Error loading sample assets:', error);
    } finally {
        loading.value = false;
    }
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        selectedIds.value = new Set();
        fetchAssets(1);
    }, 400);
});

const toggleSelect = (id) => {
    const next = new Set(selectedIds.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    selectedIds.value = next;
};

const toggleAll = () => {
    if (allOnPageSelected.value) {
        const next = new Set(selectedIds.value);
        assets.value.forEach((a) => next.delete(a.id));
        selectedIds.value = next;
    } else {
        const next = new Set(selectedIds.value);
        assets.value.forEach((a) => next.add(a.id));
        selectedIds.value = next;
    }
};

const clearSelection = () => {
    selectedIds.value = new Set();
};

const goToPage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        fetchAssets(page);
    }
};

// Bienes seleccionados computados
const selectedAssets = computed(() => {
    return assets.value.filter((a) => selectedIds.value.has(a.id));
});

const allOnPageSelected = computed(() => {
    return assets.value.length > 0 && assets.value.every((a) => selectedIds.value.has(a.id));
});

// Bienes para la previsualización en vivo
const previewAssets = computed(() => {
    if (selectedAssets.value.length > 0) {
        return selectedAssets.value;
    }
    // Si no hay nada seleccionado pero hay bienes en la tabla, mostrar los primeros 2
    if (assets.value.length > 0) {
        return assets.value.slice(0, 2);
    }
    return [];
});

// Grupos de 2 para previsualización en 2 columnas
const previewAssetPairs = computed(() => {
    const items = previewAssets.value;
    const pairs = [];
    for (let i = 0; i < items.length; i += 2) {
        pairs.push([items[i], items[i + 1] || null]);
    }
    return pairs;
});

// Generar URL del PDF con todos los parámetros
const buildPdfUrl = (idsString = '') => {
    const params = new URLSearchParams();

    if (isSampleMode.value) {
        params.append('sample', '1');
        if (idsString && idsString !== 'samples') {
            params.append('ids', String(idsString));
        }
    } else {
        params.append('ids', idsString);
    }

    params.append('mode', config.value.mode);
    params.append('code_type', config.value.codeType);
    params.append('columns', String(config.value.columns));
    params.append('width', String(config.value.labelWidth));
    params.append('height', String(config.value.labelHeight));
    params.append('gap', String(config.value.gap));
    params.append('qr_layout', config.value.qrLayout);
    params.append('entity_text', config.value.entityText);
    params.append('subtitle_text', config.value.subtitleText);
    params.append('show_entity', config.value.showEntity ? '1' : '0');
    params.append('show_subtitle', config.value.showSubtitle ? '1' : '0');
    params.append('show_code', config.value.showCode ? '1' : '0');
    params.append('show_name', config.value.showName ? '1' : '0');
    params.append('show_series', config.value.showSeries ? '1' : '0');
    params.append('show_office', config.value.showOffice ? '1' : '0');

    return `/assets/barcodes/pdf?${params.toString()}`;
};

// Imprimir PDF en nueva pestaña
const printBatchPdf = () => {
    const ids = isSampleMode.value ? 'samples' : [...selectedIds.value].join(',');
    window.open(buildPdfUrl(ids), '_blank');
};

const printSinglePdf = (asset) => {
    window.open(buildPdfUrl(asset.id), '_blank');
};

// Impresión directa del navegador
const directPrint = () => {
    const printUrl = buildPdfUrl(
        isSampleMode.value ? 'samples' : [...selectedIds.value].join(',')
    );
    const printWindow = window.open(printUrl, '_blank');
    if (printWindow) {
        printWindow.focus();
    }
};

onMounted(() => {
    loadSavedConfig();
    fetchAssets(1);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Banner de Encabezado con Indicador de TD-402S -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 rounded-2xl p-6 shadow-md text-white border border-slate-700/50">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2.5">
                        <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-emerald-400 backdrop-blur-sm">
                            <Barcode class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="text-xl font-black tracking-tight text-white flex items-center gap-2">
                                Generador de Etiquetas Patrimoniales
                                <span class="text-xs bg-emerald-500/20 text-emerald-300 px-2.5 py-0.5 rounded-full font-bold border border-emerald-500/30">
                                    TD-402S (TPL) Ready
                                </span>
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5">
                                Emisión de códigos de barra (Code 128) y códigos QR en rollos de 1 y 2 columnas
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción Rápida -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="loadSampleAssets"
                        class="cursor-pointer inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-amber-200 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 transition-all active:scale-95"
                        title="Carga bienes de ejemplo con códigos patrimoniales para pruebas rápidas"
                    >
                        <Sparkles class="w-4 h-4 mr-1.5 text-amber-400" />
                        {{ isSampleMode ? 'Ejemplos Cargados' : 'Cargar Ejemplos' }}
                    </button>

                    <button
                        :disabled="selectedIds.size === 0"
                        @click="printBatchPdf"
                        class="cursor-pointer inline-flex items-center px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-900/30 transition-all active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed"
                    >
                        <Printer class="w-4 h-4 mr-1.5" />
                        Imprimir / PDF ({{ selectedIds.size }})
                    </button>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- PANEL IZQUIERDO: CONFIGURADOR TD-402S (5 cols) -->
            <div class="lg:col-span-5 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3.5 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                            <Sliders class="w-4 h-4 text-indigo-600" />
                            Configuración del Rollo e Impresora
                        </h3>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            TD-402S
                        </span>
                    </div>

                    <div class="p-5 space-y-5">
                        <!-- 1. Presets de Rollo -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Tamaño y Disposición de Rollo:
                            </label>
                            <select
                                :value="config.preset"
                                @change="applyPreset($event.target.value)"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-semibold text-slate-800 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 outline-none text-sm transition-all"
                            >
                                <option v-for="p in presets" :key="p.id" :value="p.id">
                                    {{ p.name }}
                                </option>
                            </select>
                            <p class="text-[11px] text-slate-500 mt-1">
                                {{ presets.find((p) => p.id === config.preset)?.desc }}
                            </p>
                        </div>

                        <!-- 2. Selector de Columnas (1 o 2) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Columnas por Fila del Rollo:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="config.columns = 1"
                                    :class="[
                                        'cursor-pointer px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all',
                                        config.columns === 1
                                            ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-sm'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full" :class="config.columns === 1 ? 'bg-indigo-600' : 'bg-slate-300'"></span>
                                    1 Columna (Simple)
                                </button>
                                <button
                                    type="button"
                                    @click="config.columns = 2"
                                    :class="[
                                        'cursor-pointer px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all',
                                        config.columns === 2
                                            ? 'bg-indigo-50 border-indigo-500 text-indigo-700 shadow-sm'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    <span class="w-2.5 h-2.5 rounded-full" :class="config.columns === 2 ? 'bg-indigo-600' : 'bg-slate-300'"></span>
                                    2 Columnas (Doble)
                                </button>
                            </div>
                        </div>

                        <!-- 3. Selector de Tipo de Código (Barras vs QR) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Tipo de Código Patrimonial:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="config.codeType = 'barcode'"
                                    :class="[
                                        'cursor-pointer px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all',
                                        config.codeType === 'barcode'
                                            ? 'bg-emerald-50 border-emerald-500 text-emerald-700 shadow-sm'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    <Barcode class="w-4 h-4" />
                                    Código de Barras (1D)
                                </button>
                                <button
                                    type="button"
                                    @click="config.codeType = 'qr'"
                                    :class="[
                                        'cursor-pointer px-4 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-2 border transition-all',
                                        config.codeType === 'qr'
                                            ? 'bg-purple-50 border-purple-500 text-purple-700 shadow-sm'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50',
                                    ]"
                                >
                                    <QrCode class="w-4 h-4" />
                                    Código QR (2D)
                                </button>
                            </div>
                        </div>

                        <!-- 4. Si es QR: Diseño Horizontal vs Vertical -->
                        <div v-if="config.codeType === 'qr'" class="p-3 bg-purple-50/50 rounded-xl border border-purple-100 space-y-2">
                            <label class="block text-[11px] font-bold text-purple-900 uppercase tracking-wider">
                                Disposición del Código QR en la Etiqueta:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    @click="config.qrLayout = 'horizontal'"
                                    :class="[
                                        'cursor-pointer px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-center',
                                        config.qrLayout === 'horizontal'
                                            ? 'bg-purple-600 text-white shadow-sm'
                                            : 'bg-white text-purple-700 border border-purple-200 hover:bg-purple-100/50',
                                    ]"
                                >
                                    Horizontal (QR a la izq.)
                                </button>
                                <button
                                    type="button"
                                    @click="config.qrLayout = 'vertical'"
                                    :class="[
                                        'cursor-pointer px-3 py-1.5 rounded-lg text-xs font-bold transition-all text-center',
                                        config.qrLayout === 'vertical'
                                            ? 'bg-purple-600 text-white shadow-sm'
                                            : 'bg-white text-purple-700 border border-purple-200 hover:bg-purple-100/50',
                                    ]"
                                >
                                    Vertical (QR centrado)
                                </button>
                            </div>
                            <p class="text-[10px] text-purple-600">
                                {{ config.qrLayout === 'horizontal' ? 'Recomendado para etiquetas de 50x25 mm (2x1 pulgada).' : 'Recomendado para etiquetas altas.' }}
                            </p>
                        </div>

                        <!-- 5. Medidas Manuales (Milímetros) -->
                        <div class="pt-2 border-t border-slate-100">
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Dimensiones (mm):
                                </label>
                                <span class="text-[10px] font-mono text-slate-400">
                                    TD-402S máx. 108 mm
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div>
                                    <span class="block text-[10px] text-slate-400 font-semibold mb-1">Ancho (mm)</span>
                                    <input
                                        v-model.number="config.labelWidth"
                                        type="number"
                                        step="0.1"
                                        min="20"
                                        max="108"
                                        class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono font-bold text-slate-800 text-center"
                                    />
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-400 font-semibold mb-1">Alto (mm)</span>
                                    <input
                                        v-model.number="config.labelHeight"
                                        type="number"
                                        step="0.1"
                                        min="15"
                                        max="150"
                                        class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono font-bold text-slate-800 text-center"
                                    />
                                </div>
                                <div>
                                    <span class="block text-[10px] text-slate-400 font-semibold mb-1">Gap 2 Col (mm)</span>
                                    <input
                                        v-model.number="config.gap"
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="10"
                                        :disabled="config.columns === 1"
                                        class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs font-mono font-bold text-slate-800 text-center disabled:opacity-40"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- 6. Campos Visibles -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Elementos a Imprimir:
                            </label>
                            <div class="grid grid-cols-2 gap-2 text-xs">
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showEntity" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Entidad (DRE)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showSubtitle" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Subtítulo</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showCode" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Texto del Código</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showName" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Denominación</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showSeries" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Nro. de Serie</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer select-none">
                                    <input v-model="config.showOffice" type="checkbox" class="rounded text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-slate-700 font-medium">Oficina / Área</span>
                                </label>
                            </div>
                        </div>

                        <!-- 7. Botón de Restaurar a 2x1" -->
                        <button
                            @click="applyPreset('2x1_1col')"
                            class="cursor-pointer w-full text-center text-xs font-semibold text-slate-400 hover:text-slate-700 transition-colors flex items-center justify-center gap-1 pt-1"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            Restablecer a estándar 2x1" (50.8 x 25.4 mm)
                        </button>
                    </div>
                </div>

                <!-- PREVISUALIZADOR EN VIVO (Simulación de Rollo Térmico) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Eye class="w-4 h-4 text-indigo-600" />
                            <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                Previsualización del Rollo
                            </h3>
                        </div>
                        <div class="flex items-center gap-1">
                            <button
                                @click="config.previewZoom = Math.max(0.75, +(config.previewZoom - 0.25).toFixed(2))"
                                class="p-1 text-slate-400 hover:text-slate-700 rounded"
                                title="Reducir zoom"
                            >
                                <ZoomOut class="w-3.5 h-3.5" />
                            </button>
                            <span class="text-[10px] font-mono text-slate-500 min-w-[35px] text-center">
                                {{ Math.round(config.previewZoom * 100) }}%
                            </span>
                            <button
                                @click="config.previewZoom = Math.min(2.0, +(config.previewZoom + 0.25).toFixed(2))"
                                class="p-1 text-slate-400 hover:text-slate-700 rounded"
                                title="Aumentar zoom"
                            >
                                <ZoomIn class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Lienzo de simulación del papel térmico -->
                    <div class="p-6 bg-slate-100/80 flex flex-col items-center justify-center min-h-[220px] overflow-auto">
                        <div v-if="previewAssets.length === 0" class="text-center py-6">
                            <PackageOpen class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                            <p class="text-xs text-slate-400">Selecciona bienes para previsualizar</p>
                        </div>

                        <div v-else class="space-y-4">
                            <!-- Si es 1 COLUMNA -->
                            <template v-if="config.columns === 1">
                                <div
                                    v-for="(asset, idx) in previewAssets.slice(0, 2)"
                                    :key="'prev-1col-' + asset.id + '-' + idx"
                                    class="relative group"
                                >
                                    <!-- Línea de gap del rollo -->
                                    <div class="absolute -top-2 left-0 right-0 border-t border-dashed border-slate-400/60"></div>
                                    <ThermalLabelItem
                                        :asset="asset"
                                        :code-type="config.codeType"
                                        :label-width="config.labelWidth"
                                        :label-height="config.labelHeight"
                                        :qr-layout="config.qrLayout"
                                        :options="config"
                                        :scale="config.previewZoom"
                                    />
                                    <div class="absolute -bottom-2 left-0 right-0 border-t border-dashed border-slate-400/60"></div>
                                </div>
                            </template>

                            <!-- Si es 2 COLUMNAS -->
                            <template v-else>
                                <div
                                    v-for="(pair, pIdx) in previewAssetPairs.slice(0, 1)"
                                    :key="'prev-2col-' + pIdx"
                                    class="relative flex items-center justify-center"
                                    :style="{ gap: `${Math.round(config.gap * 3.78 * config.previewZoom)}px` }"
                                >
                                    <!-- Línea de corte arriba/abajo -->
                                    <div class="absolute -top-2 left-0 right-0 border-t border-dashed border-slate-400/60"></div>

                                    <!-- Columna Izquierda -->
                                    <ThermalLabelItem
                                        v-if="pair[0]"
                                        :asset="pair[0]"
                                        :code-type="config.codeType"
                                        :label-width="config.labelWidth"
                                        :label-height="config.labelHeight"
                                        :qr-layout="config.qrLayout"
                                        :options="config"
                                        :scale="config.previewZoom"
                                    />

                                    <!-- Columna Derecha -->
                                    <ThermalLabelItem
                                        v-if="pair[1]"
                                        :asset="pair[1]"
                                        :code-type="config.codeType"
                                        :label-width="config.labelWidth"
                                        :label-height="config.labelHeight"
                                        :qr-layout="config.qrLayout"
                                        :options="config"
                                        :scale="config.previewZoom"
                                    />
                                    <div
                                        v-else
                                        class="border border-dashed border-slate-300 rounded flex items-center justify-center text-[10px] text-slate-300"
                                        :style="{
                                            width: `${Math.round(config.labelWidth * 3.78 * config.previewZoom)}px`,
                                            height: `${Math.round(config.labelHeight * 3.78 * config.previewZoom)}px`,
                                        }"
                                    >
                                        Vacío (Fin de lote)
                                    </div>

                                    <div class="absolute -bottom-2 left-0 right-0 border-t border-dashed border-slate-400/60"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANEL DERECHO: TABLA DE BIENES Y SELECCIÓN (7 cols) -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Buscador y Acciones -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por código patrimonial, denominación, serie..."
                                class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 outline-none text-sm"
                            />
                        </div>
                        <button
                            v-if="isSampleMode"
                            @click="fetchAssets(1)"
                            class="cursor-pointer px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all flex items-center justify-center gap-1.5"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            Volver a BD
                        </button>
                    </div>
                </div>

                <!-- Tabla de Bienes -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <!-- Barra de herramientas de selección -->
                    <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggleAll"
                                class="cursor-pointer inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors"
                            >
                                <component :is="allOnPageSelected ? CheckSquare : Square" class="w-4 h-4 text-indigo-600" />
                                {{ allOnPageSelected ? 'Deseleccionar' : 'Seleccionar' }} página
                            </button>
                            <span
                                v-if="selectedIds.size > 0"
                                class="text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-100 px-2 py-0.5 rounded-full"
                            >
                                {{ selectedIds.size }} seleccionado{{ selectedIds.size > 1 ? 's' : '' }}
                            </span>
                            <button
                                v-if="selectedIds.size > 0"
                                @click="clearSelection"
                                class="cursor-pointer text-xs font-medium text-red-500 hover:text-red-700 transition-colors"
                            >
                                Limpiar
                            </button>
                        </div>
                        <span class="text-xs font-medium text-slate-400">
                            {{ total }} bien{{ total !== 1 ? 'es' : '' }}
                        </span>
                    </div>

                    <!-- Loading -->
                    <div v-if="loading" class="p-12 text-center">
                        <Loader2 class="w-8 h-8 mx-auto text-indigo-500 animate-spin" />
                        <p class="text-sm text-slate-400 mt-2">Cargando bienes...</p>
                    </div>

                    <!-- Estado Vacío -->
                    <div v-else-if="assets.length === 0" class="p-12 text-center">
                        <PackageOpen class="w-12 h-12 mx-auto text-slate-300 mb-2" />
                        <p class="text-slate-600 font-medium text-sm">No se encontraron bienes</p>
                        <p class="text-xs text-slate-400 mt-1">Prueba con otro término de búsqueda o carga ejemplos</p>
                    </div>

                    <!-- Lista de Bienes -->
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-slate-100 text-left bg-slate-50/50">
                                    <th class="px-4 py-3 w-10"></th>
                                    <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider">Código</th>
                                    <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider">Denominación</th>
                                    <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider hidden md:table-cell">Serie</th>
                                    <th class="px-4 py-3 w-12 text-center">Imprimir</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="asset in assets"
                                    :key="asset.id"
                                    @click="toggleSelect(asset.id)"
                                    :class="[
                                        'border-b border-slate-50 cursor-pointer transition-colors',
                                        selectedIds.has(asset.id)
                                            ? 'bg-indigo-50/70 hover:bg-indigo-50'
                                            : 'hover:bg-slate-50/80',
                                    ]"
                                >
                                    <td class="px-4 py-3 text-center">
                                        <component
                                            :is="selectedIds.has(asset.id) ? CheckSquare : Square"
                                            :class="[
                                                'w-4 h-4 transition-colors',
                                                selectedIds.has(asset.id) ? 'text-indigo-600' : 'text-slate-300',
                                            ]"
                                        />
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="font-mono font-bold text-slate-900 text-xs">{{ asset.codigo_patrimonio }}</span>
                                        <span v-if="asset.codigo_interno" class="font-mono text-slate-400 text-[11px] ml-1">
                                            {{ asset.codigo_interno }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-medium text-slate-700 max-w-[240px] truncate text-xs">
                                        {{ asset.denominacion }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-500 font-mono text-xs hidden md:table-cell">
                                        {{ asset.numero_serie || '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <button
                                            @click.stop="printSinglePdf(asset)"
                                            class="cursor-pointer p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-all"
                                            title="Generar PDF individual TD-402S"
                                        >
                                            <Printer class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div
                        v-if="lastPage > 1 && !isSampleMode"
                        class="px-5 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50"
                    >
                        <button
                            :disabled="currentPage <= 1"
                            @click="goToPage(currentPage - 1)"
                            class="cursor-pointer inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            <ChevronLeft class="w-3.5 h-3.5" />
                            Anterior
                        </button>
                        <span class="text-xs font-medium text-slate-500">
                            Página {{ currentPage }} de {{ lastPage }}
                        </span>
                        <button
                            :disabled="currentPage >= lastPage"
                            @click="goToPage(currentPage + 1)"
                            class="cursor-pointer inline-flex items-center gap-1 text-xs font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                        >
                            Siguiente
                            <ChevronRight class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
