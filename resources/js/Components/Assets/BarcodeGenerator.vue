<script setup>
import { ref, shallowRef, computed, watch, nextTick } from 'vue';
import {
    Search,
    Printer,
    CheckSquare,
    Square,
    Barcode,
    ChevronLeft,
    ChevronRight,
    Loader2,
    PackageOpen,
    X,
} from 'lucide-vue-next';
import axios from 'axios';
import BarcodeLabel from '@/Components/Assets/BarcodeLabel.vue';

const search = ref('');
const assets = ref([]);
const selectedIds = ref(new Set());
const loading = shallowRef(false);
const labelSize = ref('medium');
const currentPage = shallowRef(1);
const lastPage = shallowRef(1);
const total = shallowRef(0);
const showPreview = shallowRef(false);

const selectedAssets = computed(() =>
    assets.value.filter(a => selectedIds.value.has(a.id))
);

const allOnPageSelected = computed(() =>
    assets.value.length > 0 && assets.value.every(a => selectedIds.value.has(a.id))
);

const fetchAssets = async (page = 1) => {
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
        assets.value.forEach(a => next.delete(a.id));
        selectedIds.value = next;
    } else {
        const next = new Set(selectedIds.value);
        assets.value.forEach(a => next.add(a.id));
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

const handlePrint = () => {
    window.print();
};

const openPrintPreview = async () => {
    showPreview.value = true;
    await nextTick();
    setTimeout(handlePrint, 300);
};

fetchAssets(1);
</script>

<template>
    <div class="space-y-6">
        <!-- Search & Controls -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
            <div class="flex flex-col lg:flex-row gap-4">
                <!-- Search -->
                <div class="flex-1 relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por código patrimonial, denominación, serie..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all placeholder:text-slate-400 font-medium"
                    />
                </div>

                <!-- Label Size -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-bold text-slate-600 whitespace-nowrap">Tamaño:</label>
                    <select
                        v-model="labelSize"
                        class="px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-slate-600 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none text-sm"
                    >
                        <option value="small">Pequeño (40x20mm)</option>
                        <option value="medium">Mediano (60x30mm)</option>
                        <option value="large">Grande (80x40mm)</option>
                    </select>
                </div>

                <!-- Print Button -->
                <button
                    :disabled="selectedIds.size === 0"
                    @click="openPrintPreview"
                    class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-slate-600/20 text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 focus:outline-none focus:ring-4 focus:ring-slate-300 transition-all duration-300 transform hover:scale-105 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none"
                >
                    <Printer class="w-4 h-4 mr-2" />
                    Imprimir ({{ selectedIds.size }})
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Assets Table -->
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Table Header Actions -->
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleAll"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors"
                        >
                            <component :is="allOnPageSelected ? CheckSquare : Square" class="w-4 h-4" />
                            {{ allOnPageSelected ? 'Deseleccionar' : 'Seleccionar' }} página
                        </button>
                        <span v-if="selectedIds.size > 0" class="text-xs font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">
                            {{ selectedIds.size }} seleccionado{{ selectedIds.size > 1 ? 's' : '' }}
                        </span>
                        <button
                            v-if="selectedIds.size > 0"
                            @click="clearSelection"
                            class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors"
                        >
                            Limpiar
                        </button>
                    </div>
                    <span class="text-xs font-medium text-slate-400">
                        {{ total }} bien{{ total !== 1 ? 'es' : '' }} encontrado{{ total !== 1 ? 's' : '' }}
                    </span>
                </div>

                <!-- Loading -->
                <div v-if="loading" class="p-12 text-center">
                    <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                    <p class="text-sm text-slate-400 mt-2">Cargando bienes...</p>
                </div>

                <!-- Empty State -->
                <div v-else-if="assets.length === 0" class="p-12 text-center">
                    <PackageOpen class="w-14 h-14 mx-auto text-slate-300 mb-3" />
                    <p class="text-slate-500 font-medium">No se encontraron bienes</p>
                    <p class="text-sm text-slate-400 mt-1">Intenta con otro término de búsqueda</p>
                </div>

                <!-- Table -->
                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 text-left">
                            <th class="px-4 py-3 w-10"></th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider">Código</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider">Denominación</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider hidden md:table-cell">Marca</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider hidden lg:table-cell">Serie</th>
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
                                    ? 'bg-slate-100/80 hover:bg-slate-100'
                                    : 'hover:bg-slate-50'
                            ]"
                        >
                            <td class="px-4 py-3 text-center">
                                <component
                                    :is="selectedIds.has(asset.id) ? CheckSquare : Square"
                                    :class="[
                                        'w-4 h-4 transition-colors',
                                        selectedIds.has(asset.id) ? 'text-slate-700' : 'text-slate-300'
                                    ]"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono font-bold text-slate-800">{{ asset.codigo_patrimonio }}</span>
                                <span class="font-mono text-slate-400 text-xs ml-1">{{ asset.codigo_interno }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-700 max-w-[200px] truncate">
                                {{ asset.denominacion }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">
                                {{ asset.brand?.nombre || '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 font-mono text-xs hidden lg:table-cell">
                                {{ asset.numero_serie || '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="lastPage > 1" class="px-5 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <button
                        :disabled="currentPage <= 1"
                        @click="goToPage(currentPage - 1)"
                        class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        <ChevronLeft class="w-4 h-4" />
                        Anterior
                    </button>
                    <span class="text-xs font-medium text-slate-500">
                        Página {{ currentPage }} de {{ lastPage }}
                    </span>
                    <button
                        :disabled="currentPage >= lastPage"
                        @click="goToPage(currentPage + 1)"
                        class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    >
                        Siguiente
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Preview Panel -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-700 to-gray-700">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Barcode class="w-4 h-4" />
                        Previsualización
                    </h3>
                </div>

                <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto">
                    <div v-if="selectedIds.size === 0" class="py-12 text-center">
                        <Barcode class="w-12 h-12 mx-auto text-slate-200 mb-3" />
                        <p class="text-sm text-slate-400 font-medium">Selecciona bienes de la tabla</p>
                        <p class="text-xs text-slate-300 mt-1">para previsualizar sus etiquetas</p>
                    </div>

                    <template v-else>
                        <BarcodeLabel
                            v-for="asset in selectedAssets"
                            :key="'preview-' + asset.id"
                            :asset="asset"
                            :size="labelSize"
                            class="mx-auto"
                        />
                    </template>
                </div>
            </div>
        </div>

        <!-- Print Overlay (hidden on screen, visible on print) -->
        <Teleport to="body">
            <div v-if="showPreview" class="print-overlay fixed inset-0 z-[9999] bg-white overflow-auto">
                <!-- Close button (screen only) -->
                <div class="no-print fixed top-4 right-4 z-[10000] flex gap-2">
                    <button
                        @click="handlePrint"
                        class="inline-flex items-center px-4 py-2 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 shadow-lg transition-all"
                    >
                        <Printer class="w-4 h-4 mr-2" />
                        Imprimir
                    </button>
                    <button
                        @click="showPreview = false"
                        class="inline-flex items-center px-3 py-2 text-sm font-bold rounded-xl text-slate-600 bg-white border border-slate-300 hover:bg-slate-50 shadow-lg transition-all"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Print Content -->
                <div class="print-content p-8">
                    <div class="flex flex-wrap gap-3 justify-center">
                        <BarcodeLabel
                            v-for="asset in selectedAssets"
                            :key="'print-' + asset.id"
                            :asset="asset"
                            :size="labelSize"
                        />
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
@media print {
    .no-print {
        display: none !important;
    }
    .print-overlay {
        position: static !important;
        overflow: visible !important;
    }
    .print-content {
        padding: 0 !important;
    }
}
</style>
