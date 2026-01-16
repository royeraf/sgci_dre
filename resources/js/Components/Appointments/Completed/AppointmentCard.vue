<template>
    <div
        class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden hover:shadow-2xl transition-all duration-300 flex flex-col h-full group">
        <!-- Header Compacto -->
        <div
            class="bg-gradient-to-r from-slate-50 to-slate-100 px-4 py-3 border-b border-slate-200 group-hover:from-slate-100 group-hover:to-slate-200 transition-colors duration-300">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div :class="cita.estado === 'ATENDIDO' ? 'bg-green-50 border-green-100 text-green-600' : 'bg-red-50 border-red-100 text-red-600'"
                        class="bg-white p-2 rounded-xl shadow-sm border group-hover:scale-110 transition-transform duration-300">
                        <CheckCircle v-if="cita.estado === 'ATENDIDO'" class="h-4 w-4" />
                        <XCircle v-else class="h-4 w-4" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-900 truncate tracking-tight">{{ cita.nombres }} {{
                            cita.apellidos }}</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">DNI: {{ cita.dni }}
                        </p>
                    </div>
                </div>
                <span :class="{
                    'bg-green-50 text-green-700 border-green-200 shadow-green-100': cita.estado === 'ATENDIDO',
                    'bg-red-50 text-red-700 border-red-200 shadow-red-100': cita.estado === 'CANCELADO'
                }"
                    class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border shadow-sm flex-shrink-0">
                    {{ cita.estado }}
                </span>
            </div>
        </div>

        <!-- Content Compacto -->
        <div class="p-4 flex-1 flex flex-col">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3 mb-4 text-xs">
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Fecha</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.fecha }}</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Hora</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.hora }}</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Celular</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.celular }}</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100 transition-colors overflow-hidden">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Oficina</p>
                    <p class="font-black text-slate-800 tracking-tight truncate" :title="cita.oficina">{{ cita.oficina
                        }}</p>
                </div>
            </div>

            <!-- Asunto -->
            <div class="mb-4">
                <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-1.5 ml-1">Asunto</p>
                <div class="text-xs text-slate-600 bg-slate-50/50 p-3 rounded-xl border border-dotted border-slate-200">
                    <p class="line-clamp-2 font-medium leading-relaxed italic">"{{ cita.asunto }}"</p>
                </div>
            </div>

            <!-- Historial Compacto -->
            <div class="mb-4" v-if="cita.historial && cita.historial.length > 0">
                <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-2 ml-1">Seguimiento</p>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 px-1">
                    <template v-for="(paso, index) in cita.historial" :key="index">
                        <div class="flex flex-col items-center min-w-[50px]">
                            <div :class="{
                                'bg-gradient-to-r from-pink-500 to-rose-500 shadow-pink-100': paso.estado === 'PENDIENTE',
                                'bg-gradient-to-r from-green-500 to-emerald-500 shadow-green-100': paso.estado === 'ATENDIDO',
                                'bg-gradient-to-r from-red-500 to-rose-600 shadow-red-100': paso.estado === 'CANCELADO',
                                'bg-gradient-to-r from-slate-400 to-slate-500 shadow-slate-100': !['PENDIENTE', 'ATENDIDO', 'CANCELADO'].includes(paso.estado)
                            }"
                                class="w-6 h-6 rounded-full flex items-center justify-center text-white shadow-md border-2 border-white">
                                <Clock v-if="paso.estado === 'PENDIENTE'" class="w-3 h-3" />
                                <Check v-else-if="paso.estado === 'ATENDIDO'" class="w-3 h-3" />
                                <X v-else-if="paso.estado === 'CANCELADO'" class="w-3 h-3" />
                                <span v-else class="text-[8px] font-black">{{ index + 1 }}</span>
                            </div>
                            <p class="mt-1 text-[8px] font-black text-slate-500 uppercase tracking-tighter">{{
                                paso.estado }}</p>
                        </div>
                        <div v-if="index < cita.historial.length - 1"
                            class="flex-1 h-0.5 bg-slate-100 min-w-[15px] rounded-full"></div>
                    </template>
                </div>
            </div>

            <!-- Observación de Finalización -->
            <div v-if="latestObservation"
                class="p-3 bg-gradient-to-br from-slate-50 to-slate-100 rounded-2xl border border-slate-200 mt-auto group-hover:from-white group-hover:to-white transition-all duration-300 shadow-inner">
                <div class="flex items-start gap-2.5">
                    <div :class="cita.estado === 'ATENDIDO' ? 'text-green-500 bg-green-50' : 'text-red-500 bg-red-50'"
                        class="flex-shrink-0 p-1.5 rounded-lg border border-white shadow-sm mt-0.5">
                        <Info class="w-3.5 h-3.5" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-[9px] font-black uppercase tracking-widest text-slate-400 mb-0.5">
                            <span v-if="cita.estado === 'ATENDIDO'">Nota de Atención</span>
                            <span v-else>Razón de Cancelación</span>
                        </p>
                        <p class="text-xs text-slate-700 leading-relaxed font-bold line-clamp-2">
                            {{ latestObservation }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { CheckCircle, XCircle, Clock, Check, X, Info } from 'lucide-vue-next';

const props = defineProps({
    cita: {
        type: Object,
        required: true
    }
});

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
