<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full z-10 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <FileSpreadsheet class="w-6 h-6" />
                            Resumen de Planilla
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">
                            {{ periodo.nombre_periodo }} · {{ periodo.total_empleados }} empleados ·
                            Neto S/ {{ money(periodo.total_neto) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div v-if="pestana === 'planilla'" class="flex items-center gap-2">
                            <button @click="onExportar('xlsx')" :disabled="exportando !== null"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 disabled:opacity-50 transition-all">
                                <Loader2 v-if="exportando === 'xlsx'" class="w-4 h-4 animate-spin" />
                                <FileSpreadsheet v-else class="w-4 h-4" />
                                Excel
                            </button>
                            <button @click="onExportar('pdf')" :disabled="exportando !== null"
                                class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg bg-rose-50 text-rose-700 hover:bg-rose-100 disabled:opacity-50 transition-all">
                                <Loader2 v-if="exportando === 'pdf'" class="w-4 h-4 animate-spin" />
                                <FileText v-else class="w-4 h-4" />
                                PDF
                            </button>
                        </div>
                        <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Pestañas -->
                <div class="border-b border-slate-200 px-6 flex gap-1 shrink-0">
                    <button v-for="tab in PESTANAS" :key="tab.key" @click="cambiarPestana(tab.key)"
                        class="cursor-pointer whitespace-nowrap py-3 px-4 font-bold text-sm border-b-2 transition-colors duration-200"
                        :class="pestana === tab.key
                            ? 'text-teal-600 border-teal-600'
                            : 'text-slate-500 border-transparent hover:text-slate-700'">
                        {{ tab.label }}
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <template v-if="pestana === 'resumen'">
                        <div v-if="loading" class="py-16 text-center">
                            <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                        </div>

                        <div v-else-if="!resumen" class="py-16 text-center text-slate-500 font-medium">
                            No hay datos de planilla para mostrar. Genere la planilla del periodo e intente nuevamente.
                        </div>

                        <div v-else class="space-y-5">
                        <!-- Encabezado (formato hoja «Resumen») -->
                        <div class="text-center space-y-1">
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-500">
                                Dirección Regional de Educación Huánuco
                            </p>
                            <h4 class="text-lg font-bold text-slate-800">
                                Resumen Planillas de Pago Personal CAS - Sede
                            </h4>
                            <p class="text-sm font-bold uppercase text-teal-700">
                                {{ resumen.periodo.nombre_periodo }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-1.5 text-xs text-slate-600 bg-slate-50 rounded-xl border border-slate-200 px-5 py-3">
                            <p><span class="font-bold uppercase text-slate-500">Sector:</span> Educación</p>
                            <p><span class="font-bold uppercase text-slate-500">Pliego:</span> 448 Gobierno Regional Huánuco</p>
                            <p><span class="font-bold uppercase text-slate-500">Unid. Ejecutora:</span> Sede DRE Huánuco</p>
                            <p><span class="font-bold uppercase text-slate-500">Fte. Fto.:</span> Recursos Ordinarios (00)</p>
                            <p><span class="font-bold uppercase text-slate-500">N° Certificación:</span> 0000003</p>
                            <p><span class="font-bold uppercase text-slate-500">Meta:</span> 0014</p>
                            <p><span class="font-bold uppercase text-slate-500">Empleados:</span> {{ resumen.empleados }}</p>
                            <p><span class="font-bold uppercase text-slate-500">Estado:</span> {{ resumen.periodo.estado }}</p>
                        </div>

                        <!-- 3 columnas: Ingresos | Descuentos | Abono (misma altura, totales al pie) -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-stretch">
                            <!-- INGRESOS -->
                            <section class="flex flex-col rounded-xl border border-slate-200 overflow-hidden">
                                <h5 class="bg-teal-50 text-teal-700 text-xs font-bold uppercase tracking-widest px-3 py-2 border-b border-teal-100">
                                    Ingresos
                                </h5>
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Esp. Gasto</th>
                                            <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Descripción</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">Ingreso S/</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="ingreso in resumen.ingresos" :key="ingreso.esp_gasto" class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2 font-bold text-slate-700 whitespace-nowrap">{{ ingreso.esp_gasto }}</td>
                                            <td class="px-3 py-2 text-slate-600">{{ ingreso.nombre }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900 tabular-nums">{{ money(ingreso.monto) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="mt-auto bg-teal-600 text-white px-3 py-2 flex items-center justify-between gap-2 text-[10px] font-bold uppercase tracking-wider">
                                    <span>Total Planillas</span>
                                    <span class="text-xs font-bold tabular-nums">{{ money(resumen.total_planillas) }}</span>
                                </div>
                            </section>

                            <!-- DESCUENTOS -->
                            <section class="flex flex-col rounded-xl border border-slate-200 overflow-hidden">
                                <h5 class="bg-slate-100 text-slate-700 text-xs font-bold uppercase tracking-widest px-3 py-2 border-b border-slate-200">
                                    Descuentos
                                </h5>
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Concepto</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">13.11</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">13.12</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">Total S/</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="descuento in resumen.descuentos" :key="descuento.nombre" class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2 font-medium text-slate-700">{{ descuento.nombre }}</td>
                                            <td class="px-3 py-2 text-right text-slate-600 tabular-nums">{{ money(descuento.c13_11) }}</td>
                                            <td class="px-3 py-2 text-right text-slate-600 tabular-nums">{{ money(descuento.c13_12) }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900 tabular-nums">{{ money(descuento.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div class="mt-auto bg-slate-700 text-white grid grid-cols-2 items-center gap-x-2 px-3 py-2 text-[10px] font-bold uppercase tracking-wider">
                                    <span>Total Dsctos</span>
                                    <span class="text-right text-[9px] tabular-nums">
                                        13.11 {{ money(sumatoria('c13_11')) }} · 13.12 {{ money(sumatoria('c13_12')) }}
                                    </span>
                                    <span class="col-span-2 text-xs tabular-nums text-right">S/ {{ money(resumen.total_descuentos) }}</span>
                                </div>
                            </section>

                            <!-- ABONO A CTA. CORRIENTE -->
                            <section class="flex flex-col rounded-xl border border-slate-200 overflow-hidden">
                                <h5 class="bg-cyan-50 text-cyan-700 text-xs font-bold uppercase tracking-widest px-3 py-2 border-b border-cyan-100">
                                    Abono a Cta. Corriente
                                </h5>
                                <table class="w-full text-xs">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Destino</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">13.11</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">13.12</th>
                                            <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">Total S/</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2 font-medium text-slate-700">Bco. Nac. Teleahorro</td>
                                            <td class="px-3 py-2 text-right text-slate-600 tabular-nums">{{ money(resumen.abono.c13_11) }}</td>
                                            <td class="px-3 py-2 text-right text-slate-600 tabular-nums">{{ money(resumen.abono.c13_12) }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900 tabular-nums">{{ money(resumen.abono.teleahorro) }}</td>
                                        </tr>
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="px-3 py-2 font-medium text-slate-700">Aporte Essalud (2.1.31.1 15)</td>
                                            <td class="px-3 py-2 text-right text-slate-400">—</td>
                                            <td class="px-3 py-2 text-right text-slate-400">—</td>
                                            <td class="px-3 py-2 text-right font-medium text-slate-900 tabular-nums">{{ money(resumen.abono.aporte_essalud) }}</td>
                                        </tr>
                                    </tbody>
                                </table>

                                <div class="mt-auto mx-3 mb-3 rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 text-white px-4 py-3 shadow-lg shadow-teal-500/30">
                                    <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Total Líquido</p>
                                    <p class="text-xl font-bold tabular-nums">S/ {{ money(resumen.abono.total_liquido) }}</p>
                                </div>
                            </section>
                        </div>
                    </div>
                    </template>

                    <template v-else>
                        <div v-if="loadingPlanilla" class="py-16 text-center">
                            <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                        </div>

                        <div v-else-if="!resumenPlanilla" class="py-16 text-center text-slate-500 font-medium">
                            No hay datos de planilla para mostrar. Genere la planilla del periodo e intente nuevamente.
                        </div>

                        <div v-else class="space-y-5">
                            <!-- Encabezado (formato hoja «Planilla») -->
                            <div class="text-center space-y-1">
                                <p class="text-xs font-bold uppercase tracking-widest text-slate-500">
                                    Dirección Regional de Educación Huánuco
                                </p>
                                <h4 class="text-lg font-bold text-slate-800">Resumen Planilla CAS</h4>
                                <p class="text-sm font-bold uppercase text-teal-700">
                                    {{ resumenPlanilla.periodo.nombre_periodo }}
                                </p>
                            </div>

                            <!-- Bloques 2.1.1 13.11 | 2.1.1 13.12 -->
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch">
                                <section v-for="(bloque, clave) in resumenPlanilla.bloques" :key="clave"
                                    class="flex flex-col rounded-xl border border-slate-200 overflow-hidden">
                                    <h5 class="bg-slate-800 text-white text-xs font-bold uppercase tracking-widest px-3 py-2 flex justify-between items-center gap-2">
                                        <span>Génerica de gasto {{ bloque.generica }}</span>
                                        <span class="text-[10px] font-bold tabular-nums whitespace-nowrap">
                                            Essalud S/ {{ money(bloque.essalud) }}
                                        </span>
                                    </h5>

                                    <div class="px-3 pt-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-teal-700 bg-teal-50 border-b border-teal-100">
                                        Ingresos
                                    </div>
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50 text-slate-500">
                                            <tr>
                                                <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Esp. Gasto</th>
                                                <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Concepto</th>
                                                <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">Monto S/</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="ingreso in bloque.ingresos" :key="ingreso.nombre" class="hover:bg-slate-50/70">
                                                <td class="px-3 py-1.5 font-bold text-slate-700 whitespace-nowrap">{{ ingreso.esp_gasto }}</td>
                                                <td class="px-3 py-1.5 text-slate-600">{{ ingreso.nombre }}</td>
                                                <td class="px-3 py-1.5 text-right font-medium text-slate-900 tabular-nums">{{ money(ingreso.monto) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-teal-600 text-white">
                                                <td colspan="2" class="px-3 py-2 font-bold uppercase text-[10px] tracking-wider">Total Ingresos</td>
                                                <td class="px-3 py-2 text-right font-bold tabular-nums">{{ money(bloque.total_ingresos) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <div class="px-3 pt-2.5 pb-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-600 bg-slate-50 border-y border-slate-200">
                                        Descuentos
                                    </div>
                                    <table class="w-full text-xs">
                                        <thead class="bg-slate-50 text-slate-500">
                                            <tr>
                                                <th class="text-left font-bold uppercase text-[10px] tracking-wider px-3 py-2">Concepto</th>
                                                <th class="text-right font-bold uppercase text-[10px] tracking-wider px-3 py-2">Monto S/</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            <tr v-for="descuento in bloque.descuentos" :key="descuento.nombre" class="hover:bg-slate-50/70">
                                                <td class="px-3 py-1.5 font-medium text-slate-700">{{ descuento.nombre }}</td>
                                                <td class="px-3 py-1.5 text-right font-medium text-slate-900 tabular-nums">{{ money(descuento.monto) }}</td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-slate-700 text-white">
                                                <td class="px-3 py-2 font-bold uppercase text-[10px] tracking-wider">Total Descuentos</td>
                                                <td class="px-3 py-2 text-right font-bold tabular-nums">{{ money(bloque.total_descuentos) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    <div class="mt-auto px-3 py-2.5 flex items-center justify-between gap-2 bg-cyan-50 border-t border-cyan-100">
                                        <span class="text-xs font-bold uppercase tracking-wider text-cyan-700">Neto a Pagar</span>
                                        <span class="text-sm font-bold text-slate-900 tabular-nums">S/ {{ money(bloque.neto) }}</span>
                                    </div>
                                </section>
                            </div>

                            <!-- Totales globales -->
                            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                                <div class="rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 text-white px-4 py-3 shadow-lg shadow-teal-500/30">
                                    <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Total Líquido</p>
                                    <p class="text-lg font-bold tabular-nums">S/ {{ money(resumenPlanilla.totales.liquido) }}</p>
                                </div>
                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-500">Total Descuento</p>
                                    <p class="text-lg font-bold text-slate-800 tabular-nums">S/ {{ money(resumenPlanilla.totales.descuento) }}</p>
                                </div>
                                <div class="rounded-xl border border-cyan-200 bg-cyan-50 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-cyan-700">Total Aporte (Essalud)</p>
                                    <p class="text-lg font-bold text-cyan-900 tabular-nums">S/ {{ money(resumenPlanilla.totales.aporte) }}</p>
                                </div>
                                <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
                                    <p class="text-[10px] font-bold uppercase tracking-widest text-indigo-700">Total Planilla</p>
                                    <p class="text-lg font-bold text-indigo-900 tabular-nums">S/ {{ money(resumenPlanilla.totales.planilla) }}</p>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-slate-100 bg-white shrink-0 flex justify-end">
                    <button @click="$emit('close')"
                        class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 font-bold">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { FileSpreadsheet, FileText, X, Loader2 } from 'lucide-vue-next';

import { usePlanillaResumen } from '@/Composables/usePlanillaResumen';

const props = defineProps({
    periodo: { type: Object, required: true },
});

defineEmits(['close']);

const PESTANAS = [
    { key: 'resumen', label: 'Resumen' },
    { key: 'planilla', label: 'Resumen Planilla CAS' },
];

const pestana = ref('resumen');

const {
    resumen,
    loading,
    resumenPlanilla,
    loadingPlanilla,
    exportando,
    fetchResumen,
    fetchResumenPlanilla,
    exportarResumenPlanilla,
} = usePlanillaResumen();

const money = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const sumatoria = (columna) => (resumen.value?.descuentos ?? [])
    .reduce((suma, fila) => suma + Number(fila[columna] || 0), 0);

const notify = (icon, title) => {
    window.Swal?.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon,
        title,
    });
};

const cambiarPestana = (clave) => {
    pestana.value = clave;

    if (clave === 'planilla' && !resumenPlanilla.value && !loadingPlanilla.value) {
        fetchResumenPlanilla(props.periodo.id).catch(() => { /* estado vacío en la vista */ });
    }
};

const onExportar = async (format) => {
    try {
        await exportarResumenPlanilla(props.periodo.id, format);
    } catch (error) {
        notify('error', 'No se pudo exportar el resumen');
    }
};

onMounted(() => {
    fetchResumen(props.periodo.id).catch(() => { /* estado vacío en la vista */ });
});
</script>
