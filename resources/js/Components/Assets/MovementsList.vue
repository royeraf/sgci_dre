<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 mb-3 group-hover:scale-110 transition-transform">
                        <ArrowRightLeft class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total</p>
                    <h3 class="text-2xl font-black text-slate-900">{{ movStats.total }}</h3>
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
                        <UserPlus class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Asignaciones</p>
                    <h3 class="text-2xl font-black text-blue-600">{{ movStats.asignaciones }}</h3>
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
                        <RotateCcw class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Devoluciones</p>
                    <h3 class="text-2xl font-black text-amber-600">{{ movStats.devoluciones }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-purple-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 mb-3 group-hover:scale-110 transition-transform">
                        <Repeat class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Traslados</p>
                    <h3 class="text-2xl font-black text-purple-600">{{ movStats.traslados }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 mb-3 group-hover:scale-110 transition-transform">
                        <XCircle class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Bajas</p>
                    <h3 class="text-2xl font-black text-red-600">{{ movStats.bajas }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-emerald-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-3 group-hover:scale-110 transition-transform">
                        <Calendar class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Último mes</p>
                    <h3 class="text-2xl font-black text-emerald-600">{{ movStats.ultimo_mes }}</h3>
                </div>
            </div>
        </div>

        <!-- BaseTableCard con filtros colapsables -->
        <BaseTableCard title="Movimientos" description="Registro de traslados y asignaciones de bienes">
            <template #icon>
                <ArrowRightLeft class="w-5 h-5 text-blue-600" />
            </template>
            <template #actions>
                <slot name="actions" />
            </template>

            <!-- Panel colapsable -->
            <div class="filters-collapse bg-slate-50 border-b border-slate-100" :class="{ 'filters-collapse--open': filtersVisible }">
                <div class="p-4 sm:p-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                        <div class="lg:col-span-2 relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input v-model="search" type="text" placeholder="Código, denominación, responsable..."
                                class="w-full pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm outline-none" />
                        </div>
                        <select v-model="filterTipo" class="cursor-pointer w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm bg-white outline-none">
                            <option value="">Todos los tipos</option>
                            <option value="ASIGNACION">Asignación</option>
                            <option value="DEVOLUCION">Devolución</option>
                            <option value="TRASLADO">Traslado</option>
                            <option value="BAJA">Baja</option>
                        </select>
                        <select v-model="filterEstadoId" class="cursor-pointer w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm bg-white outline-none">
                            <option value="">Todos los estados</option>
                            <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                        </select>
                        <input v-model="filterFechaDesde" type="date" placeholder="Desde"
                            class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm outline-none" />
                        <input v-model="filterFechaHasta" type="date" placeholder="Hasta"
                            class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all text-sm outline-none" />
                    </div>
                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200">
                        <span class="text-sm text-slate-500"><span class="font-semibold text-slate-700">{{ total }}</span> movimientos</span>
                        <button @click="clearFilters" class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors flex items-center gap-1">
                            <X class="w-4 h-4" /> Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading / Empty / Table -->
            <!-- Loading -->
            <div v-if="loading" class="p-12 text-center">
                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                <p class="text-sm text-slate-400 mt-2">Cargando movimientos...</p>
            </div>

            <!-- Empty -->
            <template v-else-if="movements.length === 0">
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                            <ArrowRightLeft class="h-12 w-12 text-slate-400" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">No se encontraron movimientos</h3>
                        <p class="text-sm text-slate-500 mb-4">Intenta con otros filtros o registra un nuevo movimiento.
                        </p>
                        <button @click="showNewMovementModal = true"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg shadow-blue-600/20">
                            <Plus class="w-5 h-5 mr-2" />
                            Registrar Movimiento
                        </button>
                    </div>
                </div>
            </template>

            <!-- Table Content -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Bien
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                Estado
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                Responsable
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                Ubicación
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden xl:table-cell">
                                Registrado por
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden xl:table-cell">
                                Obs.
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-for="mov in movements" :key="mov.id"
                            class="hover:bg-blue-50/50 transition-colors duration-200">
                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ formatDate(mov.fecha) }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ formatTime(mov.created_at) }}
                                </div>
                            </td>

                            <!-- Tipo -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="px-3 py-1.5 inline-flex items-center gap-1.5 text-xs leading-5 font-bold rounded-lg shadow-sm"
                                    :class="getTypeClass(mov.tipo)">
                                    <component :is="getTypeIcon(mov.tipo)" class="w-3.5 h-3.5" />
                                    {{ getTypeLabel(mov.tipo) }}
                                </span>
                            </td>

                            <!-- Bien -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-slate-900 font-mono">
                                    {{ mov.asset?.codigo_patrimonio || '—' }}
                                    <span v-if="mov.asset?.codigo_interno"
                                        class="text-xs text-slate-400 ml-1 font-normal">
                                        {{ mov.asset.codigo_interno }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 line-clamp-1 max-w-[200px]"
                                    :title="mov.asset?.denominacion">
                                    {{ mov.asset?.denominacion || '—' }}
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                <span v-if="mov.state"
                                    class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                    :class="getStateClass(mov.state.nombre)">
                                    {{ mov.state.nombre }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">—</span>
                            </td>

                            <!-- Responsable -->
                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div v-if="mov.responsible?.employee?.person" class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3 flex-shrink-0">
                                        {{ (mov.responsible.employee.person.nombres || '?').charAt(0) }}
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900 truncate max-w-[150px]">
                                        {{ mov.responsible.employee.person.nombres }}
                                        {{ mov.responsible.employee.person.apellido_paterno }}
                                    </div>
                                </div>
                                <div v-else-if="mov.responsible?.nombre_original" class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-slate-400 to-slate-500 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3 flex-shrink-0">
                                        {{ mov.responsible.nombre_original.charAt(0) }}
                                    </div>
                                    <div class="text-sm text-slate-700 truncate max-w-[150px]">
                                        {{ mov.responsible.nombre_original }}
                                    </div>
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Ubicación -->
                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div v-if="mov.office" class="text-sm text-slate-600">
                                    {{ mov.office.nombre }}
                                    <div v-if="mov.office.direction" class="text-xs text-slate-400">
                                        {{ mov.office.direction.nombre }}
                                    </div>
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Registrado por -->
                            <td class="px-6 py-4 whitespace-nowrap hidden xl:table-cell">
                                <div v-if="mov.inventariador" class="text-sm text-slate-600">
                                    {{ mov.inventariador.name }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Observaciones -->
                            <td class="px-6 py-4 hidden xl:table-cell">
                                <div v-if="mov.observaciones" class="text-sm text-slate-500 line-clamp-2 max-w-[180px]"
                                    :title="mov.observaciones">
                                    {{ mov.observaciones }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <ClientPagination
                :total-items="total"
                :current-page="currentPage"
                :per-page="perPage"
                :per-page-options="[10, 15, 25, 50]"
                @update:current-page="fetchMovements($event)"
                @update:per-page="perPage = $event"
            />
        </BaseTableCard>

        <!-- Movement Modal -->
        <MovementModal
            v-if="showMovementModal"
            :states="states"
            :offices="offices"
            :employees="employees"
            :movement-types="movementTypes"
            @close="showMovementModal = false"
            @success="handleMovementSuccess"
        />
    </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import {
    ArrowRightLeft,
    UserPlus,
    RotateCcw,
    Repeat,
    XCircle,
    Calendar,
    Search,
    X,
    Plus,
    Loader2,
} from 'lucide-vue-next';
import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import MovementModal from '@/Components/Assets/MovementModal.vue';

const props = defineProps({
    states:        { type: Array, default: () => [] },
    offices:       { type: Array, default: () => [] },
    employees:     { type: Array, default: () => [] },
    movementTypes: { type: Array, default: () => [] },
    filtersVisible: { type: Boolean, default: true },
});

// ===== STATS =====
const movStats = ref({
    total: 0,
    asignaciones: 0,
    devoluciones: 0,
    traslados: 0,
    bajas: 0,
    ultimo_mes: 0,
});

// ===== FILTERS =====
const search = ref('');
const filterTipo = ref('');
const filterEstadoId = ref('');
const filterFechaDesde = ref('');
const filterFechaHasta = ref('');

// ===== PAGINATION =====
const movements = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(15);

// ===== MOVEMENT MODAL =====
const showMovementModal = ref(false);

const handleMovementSuccess = () => {
    fetchMovements(1);
    fetchStats();
};

// ===== FETCH DATA =====
const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/movements/stats');
        movStats.value = response.data;
    } catch (error) {
        console.error('Error fetching movement stats:', error);
    }
};

const fetchMovements = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/assets/movements', {
            params: {
                search: search.value || undefined,
                tipo: filterTipo.value || undefined,
                estado_id: filterEstadoId.value || undefined,
                fecha_desde: filterFechaDesde.value || undefined,
                fecha_hasta: filterFechaHasta.value || undefined,
                per_page: perPage.value,
                page,
            },
        });
        movements.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error fetching movements:', error);
    } finally {
        loading.value = false;
    }
};

