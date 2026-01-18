<template>
    <div class="border rounded-2xl p-4 sm:p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-start gap-3 sm:gap-4 mb-4">
            <div>
                <p class="font-bold text-base sm:text-lg text-gray-800">{{ formatSimpleDate(cita.fecha) }} - {{
                    cita.hora }}</p>
                <p class="text-xs sm:text-sm text-gray-600">{{ cita.oficina }}</p>
                <p class="text-xs text-gray-400 mt-1 font-mono">ID: {{ cita.id }}</p>
            </div>
            <span :class="{
                'bg-yellow-100 text-yellow-800 border-yellow-300': cita.estado === 'PENDIENTE',
                'bg-green-100 text-green-800 border-green-300': cita.estado === 'ATENDIDO',
                'bg-red-100 text-red-800 border-red-300': cita.estado === 'CANCELADO'
            }" class="px-3 sm:px-4 py-1.5 sm:py-2 rounded-full text-xs sm:text-sm font-bold border whitespace-nowrap">
                {{ cita.estado }}
            </span>
        </div>

        <!-- Stepper -->
        <div class="mb-4 py-3 sm:py-4 border-t border-b border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-3 sm:mb-4 font-semibold">Estado de la Cita</p>
            <div class="relative flex items-start justify-center">
                <div class="absolute top-5 sm:top-6 left-1/2 -translate-x-1/2 w-[80px] sm:w-[120px] h-[2px]"
                    :class="cita.estado !== 'PENDIENTE' ? 'bg-gradient-to-r from-blue-400 to-green-400' : 'bg-gray-300'">
                </div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center z-10">
                    <div
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold text-base sm:text-lg bg-white border-[3px] border-blue-500 text-blue-500">
                        1
                    </div>
                    <p class="mt-2 text-xs sm:text-sm font-medium text-blue-600">Registrada</p>
                    <p class="text-[10px] sm:text-xs text-gray-400">{{ cita.created_at ? formatDate(cita.created_at) :
                        '' }}</p>
                </div>

                <div class="w-[48px] sm:w-[72px]"></div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center z-10">
                    <div :class="{
                        'border-green-500 text-green-500': cita.estado === 'ATENDIDO',
                        'border-red-500 text-red-500': cita.estado === 'CANCELADO',
                        'border-gray-300 text-gray-300': cita.estado === 'PENDIENTE'
                    }"
                        class="w-10 h-10 sm:w-12 sm:h-12 rounded-full flex items-center justify-center font-semibold text-base sm:text-lg bg-white border-[3px]">
                        <Check v-if="cita.estado === 'ATENDIDO'" class="w-5 h-5 sm:w-6 sm:h-6" />
                        <X v-else-if="cita.estado === 'CANCELADO'" class="w-5 h-5 sm:w-6 sm:h-6" />
                        <span v-else>2</span>
                    </div>
                    <p class="mt-2 text-xs sm:text-sm font-medium" :class="{
                        'text-green-600': cita.estado === 'ATENDIDO',
                        'text-red-600': cita.estado === 'CANCELADO',
                        'text-gray-400': cita.estado === 'PENDIENTE'
                    }">
                        {{ cita.estado === 'ATENDIDO' ? 'Atendida' : cita.estado === 'CANCELADO' ? 'Cancelada' :
                            'Finalización' }}
                    </p>
                    <p v-if="cita.estado !== 'PENDIENTE'" class="text-[10px] sm:text-xs text-gray-400">
                        {{ getFinalizationDate(cita) }}
                    </p>
                    <p v-else class="text-[10px] sm:text-xs text-gray-300">Pendiente</p>
                </div>
            </div>
        </div>

        <!-- Observation -->
        <div v-if="cita.estado !== 'PENDIENTE' && latestObservation"
            class="mt-4 p-3 sm:p-4 bg-slate-50 rounded-xl border border-slate-200 mb-4">
            <div class="flex items-start gap-3">
                <div :class="cita.estado === 'ATENDIDO' ? 'text-green-500' : 'text-red-500'" class="mt-0.5">
                    <Info class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                        <span v-if="cita.estado === 'ATENDIDO'">Observación de Atención</span>
                        <span v-else>Motivo de Cancelación</span>
                    </p>
                    <p class="text-sm text-slate-700 leading-relaxed font-medium break-words">
                        {{ latestObservation }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Action -->
        <div class="flex justify-end">
            <button @click="$emit('generate-voucher', cita)"
                class="w-full sm:w-auto inline-flex items-center justify-center px-4 sm:px-5 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all text-sm font-bold shadow-md hover:shadow-lg"
                aria-label="Ver voucher de cita">
                <FileText class="w-4 h-4 mr-2" />
                <span>Ver Voucher</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Check, X, Info, FileText } from 'lucide-vue-next';

const props = defineProps({
    cita: {
        type: Object,
        required: true
    }
});

defineEmits(['generate-voucher']);

// Format simple date to dd/mm/yyyy
const formatSimpleDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    } catch {
        return dateStr;
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-PE', {
            day: '2-digit',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch {
        return dateStr;
    }
};

const getFinalizationDate = (cita) => {
    if (cita.historial && cita.historial.length > 1) {
        const lastEntry = cita.historial[cita.historial.length - 1];
        if (lastEntry?.fecha) {
            return formatDate(lastEntry.fecha);
        }
    }
    return formatDate(new Date().toISOString());
};

const latestObservation = computed(() => {
    if (props.cita.historial && Array.isArray(props.cita.historial) && props.cita.historial.length > 0) {
        const entriesWithDesc = [...props.cita.historial]
            .reverse()
            .filter(h => h.descripcion && h.descripcion.trim() !== 'Cita registrada');

        if (entriesWithDesc.length > 0) {
            return entriesWithDesc[0].descripcion;
        }
    }
    return '';
});
</script>
