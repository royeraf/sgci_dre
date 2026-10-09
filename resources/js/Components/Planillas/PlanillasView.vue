<template>
    <div class="space-y-6">
        <BaseTableCard
            title="Planillas Mensuales"
            description="Periodos de planilla CAS y generación del cálculo"
        >
            <template #icon>
                <Wallet class="w-6 h-6 text-teal-600" />
            </template>

            <template #actions>
                <button @click="showParametrosModal = true"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition-all duration-200">
                    <Landmark class="w-4 h-4 mr-2" />
                    Parámetros
                </button>
                <button @click="showParametrosAfpModal = true"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-sky-200 bg-sky-50 text-sky-700 hover:bg-sky-100 transition-all duration-200">
                    <Percent class="w-4 h-4 mr-2" />
                    Parámetros SBS
                </button>
                <button @click="showPeriodoModal = true"
                    class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-teal-500/30 text-white bg-gradient-to-r from-teal-600 to-cyan-600 hover:from-teal-700 hover:to-cyan-700 transition-all duration-300 hover:-translate-y-0.5">
                    <Plus class="w-4 h-4 mr-2" />
                    Nuevo Periodo
                </button>
            </template>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Periodo</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Fechas</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Estado</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Empleados</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Total Neto</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="6" class="py-16 text-center">
                                <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                            </td>
                        </tr>
                        <tr v-else-if="periodos.length === 0">
                            <td colspan="6" class="py-16 text-center text-slate-500 font-medium">
                                No hay planillas registradas. Cree un periodo para comenzar.
                            </td>
                        </tr>
                        <template v-else>
                            <tr v-for="periodo in periodos" :key="periodo.id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-bold text-slate-800">{{ periodo.nombre_periodo }}</td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ rango(periodo) }}</td>
                                <td class="px-5 py-3 text-center">
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full" :class="estadoClass(periodo.estado)">
                                        {{ periodo.estado }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right text-slate-600">{{ periodo.total_empleados }}</td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">S/ {{ formatMoney(periodo.total_neto) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button v-if="periodo.estado === 'CALCULADA'" @click="onCambiarEstado(periodo, 'APROBADA')"
                                            :disabled="saving" title="Aprobar planilla (publica las boletas en el portal)"
                                            class="cursor-pointer p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                            <BadgeCheck class="w-4 h-4" />
                                        </button>
                                        <button v-if="periodo.estado === 'APROBADA'" @click="onCambiarEstado(periodo, 'PAGADA')"
                                            :disabled="saving" title="Marcar como pagada"
                                            class="cursor-pointer p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                            <Banknote class="w-4 h-4" />
                                        </button>
                                        <button v-if="periodo.estado === 'APROBADA' || periodo.estado === 'PAGADA'"
                                            @click="onCambiarEstado(periodo, 'CERRADA')" :disabled="saving" title="Cerrar planilla"
                                            class="cursor-pointer p-2 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                            <Lock class="w-4 h-4" />
                                        </button>
                                        <button @click="onGenerar(periodo)" :disabled="!periodo.editable || saving"
                                            title="Generar / recalcular"
                                            class="cursor-pointer p-2 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                            <Play class="w-4 h-4" />
                                        </button>
                                        <button @click="onVer(periodo)" title="Ver detalle"
                                            class="cursor-pointer p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button @click="onResumen(periodo)" title="Ver resumen"
                                            class="cursor-pointer p-2 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-100 transition-all">
                                            <FileSpreadsheet class="w-4 h-4" />
                                        </button>
                                        <button @click="onEliminar(periodo)" :disabled="!periodo.editable || saving" title="Eliminar"
                                            class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 disabled:opacity-40 disabled:cursor-not-allowed transition-all">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </BaseTableCard>

        <PeriodoModal v-if="showPeriodoModal" :saving="saving" @close="showPeriodoModal = false"
            @submit="submitPeriodo" />

        <PeriodoDetalleModal v-if="showDetalleModal && detalle" :detalle="detalle"
            @close="showDetalleModal = false" @refresh="onRefreshDetalle" />

        <ResumenModal v-if="showResumenModal && resumenPeriodo" :periodo="resumenPeriodo"
            @close="showResumenModal = false" />

        <ParametrosPlanillaModal v-if="showParametrosModal" @close="showParametrosModal = false" />

        <ParametrosAfpModal v-if="showParametrosAfpModal" @close="showParametrosAfpModal = false" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Wallet, Plus, Play, Eye, Trash2, Loader2, Landmark, Percent, FileSpreadsheet, BadgeCheck, Banknote, Lock } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import PeriodoModal from '@/Components/Planillas/Periodos/PeriodoModal.vue';
import PeriodoDetalleModal from '@/Components/Planillas/Periodos/PeriodoDetalleModal.vue';
import ResumenModal from '@/Components/Planillas/Periodos/ResumenModal.vue';
import ParametrosPlanillaModal from '@/Components/Planillas/ParametrosPlanillaModal.vue';
import ParametrosAfpModal from '@/Components/Planillas/ParametrosAfpModal.vue';
import { usePlanillaPeriodos } from '@/Composables/usePlanillaPeriodos';

const {
    periodos,
    detalle,
    loading,
    saving,
    fetchPeriodos,
    crearPeriodo,
    generarPeriodo,
    eliminarPeriodo,
    cambiarEstadoPeriodo,
    fetchDetalle,
} = usePlanillaPeriodos();

const showPeriodoModal = ref(false);
const showDetalleModal = ref(false);
const showResumenModal = ref(false);
const resumenPeriodo = ref(null);
const showParametrosModal = ref(false);
const showParametrosAfpModal = ref(false);

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

const formatDate = (value) => {
    if (!value) return null;
    const fecha = String(value).slice(0, 10);
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return value;
    return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

const rango = (periodo) => {
    if (!periodo.fecha_inicio || !periodo.fecha_fin) return '—';
    return `${formatDate(periodo.fecha_inicio)} → ${formatDate(periodo.fecha_fin)}`;
};

const estadoClass = (estado) => ({
    'bg-slate-100 text-slate-600': estado === 'BORRADOR',
    'bg-blue-100 text-blue-700': estado === 'CALCULADA',
    'bg-indigo-100 text-indigo-700': estado === 'APROBADA',
    'bg-emerald-100 text-emerald-700': estado === 'PAGADA',
    'bg-slate-800 text-white': estado === 'CERRADA',
});

const submitPeriodo = async (payload) => {
    try {
        await crearPeriodo(payload);
        showPeriodoModal.value = false;
        notify('success', 'Periodo creado correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo crear el periodo');
    }
};

const onGenerar = async (periodo) => {
    const result = await window.Swal?.fire({
        icon: 'question',
        title: '¿Generar planilla?',
        html: `<p>Se (re)calculará <strong>${periodo.nombre_periodo}</strong>.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Generar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0d9488',
    });

    if (!result?.isConfirmed) return;

    try {
        const resumen = await generarPeriodo(periodo.id);
        notify('success', `Planilla generada: ${resumen.empleados} empleados`);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo generar la planilla');
    }
};

const onCambiarEstado = async (periodo, destino) => {
    const avisos = {
        APROBADA: {
            icon: 'question',
            title: '¿Aprobar planilla?',
            html: `<p>Al aprobar <strong>${periodo.nombre_periodo}</strong>, sus boletas quedan visibles en el portal público por DNI y la planilla deja de admitir cambios.</p>`,
            confirmButtonText: 'Aprobar',
            confirmButtonColor: '#059669',
            success: 'Planilla aprobada. Boletas disponibles en el portal',
        },
        PAGADA: {
            icon: 'question',
            title: '¿Marcar como pagada?',
            html: `<p>Se registrará <strong>${periodo.nombre_periodo}</strong> como pagada.</p>`,
            confirmButtonText: 'Marcar pagada',
            confirmButtonColor: '#059669',
            success: 'Planilla marcada como pagada',
        },
        CERRADA: {
            icon: 'warning',
            title: '¿Cerrar planilla?',
            html: `<p><strong>${periodo.nombre_periodo}</strong> quedará cerrada y no podrá modificarse.</p>`,
            confirmButtonText: 'Sí, cerrar',
            confirmButtonColor: '#475569',
            success: 'Planilla cerrada',
        },
    };

    const aviso = avisos[destino];

    const result = await window.Swal?.fire({
        icon: aviso.icon,
        title: aviso.title,
        html: aviso.html,
        showCancelButton: true,
        confirmButtonText: aviso.confirmButtonText,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: aviso.confirmButtonColor,
    });

    if (!result?.isConfirmed) return;

    try {
        await cambiarEstadoPeriodo(periodo.id, destino);
        notify('success', aviso.success);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el estado de la planilla');
    }
};

const onVer = async (periodo) => {
    try {
        await fetchDetalle(periodo.id);
        showDetalleModal.value = true;
    } catch (error) {
        notify('error', 'No se pudo cargar el detalle');
    }
};

const onResumen = (periodo) => {
    resumenPeriodo.value = periodo;
    showResumenModal.value = true;
};

const onRefreshDetalle = async (id) => {
    try {
        await fetchDetalle(id);
        await fetchPeriodos();
    } catch (error) {
        notify('error', 'No se pudo actualizar el detalle');
    }
};

const onEliminar = async (periodo) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar periodo?',
        html: `<p><strong>${periodo.nombre_periodo}</strong> y su detalle.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarPeriodo(periodo.id);
        notify('success', 'Periodo eliminado correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar el periodo');
    }
};

onMounted(fetchPeriodos);
</script>
