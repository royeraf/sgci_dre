<script setup>
import { reactive, computed } from 'vue';
import { FileBarChart } from 'lucide-vue-next';

const filtros = reactive({
    estado: 'TODOS',
    fecha_desde: '',
    fecha_hasta: '',
});

const reportUrl = computed(() => {
    const params = new URLSearchParams();
    if (filtros.estado !== 'TODOS') params.set('estado', filtros.estado);
    if (filtros.fecha_desde) params.set('fecha_desde', filtros.fecha_desde);
    if (filtros.fecha_hasta) params.set('fecha_hasta', filtros.fecha_hasta);
    return `/papeletas/report/pdf?${params.toString()}`;
});
</script>

<template>
    <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-slate-100 p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-800">Generar Reporte PDF</h3>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Estado</label>
            <select v-model="filtros.estado"
                class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20 bg-white">
                <option value="TODOS">Todos</option>
                <option value="PENDIENTE">Pendiente</option>
                <option value="APROBADO">Aprobado</option>
                <option value="DESAPROBADO">Desaprobado</option>
            </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Desde</label>
                <input type="date" v-model="filtros.fecha_desde"
                    class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20" />
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hasta</label>
                <input type="date" v-model="filtros.fecha_hasta"
                    class="w-full rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-violet-500 focus:ring-1 focus:ring-violet-500/20" />
            </div>
        </div>
        <a :href="reportUrl" target="_blank"
            class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 shadow-lg shadow-violet-500/20 transition-all">
            <FileBarChart class="h-4 w-4" />
            Generar Reporte
        </a>
    </div>
</template>
