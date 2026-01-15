<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent tracking-tight">
                        Libro de Ocurrencias
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
                    <button
                        v-if="activeTab === 'list'"
                        @click="showRegisterModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-blue-600/20 text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300 transform hover:scale-105 active:scale-95"
                    >
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Ocurrencia
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button
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
                    <button
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
            <div v-if="activeTab === 'list'" class="space-y-6">
                <!-- Filters -->
                <OccurrenceFilters
                    :filters="localFilters"
                    :result-count="filteredOccurrences.length"
                    @update:filters="localFilters = $event"
                    @clear="clearFilters"
                />

                <!-- Table -->
                <OccurrenceTable
                    :occurrences="filteredOccurrences"
                    v-model:current-page="currentPage"
                    v-model:per-page="perPage"
                    @view="openDetailModal"
                />
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
import {
    Plus,
    ArrowLeft,
    FileText,
    ClipboardList
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
const activeTab = ref('list');
const showRegisterModal = ref(false);
const showDetailModal = ref(false);
const selectedOccurrence = ref(null);

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
</script>