// ===== WATCHERS =====
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchMovements(1), 400);
});

watch([filterTipo, filterEstadoId, filterFechaDesde, filterFechaHasta], () => fetchMovements(1));
watch(perPage, () => fetchMovements(1));

const clearFilters = () => {
    search.value = '';
    filterTipo.value = '';
    filterEstadoId.value = '';
    filterFechaDesde.value = '';
    filterFechaHasta.value = '';
    fetchMovements(1);
};

onMounted(() => {
    fetchStats();
    fetchMovements(1);
});

// ===== FORMATTERS =====
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    // Si la fecha ya viene en formato ISO (con tiempo), procesarla como tal
    if (dateStr.includes('T')) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' });
    }
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
};

// ===== TYPE HELPERS =====
const getTypeClass = (tipo) => {
    const classes = {
        'ASIGNACION': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
        'DEVOLUCION': 'bg-gradient-to-r from-amber-500 to-amber-600 text-white',
        'TRASLADO': 'bg-gradient-to-r from-purple-500 to-purple-600 text-white',
        'BAJA': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[tipo] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

const getTypeIcon = (tipo) => {
    const icons = {
        'ASIGNACION': UserPlus,
        'DEVOLUCION': RotateCcw,
        'TRASLADO': Repeat,
        'BAJA': XCircle,
    };
    return icons[tipo] || ArrowRightLeft;
};

const getTypeLabel = (tipo) => {
    const labels = {
        'ASIGNACION': 'Asignación',
        'DEVOLUCION': 'Devolución',
        'TRASLADO': 'Traslado',
        'BAJA': 'Baja',
    };
    return labels[tipo] || tipo;
};

const getStateClass = (nombre) => {
    const classes = {
        'BUENO': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'REGULAR': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'MALO': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[nombre] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

// ===== EXPOSE =====
const openModal = () => {
    showMovementModal.value = true;
};

const setSearch = (code) => {
    search.value = code;
    fetchMovements(1);
};

defineExpose({ openModal, setSearch });
</script>

<style scoped>
.filters-collapse {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height 0.35s ease, opacity 0.3s ease;
}
.filters-collapse--open {
    max-height: 400px;
    opacity: 1;
}
</style>
