<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent tracking-tight">
                        Control de Personal
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">Gestión de entradas, salidas y papeletas oficiales</p>
                </div>
                <div class="flex gap-3">
                    <button @click="showAbsentModal = true"
                        class="inline-flex items-center px-4 py-3 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <UserMinus class="w-5 h-5 mr-2 text-slate-500" />
                        Personal Ausente
                    </button>
                    <Link href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Link>
                    <button @click="showModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/20 text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 focus:outline-none focus:ring-4 focus:ring-emerald-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nuevo Registro
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button v-if="canViewTab('list')" @click="activeTab = 'list'" :class="[
                        activeTab === 'list'
                            ? 'border-emerald-600 text-emerald-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Listado de Papeletas
                    </button>
                    <button v-if="canViewTab('reports')" @click="activeTab = 'reports'" :class="[
                        activeTab === 'reports'
                            ? 'border-teal-600 text-teal-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <FileText class="w-5 h-5" />
                        Reportes
                    </button>
                    <button v-if="canViewTab('reasons')" @click="activeTab = 'reasons'" :class="[
                        activeTab === 'reasons'
                            ? 'border-emerald-700 text-emerald-700'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <Settings class="w-5 h-5" />
                        Motivos
                    </button>
                </nav>
            </div>

            <!-- Filters Section -->
            <div v-if="activeTab === 'list'" class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Search -->
                    <div class="lg:col-span-2">
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                            <input type="text" v-model="localFilters.search"
                                placeholder="Buscar por nombre, DNI o motivo..."
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm" />
                        </div>
                    </div>

                    <!-- Shift Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Turno</label>
                        <select v-model="localFilters.turno"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm bg-white">
                            <option value="">Todos</option>
                            <option value="Mañana">Mañana</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noche">Noche</option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                        <select v-model="localFilters.estado"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm bg-white">
                            <option value="">Todos</option>
                            <option value="pendiente">Pendiente retorno</option>
                            <option value="completado">Completado</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha</label>
                        <input type="date" v-model="localFilters.fecha"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm" />
                    </div>
                </div>

                <!-- Filter Actions -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-700">{{ filteredEntries.length }}</span> resultados
                        encontrados
                    </p>
                    <button @click="clearFilters"
                        class="text-sm font-semibold text-slate-500 hover:text-green-600 transition-colors duration-200 flex items-center gap-1">
                        <X class="w-4 h-4" />
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Table Card -->
            <div v-if="activeTab === 'list'" class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Fecha</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Personal</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Turno</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Hora Salida</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Hora Retorno</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Motivo</th>
                                <th scope="col"
                                    class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                    Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="entry in paginatedEntries" :key="entry.id"
                                class="hover:bg-green-50 transition-colors duration-200">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-900">{{ entry.fecha }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div
                                            class="h-10 w-10 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-sm font-bold text-white shadow-md mr-3">
                                            {{ entry.personal?.charAt(0) || '?' }}
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">{{ entry.personal }}</div>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <div class="text-xs text-slate-500 font-medium">DNI: {{ entry.dni }}
                                                </div>
                                                <span v-if="entry.regimen"
                                                    class="px-2 py-0.5 inline-flex text-[10px] leading-4 font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ entry.regimen }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                        :class="getTurnoClass(entry.turno)">
                                        {{ entry.turno }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-slate-700 font-medium">
                                        <LogOut class="w-4 h-4 mr-1.5 text-red-500" />
                                        {{ entry.hora_salida || '--:--' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center text-sm text-slate-700 font-medium">
                                        <LogIn class="w-4 h-4 mr-1.5 text-green-500" />
                                        {{ entry.hora_retorno || '--:--' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div v-if="entry.reason_nombre"
                                        class="text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg px-2 py-1 mb-1.5 inline-block">
                                        {{ entry.reason_nombre }}
                                    </div>
                                    <div class="text-sm text-slate-700 line-clamp-2 max-w-xs mb-1">{{ entry.motivo }}</div>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-if="entry.tipo_motivo"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border"
                                            :class="entry.tipo_motivo === 'comision' ? 'bg-purple-50 text-purple-700 border-purple-100' : 'bg-teal-50 text-teal-700 border-teal-100'">
                                            {{ entry.tipo_motivo === 'comision' ? 'Comisión' : 'Permiso' }}
                                        </span>
                                        <div v-if="entry.papeleta"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <FileText class="mr-1 h-3 w-3 text-blue-500" />
                                            {{ entry.papeleta }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex flex-col gap-2 items-end">
                                        <button v-if="entry.is_pending" @click="openReturnModal(entry)"
                                            class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors duration-200">
                                            Registrar Retorno
                                        </button>
                                        <span v-else class="text-green-600 bg-green-50 px-3 py-1 rounded-lg">
                                            Completado
                                        </span>
                                        <button v-if="entry.papeleta" @click="downloadPdf(entry)"
                                            class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-lg transition-colors duration-200 flex items-center gap-1">
                                            <Download class="w-4 h-4" />
                                            PDF
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="paginatedEntries.length === 0">
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                                            <Users class="h-12 w-12 text-slate-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 mb-1">No hay registros</h3>
                                        <p class="text-sm text-slate-500">Comienza registrando una entrada/salida.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <span>Mostrar</span>
                            <select v-model="perPage"
                                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-green-500 bg-white">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span>por página</span>
                        </div>
                        <div class="text-sm text-slate-600">
                            Página {{ currentPage }} de {{ totalPages }}
                        </div>
                        <div class="flex items-center gap-1">
                            <button @click="currentPage = 1" :disabled="currentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsLeft class="w-4 h-4" />
                            </button>
                            <button @click="currentPage--" :disabled="currentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronLeft class="w-4 h-4" />
                            </button>
                            <button @click="currentPage++" :disabled="currentPage === totalPages"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronRight class="w-4 h-4" />
                            </button>
                            <button @click="currentPage = totalPages" :disabled="currentPage === totalPages"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports Tab -->
            <div v-if="activeTab === 'reports'">
                <EntryExitReports />
            </div>

            <!-- Reasons Tab -->
            <div v-if="activeTab === 'reasons'">
                <EntryExitReasonsManager />
            </div>

            <!-- Register Modal -->
            <EntryExitModal v-if="showModal" :reasons="reasons" :entry="selectedEntry" @close="closeModal" />

            <!-- Absent Personnel Modal -->
            <AbsentPersonnelModal v-if="showAbsentModal" @close="showAbsentModal = false" />
        </div>
    </div>
</template>

<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup lang="ts">
import { shallowRef } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTabPermission } from '@/composables/useTabPermission';
import {
    Search,
    Plus,
    X,
    LogOut,
    LogIn,
    FileText,
    ArrowLeft,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    Users,
    Download,
    UserMinus,
    ClipboardList,
    Settings,
} from 'lucide-vue-next';
import EntryExitModal from '@/Components/EntryExit/EntryExitModal.vue';
import AbsentPersonnelModal from '@/Components/EntryExit/AbsentPersonnelModal.vue';
import EntryExitReports from '@/Components/EntryExit/Reports/EntryExitReports.vue';
import EntryExitReasonsManager from '@/Components/EntryExit/Reasons/EntryExitReasonsManager.vue';
import { useEntryExitList } from '@/Composables/useEntryExitList';
import type { EntryExitRecord } from '@/Composables/useEntryExitList';

interface Reason {
    id: string;
    nombre: string;
    tipo: 'comision' | 'permiso' | 'ambos';
}

const props = defineProps<{
    entries: EntryExitRecord[];
    reasons: Reason[];
    filters?: {
        turno?: string;
        estado?: string;
        fecha?: string;
    };
}>();

const showModal = shallowRef(false);
const showAbsentModal = shallowRef(false);
const selectedEntry = shallowRef<EntryExitRecord | null>(null);
const { canViewTab, firstAllowedTab } = useTabPermission('control_personal', ['list', 'reports', 'reasons']);
const activeTab = shallowRef<'list' | 'reports' | 'reasons'>(firstAllowedTab.value as 'list' | 'reports' | 'reasons');

const {
    localFilters,
    currentPage,
    perPage,
    filteredEntries,
    paginatedEntries,
    totalPages,
    clearFilters,
} = useEntryExitList({
    entries: () => props.entries,
    initialFilters: props.filters,
});

const openReturnModal = (entry: EntryExitRecord): void => {
    selectedEntry.value = entry;
    showModal.value = true;
};

const closeModal = (): void => {
    showModal.value = false;
    selectedEntry.value = null;
};

const downloadPdf = (entry: EntryExitRecord): void => {
    window.open(`/entry-exits/${entry.id}/pdf`, '_blank');
};

const getTurnoClass = (turno: string): string => {
    const classes: Record<string, string> = {
        'Mañana': 'bg-gradient-to-r from-orange-400 to-orange-500 text-white',
        'Tarde': 'bg-gradient-to-r from-yellow-400 to-yellow-500 text-white',
        'Noche': 'bg-gradient-to-r from-indigo-400 to-indigo-500 text-white'
    };
    return classes[turno] || classes['Mañana'];
};
</script>
