<script setup>
import { ref } from 'vue';
import { CheckCircle, XCircle, FileText, Loader2 } from 'lucide-vue-next';
import ApprovePapeletaModal from './Modals/ApprovePapeletaModal.vue';
import RejectPapeletaModal from './Modals/RejectPapeletaModal.vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    loading: Boolean,
    cardBorder: { type: String, default: 'border-slate-200' },
    emptyText: { type: String, default: 'No hay papeletas pendientes' },
});

const emit = defineEmits(['refresh']);

const selectedPapeleta = ref(null);
const showApproveModal = ref(false);
const showRejectModal = ref(false);

const openApprove = (p) => { selectedPapeleta.value = p; showApproveModal.value = true; };
const openReject = (p) => { selectedPapeleta.value = p; showRejectModal.value = true; };

const handleApproved = () => {
    showApproveModal.value = false;
    selectedPapeleta.value = null;
    emit('refresh');
};

const handleRejected = () => {
    showRejectModal.value = false;
    selectedPapeleta.value = null;
    emit('refresh');
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

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const approveLabel = (p) => p.estado === 'PENDIENTE' ? 'Aprobar (Jefe)' : 'Aprobar (RRHH)';
const statusLabel = (p) => p.estado === 'PENDIENTE' ? 'PENDIENTE (Jefe)' : 'PENDIENTE (RRHH)';
</script>

<template>
    <div>
        <div v-if="loading" class="text-center py-12 text-slate-400">
            <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
            <p>Cargando...</p>
        </div>

        <div v-else-if="items.length === 0" class="text-center py-12 text-slate-400">
            <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-50" />
            <p class="font-semibold">{{ emptyText }}</p>
        </div>

        <div v-else class="space-y-3">
            <div v-for="p in items" :key="p.id"
                class="bg-white border rounded-xl p-4 hover:shadow-md transition-all"
                :class="cardBorder">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono font-bold text-blue-600 text-sm">{{ p.numero_papeleta }}</span>
                            <span :class="estadoBadgeClass(p.estado)"
                                class="text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ statusLabel(p) }}
                            </span>
                            <span v-if="p.aprobado_por_jefe"
                                class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                <CheckCircle class="h-3 w-3 inline mr-1" />
                                Jefe: {{ p.aprobador_jefe?.person?.apellidos ?? '' }}
                            </span>
                        </div>
                        <p class="font-bold text-slate-800 text-sm">
                            {{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ p.employee?.direction?.nombre ?? '-' }} |
                            {{ formatDate(p.fecha_salida) }} |
                            {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }} |
                            {{ p.reason?.nombre }}
                        </p>
                        <p class="text-xs text-slate-600 mt-1 line-clamp-1">{{ p.motivo }}</p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <button @click="openApprove(p)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                            <CheckCircle class="h-3.5 w-3.5" />
                            {{ approveLabel(p) }}
                        </button>
                        <button @click="openReject(p)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                            <XCircle class="h-3.5 w-3.5" />
                            Desaprobar
                        </button>
                        <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                            class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                            title="Ver PDF">
                            <FileText class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <ApprovePapeletaModal
            v-if="showApproveModal && selectedPapeleta"
            :papeleta="selectedPapeleta"
            @close="showApproveModal = false"
            @approved="handleApproved"
        />

        <RejectPapeletaModal
            v-if="showRejectModal && selectedPapeleta"
            :papeleta="selectedPapeleta"
            @close="showRejectModal = false"
            @rejected="handleRejected"
        />
    </div>
</template>
