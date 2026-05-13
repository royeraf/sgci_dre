<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';
import { ClipboardList, FileText, CalendarOff, User, Clock } from 'lucide-vue-next';

import EmpleadoSelector  from '@/Components/Asistencia/Shared/EmpleadoSelector.vue';
import MarcacionesTab    from '@/Components/Asistencia/Marcaciones/MarcacionesTab.vue';
import ReportesTab       from '@/Components/Asistencia/Reportes/ReportesTab.vue';
import FeriadosTab       from '@/Components/Asistencia/Feriados/FeriadosTab.vue';
import HorariosTab       from '@/Components/Asistencia/Horarios/HorariosTab.vue';

// ===== PROPS =====
const props = defineProps<{
    myEmployee: any | null;
    isAdmin: boolean;
    employees: any[];
}>();

// ===== TABS =====
type TabId = 'marcas' | 'reportes' | 'feriados' | 'horarios';
const activeTab      = ref<TabId>('marcas');
const tabsRef        = ref<HTMLElement | null>(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab: TabId) => {
    if (tab === 'marcas')   return '#0891b2';
    if (tab === 'reportes') return '#2563eb';
    if (tab === 'feriados') return '#d97706';
    if (tab === 'horarios') return '#4f46e5';
    return '#0891b2';
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const btn = tabsRef.value.querySelector('.active-tab') as HTMLElement;
    if (btn) {
        indicatorStyle.value = {
            left:            `${btn.offsetLeft}px`,
            width:           `${btn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value),
        };
    }
};

watch(activeTab, () => nextTick(updateIndicator));

// ===== EMPLEADO =====
const selectedEmployeeId = ref<number | null>(props.myEmployee?.id ?? null);

const selectedEmployee = computed(() =>
    props.isAdmin
        ? props.employees.find(e => e.id === selectedEmployeeId.value) ?? null
        : props.myEmployee
);

// ===== FERIADOS (fuente de verdad compartida entre Marcaciones y Feriados) =====
const feriados        = ref<any[]>([]);
const feriadosLoading = ref(false);
const feriadosYear    = ref(new Date().getFullYear());

const fetchFeriados = async (year: number) => {
    feriadosLoading.value = true;
    try {
        const res = await axios.get('/asistencia/api/feriados', { params: { year } });
        feriados.value = res.data;
        feriadosYear.value = year;
    } finally {
        feriadosLoading.value = false;
    }
};

const feriadosByDate = computed(() => {
    const map: Record<string, any> = {};
    for (const f of feriados.value) map[f.fecha] = f;
    return map;
});

const onFeriadoAdded = (feriado: any) => {
    feriados.value.push(feriado);
    feriados.value.sort((a, b) => a.fecha.localeCompare(b.fecha));
};

const onFeriadoRemoved = (id: number) => {
    feriados.value = feriados.value.filter(f => f.id !== id);
};

const onFeriadosYearChanged = (year: number) => {
    if (year !== feriadosYear.value) fetchFeriados(year);
};

// ===== INIT =====
onMounted(() => {
    fetchFeriados(new Date().getFullYear());
    nextTick(updateIndicator);
});
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent tracking-tight">
                        Marcas de Asistencia
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        {{ isAdmin ? 'Registro, consulta y reportes de marcaciones' : 'Consulta tus marcas de entrada y salida' }}
                    </p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button @click="activeTab = 'marcas'" :class="[
                        activeTab === 'marcas' ? 'text-cyan-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <ClipboardList class="w-5 h-5" />
                        Marcaciones
                    </button>
                    <button @click="activeTab = 'reportes'" :class="[
                        activeTab === 'reportes' ? 'text-blue-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <FileText class="w-5 h-5" />
                        Reportes
                    </button>
                    <button @click="activeTab = 'feriados'" :class="[
                        activeTab === 'feriados' ? 'text-amber-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <CalendarOff class="w-5 h-5" />
                        Días No Laborables
                        <span v-if="feriados.length"
                            class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-amber-100 text-amber-700 text-xs font-bold">
                            {{ feriados.length }}
                        </span>
                    </button>
                    <button @click="activeTab = 'horarios'" :class="[
                        activeTab === 'horarios' ? 'text-indigo-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Clock class="w-5 h-5" />
                        Horarios
                    </button>
                </nav>
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">

                    <!-- MARCACIONES -->
                    <div v-if="activeTab === 'marcas'">
                        <EmpleadoSelector v-if="isAdmin" :employees="employees"
                            v-model="selectedEmployeeId" class="mb-6" />

                        <div v-if="!myEmployee && !isAdmin" class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                            <User class="w-12 h-12 mx-auto text-amber-400 mb-3" />
                            <p class="font-bold text-amber-800">No tiene un empleado vinculado a su cuenta.</p>
                            <p class="text-sm text-amber-600 mt-1">Contacte al administrador para asociar su perfil.</p>
                        </div>
                        <div v-else-if="isAdmin && !selectedEmployee" class="bg-slate-50 border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
                            <User class="w-12 h-12 mx-auto mb-3 opacity-40" />
                            <p class="font-semibold">Seleccione un empleado para ver sus marcas</p>
                        </div>
                        <MarcacionesTab v-else
                            :employee="selectedEmployee"
                            :is-admin="isAdmin"
                            :feriados-by-date="feriadosByDate" />
                    </div>

                    <!-- REPORTES -->
                    <div v-else-if="activeTab === 'reportes'">
                        <EmpleadoSelector v-if="isAdmin" :employees="employees"
                            v-model="selectedEmployeeId" accent-color="blue" class="mb-6" />

                        <div v-if="!myEmployee && !isAdmin" class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                            <User class="w-12 h-12 mx-auto text-amber-400 mb-3" />
                            <p class="font-bold text-amber-800">No tiene un empleado vinculado a su cuenta.</p>
                            <p class="text-sm text-amber-600 mt-1">Contacte al administrador para asociar su perfil.</p>
                        </div>
                        <div v-else-if="isAdmin && !selectedEmployee" class="bg-slate-50 border border-slate-200 rounded-2xl p-10 text-center text-slate-400">
                            <User class="w-10 h-10 mx-auto mb-3 opacity-40" />
                            <p class="font-semibold text-sm">Seleccione un empleado para generar reportes</p>
                        </div>
                        <ReportesTab v-else
                            :employee="selectedEmployee"
                            :is-admin="isAdmin"
                            :employee-id="selectedEmployeeId" />
                    </div>

                    <!-- HORARIOS -->
                    <div v-else-if="activeTab === 'horarios'">
                        <HorariosTab :is-admin="isAdmin" :employees="employees" />
                    </div>

                    <!-- FERIADOS -->
                    <div v-else-if="activeTab === 'feriados'">
                        <FeriadosTab
                            :is-admin="isAdmin"
                            :feriados="feriados"
                            :loading="feriadosLoading"
                            @feriado-added="onFeriadoAdded"
                            @feriado-removed="onFeriadoRemoved"
                            @year-changed="onFeriadosYearChanged" />
                    </div>

                </div>
            </Transition>

        </div>
    </div>
</template>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active { transition: all 0.3s ease; }
.fade-slide-enter-from   { opacity: 0; transform: translateY(8px); }
.fade-slide-leave-to     { opacity: 0; transform: translateY(-8px); }
</style>
