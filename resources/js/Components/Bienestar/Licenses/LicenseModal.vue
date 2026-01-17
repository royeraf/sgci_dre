<template>
    <div v-show="show" class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="$emit('close')">
            </div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <FilePlus class="w-6 h-6" />
                            Registrar Nueva Licencia
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">Complete los datos de la solicitud</p>
                    </div>
                    <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Seleccionar Empleado <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Users class="h-4 w-4 text-slate-400" />
                                </div>
                                <select v-model="dni" @change="onEmployeeSelect"
                                    class="w-full pl-10 pr-8 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white transition-all font-medium appearance-none"
                                    :class="formErrors.dni ? 'border-red-400' : 'border-slate-200'">
                                    <option value="" disabled>Seleccione un empleado...</option>
                                    <option v-for="emp in employees" :key="emp.id" :value="emp.dni">
                                        {{ emp.apellidos }} {{ emp.nombres }} ({{ emp.dni }})
                                    </option>
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <ChevronDown class="h-4 w-4 text-slate-400" />
                                </div>
                            </div>
                            <p v-if="formErrors.dni" class="text-sm text-red-600">{{ formErrors.dni }}</p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Tipo de Licencia <span class="text-red-500">*</span>
                            </label>
                            <select v-model="tipoLicencia"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white transition-all font-medium"
                                :class="formErrors.tipo_licencia ? 'border-red-400' : 'border-slate-200'">
                                <option value="" disabled>Seleccione un tipo</option>
                                <option value="Enfermedad">Enfermedad</option>
                                <option value="Maternidad">Maternidad</option>
                                <option value="Paternidad">Paternidad</option>
                                <option value="Personal">Personal</option>
                                <option value="Otros">Otros</option>
                            </select>
                            <p v-if="formErrors.tipo_licencia" class="text-sm text-red-600">{{ formErrors.tipo_licencia
                                }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="employeeFound">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">Nombres</label>
                            <input v-model="nombres" type="text" readOnly
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">Apellidos</label>
                            <input v-model="apellidos" type="text" readOnly
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-600 font-medium cursor-not-allowed">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Fecha Inicio <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaInicio" type="date"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-medium"
                                :class="formErrors.fecha_inicio ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.fecha_inicio" class="text-sm text-red-600">{{ formErrors.fecha_inicio }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Fecha Fin <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaFin" type="date"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-medium"
                                :class="formErrors.fecha_fin ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.fecha_fin" class="text-sm text-red-600">{{ formErrors.fecha_fin }}</p>
                        </div>
                    </div>

                    <!-- Balance Check Info -->
                    <div v-if="calculatedDays > 0"
                        class="rounded-xl p-4 bg-purple-50 border border-purple-100 flex items-start gap-4">
                        <div
                            class="bg-white p-2.5 rounded-xl shadow-sm border border-purple-100 flex items-center justify-center shrink-0">
                            <Calendar class="w-6 h-6 text-purple-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-sm font-bold text-purple-900">Resumen de solicitud</span>
                                <span
                                    class="text-sm font-extrabold text-purple-700 bg-white px-3 py-1 rounded-lg border border-purple-100 shadow-sm">
                                    {{ calculatedDays }} {{ calculatedDays === 1 ? 'Día' : 'Días' }}
                                </span>
                            </div>
                            <div v-if="employeeFound" class="flex items-center gap-2 text-sm font-medium mt-1"
                                :class="hasEnoughDays ? 'text-emerald-700' : 'text-rose-700'">
                                <CheckCircle v-if="hasEnoughDays" class="w-4 h-4" />
                                <AlertTriangle v-else class="w-4 h-4" />
                                <span class="truncate">
                                    {{ hasEnoughDays ? 'Saldo suficiente' : 'Saldo insuficiente' }}
                                    <span class="opacity-75">({{ employeeFound.dias_disponibles }}
                                        disponibles)</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-bold text-slate-700">
                            Motivo / Justificación
                        </label>
                        <textarea v-model="motivo" rows="3"
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all font-medium resize-none"
                            placeholder="Detalle el motivo institucional de la licencia..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting || !hasEnoughDays"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-indigo-700 transition-all shadow-lg shadow-purple-200 disabled:opacity-50 disabled:shadow-none flex items-center justify-center gap-2">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
                            <span>{{ isSubmitting ? 'Guardando...' : 'Confirmar Licencia' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import Swal from 'sweetalert2';
import {
    FilePlus, X, Calendar, Users, ChevronDown,
    CheckCircle, AlertTriangle, Loader2
} from 'lucide-vue-next';

const props = defineProps({
    show: Boolean,
    employees: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'success']);

const isSubmitting = ref(false);
const employeeFound = ref(null);

// Validation Schema
const licenseSchema = toTypedSchema(
    yup.object({
        dni: yup.string().required('Debe seleccionar un empleado'),
        nombres: yup.string().nullable(),
        apellidos: yup.string().nullable(),
        tipo_licencia: yup.string().required('Debe seleccionar un tipo de licencia'),
        fecha_inicio: yup.string().required('La fecha de inicio es obligatoria'),
        fecha_fin: yup.string().required('La fecha de fin es obligatoria'),
        motivo: yup.string().nullable(),
        cargo: yup.string().nullable(),
        area: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm, setFieldValue, values } = useForm({
    validationSchema: licenseSchema,
    initialValues: {
        dni: '',
        nombres: '',
        apellidos: '',
        tipo_licencia: '',
        fecha_inicio: '',
        fecha_fin: '',
        motivo: '',
        cargo: '',
        area: '',
    }
});

const [dni] = defineField('dni');
const [nombres] = defineField('nombres');
const [apellidos] = defineField('apellidos');
const [tipoLicencia] = defineField('tipo_licencia');
const [fechaInicio] = defineField('fecha_inicio');
const [fechaFin] = defineField('fecha_fin');
const [motivo] = defineField('motivo');

const calculatedDays = computed(() => {
    if (!values.fecha_inicio || !values.fecha_fin) return 0;
    const start = new Date(values.fecha_inicio);
    const end = new Date(values.fecha_fin);
    const diffTime = Math.abs(end - start);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
    return diffDays > 0 ? diffDays : 0;
});

const hasEnoughDays = computed(() => {
    if (!employeeFound.value) return true;
    return (employeeFound.value.dias_disponibles >= calculatedDays.value);
});

const onEmployeeSelect = () => {
    const emp = props.employees.find(e => e.dni === dni.value);
    if (emp) {
        employeeFound.value = emp;
        setFieldValue('nombres', emp.nombres);
        setFieldValue('apellidos', emp.apellidos);
        setFieldValue('cargo', emp.cargo);
        setFieldValue('area', emp.area);
    } else {
        employeeFound.value = null;
        setFieldValue('nombres', '');
        setFieldValue('apellidos', '');
    }
};

const onSubmitForm = validateForm(async (formValues) => {
    if (!hasEnoughDays.value) return;

    isSubmitting.value = true;
    try {
        const res = await axios.post('/bienestar/api/register', formValues);

        Swal.fire({
            icon: 'success',
            title: '¡Operación Exitosa!',
            text: res.data.message,
            timer: 3000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });

        emit('success');
        emit('close');
        resetForm();
        employeeFound.value = null;
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo registrar la licencia'
        });
    } finally {
        isSubmitting.value = false;
    }
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
