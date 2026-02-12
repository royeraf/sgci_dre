<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 mb-3 group-hover:scale-110 transition-transform">
                        <Database class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Registros</p>
                    <h3 class="text-2xl font-black text-slate-900">{{ patrimonioStats.total }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-green-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 mb-3 group-hover:scale-110 transition-transform">
                        <CheckCircle class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Sincronizados</p>
                    <h3 class="text-2xl font-black text-green-600">{{ patrimonioStats.sincronizados }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-3 group-hover:scale-110 transition-transform">
                        <AlertTriangle class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Sin Sincronizar</p>
                    <h3 class="text-2xl font-black text-amber-600">{{ patrimonioStats.no_sincronizados }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-3 group-hover:scale-110 transition-transform">
                        <Layers class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Lotes Importados</p>
                    <h3 class="text-2xl font-black text-blue-600">{{ patrimonioStats.lotes?.length || 0 }}</h3>
                </div>
            </div>
        </div>

        <!-- Upload Section -->
        <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                    <Upload class="w-5 h-5 text-slate-600" />
                    Importar CSV de SIGA
                </h3>
            </div>

            <div class="flex flex-col sm:flex-row items-start sm:items-end gap-4">
                <div class="flex-1 w-full">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                        Archivo CSV
                    </label>
                    <div class="relative">
                        <input ref="fileInput" type="file" accept=".csv,.txt"
                            @change="handleFileSelect"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200" />
                    </div>
                    <p v-if="selectedFile" class="mt-1 text-xs text-slate-500">
                        {{ selectedFile.name }} ({{ formatFileSize(selectedFile.size) }})
                    </p>
                </div>

                <button @click="uploadFile" :disabled="!selectedFile || uploading"
                    class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap">
                    <Loader2 v-if="uploading" class="w-4 h-4 mr-2 animate-spin" />
                    <Upload v-else class="w-4 h-4 mr-2" />
                    {{ uploading ? 'Importando...' : 'Importar' }}
                </button>
            </div>

            <!-- Last import result -->
            <div v-if="lastImportResult" class="mt-4 p-4 rounded-xl border" :class="lastImportResult.errores > 0 ? 'bg-amber-50 border-amber-200' : 'bg-green-50 border-green-200'">
                <p class="text-sm font-semibold" :class="lastImportResult.errores > 0 ? 'text-amber-800' : 'text-green-800'">
                    Resultado de la importaci&oacute;n:
                    {{ lastImportResult.importados }} registros importados
                    <span v-if="lastImportResult.errores > 0">, {{ lastImportResult.errores }} errores</span>
                </p>
                <p class="text-xs mt-1" :class="lastImportResult.errores > 0 ? 'text-amber-600' : 'text-green-600'">
                    Archivo: {{ lastImportResult.archivo }}
                </p>
            </div>
        </div>

        <!-- Import History -->
        <div v-if="patrimonioStats.lotes?.length > 0" class="bg-white shadow-lg rounded-2xl border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-4">
                <History class="w-5 h-5 text-slate-600" />
                Historial de Importaciones
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Archivo</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Registros</th>
                            <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="lote in patrimonioStats.lotes" :key="lote.lote_importacion" class="hover:bg-blue-50 transition-colors">
                            <td class="px-4 py-3 text-sm text-slate-700">{{ lote.archivo_origen || 'Sin nombre' }}</td>
                            <td class="px-4 py-3 text-sm text-slate-500">{{ formatDate(lote.fecha_importacion) }}</td>
                            <td class="px-4 py-3 text-sm text-center font-semibold text-slate-700">{{ lote.total }}</td>
                            <td class="px-4 py-3 text-center">
                                <button @click="filterByLote(lote.lote_importacion)"
                                    class="text-blue-600 hover:text-blue-800 text-xs font-semibold">
                                    Ver registros
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input v-model="search" type="text"
                            placeholder="Buscar por c&oacute;digo, denominaci&oacute;n, responsable..."
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                    <select v-model="filterEstado"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos</option>
                        <option value="BUENO">BUENO</option>
                        <option value="REGULAR">REGULAR</option>
                        <option value="MALO">MALO</option>
                        <option value="CHATARRA">CHATARRA</option>
                        <option value="NUEVO">NUEVO</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sincronizado</label>
                    <select v-model="filterSincronizado"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos</option>
                        <option value="true">Sincronizado</option>
                        <option value="false">No sincronizado</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    <span class="font-semibold text-slate-700">{{ total }}</span>
                    resultados encontrados
                </p>
                <button @click="clearFilters"
                    class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors duration-200 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
            <div v-if="loading" class="p-12 text-center">
                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                <p class="text-sm text-slate-400 mt-2">Cargando registros...</p>
            </div>

            <template v-else-if="records.length === 0">
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                            <Database class="h-12 w-12 text-slate-400" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">No hay registros de patrimonio</h3>
                        <p class="text-sm text-slate-500">Importa un archivo CSV de SIGA para comenzar.</p>
                    </div>
                </div>
            </template>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">C&oacute;digo</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Denominaci&oacute;n</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Marca</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Serie</th>
                            <th class="px-4 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Estado</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Responsable</th>
                            <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Oficina</th>
                            <th class="px-4 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Sync</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-for="record in records" :key="record.id" class="hover:bg-blue-50 transition-colors duration-200">
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900 font-mono">{{ record.codigo_patrimonial }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ record.codigo_interno }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-slate-700 font-medium line-clamp-2 max-w-xs">{{ record.denominacion }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-slate-600">{{ record.marca || '—' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="text-sm text-slate-500 font-mono">{{ record.nro_serie || '—' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                <span v-if="record.estado_conserv"
                                    class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                    :class="getEstadoClass(record.estado_conserv)">
                                    {{ record.estado_conserv }}
                                </span>
                                <span v-else class="text-xs text-slate-400">—</span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="text-sm text-slate-600 max-w-[200px] truncate">{{ record.responsable_final || '—' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="text-sm text-slate-600 max-w-[150px] truncate">{{ record.oficina || '—' }}</div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                <span v-if="record.sincronizado"
                                    class="px-2 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">
                                    Si
                                </span>
                                <span v-else
                                    class="px-2 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-500">
                                    No
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="records.length > 0" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Mostrar</span>
                        <select v-model="perPage"
                            class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>por p&aacute;gina</span>
                    </div>
                    <div class="text-sm text-slate-600">
                        P&aacute;gina {{ currentPage }} de {{ lastPage }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="fetchRecords(1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronsLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchRecords(currentPage - 1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchRecords(currentPage + 1)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <button @click="fetchRecords(lastPage)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronsRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import {
    Database,
    CheckCircle,
    AlertTriangle,
    Layers,
    Upload,
    Search,
    X,
    Loader2,
    History,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

// Stats
const patrimonioStats = ref({ total: 0, sincronizados: 0, no_sincronizados: 0, lotes: [], por_estado: {} });

const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/patrimonio/stats');
        patrimonioStats.value = response.data;
    } catch (error) {
        console.error('Error fetching patrimonio stats:', error);
    }
};

// Upload
const fileInput = ref(null);
const selectedFile = ref(null);
const uploading = ref(false);
const lastImportResult = ref(null);

const handleFileSelect = (event) => {
    selectedFile.value = event.target.files[0] || null;
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
};

const uploadFile = async () => {
    if (!selectedFile.value) return;

    const confirm = await Swal.fire({
        title: 'Confirmar importaci\u00f3n',
        html: `<p class="text-sm text-gray-500">Se importar\u00e1 el archivo:</p>
               <p class="font-mono font-bold mt-2">${selectedFile.value.name}</p>
               <p class="text-xs text-gray-400 mt-1">${formatFileSize(selectedFile.value.size)}</p>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Importar',
        cancelButtonText: 'Cancelar',
    });

    if (!confirm.isConfirmed) return;

    uploading.value = true;
    lastImportResult.value = null;

    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await axios.post('/assets/patrimonio/import', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        lastImportResult.value = response.data;

        Swal.fire({
            icon: 'success',
            title: 'Importaci\u00f3n completada',
            html: `<p>${response.data.importados} registros importados</p>
                   ${response.data.errores > 0 ? `<p class="text-amber-600">${response.data.errores} errores</p>` : ''}`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,
        });

        // Limpiar y recargar
        selectedFile.value = null;
        if (fileInput.value) fileInput.value.value = '';
        fetchStats();
        fetchRecords(1);
    } catch (error) {
        const message = error.response?.data?.error || error.response?.data?.message || 'Error al importar el archivo';
        Swal.fire({
            icon: 'error',
            title: 'Error de importaci\u00f3n',
            text: message,
        });
    } finally {
        uploading.value = false;
    }
};

// Filters
const search = ref('');
const filterEstado = ref('');
const filterSincronizado = ref('');
const filterLote = ref('');

const clearFilters = () => {
    search.value = '';
    filterEstado.value = '';
    filterSincronizado.value = '';
    filterLote.value = '';
    fetchRecords(1);
};

const filterByLote = (lote) => {
    filterLote.value = lote;
    fetchRecords(1);
};

// Table
const records = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(15);

const fetchRecords = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/assets/patrimonio', {
            params: {
                search: search.value || undefined,
                estado_conserv: filterEstado.value || undefined,
                sincronizado: filterSincronizado.value || undefined,
                lote_importacion: filterLote.value || undefined,
                per_page: perPage.value,
                page,
            },
        });
        records.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error fetching patrimonio records:', error);
    } finally {
        loading.value = false;
    }
};

let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchRecords(1), 400);
});

watch([filterEstado, filterSincronizado, filterLote], () => fetchRecords(1));
watch(perPage, () => fetchRecords(1));

const getEstadoClass = (estado) => {
    const classes = {
        'BUENO': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'REGULAR': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'MALO': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
        'CHATARRA': 'bg-gradient-to-r from-gray-600 to-gray-700 text-white',
        'NUEVO': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
    };
    return classes[estado] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    fetchStats();
    fetchRecords(1);
});
</script>
