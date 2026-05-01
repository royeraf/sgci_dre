<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useTabPermission } from '@/composables/useTabPermission';
import {
    Plus,
    ArrowLeft,
    ClipboardList,
    FileText,
    Settings
} from 'lucide-vue-next';

// Components
import CreateVisitModal from '@/Components/ExternalVisit/List/Modals/CreateVisitModal.vue';
import ExitVisitModal from '@/Components/ExternalVisit/List/Modals/ExitVisitModal.vue';
import VisitFilters from '@/Components/ExternalVisit/List/VisitFilters.vue';
import VisitTable from '@/Components/ExternalVisit/List/VisitTable.vue';
import VisitReports from '@/Components/ExternalVisit/Reports/VisitReports.vue';
import BarcodeScanner from '@/Components/ExternalVisit/List/BarcodeScanner.vue';
import VisitReasonsManager from '@/Components/ExternalVisit/Reasons/VisitReasonsManager.vue';

// Composables
import { useVisitFilters } from '@/Composables/useVisitFilters';

// Types
import { Visit, PaginatedVisits } from '@/Types/visitor';

const props = defineProps<{
    visits: PaginatedVisits;
    filters: any;
    directions: any[];
    offices: any[];
    employees: any[];
    reasons: any[];
}>();

// State
const showCreateModal = ref(false);
const showExitModal = ref(false);
const selectedVisit = ref<Visit | null>(null);
const { canViewTab, firstAllowedTab } = useTabPermission('visitas', ['list', 'reports', 'reasons']);
const activeTab = ref<'list' | 'reports' | 'reasons'>(firstAllowedTab.value as 'list' | 'reports' | 'reasons');
const barcodeScanner = ref<any>(null);

// Filtering logic from composable
const {
    localFilters,
    updateFilters,
    changePage,
    updatePerPage,
    clearFilters
} = useVisitFilters(props.filters);

// Modal actions
const openCreateModal = () => {
    showCreateModal.value = true;
};

const closeCreateModal = () => {
    showCreateModal.value = false;
    safeFocusRestore(300);
};

const openExitModal = (visit: Visit) => {
    selectedVisit.value = visit;
    showExitModal.value = true;
};

const closeExitModal = () => {
    showExitModal.value = false;
    selectedVisit.value = null;
    safeFocusRestore(300);
};

const handleExitSuccess = () => {
    safeFocusRestore(2500); // Wait for success message to show
};

const handleVisitFound = (visit: Visit) => {
    selectedVisit.value = visit;
    showExitModal.value = true;
};

// Focus management
const isAnyInputFocused = () => {
    const activeElement = document.activeElement;
    return activeElement && (
        activeElement.tagName === 'INPUT' ||
        activeElement.tagName === 'TEXTAREA' ||
        activeElement.tagName === 'SELECT' ||
        (activeElement as HTMLElement).isContentEditable
    );
};

const safeFocusRestore = (delay = 100) => {
    setTimeout(() => {
        if (!isAnyInputFocused() && activeTab.value === 'list') {
            barcodeScanner.value?.focusInput();
        }
    }, delay);
};

// Tab indicator logic
const tabsRef = ref<HTMLElement | null>(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab: string) => {
    switch (tab) {
        case 'list': return '#9333ea'; // purple-600
        case 'reports': return '#c026d3'; // fuchsia-600
        case 'reasons': return '#4f46e5'; // indigo-600
        default: return '#9333ea';
    }
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const activeBtn = tabsRef.value.querySelector('.active-tab') as HTMLElement;
    if (activeBtn) {
        indicatorStyle.value = {
            left: `${activeBtn.offsetLeft}px`,
            width: `${activeBtn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value)
        };
    }
};

onMounted(() => {
    nextTick(() => {
        barcodeScanner.value?.focusInput();
        updateIndicator();
    });

    const handleGlobalClick = (event: MouseEvent) => {
        const target = (event.target as HTMLElement).closest('a');
        if (target && target.href && target.href.includes('/ticket')) {
            safeFocusRestore(500);
        }
    };

    document.addEventListener('click', handleGlobalClick);

    return () => {
        document.removeEventListener('click', handleGlobalClick);
    };
});

watch(activeTab, (newTab) => {
    nextTick(updateIndicator);
    if (newTab === 'list') {
        safeFocusRestore(100);
    }
});
</script>

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
                        class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </Link>
                    <button @click="openCreateModal"
                        class="cursor-pointer inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-purple-600/20 text-white bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Visita
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button v-if="canViewTab('list')" @click="activeTab = 'list'" :class="[
                        activeTab === 'list' ? 'text-purple-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Listado de Visitas
                    </button>
                    <button v-if="canViewTab('reports')" @click="activeTab = 'reports'" :class="[
                        activeTab === 'reports' ? 'text-fuchsia-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <FileText class="w-5 h-5" />
                        Reportes
                    </button>
                    <button v-if="canViewTab('reasons')" @click="activeTab = 'reasons'" :class="[
                        activeTab === 'reasons' ? 'text-indigo-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Settings class="w-5 h-5" />
                        Motivos
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content with Transition -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">
                    <!-- List Tab Content -->
                    <div v-if="activeTab === 'list'" class="space-y-6">
                        <!-- Barcode Scanner -->
                        <BarcodeScanner ref="barcodeScanner" @visitFound="handleVisitFound" />

                        <VisitFilters :filters="localFilters" @update:filters="updateFilters" @clear="clearFilters" />

                        <VisitTable :visits="visits" @exit="openExitModal" @page-change="changePage"
                            @update:perPage="updatePerPage" />
                    </div>

                    <!-- Reports Tab Content -->
                    <div v-else-if="activeTab === 'reports'">
                        <VisitReports />
                    </div>

                    <!-- Reasons Tab Content -->
                    <div v-else-if="activeTab === 'reasons'">
                        <VisitReasonsManager />
                    </div>
                </div>
            </Transition>

            <!-- Modals -->
            <CreateVisitModal v-if="showCreateModal" :directions="directions" :offices="offices" :employees="employees"
                :reasons="reasons" @close="closeCreateModal" />

            <ExitVisitModal v-if="showExitModal && selectedVisit" :visit="selectedVisit" @close="closeExitModal"
                @success="handleExitSuccess" />
        </div>
    </div>
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
