<template>
    <div
        class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition-shadow flex flex-col h-full">
        <!-- Header Compacto -->
        <div class="bg-gradient-to-r from-slate-50 to-gray-100 px-4 py-3 border-b border-gray-200">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div class="bg-pink-100 p-2 rounded-lg flex-shrink-0">
                        <Calendar class="h-4 w-4 text-pink-600" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-bold text-gray-900 truncate">{{ cita.nombres }} {{ cita.apellidos }}
                        </h3>
                        <p class="text-xs text-gray-500 truncate">DNI: {{ cita.dni }}</p>
                    </div>
                </div>
                <span
                    class="px-2 py-1 rounded-full text-xs font-bold border bg-yellow-100 text-yellow-800 border-yellow-300 flex-shrink-0">
                    PENDIENTE
                </span>
            </div>
        </div>

        <!-- Content Compacto -->
        <div class="p-4 flex-1 flex flex-col">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-2 mb-3 text-xs">
                <div>
                    <p class="text-gray-500 uppercase tracking-wider text-[10px]">Fecha</p>
                    <p class="font-semibold text-gray-900">{{ cita.fecha }}</p>
                </div>
                <div>
                    <p class="text-gray-500 uppercase tracking-wider text-[10px]">Hora</p>
                    <p class="font-semibold text-gray-900">{{ cita.hora }}</p>
                </div>
                <div>
                    <p class="text-gray-500 uppercase tracking-wider text-[10px]">Celular</p>
                    <p class="font-semibold text-gray-900">{{ cita.celular }}</p>
                </div>
                <div>
                    <p class="text-gray-500 uppercase tracking-wider text-[10px]">Oficina</p>
                    <p class="font-semibold text-gray-900 truncate" :title="cita.oficina">{{ cita.oficina }}</p>
                </div>
            </div>

            <!-- Asunto -->
            <div class="mb-3 flex-1">
                <p class="text-gray-500 uppercase tracking-wider text-[10px] mb-1">Asunto</p>
                <p class="text-xs text-gray-700 bg-gray-50 p-2 rounded-lg line-clamp-2">{{ cita.asunto }}</p>
            </div>

            <!-- Historial Compacto -->
            <div class="mb-3" v-if="cita.historial && cita.historial.length > 0">
                <p class="text-gray-500 uppercase tracking-wider text-[10px] mb-2">Historial</p>
                <div class="flex items-center gap-1 overflow-x-auto pb-1">
                    <template v-for="(paso, index) in cita.historial" :key="index">
                        <div class="flex flex-col items-center min-w-[60px]">
                            <div :class="{
                                'bg-gradient-to-r from-pink-500 to-rose-500': paso.estado === 'PENDIENTE',
                                'bg-green-500': paso.estado === 'ATENDIDO',
                                'bg-red-500': paso.estado === 'CANCELADO',
                                'bg-pink-500': !['PENDIENTE', 'ATENDIDO', 'CANCELADO'].includes(paso.estado)
                            }" class="w-6 h-6 rounded-full flex items-center justify-center text-white shadow">
                                <Clock v-if="paso.estado === 'PENDIENTE'" class="w-3 h-3" />
                                <Check v-else-if="paso.estado === 'ATENDIDO'" class="w-3 h-3" />
                                <X v-else-if="paso.estado === 'CANCELADO'" class="w-3 h-3" />
                                <span v-else class="text-[8px] font-bold">{{ index + 1 }}</span>
                            </div>
                            <p class="mt-1 text-[9px] font-semibold text-gray-700">{{ paso.estado }}</p>
                        </div>
                        <div v-if="index < cita.historial.length - 1"
                            class="flex-1 h-0.5 bg-gray-300 min-w-[20px] rounded"></div>
                    </template>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-3 border-t border-gray-200 mt-auto">
                <button @click="$emit('attend', cita.id)"
                    class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-lg hover:from-pink-700 hover:to-rose-700 transition-colors text-xs font-medium shadow-lg shadow-pink-500/30">
                    <Check class="w-3.5 h-3.5 mr-1" />
                    Atendido
                </button>
                <button @click="$emit('cancel', cita.id)"
                    class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-rose-600 text-white rounded-lg hover:bg-rose-700 transition-colors text-xs font-medium">
                    <X class="w-3.5 h-3.5 mr-1" />
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Calendar, Clock, Check, X } from 'lucide-vue-next';

defineProps({
    cita: {
        type: Object,
        required: true
    }
});

defineEmits(['attend', 'cancel']);

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
</script>
