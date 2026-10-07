<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 border-l-4 border-l-teal-500 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Periodo actual</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ summary.periodo_actual || 'Sin definir' }}</p>
                </div>
                <div class="bg-teal-100 p-3 rounded-xl">
                    <CalendarRange class="w-7 h-7 text-teal-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 border-l-4 border-l-cyan-500 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Boletas emitidas</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ summary.boletas_emitidas || 0 }}</p>
                </div>
                <div class="bg-cyan-100 p-3 rounded-xl">
                    <Receipt class="w-7 h-7 text-cyan-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 border-l-4 border-l-emerald-500 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Personal en planilla</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ summary.personal_en_planilla || 0 }}</p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-xl">
                    <Users class="w-7 h-7 text-emerald-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 border-l-4 border-l-amber-500 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest">Pendientes</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ summary.pendientes || 0 }}</p>
                </div>
                <div class="bg-amber-100 p-3 rounded-xl">
                    <Clock class="w-7 h-7 text-amber-600" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { CalendarRange, Receipt, Users, Clock } from 'lucide-vue-next';

defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
});
</script>
