<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-slate-700 to-gray-700 bg-clip-text text-transparent tracking-tight">
                        Gestión de Patrimonio
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Administración y control de bienes patrimoniales
                    </p>
                </div>

                <div class="flex gap-3">
                    <a href="/assets/catalogs"
                        class="inline-flex items-center px-4 py-3 border-2 border-slate-300 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200 transition-all duration-200">
                        <Settings class="w-5 h-5 mr-2" />
                        Catálogos
                    </a>
                    <!-- Escanear Código de Barras (visible en list y movements) -->
                    <button v-if="activeTab === 'list' || activeTab === 'movements'" @click="showBarcodeScanner = true"
                        class="inline-flex items-center px-4 py-3 border-2 border-emerald-300 text-sm font-bold rounded-xl text-emerald-700 bg-emerald-50 hover:bg-emerald-100 focus:outline-none focus:ring-4 focus:ring-emerald-200 transition-all duration-200">
                        <ScanBarcode class="w-5 h-5 mr-2" />
                        Escanear
                    </button>
                    <!-- Nuevo Bien (solo en tab list) -->
                    <button v-if="activeTab === 'list'" @click="showCreateModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-slate-600/20 text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 focus:outline-none focus:ring-4 focus:ring-slate-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nuevo Bien
                    </button>
                    <!-- Nuevo Movimiento (solo en tab movements) -->
                    <button v-if="activeTab === 'movements'" @click="openNewMovement"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-blue-600/20 text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <ArrowRightLeft class="w-5 h-5 mr-2" />
                        Nuevo Movimiento
                    </button>
                </div>
            </div>

            <!-- Asset Modal -->
            <AssetModal v-if="showCreateModal" :categories="categories" :brands="brands" :colors="colors"
                :states="states" :origins="origins" :areas="areas" :offices="offices" :employees="employees"
                @close="showCreateModal = false" @success="handleAssetCreated" />

            <!-- Barcode Scanner Modal -->
            <BarcodeScannerModal v-if="showBarcodeScanner" @close="showBarcodeScanner = false"
                @found="handleBarcodeFound" />

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'list'" :class="[
                        activeTab === 'list'
                            ? 'border-slate-600 text-slate-700'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <Box class="w-5 h-5" />
                        Inventario de Bienes
                    </button>
                    <button @click="activeTab = 'movements'" :class="[
                        activeTab === 'movements'
                            ? 'border-slate-600 text-slate-700'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <ArrowRightLeft class="w-5 h-5" />
                        Movimientos
                    </button>
                    <button @click="activeTab = 'barcodes'" :class="[
                        activeTab === 'barcodes'
                            ? 'border-slate-600 text-slate-700'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <Barcode class="w-5 h-5" />
                        Códigos de Barra
                    </button>
                    <button @click="activeTab = 'reports'" :class="[
                        activeTab === 'reports'
                            ? 'border-green-600 text-green-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <FileDown class="w-5 h-5" />
                        Reportes
                    </button>
                    <button @click="activeTab = 'patrimonio'" :class="[
                        activeTab === 'patrimonio'
                            ? 'border-indigo-600 text-indigo-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <DatabaseIcon class="w-5 h-5" />
                        Patrimonio SIGA
                    </button>
                    <button @click="activeTab = 'inventarios'" :class="[
                        activeTab === 'inventarios'
                            ? 'border-purple-600 text-purple-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Inventarios
                    </button>
                </nav>
            </div>

            <!-- Stats Overview -->
            <div v-if="activeTab === 'list'" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div
                    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative">
                        <div
                            class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-700 mb-4 group-hover:scale-110 transition-transform">
                            <Box class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Bienes</p>
                        <h3 class="text-3xl font-black text-slate-900 mt-1">{{ stats.total }}</h3>
                    </div>
                </div>

                <div
                    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative">
                        <div
                            class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-slate-700 mb-4 group-hover:scale-110 transition-transform">
                            <CheckCircle class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Buenos</p>
                        <h3 class="text-3xl font-black text-slate-700 mt-1">{{ stats.buenos }}</h3>
                    </div>
                </div>

                <div
                    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-yellow-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative">
                        <div
                            class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 mb-4 group-hover:scale-110 transition-transform">
                            <AlertTriangle class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Regulares</p>
                        <h3 class="text-3xl font-black text-yellow-600 mt-1">{{ stats.regulares }}</h3>
                    </div>
                </div>

                <div
                    class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div
                        class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                    </div>
                    <div class="relative">
                        <div
                            class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 mb-4 group-hover:scale-110 transition-transform">
                            <XCircle class="w-6 h-6" />
                        </div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Malos</p>
                        <h3 class="text-3xl font-black text-red-600 mt-1">{{ stats.malos }}</h3>
                    </div>
                </div>
            </div>

            <!-- List Content -->
            <div v-if="activeTab === 'list'" class="space-y-6">
                <!-- Filters (estilo Libro de Ocurrencias) -->
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Search -->
                        <div class="lg:col-span-2">
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                            <div class="relative">
                                <Search
                                    class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                                <input v-model="listSearch" type="text"
                                    placeholder="Buscar por código, denominación, serie..."
                                    class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm" />
                            </div>
                        </div>

                        <!-- Estado Filter -->
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                            <select v-model="listEstadoId"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                                <option value="">Todos</option>
                                <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                            </select>
                        </div>

                        <!-- Categoría Filter -->
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Categoría</label>
                            <select v-model="listCategoriaId"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                                <option value="">Todas</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Filter Actions -->
                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                        <p class="text-sm text-slate-500">
                            <span class="font-semibold text-slate-700">{{ listTotal }}</span>
                            resultados encontrados
                        </p>
                        <button @click="clearListFilters"
                            class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors duration-200 flex items-center gap-1">
                            <X class="w-4 h-4" />
                            Limpiar filtros
                        </button>
                    </div>
                </div>

                <!-- Table (estilo Libro de Ocurrencias) -->
                <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                    <!-- Loading -->
                    <div v-if="listLoading" class="p-12 text-center">
                        <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                        <p class="text-sm text-slate-400 mt-2">Cargando bienes...</p>
                    </div>

                    <!-- Empty -->
                    <template v-else-if="listAssets.length === 0">
                        <div class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-100 rounded-full p-4 mb-4">
                                    <PackageOpen class="h-12 w-12 text-slate-400" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">No se encontraron bienes</h3>
                                <p class="text-sm text-slate-500">Intenta con otro término de búsqueda o registra un
                                    nuevo bien.</p>
                            </div>
                        </div>
                    </template>

                    <!-- Table -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Código</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Denominación</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                        Marca</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                        Serie</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                        Estado</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                        Responsable</th>
                                    <th scope="col"
                                        class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                        Ubicación</th>
                                    <th scope="col" class="relative px-6 py-4">
                                        <span class="sr-only">Acciones</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="asset in listAssets" :key="asset.id"
                                    class="hover:bg-blue-50 transition-colors duration-200">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-slate-900 font-mono">{{
                                            asset.codigo_patrimonio }}</div>
                                        <div class="text-xs text-slate-500 font-mono">{{ asset.codigo_interno }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-slate-700 font-medium line-clamp-2 max-w-xs">{{
                                            asset.denominacion }}</div>
                                        <span v-if="asset.patrimonio_asset?.sincronizado"
                                            class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                            <DatabaseIcon class="w-2.5 h-2.5" />
                                            SIGA
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                        <div class="text-sm text-slate-600">{{ asset.brand?.nombre || '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                        <div class="text-sm text-slate-500 font-mono">{{ asset.numero_serie || '—' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                        <span v-if="asset.latest_movement?.state"
                                            class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                            :class="getStateClass(asset.latest_movement.state.nombre)">
                                            {{ asset.latest_movement.state.nombre }}
                                        </span>
                                        <span v-else class="text-xs text-slate-400 italic">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                        <div v-if="asset.latest_movement?.responsible?.employee?.person"
                                            class="flex items-center">
                                            <div
                                                class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3">
                                                {{ (asset.latest_movement.responsible.employee.person.nombres ||
                                                    '?').charAt(0) }}
                                            </div>
                                            <div class="text-sm font-semibold text-slate-900">
                                                {{ asset.latest_movement.responsible.employee.person.nombres }}
                                                {{ asset.latest_movement.responsible.employee.person.apellido_paterno }}
                                            </div>
                                        </div>
                                        <span v-else class="text-sm text-slate-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                        <div class="text-sm text-slate-600">{{ asset.latest_movement?.office?.nombre ||
                                            '—' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center gap-1 justify-end">
                                            <button @click="openEditModal(asset)"
                                                class="text-slate-400 hover:text-blue-600 transition-colors duration-200 p-2 hover:bg-blue-50 rounded-lg"
                                                title="Editar">
                                                <Pencil class="h-5 w-5" />
                                            </button>
                                            <button @click="confirmDelete(asset)"
                                                class="text-slate-400 hover:text-red-600 transition-colors duration-200 p-2 hover:bg-red-50 rounded-lg"
                                                title="Eliminar">
                                                <Trash2 class="h-5 w-5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="flex items-center gap-2 text-sm text-slate-600">
                                <span>Mostrar</span>
                                <select v-model="listPerPage"
                                    class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                </select>
                                <span>por página</span>
                            </div>
                            <div class="text-sm text-slate-600">
                                Página {{ listCurrentPage }} de {{ listLastPage }}
                            </div>
                            <div class="flex items-center gap-1">
                                <button @click="fetchListAssets(1)" :disabled="listCurrentPage === 1"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <ChevronsLeft class="w-4 h-4" />
                                </button>
                                <button @click="fetchListAssets(listCurrentPage - 1)" :disabled="listCurrentPage === 1"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <ChevronLeft class="w-4 h-4" />
                                </button>
                                <button @click="fetchListAssets(listCurrentPage + 1)"
                                    :disabled="listCurrentPage === listLastPage"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <ChevronRight class="w-4 h-4" />
                                </button>
                                <button @click="fetchListAssets(listLastPage)"
                                    :disabled="listCurrentPage === listLastPage"
                                    class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <ChevronsRight class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <AssetModal v-if="showEditModal" :asset="editingAsset" :categories="categories" :brands="brands"
                :colors="colors" :states="states" :origins="origins" :areas="areas" :offices="offices"
                :employees="employees" @close="showEditModal = false" @success="handleAssetUpdated" />

            <!-- Delete confirmation handled by SweetAlert2 -->

            <!-- Barcodes Tab -->
            <BarcodeGenerator v-if="activeTab === 'barcodes'" />

            <!-- Movements Tab -->
            <MovementsList v-if="activeTab === 'movements'" ref="movementsListRef" :states="states" :offices="offices"
                :employees="employees" />

            <!-- Reports Tab -->
            <div v-if="activeTab === 'reports'">
                <ReportsTab :employees="employees" />
            </div>

            <!-- Patrimonio SIGA Tab -->
            <PatrimonioSigaTab v-if="activeTab === 'patrimonio'" />

            <!-- Inventarios Tab -->
            <InventariosTab v-if="activeTab === 'inventarios'"
                :states="states" :offices="offices" :employees="employees" />
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup>
import { ref, watch, onMounted } from 'vue';
import {
    Plus,
    Search,
    FileDown,
    Box,
    CheckCircle,
    ArrowRightLeft,
    AlertTriangle,
    XCircle,
    Settings,
    Barcode,
    ScanBarcode,
    Pencil,
    Trash2,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Loader2,
    PackageOpen,
    X,
    Database as DatabaseIcon,
    ClipboardList,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';
import AssetModal from '@/Components/Assets/AssetModal.vue';
import BarcodeGenerator from '@/Components/Assets/BarcodeGenerator.vue';
import MovementsList from '@/Components/Assets/MovementsList.vue';
import BarcodeScannerModal from '@/Components/Assets/BarcodeScannerModal.vue';
import ReportsTab from '@/Components/Assets/ReportsTab.vue';
import PatrimonioSigaTab from '@/Components/Assets/PatrimonioSigaTab.vue';
import InventariosTab from '@/Components/Assets/InventariosTab.vue';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    states: { type: Array, default: () => [] },
    origins: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    offices: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

const activeTab = ref('list');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingAsset = ref(null);
const movementsListRef = ref(null);
const showBarcodeScanner = ref(false);

const openNewMovement = () => {
    if (movementsListRef.value) {
        movementsListRef.value.openModal();
    }
};

const handleBarcodeFound = (code) => {
    showBarcodeScanner.value = false;
    if (activeTab.value === 'list') {
        listSearch.value = code;
    } else if (activeTab.value === 'movements') {
        if (movementsListRef.value) {
            movementsListRef.value.setSearch(code);
        }
    }
};

// Stats
const stats = ref({ total: 0, buenos: 0, regulares: 0, malos: 0 });

const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/summary');
        stats.value = response.data;
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

// List data
const listAssets = ref([]);
const listLoading = ref(false);
const listSearch = ref('');
const listEstadoId = ref('');
const listCategoriaId = ref('');
const listCurrentPage = ref(1);
const listLastPage = ref(1);
const listTotal = ref(0);
const listPerPage = ref(15);

const fetchListAssets = async (page = 1) => {
    listLoading.value = true;
    try {
        const response = await axios.get('/assets/list', {
            params: {
                search: listSearch.value || undefined,
                estado_id: listEstadoId.value || undefined,
                categoria_id: listCategoriaId.value || undefined,
                per_page: listPerPage.value,
                page,
            },
        });
        listAssets.value = response.data.data;
        listCurrentPage.value = response.data.current_page;
        listLastPage.value = response.data.last_page;
        listTotal.value = response.data.total;
    } catch (error) {
        console.error('Error fetching assets:', error);
    } finally {
        listLoading.value = false;
    }
};

let searchTimeout = null;
watch(listSearch, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchListAssets(1), 400);
});

watch([listEstadoId, listCategoriaId], () => fetchListAssets(1));
watch(listPerPage, () => fetchListAssets(1));

const clearListFilters = () => {
    listSearch.value = '';
    listEstadoId.value = '';
    listCategoriaId.value = '';
    fetchListAssets(1);
};

// State badge classes (estilo Ocurrencias)
const getStateClass = (nombre) => {
    const classes = {
        'BUENO': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'REGULAR': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'MALO': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[nombre] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

// Edit
const openEditModal = (asset) => {
    editingAsset.value = asset;
    showEditModal.value = true;
};

const handleAssetUpdated = () => {
    showEditModal.value = false;
    editingAsset.value = null;
    fetchListAssets(listCurrentPage.value);
    fetchStats();
    Swal.fire({
        icon: 'success',
        title: 'Bien actualizado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
    });
};

// Delete con SweetAlert2
const confirmDelete = async (asset) => {
    const result = await Swal.fire({
        title: '¿Eliminar este bien?',
        html: `<p class="text-sm text-gray-500">Se eliminará permanentemente el bien:</p>
               <p class="font-mono font-bold mt-2">${asset.codigo_patrimonio} — ${asset.denominacion}</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/assets/${asset.id}`);
            fetchListAssets(listCurrentPage.value);
            fetchStats();
            Swal.fire({
                icon: 'success',
                title: 'Bien eliminado',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo eliminar el bien. Puede tener movimientos asociados.',
            });
        }
    }
};

// Create
const handleAssetCreated = () => {
    showCreateModal.value = false;
    fetchListAssets(1);
    fetchStats();
    Swal.fire({
        icon: 'success',
        title: 'Bien registrado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
    });
};

onMounted(() => {
    fetchStats();
    fetchListAssets(1);
});
</script>
