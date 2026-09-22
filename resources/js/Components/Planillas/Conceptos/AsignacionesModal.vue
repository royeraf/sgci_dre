<template>
    <div class="fixed inset-0 z-[70] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="px-6 py-4 flex justify-between items-center" :class="theme.header">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Users class="w-6 h-6" />
                            Asignaciones
                        </h3>
                        <p class="text-sm mt-1 text-white/90">{{ concepto.nombre }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-white/80 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Alta -->
                    <div class="rounded-2xl border border-slate-200 p-4 space-y-4">
                        <p class="text-sm font-bold text-slate-700">Nueva asignación</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Aplica a</label>
                                <select v-model="destino"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    <option value="REGIMEN">Todo un régimen</option>
                                    <option value="EMPLEADO">Un empleado</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                    {{ destino === 'REGIMEN' ? 'Régimen' : 'Empleado' }}
                                </label>
                                <select v-if="destino === 'REGIMEN'" v-model="contractTypeId"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    <option value="">Seleccione régimen</option>
                                    <option v-for="ct in parametros.contract_types" :key="ct.id" :value="ct.id">
                                        {{ ct.nombre }}
                                    </option>
                                </select>
                                <select v-else v-model="employeeId"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    <option value="">Seleccione empleado</option>
                                    <option v-for="emp in parametros.employees" :key="emp.id" :value="emp.id">
                                        {{ emp.dni }} - {{ emp.nombre_completo }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">
                                    {{ concepto.es_porcentaje ? 'Porcentaje (%)' : 'Monto (S/)' }}
                                </label>
                                <input v-model="valor" type="number" min="0" step="0.01"
                                    :placeholder="concepto.es_porcentaje ? 'Ej. 9 para 9%' : '0.00'"
                                    class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Desde</label>
                                    <input v-model="desde" type="date"
                                        class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-widest mb-1.5">Hasta</label>
                                    <input v-model="hasta" type="date"
                                        class="w-full px-3 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="agregar" :disabled="saving"
                                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white transition-all disabled:opacity-50"
                                :class="theme.button">
                                <Plus class="w-4 h-4 mr-1.5" />
                                Agregar asignación
                            </button>
                        </div>
                    </div>

                    <!-- Listado -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-slate-400 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="asignaciones.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            Este concepto aún no tiene asignaciones.
                        </p>
                        <ul v-else class="divide-y divide-slate-100">
                            <li v-for="asignacion in asignaciones" :key="asignacion.id"
                                class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/70">
                                <span class="text-[10px] font-bold px-2 py-1 rounded-full shrink-0"
                                    :class="asignacion.destino === 'REGIMEN' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700'">
                                    {{ asignacion.destino === 'REGIMEN' ? 'RÉGIMEN' : 'EMPLEADO' }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ asignacion.destino_nombre || '—' }}</p>
                                    <p class="text-xs text-slate-400">
                                        {{ vigencia(asignacion) }}
                                    </p>
                                </div>
                                <span class="font-bold text-slate-700 text-sm shrink-0">{{ formatValor(asignacion) }}</span>
                                <button @click="remove(asignacion)" title="Eliminar asignación"
                                    class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all shrink-0">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Users, X, Plus, Trash2, Loader2 } from 'lucide-vue-next';
import { usePlanillaAsignaciones } from '@/Composables/usePlanillaAsignaciones';

const props = defineProps({
    concepto: { type: Object, required: true },
});

const emit = defineEmits(['close', 'changed']);

const THEME = {
    INGRESO: {
        header: 'bg-gradient-to-r from-emerald-600 to-teal-600',
        button: 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700',
    },
    DESCUENTO: {
        header: 'bg-gradient-to-r from-rose-600 to-red-600',
        button: 'bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700',
    },
    APORTACION: {
        header: 'bg-gradient-to-r from-indigo-600 to-blue-600',
        button: 'bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700',
    },
};

const theme = computed(() => THEME[props.concepto.tipo] || THEME.INGRESO);

const {
    asignaciones,
    parametros,
    loading,
    saving,
    fetchAsignaciones,
    fetchParametros,
    crearAsignacion,
    eliminarAsignacion,
} = usePlanillaAsignaciones();

const destino = ref('REGIMEN');
const contractTypeId = ref('');
const employeeId = ref('');
const valor = ref('');
const desde = ref('');
const hasta = ref('');

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
    const desde = asignacion.desde || 'inicio';
    const hasta = asignacion.hasta || 'vigente';
    return `${desde} → ${hasta}`;
};

const agregar = async () => {
    const target = destino.value === 'REGIMEN' ? contractTypeId.value : employeeId.value;
    if (!target) {
        notify('warning', destino.value === 'REGIMEN' ? 'Seleccione un régimen' : 'Seleccione un empleado');
        return;
    }
    if (valor.value === '' || valor.value === null) {
        notify('warning', 'Ingrese el monto o porcentaje');
        return;
    }

    try {
        await crearAsignacion({
            concepto_id: props.concepto.id,
            employee_id: destino.value === 'EMPLEADO' ? employeeId.value : null,
            contract_type_id: destino.value === 'REGIMEN' ? contractTypeId.value : null,
            monto: props.concepto.es_porcentaje ? null : Number(valor.value),
            porcentaje: props.concepto.es_porcentaje ? Number(valor.value) / 100 : null,
            desde: desde.value || null,
            hasta: hasta.value || null,
            activo: true,
        });

        valor.value = '';
        desde.value = '';
        hasta.value = '';
        contractTypeId.value = '';
        employeeId.value = '';
        notify('success', 'Asignación registrada correctamente');
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo registrar la asignación');
    }
};

const remove = async (asignacion) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar asignación?',
        html: `<p><strong>${asignacion.destino_nombre || '—'}</strong></p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarAsignacion(asignacion.id, props.concepto.id);
        notify('success', 'Asignación eliminada correctamente');
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar la asignación');
    }
};

onMounted(async () => {
    await Promise.all([fetchParametros(), fetchAsignaciones(props.concepto.id)]);
});
</script>
