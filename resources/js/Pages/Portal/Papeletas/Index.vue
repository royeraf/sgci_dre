<template>
    <PortalLayout>
        <!-- Header -->
        <div class="relative overflow-hidden rounded-2xl p-6 sm:p-8 text-white mb-6 shadow-xl">
            <img src="/images/login-bg.png" alt="Background" class="absolute inset-0 w-full h-full object-cover" />
            <div class="absolute inset-0 bg-gradient-to-r from-blue-900/95 via-blue-900/75 to-indigo-900/40"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">
                    Mis <span class="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">Papeletas</span>
                </h1>
                <p class="text-blue-100/80 text-sm mt-1">{{ employee.full_name }} - {{ employee.direction_nombre }}</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total</p>
                <p class="text-2xl font-black text-slate-900">{{ stats.total }}</p>
            </div>
            <div class="bg-yellow-50 rounded-xl p-4 shadow-sm border border-yellow-100">
                <p class="text-xs font-semibold text-yellow-600 uppercase">Pendientes</p>
                <p class="text-2xl font-black text-yellow-700">{{ stats.pendientes }}</p>
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

        <!-- Actions -->
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-slate-800">Historial de Papeletas</h2>
            <button @click="openModal"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/20 transition-all">
                <Plus class="h-4 w-4" />
                Nueva Papeleta
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div v-if="papeletas.length === 0" class="text-center py-12 text-slate-400">
                <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                <p class="font-semibold">No tiene papeletas registradas</p>
                <p class="text-sm">Cree una nueva papeleta para comenzar</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Horario</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="p in papeletas" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ p.numero_papeleta }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ formatDate(p.fecha_salida) }}</td>
                            <td class="px-4 py-3 text-slate-600 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="estadoBadgeClass(p.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                    {{ p.estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="openDetailModal(p)"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                        title="Ver detalle">
                                        <Eye class="h-4 w-4" />
                                    </button>
                                    <a v-if="p.estado === 'APROBADO'" :href="`/portal/papeletas/${p.id}/pdf`" target="_blank"
                                        class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors"
                                        title="Descargar PDF">
                                        <Download class="h-4 w-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal: Nueva Papeleta -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeModal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                        <h3 class="text-lg font-bold text-white">Nueva Papeleta de Salida</h3>
                        <button @click="closeModal" class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <form @submit.prevent="submitPapeleta" class="p-6 space-y-4">
                        <!-- Reason -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Motivo de Salida *</label>
                            <select v-model="form.entry_exit_reason_id"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                :class="{ 'border-red-300': form.errors.entry_exit_reason_id }">
                                <option value="">Seleccione un motivo</option>
                                <option v-for="r in reasons" :key="r.id" :value="r.id">
                                    {{ r.nombre }} ({{ r.tipo === 'comision' ? 'Comision' : r.tipo === 'permiso' ? 'Permiso' : 'Ambos' }})
                                </option>
                            </select>
                            <p v-if="form.errors.entry_exit_reason_id" class="mt-1 text-xs text-red-500">{{ form.errors.entry_exit_reason_id }}</p>
                        </div>

                        <!-- Date & Shift -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de Salida *</label>
                                <input type="date" v-model="form.fecha_salida" :min="today"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    :class="{ 'border-red-300': form.errors.fecha_salida }" />
                                <p v-if="form.errors.fecha_salida" class="mt-1 text-xs text-red-500">{{ form.errors.fecha_salida }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Turno *</label>
                                <select v-model="form.turno"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    :class="{ 'border-red-300': form.errors.turno }">
                                    <option value="Manana">Manana</option>
                                    <option value="Tarde">Tarde</option>
                                    <option value="Noche">Noche</option>
                                </select>
                                <p v-if="form.errors.turno" class="mt-1 text-xs text-red-500">{{ form.errors.turno }}</p>
                            </div>
                        </div>

                        <!-- Times -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hora Salida *</label>
                                <input type="time" v-model="form.hora_salida_estimada"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    :class="{ 'border-red-300': form.errors.hora_salida_estimada }" />
                                <p v-if="form.errors.hora_salida_estimada" class="mt-1 text-xs text-red-500">{{ form.errors.hora_salida_estimada }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hora Retorno</label>
                                <input type="time" v-model="form.hora_retorno_estimada"
                                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                    :class="{ 'border-red-300': form.errors.hora_retorno_estimada }" />
                                <p v-if="form.errors.hora_retorno_estimada" class="mt-1 text-xs text-red-500">{{ form.errors.hora_retorno_estimada }}</p>
                            </div>
                        </div>

                        <!-- Justification -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Justificacion *</label>
                            <textarea v-model="form.motivo" rows="3"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all resize-none"
                                :class="{ 'border-red-300': form.errors.motivo }"
                                placeholder="Describa el motivo de su salida..."></textarea>
                            <p v-if="form.errors.motivo" class="mt-1 text-xs text-red-500">{{ form.errors.motivo }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                            <button type="button" @click="closeModal"
                                class="px-4 py-2.5 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                <Send v-else class="h-4 w-4" />
                                {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Modal: Detalle Papeleta -->
        <Teleport to="body">
            <div v-if="showDetailModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDetailModal">
                <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden">
                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">Detalle de Papeleta</h3>
                            <p class="text-blue-100 text-sm">N° {{ selectedPapeleta?.numero_papeleta }}</p>
                        </div>
                        <button @click="closeDetailModal" class="p-1.5 rounded-lg text-white/80 hover:text-white hover:bg-white/20 transition-colors">
                            <X class="h-5 w-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-4">
                        <!-- Estado -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-500">Estado</span>
                            <span :class="estadoBadgeClass(selectedPapeleta?.estado)" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold">
                                {{ selectedPapeleta?.estado }}
                            </span>
                        </div>

                        <!-- Detalles -->
                        <div class="bg-slate-50 rounded-xl p-4 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Motivo</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedPapeleta?.reason?.nombre ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Fecha</span>
                                <span class="text-sm font-semibold text-slate-800">{{ formatDate(selectedPapeleta?.fecha_salida) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Turno</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedPapeleta?.turno }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Hora Salida</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedPapeleta?.hora_salida_estimada }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Hora Retorno</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedPapeleta?.hora_retorno_estimada || '--:--' }}</span>
                            </div>
                        </div>

                        <!-- Justificación -->
                        <div>
                            <span class="text-sm font-semibold text-slate-500">Justificación</span>
                            <p class="text-sm text-slate-700 mt-1 bg-slate-50 rounded-xl p-3">{{ selectedPapeleta?.motivo }}</p>
                        </div>

                        <!-- Información de aprobación -->
                        <div v-if="selectedPapeleta?.estado !== 'PENDIENTE' && selectedPapeleta?.estado !== 'PENDIENTE_RRHH'" class="bg-slate-50 rounded-xl p-4 space-y-2">
                            <p class="text-sm font-semibold text-slate-500">Información de Aprobación</p>
                            
                            <div v-if="selectedPapeleta?.aprobado_por_jefe" class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-green-600" />
                                <span class="text-sm text-slate-600">
                                    Aprobado por Jefe: {{ selectedPapeleta?.aprobador_jefe?.person?.nombres }} {{ selectedPapeleta?.aprobador_jefe?.person?.apellidos }}
                                </span>
                            </div>
                            <div v-if="selectedPapeleta?.fecha_aprobacion_jefe" class="text-xs text-slate-400 ml-6">
                                {{ formatDateTime(selectedPapeleta?.fecha_aprobacion_jefe) }}
                            </div>
                            
                            <div v-if="selectedPapeleta?.aprobado_por_rrhh" class="flex items-center gap-2">
                                <CheckCircle class="h-4 w-4 text-green-600" />
                                <span class="text-sm text-slate-600">
                                    Aprobado por RRHH: {{ selectedPapeleta?.aprobador_rrhh?.person?.nombres }} {{ selectedPapeleta?.aprobador_rrhh?.person?.apellidos }}
                                </span>
                            </div>
                            <div v-if="selectedPapeleta?.fecha_aprobacion_rrhh" class="text-xs text-slate-400 ml-6">
                                {{ formatDateTime(selectedPapeleta?.fecha_aprobacion_rrhh) }}
                            </div>

                            <div v-if="selectedPapeleta?.comentario_aprobacion" class="mt-2 pt-2 border-t border-slate-200">
                                <span class="text-sm text-slate-500">Comentario:</span>
                                <p class="text-sm text-slate-700">{{ selectedPapeleta?.comentario_aprobacion }}</p>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                            <button @click="closeDetailModal"
                                class="px-4 py-2.5 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                                Cerrar
                            </button>
                            <a v-if="selectedPapeleta?.estado === 'APROBADO'" :href="`/portal/papeletas/${selectedPapeleta?.id}/pdf`" target="_blank"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors">
                                <Download class="h-4 w-4" />
                                Descargar PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </PortalLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { Plus, FileText, Eye, Download, X, Send, Loader2, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    papeletas: Array,
    stats: Object,
    employee: Object,
    reasons: Array,
});

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedPapeleta = ref(null);
const today = computed(() => new Date().toISOString().split('T')[0]);

const form = useForm({
    entry_exit_reason_id: '',
    fecha_salida: '',
    hora_salida_estimada: '',
    hora_retorno_estimada: '',
    turno: 'Manana',
    motivo: '',
});

const resetForm = () => {
    form.entry_exit_reason_id = '';
    form.fecha_salida = today.value;
    form.hora_salida_estimada = '';
    form.hora_retorno_estimada = '';
    form.turno = 'Manana';
    form.motivo = '';
    form.clearErrors();
};

const openModal = () => {
    resetForm();
    showCreateModal.value = true;
};

const closeModal = () => {
    showCreateModal.value = false;
};

const openDetailModal = (papeleta) => {
    selectedPapeleta.value = papeleta;
    showDetailModal.value = true;
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    selectedPapeleta.value = null;
};

const submitPapeleta = () => {
    form.post('/portal/papeletas', {
        onSuccess: () => {
            showCreateModal.value = false;
            resetForm();
        },
    });
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-PE', { 
        day: '2-digit', 
        month: '2-digit', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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
</script>
