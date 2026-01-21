<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 to-fuchsia-600 bg-clip-text text-transparent tracking-tight">
                        Control de Visitas Externas
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">Registro y control de acceso de visitantes</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Link>
                    <button @click="openCreateModal"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-purple-600/20 text-white bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Visita
                    </button>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                            <input type="text" v-model="localFilters.search"
                                placeholder="Buscar por nombre, DNI o motivo..."
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm" />
                        </div>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                        <select v-model="localFilters.estado"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm bg-white">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente salida</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha</label>
                        <input type="date" v-model="localFilters.fecha"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm" />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500">
                        <!-- Normally we rely on backend pagination but for client side filtering we count filtered -->
                        Resultados
                    </p>
                    <button @click="clearFilters"
                        class="text-sm font-semibold text-slate-500 hover:text-purple-600 transition-colors duration-200 flex items-center gap-1">
                        <X class="w-4 h-4" />
                        Limpiar filtros
                    </button>
                    <!-- Apply Backend Filters Trigger (can be automatic with watch) -->
                    <button @click="applyFilters"
                        class="text-sm font-semibold text-purple-600 hover:text-purple-800 transition-colors duration-200 flex items-center gap-1">
                        <Filter class="w-4 h-4" />
                        Aplicar Filtros
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Fecha</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Visitante</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Hora Ingreso</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Hora Salida</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Motivo</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Destino</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="visit in visits.data" :key="visit.id"
                                class="hover:bg-purple-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">{{ visit.fecha }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-sm font-bold text-white shadow-md mr-3">
                                            {{ visit.nombres?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">{{ visit.nombres }}</div>
                                            <div class="text-xs text-slate-500 font-medium">DNI: {{ visit.dni }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-slate-700 font-medium">
                                        <LogIn class="w-4 h-4 mr-1.5 text-purple-500" />
                                        {{ visit.hora_ingreso || '--:--' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-slate-700 font-medium">
                                        <LogOut class="w-4 h-4 mr-1.5 text-red-500" />
                                        {{ visit.hora_salida || '--:--' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-700 line-clamp-2 max-w-xs">{{ visit.motivo }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-slate-700">{{ visit.area }}</div>
                                    <div v-if="visit.a_quien_visita" class="text-xs text-slate-500">Visita a: {{
                                        visit.a_quien_visita }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-col gap-2 items-end">
                                        <button v-if="visit.is_pending" @click="openExitModal(visit)"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors duration-200">
                                            Registrar Salida
                                        </button>
                                        <span v-else class="text-green-600 bg-green-50 px-3 py-1 rounded-lg">
                                            Completado
                                        </span>
                                        <a :href="`/visitors/${visit.id}/ticket`" target="_blank"
                                            class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-lg transition-colors duration-200 flex items-center gap-1">
                                            <FileText class="w-4 h-4" />
                                            Ticket
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="visits.data.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                                            <Users class="h-12 w-12 text-slate-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 mb-1">No hay visitas</h3>
                                        <p class="text-sm text-slate-500">Comienza registrando una visita.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="visits.links.length > 3" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    <Pagination :links="visits.links" />
                </div>
            </div>

            <!-- Modals -->
            <ExternalVisitModal v-if="showCreateModal" :areas="areas" :offices="offices" @close="closeCreateModal" />

            <ExternalVisitExitModal v-if="showExitModal" :visit="selectedVisit" @close="closeExitModal"
                @success="handleExitSuccess" />
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
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    Search,
    Plus,
    X,
    LogOut,
    LogIn,
    ArrowLeft,
    Users,
    Filter,
    FileText
} from 'lucide-vue-next';
import ExternalVisitModal from '@/Components/ExternalVisit/ExternalVisitModal.vue';
import ExternalVisitExitModal from '@/Components/ExternalVisit/ExternalVisitExitModal.vue';
import Pagination from '@/Components/Common/Pagination.vue';

const props = defineProps({
    visits: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    filters: {
        type: Object,
        default: () => ({})
    },
    areas: {
        type: Array,
        default: () => []
    },
    offices: {
        type: Array,
        default: () => []
    }
});

const showCreateModal = ref(false);
const showExitModal = ref(false);
const selectedVisit = ref(null);

// Local filters
const localFilters = ref({
    search: props.filters.search || '',
    estado: props.filters.estado || '',
    fecha: props.filters.fecha || ''
});

const applyFilters = () => {
    router.get('/visitors', localFilters.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const clearFilters = () => {
    localFilters.value = {
        search: '',
        estado: '',
        fecha: ''
    };
    applyFilters();
};

const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
};

const openExitModal = (visit) => {
    selectedVisit.value = visit;
    showExitModal.value = true;
};

const closeExitModal = () => {
    showExitModal.value = false;
    selectedVisit.value = null;
};

const handleExitSuccess = () => {
    // Optional: Show toast or refresh if needed (Inertia handles refresh automatically)
};
</script>
