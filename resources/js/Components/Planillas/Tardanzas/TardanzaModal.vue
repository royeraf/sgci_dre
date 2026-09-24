<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-rose-600 to-orange-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Clock class="w-6 h-6" />
                            {{ registro ? 'Editar tardanza' : 'Registrar tardanza' }}
                        </h3>
                        <p class="text-rose-50 text-sm mt-1">{{ periodo?.nombre_periodo }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-rose-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Empleado <span class="text-red-500">*</span></label>
                        <select v-model="employee_id" :disabled="!!registro"
                            class="w-full px-4 py-2.5 border-2 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none disabled:bg-slate-100 disabled:text-slate-500"
                            :class="formErrors.employee_id ? 'border-red-400' : 'border-slate-200'">
                            <option value="">Seleccione empleado</option>
                            <option v-for="fila in empleados" :key="fila.employee_id" :value="fila.employee_id">
                                {{ fila.nombre_completo }}{{ fila.dni ? ` — ${fila.dni}` : '' }}
                            </option>
                        </select>
                        <p v-if="formErrors.employee_id" class="mt-1 text-sm text-red-600">{{ formErrors.employee_id }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Fecha <span class="text-red-500">*</span></label>
                        <input v-model="fecha" type="date"
                            :min="periodo?.fecha_inicio || undefined" :max="periodo?.fecha_fin || undefined"
                            class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none"
                            :class="formErrors.fecha ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="formErrors.fecha" class="mt-1 text-sm text-red-600">{{ formErrors.fecha }}</p>
                        <p v-else class="mt-1 text-xs text-slate-400">
                            Dentro del periodo: {{ periodo?.fecha_inicio }} al {{ periodo?.fecha_fin }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Días (H)</label>
                            <input v-model.number="dias" type="number" min="0" max="31" step="1"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none"
                                :class="formErrors.dias ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.dias" class="mt-1 text-sm text-red-600">{{ formErrors.dias }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Minutos (I)</label>
                            <input v-model.number="minutos" type="number" min="0" max="1440" step="1"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none"
                                :class="formErrors.minutos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.minutos" class="mt-1 text-sm text-red-600">{{ formErrors.minutos }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observación</label>
                        <input v-model="observacion" type="text" placeholder="Ej. Llegada sin boleta"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-rose-500/20 focus:border-rose-500 outline-none" />
                    </div>

                    <div v-if="preview.dias > 0 || preview.minutos > 0"
                        class="rounded-2xl bg-rose-50 border border-rose-100 p-4 text-sm space-y-1">
                        <p class="font-bold text-rose-700 uppercase text-[11px] tracking-widest mb-2">Cálculo (hoja Excel)</p>
                        <div class="flex justify-between text-slate-600">
                            <span>Por día (F) = E / 30</span>
                            <span class="font-bold">S/ {{ money(preview.valor_dia) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Por minuto (G) = (F / 8) / 60</span>
                            <span class="font-bold">S/ {{ money(preview.valor_minuto) }}</span>
                        </div>
                        <div class="flex justify-between text-slate-600">
                            <span>Monto días (J) + minutos (K)</span>
                            <span class="font-bold">S/ {{ money(preview.total) }}</span>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-rose-600 to-orange-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : (registro ? 'Guardar' : 'Registrar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { Clock, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    periodo: { type: Object, default: null },
    empleados: { type: Array, default: () => [] },
    registro: { type: Object, default: null },
    presetEmployeeId: { type: String, default: '' },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    employee_id: yup.string().required('Seleccione el empleado'),
    fecha: yup.string().required('Ingrese la fecha'),
    dias: yup.number().min(0).max(31).required(),
    minutos: yup.number().min(0).max(1440).required(),
    observacion: yup.string().nullable(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, setErrors } = useForm({
    validationSchema: schema,
    initialValues: {
        employee_id: '',
        fecha: props.periodo?.fecha_fin || '',
        dias: 0,
        minutos: 0,
        observacion: '',
    },
});

const [employee_id] = defineField('employee_id');
const [fecha] = defineField('fecha');
const [dias] = defineField('dias');
const [minutos] = defineField('minutos');
const [observacion] = defineField('observacion');

// E del empleado seleccionado (hoja Excel) para previsualizar F/G.
const ingresosFila = computed(() =>
    props.empleados.find((f) => f.employee_id === employee_id.value)?.remuneraciones || 0
);

const preview = computed(() => {
    const valorDia = Math.round((ingresosFila.value / 30) * 100) / 100;
    const valorMinuto = Math.round((valorDia / 480) * 100) / 100;

    return {
        valor_dia: valorDia,
        valor_minuto: valorMinuto,
        dias: Number(dias.value) || 0,
        minutos: Number(minutos.value) || 0,
        total: Math.round((valorDia * (Number(dias.value) || 0)
            + valorMinuto * (Number(minutos.value) || 0)) * 100) / 100,
    };
});

const money = (v) => Number(v || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

watch(() => props.registro, (registro) => {
    if (registro) {
        setValues({
            employee_id: registro.employee_id,
            fecha: registro.fecha,
            dias: registro.dias,
            minutos: registro.minutos,
            observacion: registro.observacion || '',
        });
    } else {
        setValues({
            employee_id: props.presetEmployeeId || '',
            fecha: props.periodo?.fecha_fin || '',
            dias: 0,
            minutos: 0,
            observacion: '',
        });
    }
}, { immediate: true });

const onSubmit = validateForm((values) => {
    if (Number(values.dias) === 0 && Number(values.minutos) === 0) {
        setErrors({ dias: 'Ingrese días o minutos' });
        return;
    }

    emit('submit', {
        employee_id: values.employee_id,
        fecha: values.fecha,
        dias: Number(values.dias) || 0,
        minutos: Number(values.minutos) || 0,
        observacion: values.observacion || null,
    });
});
</script>
