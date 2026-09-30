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

                <div class="px-4 sm:px-6 py-3 border-b border-slate-100 bg-white shrink-0 flex items-center gap-3">
                    <div class="relative flex-1 max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                        <input v-model="search" type="text" placeholder="Buscar por nombre o DNI..."
                            @keyup.enter="jumpToMatch(1)"
                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all duration-200 text-sm outline-none" />
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-xs font-bold tabular-nums"
                            :class="search.trim() && matches.length === 0 ? 'text-rose-600' : 'text-slate-400'">
                            {{ matchLabel }}
                        </span>
                        <div class="flex items-center gap-0.5">
                            <button type="button" @click="jumpToMatch(-1)" :disabled="!matches.length"
                                title="Resultado anterior"
                                class="cursor-pointer p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                                <ChevronUp class="w-4 h-4" />
                            </button>
                            <button type="button" @click="jumpToMatch(1)" :disabled="!matches.length"
                                title="Siguiente resultado"
                                class="cursor-pointer p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 disabled:opacity-30 disabled:cursor-not-allowed transition-all">
                                <ChevronDown class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div ref="scrollContainer" class="overflow-auto">
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
                        <tbody>
                            <tr v-if="detalle.detalles.length === 0">
                                <td colspan="9" class="py-16 text-center text-slate-500 font-medium">
                                    La planilla no tiene detalle. Genere la planilla primero.
                                </td>
                            </tr>
                            <template v-for="(d, index) in detalle.detalles" :key="d.id">
                                <tr :data-row-id="d.employee_id" class="transition-colors duration-500"
                                    :class="flashEmployeeId === d.employee_id
                                        ? 'bg-amber-100 hover:bg-amber-100/80'
                                        : 'hover:bg-slate-50/70'">
                                    <td class="px-4 py-3 text-slate-400 font-bold">{{ index + 1 }}</td>
                                    <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ d.dni || '—' }}</td>
                                    <td class="px-4 py-3 font-semibold text-slate-800">
                                        {{ d.nombre_completo }}
                                        <span v-if="d.dias_pagados < 30"
                                            title="Contrato con días limitados en este periodo: la básica se paga proporcional"
                                            class="ml-1.5 inline-flex items-center text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-100 text-amber-700 align-middle tabular-nums">
                                            {{ d.dias_pagados }}/30 días
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">{{ formatMoney(d.remuneracion_base) }}</td>
                                    <td class="px-4 py-3 text-right text-emerald-700 font-semibold">{{ formatMoney(d.total_ingresos) }}</td>
                                    <td class="px-4 py-3 text-right text-rose-700 font-semibold">{{ formatMoney(d.total_descuentos) }}</td>
                                    <td class="px-4 py-3 text-right text-indigo-700 font-semibold">{{ formatMoney(d.total_aportaciones) }}</td>
                                    <td class="px-4 py-3 text-right font-bold text-slate-900">{{ formatMoney(d.neto_pagar) }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <button v-if="detalle.periodo.editable" @click="openAsignaciones(d)"
                                            title="Asignar conceptos (Sub CAFAE, etc.)"
                                            class="relative cursor-pointer p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-all mr-1 align-middle">
                                            <ListPlus class="w-4 h-4" />
                                            <span v-if="asignacionesCount(d) > 0"
                                                class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-emerald-600 rounded-full ring-2 ring-white"></span>
                                        </button>
                                        <button v-if="detalle.periodo.editable" @click="openRegistros(d.employee_id)"
                                            title="Tardanzas del empleado"
                                            class="relative cursor-pointer p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-all mr-1 align-middle">
                                            <Clock class="w-4 h-4" />
                                            <span v-if="tardanzasCount(d.employee_id) > 0"
                                                class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-rose-600 rounded-full ring-2 ring-white"></span>
                                        </button>
                                        <button @click="openNotas(d)" title="Notas del empleado"
                                            class="relative cursor-pointer p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 hover:text-amber-700 transition-all mr-1 align-middle">
                                            <NotebookText class="w-4 h-4" />
                                            <span v-if="notasCount(d.employee_id) > 0"
                                                class="absolute -top-0.5 -right-0.5 w-2 h-2 bg-amber-500 rounded-full ring-2 ring-white"></span>
                                        </button>
                                        <button @click="toggle(d.id)" :disabled="d.items.length === 0"
                                            class="cursor-pointer p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 disabled:opacity-30 transition-all align-middle"
                                            :title="isExpanded(d.id) ? 'Ocultar conceptos' : 'Ver conceptos'">
                                            <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': isExpanded(d.id) }" />
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="isExpanded(d.id)" class="bg-slate-50/60">
                                    <td colspan="9" class="p-0">
                                        <div class="bg-white">
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-[10px] font-bold uppercase tracking-widest">
                                                        <th class="px-3 py-2 text-left w-1/3 bg-emerald-50 text-emerald-700">Ingresos</th>
                                                        <th class="px-3 py-2 text-left w-1/3 bg-rose-50 text-rose-700">Descuentos</th>
                                                        <th class="px-3 py-2 text-left w-1/3 bg-indigo-50 text-indigo-700">Aportaciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr v-for="(fila, i) in filas(d)" :key="i" class="align-top">
                                                        <td class="px-3 py-2">
                                                            <div v-if="fila.ingreso" class="flex items-baseline justify-between gap-3">
                                                                <span class="text-slate-600">
                                                                    {{ fila.ingreso.descripcion }}
                                                                    <span v-if="pct(fila.ingreso) !== '\u2014'" class="text-slate-400">({{ pct(fila.ingreso) }})</span>
                                                                </span>
                                                                <span class="font-bold text-slate-900">{{ formatMoney(fila.ingreso.monto) }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <div v-if="fila.descuento" class="flex items-baseline justify-between gap-3">
                                                                <span class="text-slate-600">
                                                                    {{ fila.descuento.descripcion }}
                                                                    <span v-if="pct(fila.descuento) !== '\u2014'" class="text-slate-400">({{ pct(fila.descuento) }})</span>
                                                                </span>
                                                                <span class="font-bold text-rose-700">{{ formatMoney(fila.descuento.monto) }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            <div v-if="fila.aportacion" class="flex items-baseline justify-between gap-3">
                                                                <span class="text-slate-600">
                                                                    {{ fila.aportacion.descripcion }}
                                                                    <span v-if="pct(fila.aportacion) !== '\u2014'" class="text-slate-400">({{ pct(fila.aportacion) }})</span>
                                                                </span>
                                                                <span class="font-bold text-indigo-700">{{ formatMoney(fila.aportacion.monto) }}</span>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr v-if="filas(d).length === 0">
                                                        <td colspan="3" class="px-3 py-4 text-center text-slate-400">Sin conceptos en este periodo</td>
                                                    </tr>
                                                </tbody>
                                                <tfoot class="bg-slate-50 font-bold">
                                                    <tr>
                                                        <td class="px-3 py-2 text-emerald-700">
                                                            Total Ingresos
                                                            <span class="float-right">{{ formatMoney(sumBy(d, 'INGRESO')) }}</span>
                                                        </td>
                                                        <td class="px-3 py-2 text-rose-700">
                                                            Total Descuentos
                                                            <span class="float-right">{{ formatMoney(sumBy(d, 'DESCUENTO')) }}</span>
                                                        </td>
                                                        <td class="px-3 py-2 text-indigo-700">
                                                            Total Aportaciones
                                                            <span class="float-right">{{ formatMoney(sumBy(d, 'APORTACION')) }}</span>
                                                        </td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>

                                        <div class="px-4 py-3 flex justify-end bg-slate-50/60">
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

        <RegistrosModal
            v-if="showRegistrosModal"
            :periodo="detalle.periodo"
            :fila="filaSeleccionada"
            @close="closeRegistros"
            @add="openCreateTardanza"
            @edit="openEditTardanza"
            @toggle="onToggleJustificado"
            @remove="onRemoveTardanza"
        />

        <TardanzaModal
            v-if="showTardanzaModal"
            :periodo="detalle.periodo"
            :empleados="filasTardanzas"
            :preset-employee-id="selectedEmployeeId"
            :registro="editingTardanza"
            :saving="saving"
            @close="closeTardanza"
            @submit="onSubmitTardanza"
        />

        <AsignacionesEmpleadoModal
            v-if="showAsignacionesModal && filaAsignaciones"
            :row="filaAsignaciones"
            :auto-recalcular="true"
            @close="closeAsignaciones"
            @changed="asignacionesSucias = true"
        />

        <NotasModal
            v-if="showNotasModal"
            ref="notasModalRef"
            :empleado="notasEmpleado"
            :notas="notas"
            :loading="loadingNotas"
            :saving="savingNotas"
            @close="closeNotas"
            @submit="onSubmitNota"
            @remove="onRemoveNota"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { CalendarRange, X, ChevronDown, ChevronUp, Clock, Search, ListPlus, NotebookText } from 'lucide-vue-next';
import TardanzaModal from '@/Components/Planillas/Tardanzas/TardanzaModal.vue';
import RegistrosModal from '@/Components/Planillas/Tardanzas/RegistrosModal.vue';
import NotasModal from '@/Components/Planillas/Notas/NotasModal.vue';
import AsignacionesEmpleadoModal from '@/Components/Planillas/Remuneraciones/AsignacionesEmpleadoModal.vue';
import { usePlanillaTardanzas } from '@/Composables/usePlanillaTardanzas';
import { usePlanillaNotas } from '@/Composables/usePlanillaNotas';
import { usePlanillaPeriodos } from '@/Composables/usePlanillaPeriodos';

const emit = defineEmits(['close', 'refresh']);

const props = defineProps({
    detalle: { type: Object, required: true },
});

const expanded = ref([]);
const showTardanzaModal = ref(false);
const showRegistrosModal = ref(false);
const showAsignacionesModal = ref(false);
const filaAsignaciones = ref(null);
const asignacionesSucias = ref(false);
const selectedEmployeeId = ref('');
const editingTardanza = ref(null);
const flashEmployeeId = ref(null);
let flashTimer = null;

const search = ref('');
const matchIndex = ref(-1);
const scrollContainer = ref(null);

const {
    filas: filasTardanzas,
    saving,
    fetchTardanzas,
    crearTardanza,
    actualizarTardanza,
    eliminarTardanza,
} = usePlanillaTardanzas();
const { generarPeriodo } = usePlanillaPeriodos();

const {
    notas,
    loading: loadingNotas,
    saving: savingNotas,
    fetchNotas,
    crearNota,
    actualizarNota,
    eliminarNota,
} = usePlanillaNotas();

const showNotasModal = ref(false);
const notasEmpleado = ref(null);
const notasModalRef = ref(null);
const notasCounts = ref({});

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

const matches = computed(() => {
    const term = search.value.trim().toLowerCase();
    if (!term) return [];
    return props.detalle.detalles.filter((d) =>
        (d.nombre_completo || '').toLowerCase().includes(term)
        || (d.dni || '').includes(term)
    );
});

const matchLabel = computed(() => {
    if (!search.value.trim()) return String(props.detalle.detalles.length);
    const total = matches.value.length;
    if (total === 0) return '0';
    if (matchIndex.value < 0) return String(total);
    return `${matchIndex.value + 1}/${total}`;
});

const filaSeleccionada = computed(() =>
    filasTardanzas.value.find((f) => f.employee_id === selectedEmployeeId.value) || null
);

const tardanzasCount = (employeeId) => {
    const fila = filasTardanzas.value.find((f) => f.employee_id === employeeId);
    return fila?.registros?.length || 0;
};

const asignacionesCount = (fila) => Number(fila?.asignaciones_count || 0);

onMounted(async () => {
    if (!props.detalle.periodo.editable) return;
    try {
        await fetchTardanzas(props.detalle.periodo.id);
    } catch (error) {
        // el badge simplemente no se muestra si no se pudo cargar
    }
});

watch(() => props.detalle?.detalles, (filas) => {
    const counts = {};
    (filas || []).forEach((d) => {
        counts[d.employee_id] = d.notas_count || 0;
    });
    notasCounts.value = counts;
}, { immediate: true });

const notasCount = (employeeId) => notasCounts.value[employeeId] || 0;

const openNotas = async (d) => {
    notasEmpleado.value = d;
    notasCounts.value[d.employee_id] = notasCounts.value[d.employee_id] || 0;
    showNotasModal.value = true;
    try {
        await fetchNotas(d.employee_id);
    } catch (error) {
        notify('error', 'No se pudieron cargar las notas del empleado');
    }
};

const closeNotas = () => {
    showNotasModal.value = false;
    notasEmpleado.value = null;
    notasModalRef.value?.cancelEdit();
};

const onSubmitNota = async ({ texto, id }) => {
    const employeeId = notasEmpleado.value?.employee_id;
    if (!employeeId) return;

    try {
        if (id) {
            await actualizarNota(id, { texto }, employeeId);
            notify('success', 'Nota actualizada correctamente');
        } else {
            await crearNota({ employee_id: employeeId, texto });
            notasCounts.value[employeeId] = (notasCounts.value[employeeId] || 0) + 1;
            notify('success', 'Nota registrada correctamente');
        }
        notasModalRef.value?.cancelEdit();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo guardar la nota');
    }
};

const onRemoveNota = async (nota) => {
    const employeeId = notasEmpleado.value?.employee_id;
    if (!employeeId) return;

    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar nota?',
        html: '<p>Esta anotación se borrará permanentemente.</p>',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d97706',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarNota(nota.id, employeeId);
        notasCounts.value[employeeId] = Math.max(0, (notasCounts.value[employeeId] || 0) - 1);
        notify('success', 'Nota eliminada correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar la nota');
    }
};

watch(search, () => {
    matchIndex.value = -1;
});

const jumpToMatch = (direction = 1) => {
    const list = matches.value;
    if (!list.length) return;
    if (direction > 0) {
        matchIndex.value = matchIndex.value + 1 >= list.length ? 0 : matchIndex.value + 1;
    } else {
        matchIndex.value = matchIndex.value - 1 < 0 ? list.length - 1 : matchIndex.value - 1;
    }
    const target = list[matchIndex.value];
    const el = scrollContainer.value?.querySelector(`[data-row-id="${target.employee_id}"]`);
    el?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    flashRow(target.employee_id);
};

const flashRow = (employeeId) => {
    flashEmployeeId.value = employeeId;
    if (flashTimer) clearTimeout(flashTimer);
    flashTimer = setTimeout(() => {
        flashEmployeeId.value = null;
        flashTimer = null;
    }, 5000);
};

const openRegistros = async (employeeId) => {
    try {
        await fetchTardanzas(props.detalle.periodo.id);
    } catch (error) {
        notify('error', 'No se pudo cargar las tardanzas del periodo');
        return;
    }
    selectedEmployeeId.value = employeeId;
    showRegistrosModal.value = true;
};

const closeRegistros = () => {
    showRegistrosModal.value = false;
    selectedEmployeeId.value = '';
};

const openAsignaciones = (d) => {
    filaAsignaciones.value = {
        id: d.employee_id,
        nombre_completo: d.nombre_completo,
        dni: d.dni,
    };
    asignacionesSucias.value = false;
    showAsignacionesModal.value = true;
};

const closeAsignaciones = async () => {
    const employeeId = filaAsignaciones.value?.id;
    showAsignacionesModal.value = false;
    filaAsignaciones.value = null;

    if (!asignacionesSucias.value) return;
    asignacionesSucias.value = false;

    await afterMutation(employeeId, 'Concepto actualizado y planilla recalculada');
};

const openCreateTardanza = () => {
    editingTardanza.value = null;
    showTardanzaModal.value = true;
};

const openEditTardanza = (registro) => {
    editingTardanza.value = { ...registro, employee_id: selectedEmployeeId.value };
    showTardanzaModal.value = true;
};

const closeTardanza = () => {
    showTardanzaModal.value = false;
    editingTardanza.value = null;
};

const afterMutation = async (employeeId, message) => {
    try {
        await generarPeriodo(props.detalle.periodo.id);
        emit('refresh', props.detalle.periodo.id);
        flashRow(employeeId);
        notify('success', message);
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo recalcular la planilla');
    }
};

const onSubmitTardanza = async (values) => {
    try {
        const periodoId = props.detalle.periodo.id;
        if (editingTardanza.value) {
            await actualizarTardanza(editingTardanza.value.id, {
                dias: values.dias,
                minutos: values.minutos,
                observacion: values.observacion,
            }, periodoId);
            await afterMutation(values.employee_id, 'Registro actualizado y planilla recalculada');
        } else {
            await crearTardanza({ ...values, periodo_id: periodoId });
            await afterMutation(values.employee_id, 'Tardanza registrada y planilla recalculada');
        }
        closeTardanza();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo guardar el registro');
    }
};

const onToggleJustificado = async (registro) => {
    try {
        await actualizarTardanza(registro.id, { justificado: !registro.justificado }, props.detalle.periodo.id);
        await afterMutation(selectedEmployeeId.value, registro.justificado
            ? 'El registro vuelve a descontar'
            : 'Registro justificado: queda excluido del descuento');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el registro');
    }
};

const onRemoveTardanza = async (registro) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar registro?',
        html: `<p><strong>${filaSeleccionada.value?.nombre_completo || ''}</strong><br>${formatDate(registro.fecha)} — ${money(registro.total)}</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarTardanza(registro.id, props.detalle.periodo.id);
        await afterMutation(selectedEmployeeId.value, 'Registro eliminado y planilla recalculada');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar el registro');
    }
};

const toggle = (id) => {
    const index = expanded.value.indexOf(id);
    if (index === -1) expanded.value.push(id);
    else expanded.value.splice(index, 1);
};

const isExpanded = (id) => expanded.value.includes(id);

const money = (value, digits = 2) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
});

const formatDate = (value) => {
    if (!value) return '—';
    const [y, m, d] = value.split('-');
    return `${d}/${m}/${y}`;
};

const formatMoney = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const itemsBy = (detalle, tipo) => (detalle.items || []).filter((item) => item.tipo === tipo);

const sumBy = (detalle, tipo) => itemsBy(detalle, tipo).reduce((sum, item) => sum + Number(item.monto), 0);

const pct = (item) => (item.porcentaje !== null && item.porcentaje !== undefined)
    ? `${(item.porcentaje * 100).toFixed(2)}%`
    : '—';

const filas = (detalle) => {
    const ingresos = itemsBy(detalle, 'INGRESO');
    const descuentos = itemsBy(detalle, 'DESCUENTO');
    const aportaciones = itemsBy(detalle, 'APORTACION');
    const total = Math.max(ingresos.length, descuentos.length, aportaciones.length);

    if (total === 0) {
        return [];
    }

    return Array.from({ length: total }, (_, i) => ({
        ingreso: ingresos[i] || null,
        descuento: descuentos[i] || null,
        aportacion: aportaciones[i] || null,
    }));
};
</script>
