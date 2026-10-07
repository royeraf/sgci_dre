<template>
    <div class="space-y-6">
        <BaseTableCard
            title="Gratificaciones del Personal"
            description="Corte: remuneración al 30/06 (Fiestas Patrias) o 30/11 (Navidad)"
            searchPlaceholder="Buscar por nombre o DNI..."
            :searchValue="search"
            @update:searchValue="search = $event"
        >
            <template #actions>
                <button @click="showParametrosModal = true"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-teal-200 bg-teal-50 text-teal-700 hover:bg-teal-100 transition-all duration-200">
                    <Scale class="w-4 h-4 mr-2" />
                    Parámetros legales
                </button>
                <select v-model="anio"
                    class="px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm font-bold text-slate-700 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none cursor-pointer">
                    <option v-for="year in anioOptions" :key="year" :value="year">{{ year }}</option>
                </select>
                <select v-model="periodo"
                    class="px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm font-bold text-slate-700 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none cursor-pointer">
                    <option value="JULIO">Julio (FP)</option>
                    <option value="DICIEMBRE">Diciembre (Navidad)</option>
                </select>
                <span v-if="esPreview"
                    class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">
                    Vista previa · sin guardar
                </span>
                <button @click="onPreview"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-slate-200 bg-white text-slate-600 hover:bg-slate-50 transition-all duration-200">
                    <Eye class="w-4 h-4 mr-2" />
                    Previsualizar
                </button>
                <button @click="onGenerar"
                    class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-teal-500/30 text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 transition-all duration-300 hover:-translate-y-0.5 disabled:opacity-60 disabled:hover:translate-y-0"
                    :disabled="saving || previewing">
                    <Loader2 v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                    <Calculator v-else class="w-4 h-4 mr-2" />
                    Generar
                </button>
            </template>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">N°</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">DNI</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Empleado</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Cargo</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Ingreso</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Rem. corte</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">%</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Meses</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Días</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Base semestral</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Proporcional</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Mínimo</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Total</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">EsSalud 9%</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading || previewing">
                            <td colspan="14" class="py-16 text-center">
                                <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                            </td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td colspan="14" class="py-16 text-center text-slate-500 font-medium">
                                No hay cálculos para este periodo. Use «Previsualizar» o «Generar».
                            </td>
                        </tr>
                        <template v-else>
                            <tr v-for="(row, index) in paginatedRows" :key="row.employee_id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 text-slate-400 font-bold">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ row.dni || '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ row.nombre_completo }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ row.cargo || '—' }}</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ row.fecha_ingreso || '—' }}</td>
                                <td class="px-5 py-3 text-right text-slate-700">{{ formatMoney(row.remuneracion_corte) }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-teal-100 text-teal-700">
                                        {{ Math.round(row.porcentaje_aplicado * 100) }}%
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-center font-bold text-slate-700">{{ row.meses_completos }}</td>
                                <td class="px-5 py-3 text-center font-bold text-slate-700">{{ row.dias }}</td>
                                <td class="px-5 py-3 text-right text-slate-600">{{ formatMoney(row.base_semestral) }}</td>
                                <td class="px-5 py-3 text-right text-slate-600">{{ formatMoney(row.monto_proporcional) }}</td>
                                <td class="px-5 py-3 text-right">
                                    <span v-if="row.monto_minimo !== null" class="text-amber-600 font-semibold">{{ formatMoney(row.monto_minimo) }}</span>
                                    <span v-else class="text-slate-300">—</span>
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">{{ formatMoney(row.monto_final) }}</td>
                                <td class="px-5 py-3 text-right text-indigo-600 font-semibold">{{ formatMoney(row.aporte_essalud) }}</td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot v-if="!loading && !previewing && filteredRows.length > 0" class="bg-slate-50 border-t-2 border-slate-200">
                        <tr>
                            <td colspan="12" class="px-5 py-3 text-right font-bold uppercase text-[11px] tracking-widest text-slate-500">
                                Totales ({{ filteredRows.length }} servidores)
                            </td>
                            <td class="px-5 py-3 text-right font-extrabold text-slate-900">{{ formatMoney(total) }}</td>
                            <td class="px-5 py-3 text-right font-extrabold text-indigo-600">{{ formatMoney(totalEssalud) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <ClientPagination :total-items="filteredRows.length" :current-page="currentPage" :per-page="perPage"
                @update:current-page="currentPage = $event" @update:per-page="perPage = $event" />
        </BaseTableCard>

        <ParametrosGratificacionModal v-if="showParametrosModal" @close="showParametrosModal = false"
            @changed="fetchListado(anio, periodo)" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Loader2, Eye, Calculator, Scale } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import ParametrosGratificacionModal from '@/Components/Planillas/Gratificaciones/ParametrosGratificacionModal.vue';
import { useGratificaciones } from '@/Composables/useGratificaciones';

const {
    rows,
    parametros,
    total,
    totalEssalud,
    esPreview,
    loading,
    previewing,
    saving,
    fetchParametros,
    fetchListado,
    previsualizar,
    generar,
} = useGratificaciones();

const hoy = new Date();
const anio = ref(hoy.getFullYear());
const periodo = ref(hoy.getMonth() + 1 >= 7 ? 'DICIEMBRE' : 'JULIO');
const search = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const showParametrosModal = ref(false);

const anioOptions = computed(() => {
    const years = new Set(parametros.value.map((p) => p.anio_fiscal));
    years.add(anio.value);
    years.add(hoy.getFullYear());
    return [...years].sort((a, b) => a - b);
});

const normalize = (text) => (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

const filteredRows = computed(() => {
    const term = normalize(search.value).trim();
    if (!term) return rows.value;
    return rows.value.filter((row) =>
        normalize(row.nombre_completo).includes(term)
        || (row.dni || '').includes(term)
        || normalize(row.cargo).includes(term)
    );
});

const paginatedRows = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredRows.value.slice(start, start + perPage.value);
});

watch([search, anio, periodo], () => {
    currentPage.value = 1;
});

watch(anio, () => {
    fetchListado(anio.value, periodo.value);
});

watch(periodo, () => {
    fetchListado(anio.value, periodo.value);
});

const formatMoney = (value) => `S/ ${Number(value || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

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

const onPreview = async () => {
    try {
        await previsualizar(anio.value, periodo.value);
        notify('success', 'Vista previa calculada (no guardada)');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo calcular la vista previa');
    }
};

const onGenerar = async () => {
    const confirm = await window.Swal?.fire({
        icon: 'question',
        title: `Generar gratificaciones ${periodo.value} ${anio.value}`,
        text: 'Se guardarán los cálculos de todos los servidores CAS activos.',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
    });

    if (confirm?.isConfirmed === false) return;

    try {
        const message = await generar(anio.value, periodo.value);
        notify('success', message);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudieron generar las gratificaciones');
    }
};

onMounted(async () => {
    await fetchParametros();
    await fetchListado(anio.value, periodo.value);
});
</script>
