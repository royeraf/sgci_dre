<template>
    <div class="space-y-6">
        <!-- Filters -->
        <CompletedAppointmentFilters :filters="filters" :resultCount="filteredCitas.length"
            @update:filters="filters = $event" @clear="clearFilters" />

        <!-- View Mode Toggle & Results Info -->
        <div class="flex items-center justify-between">
            <p class="text-sm text-slate-500">
                Mostrando <span class="font-semibold text-slate-700">{{ paginatedCitas.length }}</span>
                de <span class="font-semibold text-slate-700">{{ filteredCitas.length }}</span> citas
            </p>
            <div class="flex items-center gap-2 bg-white rounded-xl border border-slate-200 p-1 shadow-sm">
                <button @click="viewMode = 'card'" :class="[
                    'flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                    viewMode === 'card'
                        ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                ]">
                    <LayoutGrid class="w-4 h-4" />
                    Tarjetas
                </button>
                <button @click="viewMode = 'grid'" :class="[
                    'flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold transition-all duration-200',
                    viewMode === 'grid'
                        ? 'bg-gradient-to-r from-pink-500 to-rose-500 text-white shadow-md'
                        : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'
                ]">
                    <TableIcon class="w-4 h-4" />
                    Grilla
                </button>
            </div>
        </div>

        <!-- Card View -->
        <div v-if="viewMode === 'card'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <AppointmentCard v-for="cita in paginatedCitas" :key="cita.id" :cita="cita" />
            </div>

            <!-- Empty State for Card View -->
            <div v-if="filteredCitas.length === 0"
                class="text-center py-20 bg-white rounded-2xl shadow-xl border border-slate-200">
                <div class="flex flex-col items-center">
                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                        <CheckCircle class="h-12 w-12 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-black text-slate-900 mb-1">No hay citas finalizadas</h3>
                    <p class="text-sm text-slate-500 font-medium max-w-xs mx-auto">
                        Aquí se mostrará el histórico de citas que ya han sido atendidas o canceladas.
                    </p>
                </div>
            </div>

            <!-- Pagination for Card View -->
            <div v-if="totalPages > 1" class="bg-white shadow-lg rounded-2xl border border-slate-200 px-6 py-4 mt-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Mostrar</span>
                        <select v-model.number="perPage"
                            class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-pink-500 bg-white">
                            <option :value="6">6</option>
                            <option :value="12">12</option>
                            <option :value="24">24</option>
                        </select>
                        <span>por página</span>
                    </div>
                    <div class="text-sm text-slate-600">
                        Página {{ currentPage }} de {{ totalPages }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="currentPage = 1" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronsLeft class="w-4 h-4" />
                        </button>
                        <button @click="currentPage--" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <button @click="currentPage++" :disabled="currentPage === totalPages"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronsRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid/Table View -->
        <div v-else>
            <CompletedAppointmentTable :citas="filteredCitas" :currentPage="currentPage" :perPage="perPage"
                @update:currentPage="currentPage = $event" @update:perPage="handlePerPageChange" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import AppointmentCard from './AppointmentCard.vue';
import CompletedAppointmentFilters from './CompletedAppointmentFilters.vue';
import CompletedAppointmentTable from './CompletedAppointmentTable.vue';
import {
    CheckCircle,
    LayoutGrid,
    Table as TableIcon,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight
} from 'lucide-vue-next';

const props = defineProps({
    citas: {
        type: Array,
        required: true
    }
});

// View mode state
const viewMode = ref('card'); // 'card' or 'grid'

// Pagination state
const currentPage = ref(1);
const perPage = ref(6);

// Filters state
const filters = ref({
    search: '',
    estado: '',
    fechaDesde: '',
    fechaHasta: ''
});

// Filtered citas
const filteredCitas = computed(() => {
    let result = [...props.citas];

    // Search filter
    if (filters.value.search) {
        const searchLower = filters.value.search.toLowerCase();
        result = result.filter(cita =>
            (cita.nombres && cita.nombres.toLowerCase().includes(searchLower)) ||
            (cita.apellidos && cita.apellidos.toLowerCase().includes(searchLower)) ||
            (cita.dni && cita.dni.toLowerCase().includes(searchLower)) ||
            (cita.asunto && cita.asunto.toLowerCase().includes(searchLower)) ||
            (cita.oficina && cita.oficina.toLowerCase().includes(searchLower))
        );
    }

    // Status filter
    if (filters.value.estado) {
        result = result.filter(cita => cita.estado === filters.value.estado);
    }

    // Date from filter
    if (filters.value.fechaDesde) {
        const fechaDesde = new Date(filters.value.fechaDesde);
        result = result.filter(cita => {
            const citaFecha = new Date(cita.fecha);
            return citaFecha >= fechaDesde;
        });
    }

    // Date to filter
    if (filters.value.fechaHasta) {
        const fechaHasta = new Date(filters.value.fechaHasta);
        fechaHasta.setHours(23, 59, 59, 999);
        result = result.filter(cita => {
            const citaFecha = new Date(cita.fecha);
            return citaFecha <= fechaHasta;
        });
    }

    return result;
});

// Paginated citas for card view
const paginatedCitas = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return filteredCitas.value.slice(start, end);
});

// Total pages
const totalPages = computed(() => {
    return Math.ceil(filteredCitas.value.length / perPage.value) || 1;
});

// Clear filters
const clearFilters = () => {
    filters.value = {
        search: '',
        estado: '',
        fechaDesde: '',
        fechaHasta: ''
    };
    currentPage.value = 1;
};

// Handle per page change
const handlePerPageChange = (value) => {
    perPage.value = value;
    currentPage.value = 1;
};

// Reset to page 1 when filters change
watch(filters, () => {
    currentPage.value = 1;
}, { deep: true });

// Reset to page 1 when per page changes
watch(perPage, () => {
    currentPage.value = 1;
});

// Validate current page doesn't exceed total pages
watch(totalPages, (newTotal) => {
    if (currentPage.value > newTotal) {
        currentPage.value = newTotal;
    }
});
</script>
