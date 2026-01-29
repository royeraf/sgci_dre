<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup lang="ts">
import { ref, onMounted, nextTick, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Plus,
    ArrowLeft,
    ClipboardList,
    FileText
} from 'lucide-vue-next';

// Components
import CreateVisitModal from '@/Components/ExternalVisit/List/CreateVisitModal.vue';
import ExitVisitModal from '@/Components/ExternalVisit/List/ExitVisitModal.vue';
import VisitFilters from '@/Components/ExternalVisit/List/VisitFilters.vue';
import VisitTable from '@/Components/ExternalVisit/List/VisitTable.vue';
import VisitReports from '@/Components/ExternalVisit/Reports/VisitReports.vue';
import BarcodeScanner from '@/Components/ExternalVisit/List/BarcodeScanner.vue';

// Composables
import { useVisitFilters } from '@/Composables/useVisitFilters';

// Types
import { Visit, PaginatedVisits } from '@/Types/visitor';

const props = defineProps<{
    visits: PaginatedVisits;
    filters: any;
    areas: any[];
    offices: any[];
    employees: any[];
}>();

// State
const showCreateModal = ref(false);
const showExitModal = ref(false);
const selectedVisit = ref<Visit | null>(null);
const activeTab = ref<'list' | 'reports'>('list');
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

onMounted(() => {
    nextTick(() => {
        barcodeScanner.value?.focusInput();
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

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'list'" :class="[
                        activeTab === 'list'
                            ? 'border-purple-600 text-purple-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Listado de Visitas
                    </button>
                    <button @click="activeTab = 'reports'" :class="[
                        activeTab === 'reports'
                            ? 'border-fuchsia-600 text-fuchsia-600'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                        'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200'
                    ]">
                        <FileText class="w-5 h-5" />
                        Reportes
                    </button>
                </nav>
            </div>

            <!-- List Tab Content -->
            <div v-if="activeTab === 'list'" class="space-y-6">
                <!-- Barcode Scanner -->
                <BarcodeScanner ref="barcodeScanner" @visitFound="handleVisitFound" />

                <VisitFilters :filters="localFilters" @update:filters="updateFilters" @clear="clearFilters" />

                <VisitTable :visits="visits" @exit="openExitModal" @page-change="changePage"
                    @update:perPage="updatePerPage" />
            </div>

            <!-- Reports Tab Content -->
            <div v-if="activeTab === 'reports'">
                <VisitReports />
            </div>

            <!-- Modals -->
            <CreateVisitModal v-if="showCreateModal" :areas="areas" :offices="offices" :employees="employees"
                @close="closeCreateModal" />

            <ExitVisitModal v-if="showExitModal && selectedVisit" :visit="selectedVisit" @close="closeExitModal"
                @success="handleExitSuccess" />
        </div>
    </div>
</template>
