<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-pink-600 to-rose-500 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Requerimientos de Servicios del Vehículo
                        </h3>
                        <p class="text-pink-100 text-sm mt-1">Registre la solicitud de servicio</p>
                    </div>
                    <button @click="$emit('close')" class="text-pink-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Conductor -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre del conductor/a <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="conductor" v-bind="conductorProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 transition-colors"
                                :class="formErrors.conductor ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Nombre completo del conductor">
                            <p v-if="formErrors.conductor" class="mt-1 text-sm text-red-600">{{ formErrors.conductor }}
                            </p>
                        </div>

                        <!-- Vehículo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Vehículo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="vehicleId" v-bind="vehicleIdProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 bg-white transition-colors"
                                :class="formErrors.vehicle_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccionar vehículo</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }} {{
                                    v.modelo }}</option>
                            </select>
                            <p v-if="formErrors.vehicle_id" class="mt-1 text-sm text-red-600">{{ formErrors.vehicle_id
                                }}</p>
                        </div>

                        <!-- Estado del vehículo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del vehículo</label>
                            <input type="text" v-model="estadoVehiculo" v-bind="estadoVehiculoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Estado del motor -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del motor</label>
                            <input type="text" v-model="estadoMotor" v-bind="estadoMotorProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Sistema eléctrico -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Encendido y sistema
                                eléctrico</label>
                            <input type="text" v-model="encendidoElectrico" v-bind="encendidoElectricoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Transmisión -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Transmisión</label>
                            <input type="text" v-model="transmision" v-bind="transmisionProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Pintura y carrocería -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pintura y carrocería</label>
                            <input type="text" v-model="pinturaCarroceria" v-bind="pinturaCarroceriaProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Llantas -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado de llantas</label>
                            <input type="text" v-model="llantas" v-bind="llantasProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Comisiones realizadas -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Comisiones realizadas</label>
                            <textarea v-model="comisionesRealizadas" v-bind="comisionesRealizadasProps" rows="2"
                                placeholder="Describa las comisiones realizadas..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none"></textarea>
                        </div>

                        <!-- Motivo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Motivo de la solicitud <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="motivo" v-bind="motivoProps" rows="3"
                                placeholder="Describa el motivo por el cual solicita el servicio..."
                                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none transition-colors"
                                :class="formErrors.motivo ? 'border-red-400' : 'border-slate-200'"></textarea>
                            <p v-if="formErrors.motivo" class="mt-1 text-sm text-red-600">{{ formErrors.motivo }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-500 text-white font-bold rounded-xl hover:from-pink-700 hover:to-rose-600 transition-all disabled:opacity-50">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isSubmitting ? 'Guardando...' : 'Registrar Solicitud' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';

const props = defineProps({ vehicles: Array });
const emit = defineEmits(['close', 'saved']);
const isSubmitting = ref(false);

// Validation Schema
const serviceReqSchema = toTypedSchema(
    yup.object({
        conductor: yup.string()
            .required('El nombre del conductor es obligatorio')
            .min(3, 'El nombre debe tener al menos 3 caracteres'),
        vehicle_id: yup.string().required('Debe seleccionar un vehículo'),
        estado_vehiculo: yup.string().nullable(),
        estado_motor: yup.string().nullable(),
        encendido_electrico: yup.string().nullable(),
        transmision: yup.string().nullable(),
        pintura_carroceria: yup.string().nullable(),
        llantas: yup.string().nullable(),
        comisiones_realizadas: yup.string().nullable(),
        motivo: yup.string()
            .required('El motivo de la solicitud es obligatorio')
            .min(10, 'El motivo debe tener al menos 10 caracteres'),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm } = useForm({
    validationSchema: serviceReqSchema,
    initialValues: {
        conductor: '',
        vehicle_id: '',
        estado_vehiculo: '',
        estado_motor: '',
        encendido_electrico: '',
        transmision: '',
        pintura_carroceria: '',
        llantas: '',
        comisiones_realizadas: '',
        motivo: '',
    }
});

const [conductor, conductorProps] = defineField('conductor');
const [vehicleId, vehicleIdProps] = defineField('vehicle_id');
const [estadoVehiculo, estadoVehiculoProps] = defineField('estado_vehiculo');
const [estadoMotor, estadoMotorProps] = defineField('estado_motor');
const [encendidoElectrico, encendidoElectricoProps] = defineField('encendido_electrico');
const [transmision, transmisionProps] = defineField('transmision');
const [pinturaCarroceria, pinturaCarroceriaProps] = defineField('pintura_carroceria');
const [llantas, llantasProps] = defineField('llantas');
const [comisionesRealizadas, comisionesRealizadasProps] = defineField('comisiones_realizadas');
const [motivo, motivoProps] = defineField('motivo');

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        await axios.post('/vehicles/service-requirements', values);
        resetForm();
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
