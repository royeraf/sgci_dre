<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Wallet class="w-6 h-6" />
                            Nueva Remuneración
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">Registra la remuneración base (DL 1057) del empleado</p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Empleado <span class="text-red-500">*</span>
                        </label>
                        <select v-model="employee_id" :disabled="!!presetEmployeeId"
                            class="w-full px-4 py-2.5 border-2 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none disabled:bg-slate-100 disabled:cursor-not-allowed"
                            :class="formErrors.employee_id ? 'border-red-400' : 'border-slate-200'">
                            <option value="" disabled>Seleccione un empleado</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.dni }} - {{ emp.nombre_completo }}
                            </option>
                        </select>
                        <p v-if="formErrors.employee_id" class="mt-1 text-sm text-red-600">{{ formErrors.employee_id }}</p>
                        <p v-if="selectedEmployee" class="mt-2 text-xs text-slate-500">
                            Remuneración vigente:
                            <span class="font-bold text-slate-700">{{ formatMoney(selectedEmployee.remuneracion_base) }}</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Monto (S/) <span class="text-red-500">*</span>
                            </label>
                            <input v-model="monto" type="number" step="0.01" min="0" placeholder="0.00"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                                :class="formErrors.monto ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.monto" class="mt-1 text-sm text-red-600">{{ formErrors.monto }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Vigente desde <span class="text-red-500">*</span>
                            </label>
                            <input v-model="desde" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                                :class="formErrors.desde ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.desde" class="mt-1 text-sm text-red-600">{{ formErrors.desde }}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Motivo</label>
                        <input v-model="motivo" type="text" placeholder="Ej. Incremento por DS 327-2025"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Registrar' }}
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
import { Wallet, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    presetEmployeeId: { type: String, default: null },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    employee_id: yup.string().required('Seleccione un empleado'),
    monto: yup.number().typeError('Ingrese un monto válido').required('El monto es obligatorio').min(0, 'El monto no puede ser negativo'),
    desde: yup.string().required('La fecha es obligatoria'),
    motivo: yup.string().nullable(),
}));

const today = new Date().toISOString().slice(0, 10);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: { employee_id: '', monto: '', desde: today, motivo: '' },
});

const [employee_id] = defineField('employee_id');
const [monto] = defineField('monto');
const [desde] = defineField('desde');
const [motivo] = defineField('motivo');

const selectedEmployee = computed(() => props.employees.find((e) => e.id === employee_id.value) || null);

watch(() => props.presetEmployeeId, (id) => {
    if (id) employee_id.value = id;
}, { immediate: true });

const formatMoney = (value) => {
    if (value === null || value === undefined) return 'No registrada';
    return `S/ ${Number(value).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
};

const onSubmit = validateForm((values) => {
    emit('submit', {
        employee_id: values.employee_id,
        monto: Number(values.monto),
        desde: values.desde,
        motivo: values.motivo || null,
    });
});
</script>
