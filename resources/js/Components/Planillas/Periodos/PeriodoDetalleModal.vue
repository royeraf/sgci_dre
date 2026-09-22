<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full z-10 overflow-hidden flex flex-col max-h-[90vh]">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            Detalle de Planilla
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">
                            {{ detalle.periodo.nombre_periodo }} · {{ detalle.periodo.total_empleados }} empleados ·
                            Neto S/ {{ formatMoney(detalle.periodo.total_neto) }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 sticky top-0 z-10">
                            <tr>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">N°</th>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">DNI</th>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Empleado</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Base</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Ingresos</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Descuentos</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Aportes</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Neto</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="detalle.detalles.length === 0">
                                <td colspan="9" class="py-16 text-center text-slate-500 font-medium">
                                    La planilla no tiene detalle. Genere la planilla primero.
                                </td>
                            </tr>
                            <template v-for="(d, index) in detalle.detalles" :key="d.id">
                                <tr class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 text-slate-400 font-bold">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ d.dni || '—' }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ d.nombre_completo }}</td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ formatMoney(d.remuneracion_base) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ formatMoney(d.total_ingresos) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700 font-semibold">{{ formatMoney(d.total_descuentos) }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-700 font-semibold">{{ formatMoney(d.total_aportaciones) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ formatMoney(d.neto_pagar) }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <button @click="toggle(d.id)" :disabled="d.items.length === 0"
                                            class="cursor-pointer p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-30 transition-all"
                                            :title="isExpanded(d.id) ? 'Ocultar conceptos' : 'Ver conceptos'">
                                            <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isExpanded(d.id) }" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="isExpanded(d.id)" class="bg-slate-50/60">
                                    <td colspan="9" class="px-4 py-4">
                                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                                            <!-- Ingresos -->
                                            <div class="rounded-xl border border-emerald-100 overflow-hidden bg-white">
                                                <div class="px-3 py-2 bg-emerald-50 flex items-center justify-between">
                                                    <span class="text-[10px] font-bold text-emerald-700 uppercase tracking-widest">Ingresos</span>
                                                    <span class="text-xs font-bold text-emerald-700">S/ {{ formatMoney(sumBy(d, 'INGRESO')) }}</span>
                                                </div>
                                                <table class="w-full text-xs">
                                                    <thead class="text-slate-400">
                                                        <tr>
                                                            <th class="text-left font-bold uppercase tracking-widest px-3 py-2">Concepto</th>
                                                            <th class="text-right font-bold uppercase tracking-widest px-3 py-2">%</th>
                                                            <th class="text-right font-bold uppercase tracking-widest px-3 py-2">Monto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="item in itemsBy(d, 'INGRESO')" :key="item.id">
                                                            <td class="px-3 py-2 text-slate-700">{{ item.descripcion }}</td>
                                                            <td class="px-3 py-2 text-right text-slate-400">{{ pct(item) }}</td>
                                                            <td class="px-3 py-2 text-right font-bold text-slate-800">{{ formatMoney(item.monto) }}</td>
                                                        </tr>
                                                        <tr v-if="itemsBy(d, 'INGRESO').length === 0">
                                                            <td colspan="3" class="px-3 py-3 text-center text-slate-400">Sin ingresos</td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot class="bg-emerald-50/70 border-t border-emerald-100">
                                                        <tr>
                                                            <td colspan="2" class="px-3 py-2 text-right font-bold text-emerald-700 uppercase text-[10px] tracking-widest">Total Ingresos</td>
                                                            <td class="px-3 py-2 text-right font-bold text-emerald-700">{{ formatMoney(sumBy(d, 'INGRESO')) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>

                                            <!-- Descuentos -->
                                            <div class="rounded-xl border border-rose-100 overflow-hidden bg-white">
                                                <div class="px-3 py-2 bg-rose-50 flex items-center justify-between">
                                                    <span class="text-[10px] font-bold text-rose-700 uppercase tracking-widest">Descuentos</span>
                                                    <span class="text-xs font-bold text-rose-700">S/ {{ formatMoney(sumBy(d, 'DESCUENTO')) }}</span>
                                                </div>
                                                <table class="w-full text-xs">
                                                    <thead class="text-slate-400">
                                                        <tr>
                                                            <th class="text-left font-bold uppercase tracking-widest px-3 py-2">Concepto</th>
                                                            <th class="text-right font-bold uppercase tracking-widest px-3 py-2">%</th>
                                                            <th class="text-right font-bold uppercase tracking-widest px-3 py-2">Monto</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-slate-100">
                                                        <tr v-for="item in itemsBy(d, 'DESCUENTO')" :key="item.id">
                                                            <td class="px-3 py-2 text-slate-700">{{ item.descripcion }}</td>
                                                            <td class="px-3 py-2 text-right text-slate-400">{{ pct(item) }}</td>
                                                            <td class="px-3 py-2 text-right font-bold text-rose-700">{{ formatMoney(item.monto) }}</td>
                                                        </tr>
                                                        <tr v-if="itemsBy(d, 'DESCUENTO').length === 0">
                                                            <td colspan="3" class="px-3 py-3 text-center text-slate-400">Sin descuentos</td>
                                                        </tr>
                                                    </tbody>
                                                    <tfoot class="bg-rose-50/70 border-t border-rose-100">
                                                        <tr>
                                                            <td colspan="2" class="px-3 py-2 text-right font-bold text-rose-700 uppercase text-[10px] tracking-widest">Total Descuentos</td>
                                                            <td class="px-3 py-2 text-right font-bold text-rose-700">{{ formatMoney(sumBy(d, 'DESCUENTO')) }}</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Aportaciones del empleador (si aplica) -->
                                        <div v-if="itemsBy(d, 'APORTACION').length > 0"
                                            class="mt-4 max-w-md rounded-xl border border-indigo-100 overflow-hidden bg-white">
                                            <div class="px-3 py-2 bg-indigo-50 flex items-center justify-between">
                                                <span class="text-[10px] font-bold text-indigo-700 uppercase tracking-widest">Aportaciones del Empleador</span>
                                                <span class="text-xs font-bold text-indigo-700">S/ {{ formatMoney(sumBy(d, 'APORTACION')) }}</span>
                                            </div>
                                            <table class="w-full text-xs">
                                                <tbody class="divide-y divide-slate-100">
                                                    <tr v-for="item in itemsBy(d, 'APORTACION')" :key="item.id">
                                                        <td class="px-3 py-2 text-slate-700">{{ item.descripcion }}</td>
                                                        <td class="px-3 py-2 text-right text-slate-400">{{ pct(item) }}</td>
                                                        <td class="px-3 py-2 text-right font-bold text-indigo-700">{{ formatMoney(item.monto) }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-4 flex justify-end">
                                            <div class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-bold">
                                                Neto a pagar: S/ {{ formatMoney(d.neto_pagar) }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { CalendarRange, X, ChevronDown } from 'lucide-vue-next';

defineProps({
    detalle: { type: Object, required: true },
});

defineEmits(['close']);

const expanded = ref([]);

const toggle = (id) => {
    const index = expanded.value.indexOf(id);
    if (index === -1) expanded.value.push(id);
    else expanded.value.splice(index, 1);
};

const isExpanded = (id) => expanded.value.includes(id);

const formatMoney = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const itemsBy = (detalle, tipo) => (detalle.items || []).filter((item) => item.tipo === tipo);

const sumBy = (detalle, tipo) => itemsBy(detalle, tipo).reduce((sum, item) => sum + Number(item.monto), 0);

const pct = (item) => (item.porcentaje !== null && item.porcentaje !== undefined)
    ? `${(item.porcentaje * 100).toFixed(2)}%`
    : '—';
</script>
