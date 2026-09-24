<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            Fechas de Contrato
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">Define el periodo vigente del contrato del empleado</p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div class="px-4 py-3 rounded-xl bg-slate-50 border border-slate-200">
                        <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Empleado</p>
                        <p class="text-sm font-semibold text-slate-800 mt-0.5">
                            {{ row.dni }} — {{ row.nombre_completo }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaInicio" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                                :class="formErrors.fecha_inicio_contrato ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_inicio_contrato" class="mt-1 text-sm text-red-600">
                                {{ formErrors.fecha_inicio_contrato }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de fin
                                <span v-if="!indeterminado" class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaFin" type="date" :disabled="indeterminado"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                                :class="formErrors.fecha_fin_contrato ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_fin_contrato" class="mt-1 text-sm text-red-600">
                                {{ formErrors.fecha_fin_contrato }}
                            </p>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 px-4 py-3 rounded-xl bg-slate-50 border border-slate-200 cursor-pointer select-none">
                        <input v-model="indeterminado" type="checkbox" class="peer sr-only" />
                        <span
                            class="relative w-11 h-6 rounded-full bg-slate-300 transition-colors peer-checked:bg-teal-600 peer-focus:ring-4 peer-focus:ring-teal-500/20 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-transform peer-checked:after:translate-x-5">
                        </span>
                        <span class="text-sm font-bold text-slate-700">Contrato indeterminado</span>
                        <span class="text-xs text-slate-400 ml-auto">Sin fecha de finalización</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { CalendarRange, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    row: { type: Object, required: true },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    fecha_inicio_contrato: yup.string().required('La fecha de inicio es obligatoria'),
    fecha_fin_contrato: yup.string().nullable().when('indeterminado', {
        is: false,
        then: (s) => s
            .required('La fecha de fin es obligatoria (o marca indeterminado)')
            .test('after-inicio', 'Debe ser posterior a la fecha de inicio', (value, ctx) => {
                if (!value) return true;
                return value >= ctx.parent.fecha_inicio_contrato;
            }),
        otherwise: (s) => s.nullable(),
    }),
    indeterminado: yup.boolean(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: {
        fecha_inicio_contrato: '',
        fecha_fin_contrato: '',
        indeterminado: false,
    },
});

const [fechaInicio] = defineField('fecha_inicio_contrato');
const [fechaFin] = defineField('fecha_fin_contrato');
const [indeterminado] = defineField('indeterminado');

watch(indeterminado, (on) => {
    if (on) fechaFin.value = '';
});

watch(() => props.row, (row) => {
    const inicio = row.fecha_inicio_contrato || row.fecha_ingreso || '';
    const fin = row.fecha_fin_contrato || '';
    setValues({
        fecha_inicio_contrato: inicio,
        fecha_fin_contrato: fin,
        indeterminado: !!inicio && !fin,
    });
}, { immediate: true });

const onSubmit = validateForm((formValues) => {
    emit('submit', {
        fecha_inicio_contrato: formValues.fecha_inicio_contrato,
        fecha_fin_contrato: formValues.indeterminado ? null : (formValues.fecha_fin_contrato || null),
    });
});
</script>
