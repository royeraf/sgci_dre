<template>
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-5xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Percent class="w-6 h-6" />
                            Parámetros SBS AFP
                        </h3>
                        <p class="text-sky-100 text-sm mt-1">
                            Vigentes por mes de devengue: aporte obligatorio, prima de seguro, remuneración máxima
                            asegurable y comisiones (flujo / saldo)
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-sky-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-6">
                    <div v-if="loading" class="py-12 text-center">
                        <Loader2 class="w-6 h-6 text-sky-500 animate-spin mx-auto" />
                    </div>

                    <template v-else>
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">
                                Parámetros por mes
                            </h4>
                            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Mes</th>
                                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Aporte</th>
                                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Prima seguro</th>
                                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Rem. máx. asegurable</th>
                                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="param in parametros" :key="param.id" class="hover:bg-slate-50/70">
                                            <td class="px-4 py-3 font-bold text-slate-800">{{ formatMes(param.mes) }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <input v-model.number="param.aporte_obligatorio" type="number" min="0" max="100" step="0.01"
                                                    class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                                                <span class="ml-1 text-slate-400 font-bold">%</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <input v-model.number="param.prima_seguro" type="number" min="0" max="100" step="0.01"
                                                    class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                                                <span class="ml-1 text-slate-400 font-bold">%</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <span class="text-slate-400 font-bold mr-1">S/</span>
                                                <input v-model.number="param.remuneracion_maxima_asegurable" type="number" min="0" step="0.01"
                                                    class="w-28 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button @click="saveParametro(param)" :disabled="saving" title="Guardar parámetro"
                                                    class="cursor-pointer p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 disabled:opacity-50 transition-all">
                                                    <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                                                    <Save v-else class="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">
                                Comisiones por AFP y mes
                            </h4>
                            <div class="rounded-2xl border border-slate-200 overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead class="bg-slate-50 text-slate-500">
                                        <tr>
                                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Mes</th>
                                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">AFP</th>
                                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">% Flujo</th>
                                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">% Saldo</th>
                                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <tr v-for="com in comisiones" :key="com.id" class="hover:bg-slate-50/70">
                                            <td class="px-4 py-3 font-bold text-slate-800">{{ formatMes(com.mes) }}</td>
                                            <td class="px-4 py-3 text-slate-600">{{ com.regimen || '—' }}</td>
                                            <td class="px-4 py-3 text-right">
                                                <input v-model.number="com.comision_flujo" type="number" min="0" max="100" step="0.01"
                                                    class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                                                <span class="ml-1 text-slate-400 font-bold">%</span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <input v-model.number="com.comision_saldo" type="number" min="0" max="100" step="0.01"
                                                    class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                                                <span class="ml-1 text-slate-400 font-bold">%</span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button @click="saveComision(com)" :disabled="saving" title="Guardar comisión"
                                                    class="cursor-pointer p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 disabled:opacity-50 transition-all">
                                                    <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                                                    <Save v-else class="w-4 h-4" />
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="px-4 py-3 rounded-xl bg-sky-50 border border-sky-100">
                            <p class="text-xs text-sky-700 font-medium">
                                La comisión que descuenta la planilla es la de <b>flujo</b> y solo para empleados con tipo
                                de comisión FLUJO: la MIXTA tiene componente flujo 0% desde febrero 2023 y la SALDO se
                                cobra sobre el saldo administrado (no es retención mensual). Los cambios se aplican al
                                volver a generar la planilla.
                            </p>
                        </div>
                    </template>
                </div>

                <div class="px-6 py-4 border-t border-slate-200 flex justify-end bg-slate-50">
                    <button @click="$emit('close')"
                        class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-white font-bold transition-all">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { Percent, X, Save, Loader2 } from 'lucide-vue-next';
import { usePlanillaParametrosAfp } from '@/Composables/usePlanillaParametrosAfp';

const emit = defineEmits(['close']);

const { parametros, comisiones, loading, saving, fetchParametrosAfp, actualizarParametroAfp, actualizarComisionAfp } =
    usePlanillaParametrosAfp();

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

const formatMes = (fecha) => {
    const [anio, mes] = String(fecha).split('-');
    const nombres = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];
    return `${nombres[Number(mes) - 1]} ${anio}`;
};

const saveParametro = async (param) => {
    try {
        await actualizarParametroAfp(param.id, {
            aporte_obligatorio: Number(param.aporte_obligatorio) / 100,
            prima_seguro: Number(param.prima_seguro) / 100,
            remuneracion_maxima_asegurable: Number(param.remuneracion_maxima_asegurable),
        });
        notify('success', `Parámetro ${formatMes(param.mes)} actualizado`);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el parámetro');
    }
};

const saveComision = async (com) => {
    try {
        await actualizarComisionAfp(com.id, {
            comision_flujo: Number(com.comision_flujo) / 100,
            comision_saldo: Number(com.comision_saldo) / 100,
        });
        notify('success', `Comisión ${formatMes(com.mes)} actualizada`);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar la comisión');
    }
};

onMounted(fetchParametrosAfp);
</script>
