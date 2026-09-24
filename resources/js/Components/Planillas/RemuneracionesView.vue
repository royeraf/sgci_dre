<template>
    <div class="space-y-6">
        <BaseTableCard
            title="Remuneraciones del Personal"
            description="Remuneración base (DL 1057) y datos de pensión por empleado"
            searchPlaceholder="Buscar por nombre, DNI o cargo..."
            :searchValue="search"
            @update:searchValue="search = $event"
        >
            <template #actions>
                <button @click="showConceptosModal = true"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 transition-all duration-200">
                    <Coins class="w-4 h-4 mr-2" />
                    Remuneraciones (Ingresos)
                </button>
                <button @click="openRemuneracion()"
                    class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-teal-500/30 text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 transition-all duration-300 hover:-translate-y-0.5">
                    <Plus class="w-4 h-4 mr-2" />
                    Nueva Remuneración
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
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Régimen</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Contrato</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Base (DL 1057)</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Pensión</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="9" class="py-16 text-center">
                                <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                            </td>
                        </tr>
                        <tr v-else-if="filteredRows.length === 0">
                            <td colspan="9" class="py-16 text-center text-slate-500 font-medium">
                                No se encontraron empleados.
                            </td>
                        </tr>
                        <template v-else>
                            <tr v-for="(row, index) in paginatedRows" :key="row.id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 text-slate-400 font-bold">{{ (currentPage - 1) * perPage + index + 1 }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ row.dni || '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-800">{{ row.nombre_completo }}</td>
                                <td class="px-5 py-3 text-slate-500">{{ row.cargo || '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-block text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-600">
                                        {{ row.regimen || '—' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="row.fecha_inicio_contrato" class="text-xs font-semibold text-slate-600">
                                        {{ formatDate(row.fecha_inicio_contrato) }}
                                        →
                                        <span v-if="row.fecha_fin_contrato">{{ formatDate(row.fecha_fin_contrato) }}</span>
                                        <span v-else class="inline-block px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-bold">Indeterminado</span>
                                    </span>
                                    <span v-else class="text-xs font-semibold text-slate-400">Sin registrar</span>
                                </td>
                                <td class="px-5 py-3 text-right font-bold"
                                    :class="row.remuneracion_base === null ? 'text-slate-400' : 'text-slate-800'">
                                    {{ formatMoney(row.remuneracion_base) }}
                                </td>
                                <td class="px-5 py-3">
                                    <div v-if="row.tipo_pension" class="flex flex-col">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full w-fit"
                                            :class="row.tipo_pension === 'AFP' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                            {{ row.tipo_pension }}
                                        </span>
                                        <span class="text-[11px] text-slate-400 mt-1">{{ row.cuspp || 'Sin CUSPP' }}</span>
                                    </div>
                                    <span v-else class="text-xs font-semibold text-slate-400">Sin asignar</span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="openRemuneracion(row)" title="Registrar nueva base"
                                            class="cursor-pointer p-2 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 transition-all">
                                            <Wallet class="w-4 h-4" />
                                        </button>
                                        <button @click="openPerfil(row)" title="Editar perfil de pensión"
                                            class="cursor-pointer p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all">
                                            <Landmark class="w-4 h-4" />
                                        </button>
                                        <button @click="openContrato(row)" title="Administrar fechas de contrato"
                                            class="cursor-pointer p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 transition-all">
                                            <CalendarRange class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <ClientPagination :total-items="filteredRows.length" :current-page="currentPage" :per-page="perPage"
                @update:current-page="currentPage = $event" @update:per-page="perPage = $event" />
        </BaseTableCard>

        <ConceptosModal v-if="showConceptosModal" tipo="INGRESO" @close="showConceptosModal = false" />

        <RemuneracionModal v-if="showRemuneracionModal" :employees="rows" :preset-employee-id="presetEmployeeId"
            :saving="saving" @close="closeRemuneracion" @submit="submitRemuneracion" />

        <PerfilPensionModal v-if="showPerfilModal && selectedRow" :row="selectedRow" :regimenes="regimenes"
            :saving="saving" @close="closePerfil" @submit="submitPerfil" @changed="fetchAll" />

        <ContratoModal v-if="showContratoModal && selectedRow" :row="selectedRow" :saving="saving"
            @close="closeContrato" @submit="submitContrato" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Plus, Wallet, Landmark, Loader2, Coins, CalendarRange } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import ConceptosModal from '@/Components/Planillas/Conceptos/ConceptosModal.vue';
import RemuneracionModal from '@/Components/Planillas/Remuneraciones/RemuneracionModal.vue';
import PerfilPensionModal from '@/Components/Planillas/Remuneraciones/PerfilPensionModal.vue';
import ContratoModal from '@/Components/Planillas/Remuneraciones/ContratoModal.vue';
import { useRemuneraciones } from '@/Composables/useRemuneraciones';

const { rows, regimenes, loading, saving, fetchAll, crearRemuneracion, actualizarPerfil, actualizarContrato } = useRemuneraciones();

const search = ref('');
const showRemuneracionModal = ref(false);
const showPerfilModal = ref(false);
const showContratoModal = ref(false);
const showConceptosModal = ref(false);
const selectedRow = ref(null);
const presetEmployeeId = ref(null);
const currentPage = ref(1);
const perPage = ref(10);

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

watch(search, () => {
    currentPage.value = 1;
});

const formatMoney = (value) => {
    if (value === null || value === undefined) return 'Sin registrar';
    return `S/ ${Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const formatDate = (value) => {
    if (!value) return null;
    return new Date(`${value}T00:00:00`).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

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

const openRemuneracion = (row = null) => {
    presetEmployeeId.value = row?.id || null;
    showRemuneracionModal.value = true;
};

const closeRemuneracion = () => {
    showRemuneracionModal.value = false;
    presetEmployeeId.value = null;
};

const submitRemuneracion = async (payload) => {
    try {
        await crearRemuneracion(payload);
        closeRemuneracion();
        notify('success', 'Remuneración registrada correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo registrar la remuneración');
    }
};

const openPerfil = (row) => {
    selectedRow.value = row;
    showPerfilModal.value = true;
};

const closePerfil = () => {
    showPerfilModal.value = false;
    selectedRow.value = null;
};

const submitPerfil = async (payload) => {
    try {
        await actualizarPerfil(selectedRow.value.id, payload);
        closePerfil();
        notify('success', 'Perfil de planilla actualizado');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el perfil');
    }
};

const openContrato = (row) => {
    selectedRow.value = row;
    showContratoModal.value = true;
};

const closeContrato = () => {
    showContratoModal.value = false;
    selectedRow.value = null;
};

const submitContrato = async (payload) => {
    try {
        await actualizarContrato(selectedRow.value.id, payload);
        closeContrato();
        notify('success', 'Fechas de contrato actualizadas');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudieron actualizar las fechas de contrato');
    }
};

onMounted(fetchAll);
</script>
