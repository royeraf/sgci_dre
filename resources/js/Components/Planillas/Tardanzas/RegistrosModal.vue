<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-5xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-rose-600 to-orange-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Clock class="w-6 h-6" />
                            Tardanzas
                        </h3>
                        <p class="text-rose-50 text-sm mt-1">
                            {{ fila?.nombre_completo || '—' }} · {{ periodo?.nombre_periodo }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-rose-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="px-6 pt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
                    <div class="rounded-xl bg-teal-50 border border-teal-100 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-teal-600">Remuneración (E)</p>
                        <p class="font-bold text-slate-800 tabular-nums">S/ {{ money(fila?.remuneraciones) }}</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600">Por día (F)</p>
                        <p class="font-bold text-slate-800 tabular-nums">S/ {{ money(fila?.valor_dia) }}</p>
                    </div>
                    <div class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-amber-600">Por minuto (G)</p>
                        <p class="font-bold text-slate-800 tabular-nums">S/ {{ money(fila?.valor_minuto, 4) }}</p>
                    </div>
                    <div class="rounded-xl bg-rose-50 border border-rose-100 px-3 py-2">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-rose-600">Total a descontar</p>
                        <p class="font-bold text-rose-700 tabular-nums">S/ {{ money(fila?.total) }}</p>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">Registros diarios</p>
                        <button v-if="periodo?.editable" @click="$emit('add')"
                            class="cursor-pointer inline-flex items-center text-xs font-bold text-rose-600 hover:text-rose-700">
                            <Plus class="w-4 h-4 mr-1" />
                            Agregar día
                        </button>
                    </div>

                    <div v-if="!fila || !fila.registros?.length"
                        class="rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                        <Clock class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                        <p class="text-sm font-medium text-slate-500">Sin registros de tardanza para este empleado</p>
                    </div>

                    <div v-else class="rounded-xl border border-slate-200 overflow-hidden">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-50 text-slate-500 text-[10px] uppercase tracking-wider">
                                <tr>
                                    <th class="text-left font-bold px-4 py-2">Fecha</th>
                                    <th class="text-right font-bold px-4 py-2">Días</th>
                                    <th class="text-right font-bold px-4 py-2">Minutos</th>
                                    <th class="text-right font-bold px-4 py-2">Monto Días</th>
                                    <th class="text-right font-bold px-4 py-2">Monto Minutos</th>
                                    <th class="text-right font-bold px-4 py-2">Total</th>
                                    <th class="text-left font-bold px-4 py-2">Observación</th>
                                    <th class="text-center font-bold px-4 py-2">Estado</th>
                                    <th v-if="periodo?.editable" class="text-center font-bold px-4 py-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="r in fila.registros" :key="r.id"
                                    :class="r.justificado ? 'bg-emerald-50/40 text-slate-400' : ''">
                                    <td class="px-4 py-2 whitespace-nowrap text-slate-700">{{ formatDate(r.fecha) }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ r.dias || '—' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ r.minutos || '—' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ r.monto_dias ? money(r.monto_dias) : '—' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums">{{ r.monto_minutos ? money(r.monto_minutos) : '—' }}</td>
                                    <td class="px-4 py-2 text-right tabular-nums font-bold"
                                        :class="r.justificado ? 'text-slate-400 line-through' : 'text-rose-700'">
                                        {{ money(r.total) }}
                                    </td>
                                    <td class="px-4 py-2 text-slate-500">{{ r.observacion || '—' }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full whitespace-nowrap"
                                            :class="r.justificado ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                            {{ r.justificado ? 'Justificado' : 'Descuenta' }}
                                        </span>
                                    </td>
                                    <td v-if="periodo?.editable" class="px-4 py-2">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="$emit('toggle', r)"
                                                :title="r.justificado ? 'Quitar justificación' : 'Justificar (excluir del descuento)'"
                                                class="cursor-pointer p-1.5 rounded-lg transition-all"
                                                :class="r.justificado ? 'bg-slate-100 text-slate-500 hover:bg-slate-200' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'">
                                                <ShieldCheck class="w-4 h-4" />
                                            </button>
                                            <button @click="$emit('edit', r)" title="Editar"
                                                class="cursor-pointer p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="$emit('remove', r)" title="Eliminar"
                                                class="cursor-pointer p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Clock, X, Plus, ShieldCheck, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    periodo: { type: Object, default: null },
    fila: { type: Object, default: null },
});

defineEmits(['close', 'add', 'edit', 'toggle', 'remove']);

const money = (value, digits = 2) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
});

const formatDate = (value) => {
    if (!value) return '—';
    const [y, m, d] = value.split('-');
    return `${d}/${m}/${y}`;
};
</script>
