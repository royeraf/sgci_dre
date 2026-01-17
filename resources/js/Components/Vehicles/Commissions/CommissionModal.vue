<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-sky-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ isEditing ? 'Gestionar Comisión' : 'Nueva Comisión' }}
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">{{ isEditing ? 'Actualice los datos de la comisión' :
                            'Complete los datos de la comisión' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Dependencia -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Dependencia <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="dependencia" v-bind="dependenciaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.dependencia ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: Dirección Regional de Educación">
                            <p v-if="formErrors.dependencia" class="mt-1 text-sm text-red-600">{{ formErrors.dependencia
                                }}</p>
                        </div>

                        <!-- Lugar -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Lugar de Destino <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="lugar" v-bind="lugarProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.lugar ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: Lima - Ministerio de Educación">
                            <p v-if="formErrors.lugar" class="mt-1 text-sm text-red-600">{{ formErrors.lugar }}</p>
                        </div>

                        <!-- Día -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="dia" v-bind="diaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.dia ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.dia" class="mt-1 text-sm text-red-600">{{ formErrors.dia }}</p>
                        </div>

                        <!-- Hora -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora Programada <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="hora" v-bind="horaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.hora ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.hora" class="mt-1 text-sm text-red-600">{{ formErrors.hora }}</p>
                        </div>

                        <!-- Vehículo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Vehículo</label>
                            <select v-model="vehicleId" v-bind="vehicleIdProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">Seleccionar vehículo</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }} {{
                                    v.modelo }}</option>
                            </select>
                        </div>

                        <!-- Chofer -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Chofer <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="chofer" v-bind="choferProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.chofer ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Nombre del conductor">
                            <p v-if="formErrors.chofer" class="mt-1 text-sm text-red-600">{{ formErrors.chofer }}</p>
                        </div>

                        <!-- Usuarios -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pasajeros</label>
                            <input type="text" v-model="usuarios" v-bind="usuariosProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nombres de los pasajeros separados por coma">
                        </div>

                        <!-- Motivo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Motivo de la Comisión</label>
                            <textarea v-model="motivo" v-bind="motivoProps" rows="3"
                                placeholder="Describa el motivo de la comisión..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Sección de Control (solo en edición) -->
                    <div v-if="isEditing" class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-4">
                        <h4 class="font-bold text-blue-800 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Control de Tiempos
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Salida</label>
                                <input type="time" v-model="horaSalida" v-bind="horaSalidaProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Regreso</label>
                                <input type="time" v-model="horaRegreso" v-bind="horaRegresoProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-sky-500 text-white font-bold rounded-xl hover:from-blue-700 hover:to-sky-600 transition-all disabled:opacity-50">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Comisión' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';

const props = defineProps({ commission: Object, vehicles: Array });
const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.commission?.id);
const isSubmitting = ref(false);

// Current date and time
const currentDate = new Date().toISOString().split('T')[0];
const currentTime = new Date().toTimeString().slice(0, 5);

// Validation Schema
const commissionSchema = toTypedSchema(
    yup.object({
        dependencia: yup.string()
            .required('La dependencia es obligatoria')
            .min(3, 'La dependencia debe tener al menos 3 caracteres'),
        lugar: yup.string()
            .required('El lugar de destino es obligatorio')
            .min(3, 'El lugar debe tener al menos 3 caracteres'),
        dia: yup.string().required('La fecha es obligatoria'),
        hora: yup.string().required('La hora programada es obligatoria'),
        vehicle_id: yup.string().nullable(),
        chofer: yup.string()
            .required('El nombre del chofer es obligatorio')
            .min(3, 'El nombre del chofer debe tener al menos 3 caracteres'),
        usuarios: yup.string().nullable(),
        motivo: yup.string().nullable(),
        hora_salida: yup.string().nullable(),
        hora_regreso: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm, setValues } = useForm({
    validationSchema: commissionSchema,
    initialValues: {
        dependencia: '',
        lugar: '',
        dia: currentDate,
        hora: currentTime,
        vehicle_id: '',
        chofer: '',
        usuarios: '',
        motivo: '',
        hora_salida: '',
        hora_regreso: '',
    }
});

const [dependencia, dependenciaProps] = defineField('dependencia');
const [lugar, lugarProps] = defineField('lugar');
const [dia, diaProps] = defineField('dia');
const [hora, horaProps] = defineField('hora');
const [vehicleId, vehicleIdProps] = defineField('vehicle_id');
const [chofer, choferProps] = defineField('chofer');
const [usuarios, usuariosProps] = defineField('usuarios');
const [motivo, motivoProps] = defineField('motivo');
const [horaSalida, horaSalidaProps] = defineField('hora_salida');
const [horaRegreso, horaRegresoProps] = defineField('hora_regreso');

// Load existing commission data if editing
onMounted(() => {
    if (props.commission) {
        setValues({
            dependencia: props.commission.dependencia || '',
            lugar: props.commission.lugar || '',
            dia: props.commission.dia || currentDate,
            hora: props.commission.hora || currentTime,
            vehicle_id: props.commission.vehicle_id || '',
            chofer: props.commission.chofer || '',
            usuarios: props.commission.usuarios || '',
            motivo: props.commission.motivo || '',
            hora_salida: props.commission.hora_salida || '',
            hora_regreso: props.commission.hora_regreso || '',
        });
    }
});

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/vehicles/commissions/${props.commission.id}`, values);
        } else {
            await axios.post('/vehicles/commissions', values);
        }
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        isSubmitting.value = false;
    }
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
