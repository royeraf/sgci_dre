<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            Nuevo Periodo
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">Planilla CAS mensual</p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Mes</label>
                            <select v-model="mes"
                                class="w-full px-4 py-2.5 border-2 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 outline-none"
                                :class="formErrors.mes ? 'border-red-400' : 'border-slate-200'">
                                <option v-for="(nombre, numero) in MESES" :key="numero" :value="Number(numero)">
                                    {{ nombre }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Año</label>
                            <input v-model="anio" type="number" min="2000" max="2100"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 outline-none"
                                :class="formErrors.anio ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.anio" class="mt-1 text-sm text-red-600">{{ formErrors.anio }}</p>
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 bg-slate-50 rounded-xl px-3 py-2">
                        Solo se permite <strong>una planilla por mes</strong>.
                    </p>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { CalendarRange, X, Loader2 } from 'lucide-vue-next';

defineProps({
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const MESES = {
    1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril', 5: 'Mayo', 6: 'Junio',
    7: 'Julio', 8: 'Agosto', 9: 'Setiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre',
};

const schema = toTypedSchema(yup.object({
    anio: yup.number().typeError('Ingrese un año válido').required('El año es obligatorio').min(2000).max(2100),
    mes: yup.number().required('El mes es obligatorio').min(1).max(12),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm } = useForm({
    validationSchema: schema,
    initialValues: { anio: new Date().getFullYear(), mes: new Date().getMonth() + 1 },
});

const [anio] = defineField('anio');
const [mes] = defineField('mes');

const onSubmit = validateForm((values) => {
    emit('submit', { anio: Number(values.anio), mes: Number(values.mes) });
});
</script>
