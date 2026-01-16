<template>
    <div
        class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden hover:shadow-2xl transition-all duration-300 flex flex-col h-full group">
        <!-- Header Compacto -->
        <div
            class="bg-gradient-to-r from-slate-50 to-slate-100 px-4 py-3 border-b border-slate-200 group-hover:from-pink-50 group-hover:to-rose-50 transition-colors duration-300">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2 min-w-0">
                    <div
                        class="bg-white p-2 rounded-xl shadow-sm border border-pink-100 group-hover:scale-110 transition-transform duration-300">
                        <Calendar class="h-4 w-4 text-pink-600" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-slate-900 truncate tracking-tight">{{ cita.nombres }} {{
                            cita.apellidos }}</h3>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">DNI: {{ cita.dni }}
                        </p>
                    </div>
                </div>
                <span
                    class="px-2.5 py-1 rounded-lg text-[10px] font-black tracking-widest uppercase border bg-yellow-50 text-yellow-700 border-yellow-200 shadow-sm flex-shrink-0 animate-pulse">
                    PENDIENTE
                </span>
            </div>
        </div>

        <!-- Content Compacto -->
        <div class="p-4 flex-1 flex flex-col">
            <!-- Info Grid -->
            <div class="grid grid-cols-2 gap-3 mb-4 text-xs">
                <div
                    class="bg-slate-50 p-2 rounded-xl border border-slate-100 group-hover:border-pink-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Fecha</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.fecha }}</p>
                </div>
                <div
                    class="bg-slate-50 p-2 rounded-xl border border-slate-100 group-hover:border-pink-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Hora</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.hora }}</p>
                </div>
                <div
                    class="bg-slate-50 p-2 rounded-xl border border-slate-100 group-hover:border-pink-100 transition-colors">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Celular</p>
                    <p class="font-black text-slate-800 tracking-tight">{{ cita.celular }}</p>
                </div>
                <div
                    class="bg-slate-50 p-2 rounded-xl border border-slate-100 group-hover:border-pink-100 transition-colors overflow-hidden">
                    <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-0.5">Oficina</p>
                    <p class="font-black text-slate-800 tracking-tight truncate" :title="cita.oficina">{{ cita.oficina
                        }}</p>
                </div>
            </div>

            <!-- Asunto -->
            <div class="mb-4 flex-1">
                <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-1.5 ml-1">Asunto de la cita
                </p>
                <div
                    class="text-xs text-slate-700 bg-slate-50 p-3 rounded-xl border border-slate-100 group-hover:bg-white transition-colors">
                    <p class="line-clamp-3 font-medium leading-relaxed">{{ cita.asunto }}</p>
                </div>
            </div>

            <!-- Historial Compacto -->
            <div class="mb-4" v-if="cita.historial && cita.historial.length > 0">
                <p class="text-slate-400 uppercase tracking-widest text-[9px] font-bold mb-2 ml-1">Traza de Estado</p>
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 px-1">
                    <template v-for="(paso, index) in cita.historial" :key="index">
                        <div class="flex flex-col items-center min-w-[50px]">
                            <div :class="{
                                'bg-gradient-to-r from-pink-500 to-rose-500 shadow-pink-200': paso.estado === 'PENDIENTE',
                                'bg-gradient-to-r from-green-500 to-emerald-500 shadow-green-200': paso.estado === 'ATENDIDO',
                                'bg-gradient-to-r from-red-500 to-rose-600 shadow-red-200': paso.estado === 'CANCELADO',
                                'bg-gradient-to-r from-slate-400 to-slate-500 shadow-slate-200': !['PENDIENTE', 'ATENDIDO', 'CANCELADO'].includes(paso.estado)
                            }"
                                class="w-7 h-7 rounded-full flex items-center justify-center text-white shadow-lg border-2 border-white">
                                <Clock v-if="paso.estado === 'PENDIENTE'" class="w-3.5 h-3.5" />
                                <Check v-else-if="paso.estado === 'ATENDIDO'" class="w-3.5 h-3.5" />
                                <X v-else-if="paso.estado === 'CANCELADO'" class="w-3.5 h-3.5" />
                                <span v-else class="text-[10px] font-black">{{ index + 1 }}</span>
                            </div>
                            <p class="mt-1 text-[8px] font-black text-slate-600 uppercase tracking-tighter">{{
                                paso.estado }}</p>
                        </div>
                        <div v-if="index < cita.historial.length - 1"
                            class="flex-1 h-0.5 bg-slate-200 min-w-[15px] rounded-full"></div>
                    </template>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2.5 pt-4 border-t border-slate-100 mt-auto">
                <button @click="$emit('attend', cita.id)"
                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-pink-600 to-rose-600 text-white rounded-xl hover:from-pink-700 hover:to-rose-700 transition-all duration-300 text-xs font-black uppercase tracking-widest shadow-lg shadow-pink-500/25 transform hover:scale-[1.02] active:scale-[0.98]">
                    <Check class="w-4 h-4 mr-1.5" />
                    Atender
                </button>
                <button @click="$emit('cancel', cita.id)"
                    class="flex-1 inline-flex items-center justify-center px-4 py-2.5 bg-white text-rose-600 border-2 border-rose-100 rounded-xl hover:bg-rose-50 hover:border-rose-200 transition-all duration-300 text-xs font-black uppercase tracking-widest transform hover:scale-[1.02] active:scale-[0.98]">
                    <X class="w-4 h-4 mr-1.5" />
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
