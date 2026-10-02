<template>
    <div class="fixed inset-0 z-[70] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-orange-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarOff class="w-6 h-6" />
                            Licencias del Empleado
                        </h3>
                        <p class="text-sm mt-1 text-amber-50">
                            {{ row.nombre_completo }}<span v-if="row.dni"> · DNI {{ row.dni }}</span>
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-white/80 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <button type="button" @click="toggleForm"
                            class="w-full flex items-center justify-between gap-3 px-4 py-3 bg-amber-50/70 hover:bg-amber-50 transition-colors cursor-pointer">
                            <span class="flex items-center gap-2 text-sm font-bold text-slate-700">
                                <Plus class="w-4 h-4 text-amber-600" />
                                {{ editingId ? 'Editar licencia' : 'Registrar licencia' }}
                            </span>
                            <ChevronDown class="w-4 h-4 text-amber-600 transition-transform duration-300"
                                :class="formAbierto ? 'rotate-180' : ''" />
                        </button>

                        <div class="grid transition-all duration-300 ease-out"
                            :class="formAbierto ? 'grid-rows-[1fr]' : 'grid-rows-[0fr]'">
                            <div class="overflow-hidden">
                                <div class="p-4 space-y-4 border-t border-slate-200">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tipo de licencia</label>
                                <select v-model="form.tipo_licencia"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                    <option value="" disabled>Seleccione un tipo</option>
                                    <option v-for="tipo in TIPOS" :key="tipo" :value="tipo">{{ tipo }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Estado</label>
                                <select v-model="form.estado"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                    <option v-for="estado in ESTADOS" :key="estado" :value="estado">{{ estado }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Desde</label>
                                <input v-model="form.fecha_inicio" type="date"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Hasta</label>
                                <input v-model="form.fecha_fin" type="date"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none" />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                    Motivo / Resolución (RDR)
                                </label>
                                <input v-model="form.motivo" type="text" placeholder="Ej. RDR 1693-2026"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none" />
                            </div>

                            <label class="sm:col-span-2 flex items-start gap-3 cursor-pointer rounded-xl border-2 border-amber-200 bg-amber-50/60 p-3 hover:bg-amber-50 transition-colors">
                                <input v-model="form.sin_goce" type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-600 focus:ring-amber-500" />
                                <span class="text-sm">
                                    <span class="font-bold text-slate-800">Sin goce de remuneración</span>
                                    <span class="block text-xs text-slate-500 mt-0.5">
                                        Los días se descuentan de la planilla. Si la licencia cubre el periodo completo,
                                        el empleado no figura en la planilla (igual que en el Excel). Con goce no afecta.
                                    </span>
                                </span>
                            </label>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button v-if="editingId" @click="cancelar" :disabled="saving"
                                class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-slate-200 text-slate-600 hover:bg-slate-50 transition-all disabled:opacity-50">
                                Cancelar
                            </button>
                            <button @click="guardar" :disabled="saving"
                                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 transition-all disabled:opacity-50">
                                <Loader2 v-if="saving" class="w-4 h-4 mr-1.5 animate-spin" />
                                <Plus v-else class="w-4 h-4 mr-1.5" />
                                {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Registrar licencia') }}
                            </button>
                        </div>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-amber-500 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="licencias.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            Este empleado no tiene licencias registradas.
                        </p>
                        <ul v-else class="divide-y divide-slate-100">
                            <li v-for="licencia in licencias" :key="licencia.id"
                                class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/70"
                                :class="{ 'bg-amber-50/50': licencia.vigente }">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">
                                        {{ licencia.tipo_licencia }}
                                        <span class="text-xs text-slate-400 font-normal">· {{ licencia.dias_solicitados }} día(s)</span>
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ formatDate(licencia.fecha_inicio) }} → {{ formatDate(licencia.fecha_fin) }}
                                        <span v-if="licencia.motivo" class="text-slate-500">· {{ licencia.motivo }}</span>
                                    </p>
                                    <div class="flex flex-wrap gap-1.5 mt-1.5">
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                            :class="estadoBadge(licencia.estado)">
                                            {{ licencia.estado }}
                                        </span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                            :class="licencia.sin_goce
                                                ? 'bg-amber-100 text-amber-700'
                                                : 'bg-emerald-100 text-emerald-700'">
                                            {{ licencia.sin_goce ? 'SIN GOCE · descuenta' : 'CON GOCE · no afecta' }}
                                        </span>
                                        <span v-if="licencia.vigente"
                                            class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                            VIGENTE
                                        </span>
                                    </div>
                                </div>
                                <button @click="editar(licencia)" title="Editar licencia"
                                    class="cursor-pointer p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-all shrink-0">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="eliminar(licencia)" title="Eliminar licencia"
                                    class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shrink-0">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </li>
                        </ul>
                    </div>

                    <p class="text-xs text-slate-400 flex items-start gap-1.5">
                        <RefreshCw class="w-3.5 h-3.5 mt-0.5 shrink-0" />
                        Las licencias sin goce APROBADAS descuentan días de la planilla. Regenera el periodo para
                        que los cambios se reflejen en los montos.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import { CalendarOff, X, Plus, Trash2, Loader2, RefreshCw, Pencil, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    row: { type: Object, required: true },
});

