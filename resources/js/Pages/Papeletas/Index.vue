<template>
    <MainLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Papeletas de Salida</h1>
                <p class="text-sm text-slate-500 mt-1">Gestion y aprobacion de solicitudes de papeletas</p>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4 mb-6">
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
                    @click="activeTab = 'pendientes'">
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

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="border-b border-slate-100">
                    <nav class="flex">
                        <button @click="activeTab = 'pendientes'"
                            :class="[activeTab === 'pendientes' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <Clock class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Pendientes
                            <span v-if="stats.pendientes > 0" class="ml-1.5 bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ stats.pendientes }}
                            </span>
                        </button>
                        <button @click="activeTab = 'pendientes_rrhh'"
                            :class="[activeTab === 'pendientes_rrhh' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <Clock class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Pendientes RRHH
                            <span v-if="stats.pendientes_rrhh > 0" class="ml-1.5 bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ stats.pendientes_rrhh }}
                            </span>
                        </button>
                        <button @click="activeTab = 'historial'"
                            :class="[activeTab === 'historial' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <History class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Historial
                        </button>
                        <button v-if="userRole === 'ROL009' || userRole === 'ROL001'" @click="activeTab = 'reportes'"
                            :class="[activeTab === 'reportes' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <FileBarChart class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Reportes
                        </button>
                    </nav>
                </div>

                <!-- Tab: Pendientes -->
                <div v-if="activeTab === 'pendientes'" class="p-4 sm:p-6">
                    <div v-if="loading" class="text-center py-12 text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
                        <p>Cargando...</p>
                    </div>

                    <div v-else-if="pendientes.length === 0" class="text-center py-12 text-slate-400">
                        <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No hay papeletas pendientes</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="p in pendientes" :key="p.id"
                            class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono font-bold text-blue-600 text-sm">{{ p.numero_papeleta }}</span>
                                        <span :class="estadoBadgeClass(p.estado)" class="text-xs font-bold px-2 py-0.5 rounded-full">
                                            {{ p.estado === 'PENDIENTE' ? 'PENDIENTE (Jefe)' : 'PENDIENTE (RRHH)' }}
                                        </span>
                                        <span v-if="p.aprobado_por_jefe" class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                            <CheckCircle class="h-3 w-3 inline mr-1" />
                                            Jefe
                                        </span>
                                    </div>
                                    <p class="font-bold text-slate-800 text-sm">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ p.employee?.direction?.nombre ?? '-' }} |
                                        {{ formatDate(p.fecha_salida) }} |
                                        {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }} |
                                        {{ p.reason?.nombre }}
                                    </p>
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-1">{{ p.motivo }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="openApproveModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <CheckCircle class="h-3.5 w-3.5" />
                                        {{ p.estado === 'PENDIENTE' ? 'Aprobar (Jefe)' : 'Aprobar (RRHH)' }}
                                    </button>
                                    <button @click="openRejectModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <XCircle class="h-3.5 w-3.5" />
                                        Desaprobar
                                    </button>
                                    <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Ver PDF">
                                        <FileText class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pendientes RRHH -->
                <div v-if="activeTab === 'pendientes_rrhh'" class="p-4 sm:p-6">
                    <div v-if="loading" class="text-center py-12 text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
                        <p>Cargando...</p>
                    </div>

                    <div v-else-if="pendientesRrhh.length === 0" class="text-center py-12 text-slate-400">
                        <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No hay papeletas pendientes de RRHH</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="p in pendientesRrhh" :key="p.id"
                            class="bg-white border border-orange-200 rounded-xl p-4 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono font-bold text-blue-600 text-sm">{{ p.numero_papeleta }}</span>
                                        <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded-full">PENDIENTE RRHH</span>
                                        <span v-if="p.aprobado_por_jefe" class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                            <CheckCircle class="h-3 w-3 inline mr-1" />
                                            Jefe: {{ p.aprobador_jefe?.person?.apellidos }}
                                        </span>
                                    </div>
                                    <p class="font-bold text-slate-800 text-sm">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ p.employee?.direction?.nombre ?? '-' }} |
                                        {{ formatDate(p.fecha_salida) }} |
                                        {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }} |
                                        {{ p.reason?.nombre }}
                                    </p>
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-1">{{ p.motivo }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="openApproveModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <CheckCircle class="h-3.5 w-3.5" />
                                        Aprobar (RRHH)
                                    </button>
                                    <button @click="openRejectModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <XCircle class="h-3.5 w-3.5" />
                                        Desaprobar
                                    </button>
                                    <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Ver PDF">
                                        <FileText class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Historial -->
                <div v-if="activeTab === 'historial'" class="p-4 sm:p-6">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                        <select v-model="filtros.estado" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20">
                            <option value="TODOS">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente (Jefe)</option>
                            <option value="PENDIENTE_RRHH">Pendiente RRHH</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="DESAPROBADO">Desaprobado</option>
                        </select>
                        <input type="date" v-model="filtros.fecha_desde" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                            placeholder="Desde" />
                        <input type="date" v-model="filtros.fecha_hasta" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                            placeholder="Hasta" />
                        <button @click="resetFiltros"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 text-slate-500 hover:bg-slate-50 font-semibold transition-colors">
                            Limpiar Filtros
                        </button>
                    </div>

                    <div v-if="loading" class="text-center py-8 text-slate-400">
                        <Loader2 class="h-6 w-6 animate-spin mx-auto" />
                    </div>

                    <div v-else-if="historial.length === 0" class="text-center py-12 text-slate-400">
                        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No se encontraron papeletas</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Servidor</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Direccion</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">PDF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="p in historial" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ p.numero_papeleta }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ p.employee?.direction?.nombre ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ formatDate(p.fecha_salida) }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="estadoBadgeClass(p.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                            {{ p.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a :href="`/papeletas/${p.id}/pdf`" target="_blank" class="text-slate-400 hover:text-blue-600 transition-colors">
                                            <FileText class="h-4 w-4 inline-block" />
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Reportes (RRHH only) -->
                <div v-if="activeTab === 'reportes'" class="p-4 sm:p-6">
                    <div class="max-w-lg mx-auto space-y-4">
                        <h3 class="text-lg font-bold text-slate-800">Generar Reporte PDF</h3>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                            <select v-model="reportFiltros.estado"
                                class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2">
                                <option value="TODOS">Todos</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="APROBADO">Aprobado</option>
                                <option value="DESAPROBADO">Desaprobado</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Desde</label>
                                <input type="date" v-model="reportFiltros.fecha_desde"
                                    class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Hasta</label>
                                <input type="date" v-model="reportFiltros.fecha_hasta"
                                    class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2" />
                            </div>
                        </div>
                        <a :href="reportUrl" target="_blank"
                            class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/20 transition-all">
                            <FileBarChart class="h-4 w-4" />
                            Generar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <Teleport to="body">
            <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showApproveModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Aprobar Papeleta</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Papeleta <strong class="text-blue-600">{{ selectedPapeleta?.numero_papeleta }}</strong> de
                        <strong>{{ selectedPapeleta?.employee?.person?.apellidos }}, {{ selectedPapeleta?.employee?.person?.nombres }}</strong>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Comentario (opcional)</label>
                        <textarea v-model="modalComentario" rows="3"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Agregue un comentario..."></textarea>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="showApproveModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button @click="handleAprobar" :disabled="approvalProcessing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors">
                            <Loader2 v-if="approvalProcessing" class="h-4 w-4 animate-spin" />
                            Confirmar Aprobacion
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Desaprobar Papeleta</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Papeleta <strong class="text-blue-600">{{ selectedPapeleta?.numero_papeleta }}</strong> de
                        <strong>{{ selectedPapeleta?.employee?.person?.apellidos }}, {{ selectedPapeleta?.employee?.person?.nombres }}</strong>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo del rechazo *</label>
                        <textarea v-model="modalComentario" rows="3"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            :class="{ 'border-red-300': rejectError }"
                            placeholder="Indique el motivo del rechazo..."></textarea>
                        <p v-if="rejectError" class="mt-1 text-xs text-red-500">{{ rejectError }}</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="showRejectModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button @click="handleDesaprobar" :disabled="approvalProcessing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                            <Loader2 v-if="approvalProcessing" class="h-4 w-4 animate-spin" />
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import { usePapeletaList } from '@/Composables/usePapeletaList';
import { usePapeletaApproval } from '@/Composables/usePapeletaApproval';
import { Clock, History, FileBarChart, FileText, CheckCircle, XCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    userRole: String,
});

const activeTab = ref('pendientes');
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedPapeleta = ref(null);
const modalComentario = ref('');
const rejectError = ref('');

const { pendientes, historial, stats, loading, filtros, fetchPendientes, fetchHistorial, fetchStats } = usePapeletaList();
const { processing: approvalProcessing, aprobar, desaprobar } = usePapeletaApproval();

const pendientesRrhh = computed(() => {
    return pendientes.value.filter(p => p.estado === 'PENDIENTE_RRHH');
});

const reportFiltros = reactive({
    estado: 'TODOS',
    fecha_desde: '',
    fecha_hasta: '',
});

const reportUrl = computed(() => {
    const params = new URLSearchParams();
    if (reportFiltros.estado !== 'TODOS') params.set('estado', reportFiltros.estado);
    if (reportFiltros.fecha_desde) params.set('fecha_desde', reportFiltros.fecha_desde);
    if (reportFiltros.fecha_hasta) params.set('fecha_hasta', reportFiltros.fecha_hasta);
    return `/papeletas/report/pdf?${params.toString()}`;
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const estadoBadgeClass = (estado) => {
    const classes = {
        PENDIENTE: 'bg-yellow-100 text-yellow-800',
        PENDIENTE_RRHH: 'bg-orange-100 text-orange-800',
        APROBADO: 'bg-green-100 text-green-800',
        DESAPROBADO: 'bg-red-100 text-red-800',
    };
    return classes[estado] || 'bg-slate-100 text-slate-800';
};

const openApproveModal = (papeleta) => {
    selectedPapeleta.value = papeleta;
    modalComentario.value = '';
    showApproveModal.value = true;
};

const openRejectModal = (papeleta) => {
    selectedPapeleta.value = papeleta;
    modalComentario.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const handleAprobar = async () => {
    try {
        await aprobar(selectedPapeleta.value.id, modalComentario.value);
        showApproveModal.value = false;
        window.Swal?.fire({ icon: 'success', title: 'Papeleta aprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        refreshData();
    } catch {
        // error handled in composable
    }
};

const handleDesaprobar = async () => {
    if (!modalComentario.value.trim()) {
        rejectError.value = 'Debe indicar el motivo del rechazo';
        return;
    }
    try {
        await desaprobar(selectedPapeleta.value.id, modalComentario.value);
        showRejectModal.value = false;
        window.Swal?.fire({ icon: 'success', title: 'Papeleta desaprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        refreshData();
    } catch {
        // error handled in composable
    }
};

const resetFiltros = () => {
    filtros.estado = 'TODOS';
    filtros.fecha_desde = '';
    filtros.fecha_hasta = '';
    filtros.direction_id = '';
    filtros.office_id = '';
    fetchHistorial();
};

const refreshData = () => {
    fetchPendientes();
    fetchStats();
    if (activeTab.value === 'historial') fetchHistorial();
};

watch(activeTab, (newTab) => {
    if (newTab === 'pendientes_rrhh' || newTab === 'pendientes') {
        fetchPendientes();
    }
    if (newTab === 'historial') {
        fetchHistorial();
    }
});

onMounted(() => {
    fetchPendientes();
    fetchHistorial();
    fetchStats();
});
</script>
