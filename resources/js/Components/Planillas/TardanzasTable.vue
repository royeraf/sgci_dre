<template>
    <PlanillaSection
        title="Tardanzas"
        description="Descuento por faltas y tardanzas — hoja «Dscto. Tard.» (registro manual por empleado y día)."
        :icon="Clock"
        searchPlaceholder="Buscar por nombre o DNI..."
        :searchValue="search"
        @update:searchValue="search = $event"
    >
        <template #actions>
            <select v-model="periodoId" @change="cargar"
                class="px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm font-semibold focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none">
                <option v-for="p in periodos" :key="p.id" :value="p.id">
                    {{ p.nombre_periodo }} ({{ p.estado }})
                </option>
            </select>

            <button v-if="periodoActual?.editable" @click="openCreate()"
                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-rose-600 to-orange-600 hover:from-rose-700 hover:to-orange-700 transition-all duration-300 hover:-translate-y-0.5">
                <Plus class="w-4 h-4 mr-2" />
                Registrar tardanza
            </button>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr class="text-[11px] uppercase tracking-widest">
                        <th rowspan="2" class="font-bold px-3 py-2 text-center border-r border-slate-200">N°</th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-left border-r border-slate-200">DNI</th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-left border-r border-slate-200">Apellidos y Nombres</th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-right border-r-2 border-slate-300 bg-teal-50 text-teal-700">
                            Remuneraciones<br /><span class="text-[10px] normal-case tracking-normal">(E)</span>
                        </th>
                        <th colspan="2" class="font-bold px-3 py-2 text-center border-x border-slate-300 bg-amber-50 text-amber-700">
                            Importe Dscto S/.
                        </th>
                        <th colspan="2" class="font-bold px-3 py-2 text-center border-x border-slate-300 bg-rose-50 text-rose-700">
                            Descuento
                        </th>
                        <th colspan="2" class="font-bold px-3 py-2 text-center border-x border-slate-300 bg-rose-50 text-rose-700">
                            Monto a Descontar
                        </th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-center border-x border-slate-300 bg-rose-100 text-rose-700">
                            Total<br />Dscto.
                        </th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-center border-x border-slate-300 bg-teal-100 text-teal-700">
                            Base<br />Impon.
                        </th>
                        <th rowspan="2" class="font-bold px-3 py-2 text-center"></th>
                    </tr>
                    <tr class="text-[10px] uppercase tracking-wider">
                        <th class="font-bold px-3 py-1.5 text-right bg-amber-50/70 text-amber-700 border-x border-slate-300">Por día (F)</th>
                        <th class="font-bold px-3 py-1.5 text-right bg-amber-50/70 text-amber-700 border-x border-slate-300">Por minutos (G)</th>
                        <th class="font-bold px-3 py-1.5 text-right bg-rose-50/70 text-rose-700 border-x border-slate-300">Días (H)</th>
                        <th class="font-bold px-3 py-1.5 text-right bg-rose-50/70 text-rose-700 border-x border-slate-300">Minutos (I)</th>
                        <th class="font-bold px-3 py-1.5 text-right bg-rose-50/70 text-rose-700 border-x border-slate-300">Días (J)</th>
                        <th class="font-bold px-3 py-1.5 text-right bg-rose-50/70 text-rose-700 border-x border-slate-300">Minutos (K)</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <template v-for="(fila, index) in filtradas" :key="fila.employee_id">
                        <tr class="hover:bg-slate-50/70 cursor-pointer"
                            :class="expandedId === fila.employee_id ? 'bg-rose-50/50' : ''"
                            @click="toggle(fila.employee_id)">
                            <td class="px-3 py-2.5 text-center text-slate-400 text-xs">{{ index + 1 }}</td>
                            <td class="px-3 py-2.5 text-slate-500 tabular-nums">{{ fila.dni || '—' }}</td>
                            <td class="px-3 py-2.5 font-semibold text-slate-800 whitespace-nowrap">
                                {{ fila.nombre_completo }}
                                <span v-if="fila.con_registros && fila.total === 0"
                                    class="ml-2 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                                    Todo justificado
                                </span>
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums border-r-2 border-slate-300 bg-teal-50/50 text-slate-700">
                                {{ money(fila.remuneraciones) }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums bg-amber-50/40 text-slate-600 border-x border-slate-200">
                                {{ money(fila.valor_dia) }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums bg-amber-50/40 text-slate-600 border-x border-slate-200">
                                {{ money(fila.valor_minuto, 4) }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 border-x border-slate-200">
                                {{ fila.dias || '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 border-x border-slate-200">
                                {{ fila.minutos || '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 border-x border-slate-200">
                                {{ fila.monto_dias ? money(fila.monto_dias) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 border-x border-slate-200">
                                {{ fila.monto_minutos ? money(fila.monto_minutos) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-bold border-x border-slate-300"
                                :class="fila.total > 0 ? 'text-rose-700 bg-rose-50' : 'text-slate-400'">
                                {{ fila.total > 0 ? money(fila.total) : '—' }}
                            </td>
                            <td class="px-3 py-2.5 text-right tabular-nums font-semibold text-teal-700 bg-teal-50/60 border-x border-slate-300">
                                {{ money(fila.base_imponible) }}
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <ChevronDown v-if="fila.con_registros" class="w-4 h-4 mx-auto text-slate-400 transition-transform"
                                    :class="expandedId === fila.employee_id ? 'rotate-180 text-rose-500' : ''" />
                            </td>
                        </tr>

                        <!-- Registros diarios del empleado -->
                        <tr v-if="expandedId === fila.employee_id" class="bg-slate-50/70">
                            <td colspan="13" class="p-0">
                                <div class="px-6 py-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                                            Registros diarios — {{ fila.nombre_completo }}
                                        </p>
                                        <button v-if="periodoActual?.editable" @click.stop="openCreate(fila.employee_id)"
                                            class="cursor-pointer inline-flex items-center text-xs font-bold text-rose-600 hover:text-rose-700">
                                            <Plus class="w-3.5 h-3.5 mr-1" />
                                            Agregar día
                                        </button>
                                    </div>

                                    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
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
                                                    <th class="text-center font-bold px-4 py-2">Acciones</th>
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
                                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                                            :class="r.justificado ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                                            {{ r.justificado ? 'Justificado' : 'Descuenta' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2">
                                                        <div class="flex items-center justify-center gap-1">
                                                            <button v-if="periodoActual?.editable" @click.stop="toggleJustificado(fila, r)"
                                                                :title="r.justificado ? 'Quitar justificación' : 'Justificar (excluir del descuento)'"
                                                                class="cursor-pointer p-1.5 rounded-lg transition-all"
                                                                :class="r.justificado ? 'bg-slate-100 text-slate-500 hover:bg-slate-200' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'">
                                                                <ShieldCheck class="w-4 h-4" />
                                                            </button>
                                                            <button v-if="periodoActual?.editable" @click.stop="openEdit(fila, r)"
                                                                title="Editar" class="cursor-pointer p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">
                                                                <Pencil class="w-4 h-4" />
                                                            </button>
                                                            <button v-if="periodoActual?.editable" @click.stop="remove(fila, r)"
                                                                title="Eliminar" class="cursor-pointer p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100">
                                                                <Trash2 class="w-4 h-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr v-if="loading">
                        <td colspan="13" class="py-16 text-center">
                            <Loader2 class="w-7 h-7 text-rose-500 animate-spin mx-auto" />
                        </td>
                    </tr>
                    <tr v-else-if="filtradas.length === 0">
                        <td colspan="13" class="py-16 text-center text-slate-500 font-medium">
                            No hay personal que coincida con la búsqueda.
                        </td>
                    </tr>
                </tbody>

                <tfoot v-if="!loading && filtradas.length" class="bg-slate-100 font-bold text-slate-700 border-t-2 border-slate-300">
                    <tr>
                        <td colspan="6" class="px-3 py-3 text-right text-[11px] uppercase tracking-widest text-slate-500">
                            Sub total
                        </td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ totales.dias }}</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ totales.minutos }}</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ money(totales.monto_dias) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums">{{ money(totales.monto_minutos) }}</td>
                        <td class="px-3 py-3 text-right tabular-nums text-rose-700 bg-rose-50 border-x border-slate-300">
                            {{ money(totales.total) }}
                        </td>
                        <td class="px-3 py-3 text-right tabular-nums text-teal-700 bg-teal-50 border-x border-slate-300">
                            {{ money(totales.base_imponible) }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <TardanzaModal
            v-if="showModal"
            :periodo="periodoActual"
            :empleados="filas"
            :registro="editing"
            :preset-employee-id="presetEmployeeId"
            :saving="saving"
            @close="closeModal"
            @submit="onSubmit"
        />
    </PlanillaSection>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { Clock, Plus, ChevronDown, Pencil, Trash2, ShieldCheck, Loader2 } from 'lucide-vue-next';
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import TardanzaModal from '@/Components/Planillas/Tardanzas/TardanzaModal.vue';
import { usePlanillaPeriodos } from '@/Composables/usePlanillaPeriodos';
import { usePlanillaTardanzas } from '@/Composables/usePlanillaTardanzas';

const { periodos, fetchPeriodos } = usePlanillaPeriodos();
const {
    filas,
    loading,
    saving,
    fetchTardanzas,
    crearTardanza,
    actualizarTardanza,
    eliminarTardanza,
} = usePlanillaTardanzas();

const search = ref('');
const periodoId = ref('');
const expandedId = ref(null);
const showModal = ref(false);
const editing = ref(null);
const presetEmployeeId = ref('');

const periodoActual = computed(() => periodos.value.find((p) => p.id === periodoId.value) || null);

const filtradas = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return filas.value;

    return filas.value.filter((f) =>
        (f.nombre_completo || '').toLowerCase().includes(q)
        || (f.dni || '').includes(q)
    );
});

const totales = computed(() => filtradas.value.reduce((acc, f) => ({
    dias: acc.dias + f.dias,
    minutos: acc.minutos + f.minutos,
    monto_dias: acc.monto_dias + f.monto_dias,
    monto_minutos: acc.monto_minutos + f.monto_minutos,
    total: acc.total + f.total,
    base_imponible: acc.base_imponible + f.base_imponible,
}), { dias: 0, minutos: 0, monto_dias: 0, monto_minutos: 0, total: 0, base_imponible: 0 }));

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

const money = (value, digits = 2) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
});

const formatDate = (value) => {
    if (!value) return '—';
    const [y, m, d] = value.split('-');
    return `${d}/${m}/${y}`;
};

const cargar = async () => {
    if (!periodoId.value) return;
    expandedId.value = null;
    await fetchTardanzas(periodoId.value);
};

const toggle = (id) => {
    expandedId.value = expandedId.value === id ? null : id;
};

const openCreate = (employeeId = '') => {
    editing.value = null;
    presetEmployeeId.value = employeeId || '';
    showModal.value = true;
};

const openEdit = (fila, registro) => {
    editing.value = { ...registro, employee_id: fila.employee_id };
    presetEmployeeId.value = '';
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editing.value = null;
    presetEmployeeId.value = '';
};

const onSubmit = async (values) => {
    try {
        if (editing.value) {
            await actualizarTardanza(editing.value.id, {
                dias: values.dias,
                minutos: values.minutos,
                observacion: values.observacion,
            }, periodoId.value);
            notify('success', 'Registro actualizado correctamente');
        } else {
            await crearTardanza({ ...values, periodo_id: periodoId.value });
            notify('success', 'Tardanza registrada correctamente');
        }
        closeModal();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo guardar el registro');
    }
};

const toggleJustificado = async (fila, registro) => {
    try {
        await actualizarTardanza(registro.id, { justificado: !registro.justificado }, periodoId.value);
        notify('success', registro.justificado
            ? 'El registro vuelve a descontar'
            : 'Registro justificado: queda excluido del descuento');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el registro');
    }
};

const remove = async (fila, registro) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar registro?',
        html: `<p><strong>${fila.nombre_completo}</strong><br>${formatDate(registro.fecha)} — ${money(registro.total)}</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarTardanza(registro.id, periodoId.value);
        notify('success', 'Registro eliminado correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar el registro');
    }
};

onMounted(async () => {
    await fetchPeriodos();
    if (periodos.value.length) {
        periodoId.value = periodos.value[0].id;
        await cargar();
    }
});
</script>
