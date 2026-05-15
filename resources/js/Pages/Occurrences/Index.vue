<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent tracking-tight">
                        Cuaderno de Ocurrencias
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Registro y seguimiento de incidentes institucionales
                    </p>
                </div>
                <div class="flex gap-3">
                    <Link
                        href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm"
                    >
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Link>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button v-if="canViewTab('list')"
                        @click="activeTab = 'list'"
                        :class="[
                            activeTab === 'list'
                                ? 'border-blue-600 text-blue-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                        ]"
                    >
                        <ClipboardList class="w-5 h-5" />
                        Listado de Ocurrencias
                    </button>
                    <button v-if="canViewTab('reports')"
                        @click="activeTab = 'reports'"
                        :class="[
                            activeTab === 'reports'
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                            'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                        ]"
                    >
                        <FileText class="w-5 h-5" />
                        Reportes Semanales
                    </button>
                </nav>
            </div>

            <!-- List Tab Content -->
            <div v-if="activeTab === 'list'">
                <!-- Unified Card Container -->
                <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                    
                    <!-- Table Title -->
                    <div class="px-4 sm:px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-base font-bold text-slate-800">Listado de Ocurrencias</h2>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">Registro general de incidentes en el sistema</p>
                    </div>

                    <!-- Top row: Actions -->
                    <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-end gap-4 bg-white">
                        <!-- Right-aligned action buttons -->
                        <div class="flex items-center justify-end gap-3 w-full lg:w-auto">
                            <button
                                @click="filtersVisible = !filtersVisible"
                                class="cursor-pointer w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm"
                            >
                                <SlidersHorizontal class="w-4 h-4" />
                                Filtros
                                <ChevronDown
                                    class="w-4 h-4 transition-transform duration-300"
                                    :class="{ 'rotate-180': filtersVisible }"
                                />
                            </button>

                            <button @click="showRegisterModal = true"
                                class="cursor-pointer w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-blue-600/20 text-white bg-blue-600 hover:bg-blue-700 transition-all duration-200">
                                <Plus class="w-4 h-4 mr-1.5" />
                                Nueva Ocurrencia
                            </button>
                        </div>
                    </div>

                    <!-- Filters toggle + collapsible panel -->
                    <div
                        class="filters-collapse bg-slate-50 border-b border-slate-100"
                        :class="{ 'filters-collapse--open': filtersVisible }"
                    >
                        <div class="p-4 sm:p-5">
                            <OccurrenceFilters
                                :filters="localFilters"
                                :result-count="filteredOccurrences.length"
                                @update:filters="localFilters = $event"
                                @clear="clearFilters"
                            />
                        </div>
                    </div>

                    <!-- Table -->
                    <OccurrenceTable
                        :occurrences="filteredOccurrences"
                        v-model:current-page="currentPage"
                        v-model:per-page="perPage"
                        @view="openDetailModal"
                        @edit="openEditModal"
                    />
                </div>
            </div>

            <!-- Reports Tab Content -->
            <div v-if="activeTab === 'reports'">
                <WeeklyReports :occurrences="occurrences" />
            </div>

            <!-- Register Modal -->
            <OccurrenceModal
                v-if="showRegisterModal"
                @close="showRegisterModal = false"
            />

            <!-- Edit Modal -->
            <OccurrenceModal
                v-if="showEditModal"
                :occurrence="editingOccurrence"
                @close="showEditModal = false; editingOccurrence = null"
            />

            <!-- Detail Modal -->
            <OccurrenceDetailModal
                v-if="showDetailModal"
                :occurrence="selectedOccurrence"
                @close="showDetailModal = false"
            />
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
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTabPermission } from '@/composables/useTabPermission';
import {
    Plus,
    ArrowLeft,
    FileText,
    ClipboardList,
    SlidersHorizontal,
    ChevronDown
} from 'lucide-vue-next';

// Components - List
import OccurrenceFilters from '@/Components/Occurrences/List/OccurrenceFilters.vue';
import OccurrenceTable from '@/Components/Occurrences/List/OccurrenceTable.vue';
import OccurrenceModal from '@/Components/Occurrences/List/OccurrenceModal.vue';
import OccurrenceDetailModal from '@/Components/Occurrences/List/OccurrenceDetailModal.vue';

// Components - Reports
import WeeklyReports from '@/Components/Occurrences/Reports/WeeklyReports.vue';

const props = defineProps({
    occurrences: {
        type: Array,
        default: () => []
    },
    filters: {
        type: Object,
        default: () => ({})
    }
});

// State
const { canViewTab, firstAllowedTab } = useTabPermission('ocurrencias', ['list', 'reports']);
const activeTab = ref(firstAllowedTab.value);
const showRegisterModal = ref(false);
const showDetailModal = ref(false);
const showEditModal = ref(false);
const selectedOccurrence = ref(null);
const editingOccurrence = ref(null);

const FILTERS_STORAGE_KEY = 'occurrences_filters_open';
const filtersVisible = ref(
    localStorage.getItem(FILTERS_STORAGE_KEY) === 'true'
);
watch(filtersVisible, (val) => localStorage.setItem(FILTERS_STORAGE_KEY, String(val)));

// Local filters (for client-side filtering)
const localFilters = ref({
    search: props.filters?.search || '',
    tipo: props.filters?.tipo || '',
    fechaDesde: props.filters?.fecha_desde || '',
    fechaHasta: props.filters?.fecha_hasta || ''
});

// Pagination
const currentPage = ref(1);
const perPage = ref(10);

// Filtered occurrences
const filteredOccurrences = computed(() => {
    let result = [...props.occurrences];

    if (localFilters.value.search) {
        const searchLower = localFilters.value.search.toLowerCase();
        result = result.filter(occ =>
            (occ.descripcion && occ.descripcion.toLowerCase().includes(searchLower)) ||
            (occ.vigilante && occ.vigilante.toLowerCase().includes(searchLower))
        );
    }

    if (localFilters.value.tipo) {
        result = result.filter(occ => occ.tipo === localFilters.value.tipo);
    }

    if (localFilters.value.fechaDesde) {
        result = result.filter(occ => occ.fecha >= localFilters.value.fechaDesde);
    }

    if (localFilters.value.fechaHasta) {
        result = result.filter(occ => occ.fecha <= localFilters.value.fechaHasta);
    }

    return result;
});

// Reset page when filters change
watch(localFilters, () => {
    currentPage.value = 1;
}, { deep: true });

// Methods
const clearFilters = () => {
    localFilters.value = {
        search: '',
        tipo: '',
        fechaDesde: '',
        fechaHasta: ''
    };
};

const openDetailModal = (occurrence) => {
    selectedOccurrence.value = occurrence;
    showDetailModal.value = true;
};

const openEditModal = (occurrence) => {
    editingOccurrence.value = occurrence;
    showEditModal.value = true;
};
</script>

<style scoped>
/* Filters collapse animation */
.filters-collapse {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height 0.35s ease, opacity 0.3s ease;
}

.filters-collapse--open {
    max-height: 500px;
    opacity: 1;
}
</style>
