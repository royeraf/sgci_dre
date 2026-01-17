<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            {{ isEditing ? 'Editar Vacaciones' : 'Registrar Vacaciones' }}
                        </h3>
                        <p class="text-orange-50 text-sm mt-1">{{ isEditing ? 'Editar' : 'Registrar vacaciones' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-orange-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Empleado <span
                                class="text-red-500">*</span></label>
                        <select v-model="empleadoId"
                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-orange-500 bg-white"
                            :class="formErrors.empleado_id ? 'border-red-400' : 'border-slate-200'">
                            <option value="">Seleccione al empleado...</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">{{ emp.dni }} - {{
                                emp.nombres }} {{ emp.apellidos }}</option>
                        </select>
                        <p v-if="formErrors.empleado_id" class="mt-1 text-sm text-red-600">{{ formErrors.empleado_id }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Inicio <span
                                    class="text-red-500">*</span></label>
                            <input v-model="fechaInicio" type="date"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-orange-500"
                                :class="formErrors.fecha_inicio ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_inicio" class="mt-1 text-sm text-red-600">{{
                                formErrors.fecha_inicio }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Fin <span
                                    class="text-red-500">*</span></label>
                            <input v-model="fechaFin" type="date"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-orange-500"
                                :class="formErrors.fecha_fin ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_fin" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_fin }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="observaciones" rows="3" placeholder="Detalles adicionales..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Registrar') }}
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
import { X, CalendarRange, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    submitting: { type: Boolean, default: false },
    vacation: { type: Object, default: null },
    isEditing: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    empleado_id: yup.string().required('Debe seleccionar un empleado'),
    fecha_inicio: yup.string().required('La fecha de inicio es obligatoria'),
    fecha_fin: yup.string().required('La fecha de fin es obligatoria'),
    observaciones: yup.string().nullable(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: { empleado_id: '', fecha_inicio: '', fecha_fin: '', observaciones: '' }
});

const [empleadoId] = defineField('empleado_id');
const [fechaInicio] = defineField('fecha_inicio');
const [fechaFin] = defineField('fecha_fin');
const [observaciones] = defineField('observaciones');

watch(() => props.vacation, (v) => {
    if (v && props.isEditing) {
        setValues({ empleado_id: v.empleado_id || '', fecha_inicio: v.fecha_inicio || '', fecha_fin: v.fecha_fin || '', observaciones: v.observaciones || '' });
    } else {
        setValues({ empleado_id: '', fecha_inicio: '', fecha_fin: '', observaciones: '' });
    }
}, { immediate: true });

const onSubmit = validateForm((values) => emit('submit', values));
</script>
