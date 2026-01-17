<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                            {{ isEditing ? 'Editar Vehículo' : 'Registrar Vehículo' }}
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">{{ isEditing ? 'Modifique los datos del vehículo' :
                            'Complete los datos del vehículo' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="tipo" v-bind="tipoProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white transition-colors"
                                :class="formErrors.tipo ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione tipo</option>
                                <option>Automóvil</option>
                                <option>Camioneta</option>
                                <option>Minivan</option>
                                <option>Bus</option>
                                <option>Motocicleta</option>
                            </select>
                            <p v-if="formErrors.tipo" class="mt-1 text-sm text-red-600">{{ formErrors.tipo }}</p>
                        </div>

                        <!-- Placa -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Placa <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="placa" v-bind="placaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.placa ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: ABC-123">
                            <p v-if="formErrors.placa" class="mt-1 text-sm text-red-600">{{ formErrors.placa }}</p>
                        </div>

                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Marca <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="marca" v-bind="marcaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.marca ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: Toyota">
                            <p v-if="formErrors.marca" class="mt-1 text-sm text-red-600">{{ formErrors.marca }}</p>
                        </div>

                        <!-- Modelo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Modelo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="modelo" v-bind="modeloProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.modelo ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: Hilux">
                            <p v-if="formErrors.modelo" class="mt-1 text-sm text-red-600">{{ formErrors.modelo }}</p>
                        </div>

                        <!-- Año -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Año</label>
                            <input type="text" v-model="anio" v-bind="anioProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 2022">
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                            <input type="text" v-model="color" v-bind="colorProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Blanco">
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado</label>
                            <select v-model="estado" v-bind="estadoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option>Operativo</option>
                                <option>En Mantenimiento</option>
                                <option>Inoperativo</option>
                            </select>
                        </div>

                        <!-- Combustible -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Combustible</label>
                            <select v-model="combustible" v-bind="combustibleProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option>Gasolina</option>
                                <option>Diesel</option>
                                <option>GLP</option>
                            </select>
                        </div>

                        <!-- Kilometraje -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kilometraje</label>
                            <input type="text" v-model="kilometraje" v-bind="kilometrajeProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 50000">
                        </div>

                        <!-- Nº Motor -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nº Motor</label>
                            <input type="text" v-model="motor" v-bind="motorProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Fecha SOAT -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha SOAT</label>
                            <input type="date" v-model="fechaSoat" v-bind="fechaSoatProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Fecha Revisión -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Revisión Técnica</label>
                            <input type="date" v-model="fechaRevision" v-bind="fechaRevisionProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="observaciones" v-bind="observacionesProps" rows="3"
                            placeholder="Ingrese observaciones adicionales..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-violet-700 transition-all disabled:opacity-50">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Vehículo' }}
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

const props = defineProps({ vehicle: Object });
const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.vehicle?.id);
const isSubmitting = ref(false);

// Validation Schema
const vehicleSchema = toTypedSchema(
    yup.object({
        tipo: yup.string().required('El tipo de vehículo es obligatorio'),
        placa: yup.string()
            .required('La placa es obligatoria')
            .min(6, 'La placa debe tener al menos 6 caracteres'),
        marca: yup.string()
            .required('La marca es obligatoria')
            .min(2, 'La marca debe tener al menos 2 caracteres'),
        modelo: yup.string()
            .required('El modelo es obligatorio')
            .min(2, 'El modelo debe tener al menos 2 caracteres'),
        anio: yup.string().nullable(),
        motor: yup.string().nullable(),
        color: yup.string().nullable(),
        estado: yup.string().nullable(),
        kilometraje: yup.string().nullable(),
        combustible: yup.string().nullable(),
        fecha_soat: yup.string().nullable(),
        fecha_revision: yup.string().nullable(),
        observaciones: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: vehicleSchema,
    initialValues: {
        tipo: 'Automóvil',
        placa: '',
        marca: '',
        modelo: '',
        anio: '',
        motor: '',
        color: '',
        estado: 'Operativo',
        kilometraje: '',
        combustible: 'Gasolina',
        fecha_soat: '',
        fecha_revision: '',
        observaciones: '',
    }
});

const [tipo, tipoProps] = defineField('tipo');
const [placa, placaProps] = defineField('placa');
const [marca, marcaProps] = defineField('marca');
const [modelo, modeloProps] = defineField('modelo');
const [anio, anioProps] = defineField('anio');
const [motor, motorProps] = defineField('motor');
const [color, colorProps] = defineField('color');
const [estado, estadoProps] = defineField('estado');
const [kilometraje, kilometrajeProps] = defineField('kilometraje');
const [combustible, combustibleProps] = defineField('combustible');
const [fechaSoat, fechaSoatProps] = defineField('fecha_soat');
const [fechaRevision, fechaRevisionProps] = defineField('fecha_revision');
const [observaciones, observacionesProps] = defineField('observaciones');

// Load existing vehicle data if editing
onMounted(() => {
    if (props.vehicle) {
        setValues({
            tipo: props.vehicle.tipo || 'Automóvil',
            placa: props.vehicle.placa || '',
            marca: props.vehicle.marca || '',
            modelo: props.vehicle.modelo || '',
            anio: props.vehicle.anio || '',
            motor: props.vehicle.motor || '',
            color: props.vehicle.color || '',
            estado: props.vehicle.estado || 'Operativo',
            kilometraje: props.vehicle.kilometraje || '',
            combustible: props.vehicle.combustible || 'Gasolina',
            fecha_soat: props.vehicle.fecha_soat || '',
            fecha_revision: props.vehicle.fecha_revision || '',
            observaciones: props.vehicle.observaciones || '',
        });
    }
});

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/vehicles/inventory/${props.vehicle.id}`, values);
        } else {
            await axios.post('/vehicles/inventory', values);
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
