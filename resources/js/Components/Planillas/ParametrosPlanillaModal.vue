<template>
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Landmark class="w-6 h-6" />
                            Parámetros de Planilla
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">
                            Base imponible de EsSalud (CAS): tope = UIT × % (art. 6 inc. k, DL 1057) · mínimo = RMV
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 max-h-[75vh] overflow-y-auto">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-indigo-500 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="parametros.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            No hay parámetros configurados.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Año</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">UIT</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">% Tope</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Tope EsSalud</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">RMV</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Tasa</th>
                                    <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Estado</th>
                                    <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="param in parametros" :key="param.id" class="hover:bg-slate-50/70">
                                    <td class="px-4 py-3 font-bold text-slate-800">{{ param.anio }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <span class="text-slate-400 font-bold mr-1">S/</span>
                                        <input v-model.number="param.uit" type="number" min="1" step="0.01"
                                            class="w-24 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input v-model.number="param.pct_tope_essalud" type="number" min="0" max="100" step="0.01"
                                            class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                                        <span class="ml-1 text-slate-400 font-bold">%</span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold text-indigo-700">
                                        S/ {{ formatMoney(calcularTope(param)) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input v-model.number="param.rmv" type="number" min="0" step="0.01" placeholder="—"
                                            class="w-24 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 placeholder:text-slate-300 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input v-model.number="param.tasa_essalud" type="number" min="0" max="100" step="0.01"
                                            class="w-20 px-3 py-1.5 border-2 border-slate-200 rounded-lg text-right text-sm text-slate-800 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                                        <span class="ml-1 text-slate-400 font-bold">%</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <label class="inline-flex items-center gap-2 cursor-pointer">
                                            <input v-model="param.activo" type="checkbox"
                                                class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer" />
                                            <span class="text-xs font-bold" :class="param.activo ? 'text-emerald-600' : 'text-slate-400'">
                                                {{ param.activo ? 'Vigente' : 'Inactivo' }}
                                            </span>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="saveParametro(param)" :disabled="saving" title="Guardar parámetro"
                                            class="cursor-pointer p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 disabled:opacity-50 transition-all">
                                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                                            <Save v-else class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 px-4 py-3 rounded-xl bg-indigo-50 border border-indigo-100">
                        <p class="text-xs text-indigo-700 font-medium">
                            El tope se recalcula como UIT × %Tope y se aplica al generar la planilla. Si el año consultado
                            no tiene fila, se usa la última vigente ≤ año. Los cambios no afectan periodos ya generados
                            hasta volver a generarlos.
                        </p>
                    </div>
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
import { Landmark, X, Save, Loader2 } from 'lucide-vue-next';
import { usePlanillaParametros } from '@/Composables/usePlanillaParametros';

const emit = defineEmits(['close']);

const { parametros, loading, saving, fetchParametros, actualizarParametro } = usePlanillaParametros();

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

const formatMoney = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const calcularTope = (param) => (Number(param.uit) || 0) * ((Number(param.pct_tope_essalud) || 0) / 100);

const saveParametro = async (param) => {
    try {
        await actualizarParametro(param.id, {
            uit: Number(param.uit),
            pct_tope_essalud: Number(param.pct_tope_essalud) / 100,
            rmv: param.rmv === null || param.rmv === '' ? null : Number(param.rmv),
            tasa_essalud: Number(param.tasa_essalud) / 100,
            activo: Boolean(param.activo),
        });
        notify('success', `Parámetro ${param.anio} actualizado`);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el parámetro');
    }
};

onMounted(fetchParametros);
</script>
