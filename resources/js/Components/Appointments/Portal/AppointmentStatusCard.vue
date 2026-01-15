<template>
    <div class="border rounded-xl p-6 bg-white shadow-sm hover:shadow-md transition-shadow">
        <!-- Header -->
        <div class="flex flex-wrap justify-between items-start gap-4 mb-4">
            <div>
                <p class="font-bold text-lg text-gray-800">{{ cita.fecha }} - {{ cita.hora }}</p>
                <p class="text-sm text-gray-600">{{ cita.oficina }}</p>
                <p class="text-xs text-gray-400 mt-1 font-mono">ID: {{ cita.id }}</p>
            </div>
            <span :class="{
                'bg-yellow-100 text-yellow-800 border-yellow-300': cita.estado === 'PENDIENTE',
                'bg-green-100 text-green-800 border-green-300': cita.estado === 'ATENDIDO',
                'bg-red-100 text-red-800 border-red-300': cita.estado === 'CANCELADO'
            }" class="px-4 py-2 rounded-full text-sm font-bold border">
                {{ cita.estado }}
            </span>
        </div>

        <!-- Stepper -->
        <div class="mb-4 py-4 border-t border-b border-gray-100">
            <p class="text-xs text-gray-500 uppercase tracking-wider mb-4 font-semibold">Estado de la Cita</p>
            <div class="relative flex items-start justify-center">
                <div class="absolute top-6 left-1/2 -translate-x-1/2 w-[120px] h-[2px]"
                    :class="cita.estado !== 'PENDIENTE' ? 'bg-gradient-to-r from-blue-400 to-green-400' : 'bg-gray-300'">
                </div>

                <!-- Step 1 -->
                <div class="flex flex-col items-center z-10">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-lg bg-white border-[3px] border-blue-500 text-blue-500">
                        1
                    </div>
                    <p class="mt-2 text-sm font-medium text-blue-600">Registrada</p>
                    <p class="text-xs text-gray-400">{{ cita.created_at ? formatDate(cita.created_at) : '' }}</p>
                </div>

                <div class="w-[72px]"></div>

                <!-- Step 2 -->
                <div class="flex flex-col items-center z-10">
                    <div :class="{
                        'border-green-500 text-green-500': cita.estado === 'ATENDIDO',
                        'border-red-500 text-red-500': cita.estado === 'CANCELADO',
                        'border-gray-300 text-gray-300': cita.estado === 'PENDIENTE'
                    }"
                        class="w-12 h-12 rounded-full flex items-center justify-center font-semibold text-lg bg-white border-[3px]">
                        <Check v-if="cita.estado === 'ATENDIDO'" class="w-6 h-6" />
                        <X v-else-if="cita.estado === 'CANCELADO'" class="w-6 h-6" />
                        <span v-else>2</span>
                    </div>
                    <p class="mt-2 text-sm font-medium" :class="{
                        'text-green-600': cita.estado === 'ATENDIDO',
                        'text-red-600': cita.estado === 'CANCELADO',
                        'text-gray-400': cita.estado === 'PENDIENTE'
                    }">
                        {{ cita.estado === 'ATENDIDO' ? 'Atendida' : cita.estado === 'CANCELADO' ? 'Cancelada' :
                        'Finalización' }}
                    </p>
                    <p v-if="cita.estado !== 'PENDIENTE'" class="text-xs text-gray-400">
                        {{ getFinalizationDate(cita) }}
                    </p>
                    <p v-else class="text-xs text-gray-300">Pendiente</p>
                </div>
            </div>
        </div>

        <!-- Observation -->
        <div v-if="cita.estado !== 'PENDIENTE' && latestObservation"
            class="mt-4 p-4 bg-slate-50 rounded-xl border border-slate-200 mb-4">
            <div class="flex items-start gap-3">
                <div :class="cita.estado === 'ATENDIDO' ? 'text-green-500' : 'text-red-500'" class="mt-0.5">
                    <Info class="w-5 h-5" />
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">
                        <span v-if="cita.estado === 'ATENDIDO'">Observación de Atención</span>
                        <span v-else>Motivo de Cancelación</span>
                    </p>
                    <p class="text-sm text-slate-700 leading-relaxed font-medium">
                        {{ latestObservation }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Action -->
        <div class="flex justify-end">
            <button @click="$emit('generate-voucher', cita)"
                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium">
                <FileText class="w-4 h-4 mr-2" />
                Ver Voucher
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
