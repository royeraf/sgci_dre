<script setup>
import { ref, shallowRef, computed, watch } from 'vue';
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
} from 'lucide-vue-next';
import axios from 'axios';

const search = ref('');
const assets = ref([]);
const selectedIds = ref(new Set());
const loading = shallowRef(false);
const labelSize = ref('medium');
const currentPage = shallowRef(1);
const lastPage = shallowRef(1);
const total = shallowRef(0);

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

// Generar PDF individual - abre en nueva pestaña
const printSingle = (asset) => {
    window.open(`/assets/barcodes/pdf?ids=${asset.id}&size=${labelSize.value}`, '_blank');
};

// Generar PDF por lote - abre en nueva pestaña
const printBatch = () => {
    const ids = [...selectedIds.value].join(',');
    window.open(`/assets/barcodes/pdf?ids=${ids}&size=${labelSize.value}`, '_blank');
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
                    <input v-model="search" type="text"
                        placeholder="Buscar por código patrimonial, denominación, serie..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all placeholder:text-slate-400 font-medium" />
                </div>

                <!-- Label Size -->
                <div class="flex items-center gap-2">
                    <label class="text-sm font-bold text-slate-600 whitespace-nowrap">Tamaño:</label>
                    <select v-model="labelSize"
                        class="px-3 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-slate-600 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none text-sm">
                        <option value="small">Pequeño (40x20mm)</option>
                        <option value="medium">Mediano (60x30mm)</option>
                        <option value="large">Grande (80x40mm)</option>
                    </select>
                </div>

                <!-- Print Batch Button -->
                <button :disabled="selectedIds.size === 0" @click="printBatch"
                    class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-slate-600/20 text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 focus:outline-none focus:ring-4 focus:ring-slate-300 transition-all duration-300 transform hover:scale-105 active:scale-95 disabled:opacity-40 disabled:cursor-not-allowed disabled:transform-none disabled:shadow-none">
                    <Printer class="w-4 h-4 mr-2" />
                    Imprimir Lote ({{ selectedIds.size }})
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
            <!-- Assets Table -->
            <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Table Header Actions -->
                <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <button @click="toggleAll"
                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                            <component :is="allOnPageSelected ? CheckSquare : Square" class="w-4 h-4" />
                            {{ allOnPageSelected ? 'Deseleccionar' : 'Seleccionar' }} página
                        </button>
                        <span v-if="selectedIds.size > 0"
                            class="text-xs font-bold text-slate-500 bg-slate-200 px-2 py-0.5 rounded-full">
                            {{ selectedIds.size }} seleccionado{{ selectedIds.size > 1 ? 's' : '' }}
                        </span>
                        <button v-if="selectedIds.size > 0" @click="clearSelection"
                            class="text-xs font-medium text-red-500 hover:text-red-700 transition-colors">
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
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider hidden md:table-cell">Cód. Barras</th>
                            <th class="px-4 py-3 font-bold text-slate-600 text-xs uppercase tracking-wider hidden lg:table-cell">Serie</th>
                            <th class="px-4 py-3 w-12"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="asset in assets" :key="asset.id" @click="toggleSelect(asset.id)" :class="[
                            'border-b border-slate-50 cursor-pointer transition-colors',
                            selectedIds.has(asset.id)
                                ? 'bg-slate-100/80 hover:bg-slate-100'
                                : 'hover:bg-slate-50'
                        ]">
                            <td class="px-4 py-3 text-center">
                                <component :is="selectedIds.has(asset.id) ? CheckSquare : Square" :class="[
                                    'w-4 h-4 transition-colors',
                                    selectedIds.has(asset.id) ? 'text-slate-700' : 'text-slate-300'
                                ]" />
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-mono font-bold text-slate-800">{{ asset.codigo_patrimonio }}</span>
                                <span class="font-mono text-slate-400 text-xs ml-1">{{ asset.codigo_interno }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-700 max-w-[200px] truncate">
                                {{ asset.denominacion }}
                            </td>
                            <td class="px-4 py-3 hidden md:table-cell">
                                <span v-if="asset.codigo_barras"
                                    class="font-mono text-xs text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">
                                    {{ asset.codigo_barras }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">Sin generar</span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 font-mono text-xs hidden lg:table-cell">
                                {{ asset.numero_serie || '—' }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <button @click.stop="printSingle(asset)"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-all"
                                    title="Generar PDF individual">
                                    <Printer class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="lastPage > 1"
                    class="px-5 py-3 border-t border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <button :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)"
                        class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        <ChevronLeft class="w-4 h-4" />
                        Anterior
                    </button>
                    <span class="text-xs font-medium text-slate-500">
                        Página {{ currentPage }} de {{ lastPage }}
                    </span>
                    <button :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)"
                        class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 hover:text-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
                        Siguiente
                        <ChevronRight class="w-4 h-4" />
                    </button>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-5 py-3 border-b border-slate-100 bg-gradient-to-r from-slate-700 to-gray-700">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <Barcode class="w-4 h-4" />
                        Códigos de Barra
                    </h3>
                </div>

                <div class="p-5 space-y-4">
                    <div v-if="selectedIds.size === 0" class="py-8 text-center">
                        <Barcode class="w-12 h-12 mx-auto text-slate-200 mb-3" />
                        <p class="text-sm text-slate-400 font-medium">Selecciona bienes de la tabla</p>
                        <p class="text-xs text-slate-300 mt-1">para generar sus etiquetas en PDF</p>
                    </div>

                    <template v-else>
                        <div class="space-y-2">
                            <p class="text-sm font-bold text-slate-700">
                                {{ selectedIds.size }} bien{{ selectedIds.size > 1 ? 'es' : '' }} seleccionado{{ selectedIds.size > 1 ? 's' : '' }}
                            </p>
                            <ul class="space-y-1.5 max-h-[400px] overflow-y-auto">
                                <li v-for="asset in selectedAssets" :key="'sel-' + asset.id"
                                    class="flex items-center justify-between text-sm bg-slate-50 px-3 py-2 rounded-lg">
                                    <div>
                                        <span class="font-mono font-bold text-slate-700">{{ asset.codigo_patrimonio }}</span>
                                        <span class="text-slate-400 ml-2">{{ asset.denominacion }}</span>
                                    </div>
                                    <span v-if="asset.codigo_barras"
                                        class="text-[10px] text-emerald-600 font-mono">{{ asset.codigo_barras }}</span>
                                </li>
                            </ul>
                        </div>

                        <button @click="printBatch"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 shadow-lg transition-all">
                            <Printer class="w-4 h-4 mr-2" />
                            Generar PDF de Etiquetas
                        </button>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>
