<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTabPermission } from '@/composables/useTabPermission';
import { usePapeletaList } from '@/Composables/usePapeletaList';
import { ArrowLeft, Plus, ClipboardList, Clock, History, FileBarChart } from 'lucide-vue-next';

// Components
import MisPapeletasManager from '@/Components/Papeletas/List/MisPapeletasManager.vue';
import CreatePapeletaModal from '@/Components/Papeletas/List/Modals/CreatePapeletaModal.vue';
import PendientesList from '@/Components/Papeletas/Pending/PendientesList.vue';
import HistorialManager from '@/Components/Papeletas/Historial/HistorialManager.vue';
import PapeletaReports from '@/Components/Papeletas/Reports/PapeletaReports.vue';

const props = defineProps({
    userRole: String,
    myEmployee: { type: Object, default: null },
    reasons: { type: Array, default: () => [] },
});

const isAdminRole = computed(() => ['ROL001', 'ROL009', 'ROL011'].includes(props.userRole));

const { canViewTab, firstAllowedTab } = useTabPermission('papeletas', ['pendientes', 'pendientes_rrhh', 'historial', 'reportes']);
const activeTab = ref(props.myEmployee ? 'mis_papeletas' : firstAllowedTab.value);

// Data (pendientes + stats shared across tabs)
const { pendientes, stats, loading, fetchPendientes, fetchStats } = usePapeletaList();
const pendientesRrhh = computed(() => pendientes.value.filter(p => p.estado === 'PENDIENTE_RRHH'));

// Refs for child refresh
const misPapeletasRef = ref(null);
const showCreateModal = ref(false);

const handleCreated = (papeleta) => {
    showCreateModal.value = false;
    misPapeletasRef.value?.refresh();
};

const handleRefresh = () => {
    fetchPendientes();
    fetchStats();
};

// Tab indicator
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    const colors = {
        mis_papeletas:   '#4f46e5',
        pendientes:      '#2563eb',
        pendientes_rrhh: '#ea580c',
        historial:       '#475569',
        reportes:        '#7c3aed',
    };
    return colors[tab] ?? '#4f46e5';
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const activeBtn = tabsRef.value.querySelector('.active-tab');
    if (activeBtn) {
        indicatorStyle.value = {
            left: `${activeBtn.offsetLeft}px`,
            width: `${activeBtn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value),
        };
    }
};

watch(activeTab, () => nextTick(updateIndicator));

onMounted(() => {
    nextTick(updateIndicator);
    if (isAdminRole.value) {
        fetchPendientes();
        fetchStats();
    }
});
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold bg-gradient-to-r from-indigo-600 to-blue-600 bg-clip-text text-transparent tracking-tight">
                        Papeletas de Salida
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">Gestión y aprobación de solicitudes de papeletas</p>
                </div>
                <div class="flex gap-3">
                    <Link href="/dashboard"
                        class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Link>
                    <button v-if="myEmployee" @click="showCreateModal = true"
                        class="cursor-pointer inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/20 text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Papeleta
                    </button>
                </div>
            </div>

            <!-- Stats (admin only) -->
            <div v-if="isAdminRole" class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4 mb-8">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Total</p>
                    <p class="text-2xl font-black text-slate-900">{{ stats.total }}</p>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 shadow-sm border border-yellow-100 cursor-pointer hover:ring-2 ring-yellow-300 transition-all"
                    @click="activeTab = 'pendientes'">
                    <p class="text-xs font-semibold text-yellow-600 uppercase">Pendientes</p>
                    <p class="text-2xl font-black text-yellow-700">{{ stats.pendientes }}</p>
                </div>
                <div class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 cursor-pointer hover:ring-2 ring-orange-300 transition-all"
                    @click="activeTab = 'pendientes_rrhh'">
                    <p class="text-xs font-semibold text-orange-600 uppercase">P. RRHH</p>
                    <p class="text-2xl font-black text-orange-700">{{ stats.pendientes_rrhh }}</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100">
                    <p class="text-xs font-semibold text-green-600 uppercase">Aprobadas</p>
                    <p class="text-2xl font-black text-green-700">{{ stats.aprobadas }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-4 shadow-sm border border-red-100">
                    <p class="text-xs font-semibold text-red-600 uppercase">Desaprobadas</p>
                    <p class="text-2xl font-black text-red-700">{{ stats.desaprobadas }}</p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button v-if="myEmployee" @click="activeTab = 'mis_papeletas'" :class="[
                        activeTab === 'mis_papeletas' ? 'text-indigo-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Mis Papeletas
                    </button>
                    <button v-if="isAdminRole && canViewTab('pendientes')" @click="activeTab = 'pendientes'" :class="[
                        activeTab === 'pendientes' ? 'text-blue-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Clock class="w-5 h-5" />
                        Pendientes
                        <span v-if="stats.pendientes > 0"
                            class="bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ stats.pendientes }}
                        </span>
                    </button>
                    <button v-if="isAdminRole && canViewTab('pendientes_rrhh')" @click="activeTab = 'pendientes_rrhh'" :class="[
                        activeTab === 'pendientes_rrhh' ? 'text-orange-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Clock class="w-5 h-5" />
                        Pendientes RRHH
                        <span v-if="stats.pendientes_rrhh > 0"
                            class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ stats.pendientes_rrhh }}
                        </span>
                    </button>
                    <button v-if="isAdminRole && canViewTab('historial')" @click="activeTab = 'historial'" :class="[
                        activeTab === 'historial' ? 'text-slate-700 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <History class="w-5 h-5" />
                        Historial
                    </button>
                    <button v-if="(userRole === 'ROL009' || userRole === 'ROL001') && canViewTab('reportes')" @click="activeTab = 'reportes'" :class="[
                        activeTab === 'reportes' ? 'text-violet-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <FileBarChart class="w-5 h-5" />
                        Reportes
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">
                    <MisPapeletasManager v-if="activeTab === 'mis_papeletas'" ref="misPapeletasRef" />

                    <PendientesList v-else-if="activeTab === 'pendientes'"
                        :items="pendientes"
                        :loading="loading"
                        @refresh="handleRefresh"
                    />

                    <PendientesList v-else-if="activeTab === 'pendientes_rrhh'"
                        :items="pendientesRrhh"
                        :loading="loading"
                        card-border="border-orange-200"
                        empty-text="No hay papeletas pendientes de RRHH"
                        @refresh="handleRefresh"
                    />

                    <HistorialManager v-else-if="activeTab === 'historial'" />

                    <PapeletaReports v-else-if="activeTab === 'reportes'" />
                </div>
            </Transition>
        </div>
    </div>

    <!-- Modal: Nueva Papeleta -->
    <CreatePapeletaModal
        v-if="showCreateModal"
        :reasons="reasons"
        :my-employee="myEmployee"
        @close="showCreateModal = false"
        @created="handleCreated"
    />
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}
.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}
.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}
</style>