const emit = defineEmits(['close', 'changed']);

const TIPOS = ['Enfermedad', 'Maternidad', 'Paternidad', 'Personal', 'Otros'];
const ESTADOS = ['APROBADO', 'PENDIENTE', 'RECHAZADO'];

const ESTADO_BADGES = {
    APROBADO: 'bg-emerald-100 text-emerald-700',
    PENDIENTE: 'bg-amber-100 text-amber-700',
    RECHAZADO: 'bg-rose-100 text-rose-700',
};

const loading = ref(false);
const saving = ref(false);
const licencias = ref([]);
const editingId = ref(null);
const formAbierto = ref(false);

const formVacio = () => ({
    tipo_licencia: '',
    fecha_inicio: '',
    fecha_fin: '',
    sin_goce: true,
    motivo: '',
    estado: 'APROBADO',
});

const form = reactive(formVacio());

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

const formatDate = (fecha) => {
    if (!fecha) return '';
    const [anio, mes, dia] = fecha.split('-');
    return `${dia}/${mes}/${anio}`;
};

const estadoBadge = (estado) => ESTADO_BADGES[estado] || ESTADO_BADGES.APROBADO;

const fetchAll = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get('/planillas/licencias', {
            params: { employee_id: props.row.id },
        });
        licencias.value = data.licencias;
    } catch (error) {
        notify('error', 'No se pudieron cargar las licencias');
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    editingId.value = null;
    Object.assign(form, formVacio());
};

const toggleForm = () => {
    formAbierto.value = !formAbierto.value;
    if (!formAbierto.value) {
        resetForm();
    }
};

const cancelar = () => {
    resetForm();
    formAbierto.value = false;
};

const editar = (licencia) => {
    editingId.value = licencia.id;
    form.tipo_licencia = licencia.tipo_licencia;
    form.fecha_inicio = licencia.fecha_inicio;
    form.fecha_fin = licencia.fecha_fin;
    form.sin_goce = !!licencia.sin_goce;
    form.motivo = licencia.motivo || '';
    form.estado = licencia.estado;
    formAbierto.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const guardar = async () => {
    if (!form.tipo_licencia) {
        notify('warning', 'Seleccione el tipo de licencia');
        return;
    }
    if (!form.fecha_inicio || !form.fecha_fin) {
        notify('warning', 'Ingrese las fechas de la licencia');
        return;
    }
    if (form.fecha_fin < form.fecha_inicio) {
        notify('warning', 'La fecha «Hasta» debe ser igual o posterior a «Desde»');
        return;
    }

    saving.value = true;
    try {
        const payload = {
            employee_id: props.row.id,
            tipo_licencia: form.tipo_licencia,
            fecha_inicio: form.fecha_inicio,
            fecha_fin: form.fecha_fin,
            sin_goce: !!form.sin_goce,
            motivo: form.motivo || null,
            estado: form.estado,
        };

        if (editingId.value) {
            await axios.put(`/planillas/licencias/${editingId.value}`, payload);
            notify('success', 'Licencia actualizada');
        } else {
            await axios.post('/planillas/licencias', payload);
            notify('success', 'Licencia registrada');
        }

        resetForm();
        formAbierto.value = false;
        emit('changed');
        await fetchAll();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo guardar la licencia');
    } finally {
        saving.value = false;
    }
};

const eliminar = async (licencia) => {
    const result = await window.Swal?.fire({
        title: '¿Eliminar licencia?',
        text: `${licencia.tipo_licencia} — ${licencia.fecha_inicio} a ${licencia.fecha_fin}`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
        reverseButtons: true,
    });

    if (!result?.isConfirmed) return;

    try {
        await axios.delete(`/planillas/licencias/${licencia.id}`);
        if (editingId.value === licencia.id) {
            resetForm();
            formAbierto.value = false;
        }
        notify('success', 'Licencia eliminada');
        emit('changed');
        await fetchAll();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar la licencia');
    }
};

onMounted(fetchAll);
</script>
