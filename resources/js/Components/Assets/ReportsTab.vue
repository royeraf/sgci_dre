<script setup>
import { ref, computed } from 'vue';
import {
    FileText,
    Download,
    User,
    Search,
    X,
    Loader2,
    ClipboardList,
    Eye,
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    employees: { type: Array, default: () => [] }
});

// ── Búsqueda de empleado ─────────────────────────────────────────────────────
const employeeSearch = ref('');
const selectedEmployee = ref(null);
const showResults = ref(false);
const errorMsg = ref('');

const filteredEmployees = computed(() => {
    if (!employeeSearch.value || employeeSearch.value.length < 2) return [];
    const term = employeeSearch.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    return props.employees.filter(emp => {
        const name = (emp.nombre_completo || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const dni  = (emp.dni || '').toLowerCase();
        return name.includes(term) || dni.includes(term);
    }).slice(0, 10);
});

const selectEmployee = (employee) => {
    selectedEmployee.value = employee;
    showResults.value = false;
    employeeSearch.value = '';
};

const clearSelection = () => {
    selectedEmployee.value = null;
    employeeSearch.value = '';
    errorMsg.value = '';
};

// ── Estado del modal de vista previa ────────────────────────────────────────
const previewOpen      = ref(false);
const previewTitle     = ref('');
const previewSrc       = ref('');
const previewLoading   = ref(false);
const iframeLoaded     = ref(false);
const downloadUrl      = ref('');
const downloadLabel    = ref('Descargar');

// responsibleId guardado para usar en descarga Excel
const currentResponsibleId = ref(null);

const closePreview = () => {
    previewOpen.value  = false;
    previewSrc.value   = '';
    iframeLoaded.value = false;
};

const onIframeLoad = () => {
    iframeLoaded.value = true;
};

// ── Reporte por Responsable ──────────────────────────────────────────────────
const loadingResponsible = ref(false);

const previewResponsible = async () => {
    if (!selectedEmployee.value) return;

    loadingResponsible.value = true;
    errorMsg.value = '';

    try {
        const resp = await axios.post('/assets/responsibles', {
            employee_id: selectedEmployee.value.id
        });
        currentResponsibleId.value = resp.data.id;

        previewTitle.value  = `Bienes Asignados — ${selectedEmployee.value.nombre_completo}`;
        previewSrc.value    = `/assets/reports/responsible/${resp.data.id}/pdf`;
        downloadUrl.value   = `/assets/reports/responsible/${resp.data.id}`;
        downloadLabel.value = 'Descargar Excel';
        iframeLoaded.value  = false;
        previewOpen.value   = true;
    } catch {
        errorMsg.value = 'Error al obtener datos del responsable.';
    } finally {
        loadingResponsible.value = false;
    }
};

const downloadResponsible = () => {
    if (!currentResponsibleId.value) return;
    window.location.href = `/assets/reports/responsible/${currentResponsibleId.value}`;
};

// ── Reporte Bienes Muebles en Uso ────────────────────────────────────────────
const previewBienesMuebles = () => {
    previewTitle.value  = 'Bienes Muebles en Uso';
    previewSrc.value    = '/assets/reports/bienes-muebles-en-uso';
    downloadUrl.value   = '/assets/reports/bienes-muebles-en-uso';
    downloadLabel.value = 'Descargar PDF';
    iframeLoaded.value  = false;
    previewOpen.value   = true;
};

const downloadBienesMuebles = () => {
    window.location.href = '/assets/reports/bienes-muebles-en-uso';
};
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Card: Bienes por Responsable -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-blue-100 text-blue-600 rounded-xl">
                            <User class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Bienes por Responsable</h3>
                            <p class="text-sm text-slate-500 mt-1">Inventario individual de bienes asignados a un servidor</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Buscador -->
                    <div v-if="!selectedEmployee" class="relative">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar Servidor</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                            <input v-model="employeeSearch" type="text" placeholder="Buscar por nombre o DNI..."
                                @focus="showResults = true"
                                class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition-all text-sm font-medium text-slate-700" />
                        </div>
                        <div v-if="showResults && filteredEmployees.length > 0"
                            class="absolute z-10 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-slate-100 max-h-60 overflow-y-auto">
                            <button v-for="employee in filteredEmployees" :key="employee.id"
                                @click="selectEmployee(employee)"
                                class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0 group">
                                <div class="font-bold text-slate-700 text-sm group-hover:text-blue-700 transition-colors">
                                    {{ employee.nombre_completo }}
                                </div>
                                <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-500">{{ employee.dni }}</span>
                                </div>
                            </button>
                        </div>
                        <div v-if="showResults && employeeSearch.length > 1 && filteredEmployees.length === 0"
                            class="absolute z-10 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-slate-100 p-4 text-center text-sm text-slate-500">
                            No se encontraron servidores con ese nombre.
                        </div>
                    </div>

                    <!-- Empleado seleccionado -->
                    <div v-else class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm border border-blue-200">
                                {{ selectedEmployee.nombre_completo.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-700 text-sm">{{ selectedEmployee.nombre_completo }}</div>
                                <div class="text-xs text-blue-600 font-medium font-mono bg-blue-100 inline-block px-1.5 rounded mt-0.5">
                                    {{ selectedEmployee.dni }}
                                </div>
                            </div>
                        </div>
                        <button @click="clearSelection" class="text-slate-400 hover:text-red-500 transition-colors p-1.5 hover:bg-red-50 rounded-lg">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div v-if="errorMsg" class="text-xs text-red-600 font-medium bg-red-50 border border-red-100 p-3 rounded-lg flex gap-2 items-center">
                        <div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>
                        {{ errorMsg }}
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-2">
                        <button @click="previewResponsible" :disabled="!selectedEmployee || loadingResponsible"
                            class="flex-1 py-3 px-4 bg-white border-2 border-slate-200 hover:border-blue-400 hover:bg-blue-50 text-slate-700 hover:text-blue-700 font-bold rounded-xl transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group">
                            <Loader2 v-if="loadingResponsible" class="w-4 h-4 animate-spin" />
                            <Eye v-else class="w-4 h-4 group-hover:scale-110 transition-transform" />
                            Vista Previa
                        </button>
                        <button @click="downloadResponsible" :disabled="!currentResponsibleId || loadingResponsible"
                            class="flex-1 py-3 px-4 bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-900 hover:to-slate-800 text-white font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed group">
                            <Download class="w-4 h-4 group-hover:scale-110 transition-transform" />
                            Excel
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 text-center">Usa "Vista Previa" para ver el PDF antes de descargar el Excel</p>
                </div>
            </div>

            <!-- Card: Bienes Muebles en Uso -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 bg-green-100 text-green-600 rounded-xl">
                            <ClipboardList class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">Bienes Muebles en Uso</h3>
                            <p class="text-sm text-slate-500 mt-1">Resumen por denominación con estado de conservación</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div class="bg-green-50 border border-green-100 rounded-xl p-4">
                        <p class="text-sm text-green-800 font-medium">
                            Agrupa todos los bienes por denominación con conteo por estado de conservación.
                        </p>
                        <p class="text-xs text-green-600 mt-1">Formato: PDF A4 vertical</p>
                    </div>

                    <div class="flex gap-2">
                        <button @click="previewBienesMuebles"
                            class="flex-1 py-3 px-4 bg-white border-2 border-slate-200 hover:border-green-400 hover:bg-green-50 text-slate-700 hover:text-green-700 font-bold rounded-xl transition-all flex items-center justify-center gap-2 group">
                            <Eye class="w-4 h-4 group-hover:scale-110 transition-transform" />
                            Vista Previa
                        </button>
                        <button @click="downloadBienesMuebles"
                            class="flex-1 py-3 px-4 bg-gradient-to-r from-green-700 to-emerald-600 hover:from-green-800 hover:to-emerald-700 text-white font-bold rounded-xl shadow-sm transition-all flex items-center justify-center gap-2 group">
                            <Download class="w-4 h-4 group-hover:scale-110 transition-transform" />
                            PDF
                        </button>
                    </div>
                    <p class="text-xs text-slate-400 text-center">Usa "Vista Previa" para revisar el reporte antes de descargarlo</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Vista Previa -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="previewOpen" class="fixed inset-0 z-50 flex flex-col bg-black/70 backdrop-blur-sm">

                <!-- Header del modal -->
                <div class="flex items-center justify-between px-5 py-3 bg-slate-900 flex-shrink-0">
                    <div class="flex items-center gap-2.5">
                        <FileText class="w-5 h-5 text-blue-400" />
                        <span class="font-bold text-white text-sm truncate max-w-lg">{{ previewTitle }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="previewSrc === downloadUrl ? downloadBienesMuebles() : downloadResponsible()"
                            class="flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg transition-colors">
                            <Download class="w-4 h-4" />
                            {{ downloadLabel }}
                        </button>
                        <button @click="closePreview"
                            class="p-2 text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>
                </div>

                <!-- Spinner mientras carga el iframe -->
                <div v-if="!iframeLoaded" class="absolute inset-0 flex items-center justify-center pointer-events-none" style="top:48px">
                    <div class="flex flex-col items-center gap-3">
                        <Loader2 class="w-10 h-10 text-blue-400 animate-spin" />
                        <span class="text-slate-300 text-sm font-medium">Generando vista previa…</span>
                    </div>
                </div>

                <!-- iframe PDF -->
                <iframe
                    :src="previewSrc"
                    class="flex-1 w-full border-0"
                    @load="onIframeLoad"
                    :class="iframeLoaded ? 'opacity-100' : 'opacity-0'"
                    style="transition: opacity 0.3s"
                />
            </div>
        </Transition>
    </Teleport>
</template>
