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
                    <button @click="showCreateModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-slate-600/20 text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 focus:outline-none focus:ring-4 focus:ring-slate-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nuevo Bien
                    </button>
                </div>
            </div>

            <!-- Asset Modal -->
            <AssetModal v-if="showCreateModal" :categories="categories" :brands="brands" :colors="colors"
                :states="states" :origins="origins" :offices="offices" @close="showCreateModal = false"
                @success="handleAssetCreated" />

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
                <!-- Filters -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                    <div class="flex flex-col md:flex-row gap-4">
                        <div class="flex-1 relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-5 h-5" />
                            <input type="text" placeholder="Buscar por código, descripción, serie..."
                                class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none transition-all placeholder:text-slate-400 font-medium" />
                        </div>
                        <div class="flex gap-3">
                            <select
                                class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-slate-600 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none">
                                <option value="">Todos los Estados</option>
                                <option value="BUENO">Bueno</option>
                                <option value="REGULAR">Regular</option>
                                <option value="MALO">Malo</option>
                            </select>
                            <select
                                class="px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-medium text-slate-600 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 outline-none">
                                <option value="">Todas las Categorías</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-8 text-center text-slate-500">
                        <LayoutGrid class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                        <p class="text-lg font-medium">Tabla de activos en construcción...</p>
                    </div>
                </div>
            </div>

            <!-- Barcodes Tab -->
            <BarcodeGenerator v-if="activeTab === 'barcodes'" />

            <!-- Movements Tab Placeholder -->
            <div v-if="activeTab === 'movements'"
                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
                <ArrowRightLeft class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                <h3 class="text-lg font-bold text-slate-700">Historial de Movimientos</h3>
                <p class="text-slate-500">Próximamente: registro detallado de asignaciones y devoluciones.</p>
            </div>

            <!-- Reports Tab Placeholder -->
            <div v-if="activeTab === 'reports'"
                class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 text-center">
                <FileDown class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                <h3 class="text-lg font-bold text-slate-700">Reportes y Exportación</h3>
                <p class="text-slate-500">Próximamente: descarga de inventario en PDF/Excel y etiquetas.</p>
            </div>
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
import { ref, onMounted } from 'vue';
import {
    Plus,
    Search,
    Filter,
    FileDown,
    Box,
    CheckCircle,
    LayoutGrid,
    ArrowRightLeft,
    AlertTriangle,
    XCircle,
    Settings,
    Barcode
} from 'lucide-vue-next';
import axios from 'axios';
import AssetModal from '@/Components/Assets/AssetModal.vue';
import BarcodeGenerator from '@/Components/Assets/BarcodeGenerator.vue';

defineProps({
    categories: {
        type: Array,
        default: () => []
    },
    brands: {
        type: Array,
        default: () => []
    },
    colors: {
        type: Array,
        default: () => []
    },
    states: {
        type: Array,
        default: () => []
    },
    origins: {
        type: Array,
        default: () => []
    },
    offices: {
        type: Array,
        default: () => []
    }
});

const activeTab = ref('list');
const showCreateModal = ref(false);
const stats = ref({
    total: 0,
    buenos: 0,
    regulares: 0,
    malos: 0
});

const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/summary');
        stats.value = response.data;
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

const handleAssetCreated = () => {
    fetchStats();
    // Here we would also reload the table data once implemented
};

onMounted(() => {
    fetchStats();
});
</script>
