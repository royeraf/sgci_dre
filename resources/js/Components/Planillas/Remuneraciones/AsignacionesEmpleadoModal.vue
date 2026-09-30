<template>
    <div class="fixed inset-0 z-[70] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-rose-600 to-red-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <ListPlus class="w-6 h-6" />
                            Conceptos del Empleado
                        </h3>
                        <p class="text-sm mt-1 text-rose-50">
                            {{ row.nombre_completo }}<span v-if="row.dni"> · DNI {{ row.dni }}</span>
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-white/80 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-4">
                        <p class="text-sm font-bold text-slate-700">Asignar concepto</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Concepto</label>
                                <div class="relative" ref="conceptoContainer">
                                    <div class="relative">
                                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        <input v-model="conceptoQuery" type="text" @focus="showConceptoDropdown = true"
                                            @input="onConceptoInput" placeholder="Buscar concepto por nombre, código o tipo..."
                                            autocomplete="off"
                                            class="w-full pl-9 pr-9 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm placeholder:text-slate-400 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none" />
                                        <button v-if="conceptoId" type="button" @click="limpiarConcepto"
                                            title="Quitar concepto seleccionado"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-500 transition-colors p-0.5 rounded-full hover:bg-slate-100">
                                            <X class="w-4 h-4" />
                                        </button>
                                        <ChevronDown v-else
                                            class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>

                                    <div v-if="showConceptoDropdown && conceptosFiltrados.length > 0"
                                        class="absolute z-50 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl max-h-52 overflow-y-auto divide-y divide-slate-100">
                                        <button type="button" v-for="c in conceptosFiltrados" :key="c.id"
                                            @mousedown.prevent="seleccionarConcepto(c)"
                                            class="w-full text-left px-4 py-2.5 hover:bg-rose-50/80 transition-colors flex items-center justify-between gap-2 group">
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-800 text-sm truncate group-hover:text-rose-800">
                                                    {{ c.nombre }}
                                                </p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    <span class="font-mono bg-slate-100 px-1.5 py-0.2 rounded text-[11px] text-slate-600">{{ c.codigo }}</span>
                                                    · {{ c.tipo }}
                                                </p>
                                            </div>
                                            <Check v-if="conceptoId === c.id" class="w-4 h-4 text-rose-600 shrink-0" />
                                        </button>
                                    </div>

                                    <div v-if="showConceptoDropdown && conceptoQuery.trim().length >= 2 && conceptosFiltrados.length === 0"
                                        class="absolute z-50 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl p-3 text-center text-xs text-slate-500">
                                        No se encontraron conceptos para «{{ conceptoQuery }}».
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                    {{ conceptoSel?.es_porcentaje ? 'Porcentaje (%)' : 'Monto (S/)' }}
                                </label>
                                <input v-model="valor" type="number" min="0" step="0.01"
                                    :placeholder="conceptoSel?.es_porcentaje ? 'Ej. 9 para 9%' : 'Ej. 590.00'"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Mes aplicable</label>
                                <select v-model="periodo"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none">
                                    <option value="">Todos los meses (permanente)</option>
                                    <optgroup v-for="anio in anios" :key="anio" :label="anio">
                                        <option v-for="(mes, i) in MESES" :key="`${anio}-${i}`" :value="`${anio}-${i + 1}`">
                                            {{ mes }} {{ anio }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Desde</label>
                                <input v-model="desde" type="date"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Hasta</label>
                                <input v-model="hasta" type="date"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none" />
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="agregar" :disabled="saving"
                                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 transition-all disabled:opacity-50">
                                <Loader2 v-if="saving" class="w-4 h-4 mr-1.5 animate-spin" />
                                <Plus v-else class="w-4 h-4 mr-1.5" />
                                {{ saving ? 'Asignando...' : 'Asignar concepto' }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-rose-500 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="asignaciones.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            Este empleado no tiene conceptos asignados.
                        </p>
                        <ul v-else class="divide-y divide-slate-100">
                            <li v-for="asignacion in asignaciones" :key="asignacion.id"
                                class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/70">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0"
                                    :class="badgeTipo(asignacion)">
                                    {{ tipoAsignacion(asignacion) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ asignacion.concepto || '—' }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ vigencia(asignacion) }}
                                        <span v-if="asignacion.mes" class="font-semibold text-slate-500">
                                            · {{ mesAplicable(asignacion) }}
                                        </span>
                                        <span v-if="!asignacion.activo" class="text-slate-400 font-semibold">· Inactivo</span>
                                    </p>
                                </div>
                                <span class="font-bold text-slate-700 text-sm shrink-0">{{ formatValor(asignacion) }}</span>
                                <button @click="alternar(asignacion)"
                                    :title="asignacion.activo ? 'Desactivar asignación' : 'Activar asignación'"
                                    class="cursor-pointer p-2 rounded-lg transition-all shrink-0"
                                    :class="asignacion.activo
                                        ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'
                                        : 'bg-slate-100 text-slate-400 hover:bg-slate-200'">
                                    <Power class="w-4 h-4" />
                                </button>
                                <button @click="eliminar(asignacion)" title="Eliminar asignación"
                                    class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shrink-0">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-slate-400 flex items-start gap-1.5">
                        <RefreshCw class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                        {{ autoRecalcular
                            ? 'La planilla se recalculará automáticamente al cerrar este panel.'
                            : 'Regenera la planilla del periodo para que los cambios se reflejen en los montos.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { ListPlus, X, Plus, Trash2, Loader2, Power, RefreshCw, Search, ChevronDown, Check } from 'lucide-vue-next';

const props = defineProps({
    row: { type: Object, required: true },
    autoRecalcular: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'changed']);

const GESTIONADOS = [
    'REM_DL1057',
    'ONP_19990',
    'AFP_FONDO',
    'AFP_SEGURO',
    'AFP_COMISION',
    'AFP_REJA',
    'ESSALUD',
    'FALTAS_TARDANZAS',
    'RENTA_4TA',
    'GRATIFICACION',
];

const TIPOS = {
    INGRESO: 'bg-emerald-100 text-emerald-700',
    DESCUENTO: 'bg-rose-100 text-rose-700',
    APORTACION: 'bg-indigo-100 text-indigo-700',
};

const loading = ref(false);
const saving = ref(false);
const asignaciones = ref([]);
const conceptos = ref([]);
const conceptoId = ref('');
const valor = ref('');
const periodo = ref('');
const desde = ref('');
const hasta = ref('');

const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre'];
const anioActual = new Date().getFullYear();
const anios = [anioActual - 1, anioActual, anioActual + 1];

const conceptoSel = computed(() => conceptos.value.find((c) => c.id === conceptoId.value) || null);

const disponibles = computed(() => {
    const asignados = new Set(asignaciones.value.map((a) => a.concepto_id));
    return conceptos.value.filter((c) => c.activo && !GESTIONADOS.includes(c.codigo) && !asignados.has(c.id));
});

// ===== Buscador de concepto (combobox) =====
const conceptoQuery = ref('');
const showConceptoDropdown = ref(false);
const conceptoContainer = ref(null);

const conceptosFiltrados = computed(() => {
    const q = conceptoQuery.value.trim();

    if (!q) return disponibles.value.slice(0, 15);

    const norm = (texto) => String(texto || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const terminos = norm(q).split(' ').filter((t) => t.length > 0);

    return disponibles.value.filter((c) => {
        if (norm(c.codigo).includes(norm(q)) || norm(c.tipo).includes(norm(q))) return true;
        const nombre = norm(c.nombre);
        return terminos.every((t) => nombre.includes(t));
    }).slice(0, 15);
});

const onConceptoInput = () => {
    showConceptoDropdown.value = true;
    conceptoId.value = '';
};

const seleccionarConcepto = (c) => {
    conceptoId.value = c.id;
    conceptoQuery.value = `${c.nombre} (${c.tipo})`;
    showConceptoDropdown.value = false;
};

const limpiarConcepto = () => {
    conceptoId.value = '';
    conceptoQuery.value = '';
    showConceptoDropdown.value = true;
};

const cerrarDropdownConcepto = (event) => {
    if (conceptoContainer.value && !conceptoContainer.value.contains(event.target)) {
        showConceptoDropdown.value = false;
    }
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

const formatValor = (asignacion) => {
    if (asignacion.porcentaje !== null && asignacion.porcentaje !== undefined) {
        return `${(Number(asignacion.porcentaje) * 100).toFixed(2)}%`;
    }
    if (asignacion.monto !== null && asignacion.monto !== undefined) {
        return `S/ ${Number(asignacion.monto).toFixed(2)}`;
    }
    return 'Según concepto';
};

const vigencia = (asignacion) => {
    if (!asignacion.desde && !asignacion.hasta) return 'Sin vigencia definida';
    return `${asignacion.desde || 'inicio'} → ${asignacion.hasta || 'vigente'}`;
};

const mesAplicable = (asignacion) => {
    if (!asignacion.mes) return '';
    return `${MESES[asignacion.mes - 1]} ${asignacion.anio}`;
};

const tipoAsignacion = (asignacion) => {
    if (asignacion.tipo) return asignacion.tipo;
    const concepto = conceptos.value.find((c) => c.id === asignacion.concepto_id);
    return concepto?.tipo || 'INGRESO';
};

const badgeTipo = (asignacion) => TIPOS[tipoAsignacion(asignacion)] || TIPOS.INGRESO;

const fetchAll = async () => {
    loading.value = true;
    try {
        const [asig, conc] = await Promise.all([
            axios.get('/planillas/asignaciones', { params: { employee_id: props.row.id } }),
            axios.get('/planillas/conceptos'),
        ]);
        asignaciones.value = asig.data;
        conceptos.value = conc.data;
    } catch (error) {
        notify('error', 'No se pudieron cargar las asignaciones');
    } finally {
        loading.value = false;
    }
};

const agregar = async () => {
    if (!conceptoId.value) {
        notify('warning', 'Seleccione un concepto');
        return;
    }
    if (valor.value === '' || valor.value === null) {
        notify('warning', 'Ingrese el monto o porcentaje');
        return;
    }

    saving.value = true;
    try {
        const esPorcentaje = !!conceptoSel.value?.es_porcentaje;
        const [anioSel, mesSel] = periodo.value ? periodo.value.split('-') : [null, null];
        await axios.post('/planillas/asignaciones', {
            concepto_id: conceptoId.value,
            employee_id: props.row.id,
            monto: esPorcentaje ? null : Number(valor.value),
            porcentaje: esPorcentaje ? Number(valor.value) / 100 : null,
            desde: desde.value || null,
            hasta: hasta.value || null,
            anio: anioSel ? Number(anioSel) : null,
            mes: mesSel ? Number(mesSel) : null,
            activo: true,
        });

        conceptoId.value = '';
        conceptoQuery.value = '';
        showConceptoDropdown.value = false;
        valor.value = '';
        periodo.value = '';
        desde.value = '';
        hasta.value = '';

        notify('success', props.autoRecalcular
            ? 'Concepto asignado'
            : 'Concepto asignado. Regenera la planilla para aplicarlo');
        emit('changed');
        await fetchAll();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo asignar el concepto');
    } finally {
        saving.value = false;
    }
};

const alternar = async (asignacion) => {
    try {
        await axios.put(`/planillas/asignaciones/${asignacion.id}`, { activo: !asignacion.activo });
        notify('success', asignacion.activo ? 'Asignación desactivada' : 'Asignación activada');
        emit('changed');
        await fetchAll();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar la asignación');
    }
};

const eliminar = async (asignacion) => {
    const result = await window.Swal?.fire({
        title: '¿Eliminar asignación?',
        text: `${asignacion.concepto || ''} — ${formatValor(asignacion)}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
        reverseButtons: true,
    });

    if (!result?.isConfirmed) return;

    try {
        await axios.delete(`/planillas/asignaciones/${asignacion.id}`);
        notify('success', 'Asignación eliminada');
        emit('changed');
        await fetchAll();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar la asignación');
    }
};

onMounted(() => {
    document.addEventListener('mousedown', cerrarDropdownConcepto);
    fetchAll();
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', cerrarDropdownConcepto);
});
</script>
