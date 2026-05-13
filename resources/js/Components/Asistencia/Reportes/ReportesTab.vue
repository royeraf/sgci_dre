<script setup lang="ts">
import { ref } from 'vue';
import { FileText, Download, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    employee: any;
    isAdmin: boolean;
    employeeId: number | null;
}>();

const today        = new Date();
const reportYear   = ref(today.getFullYear());
const reportMonth  = ref(today.getMonth() + 1);
const generating   = ref<'mensual' | 'anual' | null>(null);

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const download = (type: 'mensual' | 'anual') => {
    generating.value = type;
    const params = new URLSearchParams();
    if (props.isAdmin && props.employeeId) params.set('employee_id', String(props.employeeId));
    params.set('year', String(reportYear.value));
    if (type === 'mensual') params.set('month', String(reportMonth.value));
    const a = document.createElement('a');
    a.href = `/asistencia/reporte/${type}?${params}`;
    a.target = '_blank';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => { generating.value = null; }, 1500);
};
</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

            <!-- Mensual -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center flex-shrink-0">
                        <FileText class="w-5 h-5 text-blue-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Reporte Mensual</h3>
                        <p class="text-xs text-slate-500">Detalle diario de marcaciones</p>
                    </div>
                </div>
                <p class="text-sm text-slate-500 mt-3 mb-4">
                    PDF con todos los días del mes, tardanzas (ref. 07:30), horas normales y minutos extras.
                </p>
                <div class="flex gap-3 mb-5">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Mes</label>
                        <select v-model="reportMonth"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none bg-white">
                            <option v-for="(n, i) in monthNames" :key="i+1" :value="i+1">{{ n }}</option>
                        </select>
                    </div>
                    <div class="w-24">
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Año</label>
                        <input type="number" v-model="reportYear" min="2020" :max="today.getFullYear()+1"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" />
                    </div>
                </div>
                <button @click="download('mensual')" :disabled="generating === 'mensual'"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 disabled:opacity-40 transition-all shadow-md shadow-blue-500/20">
                    <Loader2 v-if="generating === 'mensual'" class="w-4 h-4 animate-spin" />
                    <Download v-else class="w-4 h-4" />
                    Descargar PDF
                </button>
            </div>

            <!-- Anual -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <div class="flex items-center gap-3 mb-1">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 flex items-center justify-center flex-shrink-0">
                        <FileText class="w-5 h-5 text-violet-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Reporte Anual</h3>
                        <p class="text-xs text-slate-500">Resumen y detalle por mes</p>
                    </div>
                </div>
                <p class="text-sm text-slate-500 mt-3 mb-4">
                    PDF con resumen de los 12 meses y el detalle de cada uno con marcas y cálculos.
                </p>
                <div class="flex gap-3 mb-5">
                    <div class="w-28">
                        <label class="block text-xs font-bold text-slate-600 mb-1.5">Año</label>
                        <input type="number" v-model="reportYear" min="2020" :max="today.getFullYear()+1"
                            class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-violet-500 outline-none" />
                    </div>
                </div>
                <button @click="download('anual')" :disabled="generating === 'anual'"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-violet-500 to-violet-600 hover:from-violet-600 hover:to-violet-700 disabled:opacity-40 transition-all shadow-md shadow-violet-500/20">
                    <Loader2 v-if="generating === 'anual'" class="w-4 h-4 animate-spin" />
                    <Download v-else class="w-4 h-4" />
                    Descargar PDF
                </button>
            </div>
        </div>

        <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 text-xs text-slate-500 space-y-1">
            <p class="font-semibold text-slate-600 mb-1">Criterios de cálculo</p>
            <p>• <strong>Tardanza:</strong> minutos de retraso respecto a la hora de referencia 07:30.</p>
            <p>• <strong>H. Normales:</strong> tiempo trabajado efectivo, máximo 8 horas por día.</p>
            <p>• <strong>Min. Extras:</strong> minutos trabajados por encima de las 8 horas.</p>
            <p>• Los sábados, domingos y días no laborables se excluyen del conteo de laborables.</p>
        </div>
    </div>
</template>
