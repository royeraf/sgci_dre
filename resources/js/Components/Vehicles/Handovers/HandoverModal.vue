<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nueva Acta de Entrega y Recepción
                        </h3>
                        <p class="text-amber-100 text-sm mt-1">Complete el formulario del acta vehicular</p>
                    </div>
                    <button @click="$emit('close')" class="text-amber-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <!-- Datos Generales -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h4 class="font-bold text-amber-800 mb-4">Datos Generales del Vehículo</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Fecha <span
                                        class="text-red-500">*</span></label>
                                <input type="date" v-model="fecha" v-bind="fechaProps"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors"
                                    :class="formErrors.fecha ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="formErrors.fecha" class="mt-1 text-sm text-red-600">{{ formErrors.fecha }}</p>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Entidad</label>
                                <input type="text" v-model="entidad" v-bind="entidadProps"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Placa <span
                                        class="text-red-500">*</span></label>
                                <input type="text" v-model="placa" v-bind="placaProps"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors"
                                    :class="formErrors.placa ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="formErrors.placa" class="mt-1 text-sm text-red-600">{{ formErrors.placa }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Kilometraje <span
                                        class="text-red-500">*</span></label>
                                <input type="text" v-model="kilometraje" v-bind="kilometrajeProps"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors"
                                    :class="formErrors.kilometraje ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="formErrors.kilometraje" class="mt-1 text-sm text-red-600">{{
                                    formErrors.kilometraje }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                                <input type="text" v-model="color" v-bind="colorProps"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- Estado de Sistemas -->
                    <div>
                        <h4 class="font-bold text-slate-800 mb-3">Estado de Sistemas del Vehículo</h4>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-4 bg-slate-50">
                            <div v-for="(group, idx) in systemGroups" :key="idx" class="bg-white rounded-lg p-3 border">
                                <div class="font-semibold text-amber-700 text-sm mb-2">{{ idx + 1 }}. {{ group.name }}
                                </div>
                                <div class="space-y-2">
                                    <div v-for="item in group.items" :key="item"
                                        class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600">{{ item }}</span>
                                        <select v-model="sistemas[item]"
                                            class="text-xs px-2 py-1 border rounded-lg w-20">
                                            <option value="">-</option>
                                            <option value="Bueno">Bueno</option>
                                            <option value="Regular">Regular</option>
                                            <option value="Malo">Malo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Responsables -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Jefe de Abastecimiento</label>
                            <input type="text" v-model="abastecimiento" v-bind="abastecimientoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Recepciona <span
                                    class="text-red-500">*</span></label>
                            <input type="text" v-model="recepciona" v-bind="recepcionaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-amber-500 transition-colors"
                                :class="formErrors.recepciona ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.recepciona" class="mt-1 text-sm text-red-600">{{ formErrors.recepciona
                                }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:from-amber-600 hover:to-orange-600 disabled:opacity-50">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Acta' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';

const emit = defineEmits(['close', 'saved']);
const isSubmitting = ref(false);
const currentDate = new Date().toISOString().split('T')[0];

const systemGroups = [
    { name: 'MOTOR', items: ['CILINDRO', 'CULATA', 'CARBURADOR'] },
    { name: 'FRENOS', items: ['BOMBA DE FRENOS', 'ZAPATA Y TAMBORES'] },
    { name: 'REFRIGERACIÓN', items: ['RADIADOR', 'VENTILADOR'] },
    { name: 'ELÉCTRICO', items: ['BATERIA', 'ALTERNADOR', 'FAROS'] },
    { name: 'TRANSMISIÓN', items: ['CAJA DE CAMBIO', 'DIFERENCIAL'] },
    { name: 'SUSPENSIÓN', items: ['AMORTIGUADORES', 'LLANTAS'] }
];

// Initialize sistemas reactively (not validated by vee-validate)
const initSistemas = () => {
    const s = {};
    systemGroups.forEach(g => g.items.forEach(i => s[i] = ''));
    return s;
};
const sistemas = reactive(initSistemas());

// Validation Schema
const handoverSchema = toTypedSchema(
    yup.object({
        fecha: yup.string().required('La fecha es obligatoria'),
        entidad: yup.string().nullable(),
        placa: yup.string()
            .required('La placa es obligatoria')
            .min(6, 'La placa debe tener al menos 6 caracteres'),
        kilometraje: yup.string().required('El kilometraje es obligatorio'),
        color: yup.string().nullable(),
        abastecimiento: yup.string().nullable(),
        recepciona: yup.string()
            .required('El nombre de quien recepciona es obligatorio')
            .min(3, 'Debe tener al menos 3 caracteres'),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm } = useForm({
    validationSchema: handoverSchema,
    initialValues: {
        fecha: currentDate,
        entidad: '',
        placa: '',
        kilometraje: '',
        color: '',
        abastecimiento: '',
        recepciona: '',
    }
});

const [fecha, fechaProps] = defineField('fecha');
const [entidad, entidadProps] = defineField('entidad');
const [placa, placaProps] = defineField('placa');
const [kilometraje, kilometrajeProps] = defineField('kilometraje');
const [color, colorProps] = defineField('color');
const [abastecimiento, abastecimientoProps] = defineField('abastecimiento');
const [recepciona, recepcionaProps] = defineField('recepciona');

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        // Combine validated values with sistemas
        const payload = { ...values, sistemas };
        await axios.post('/vehicles/handovers', payload);
        resetForm();
        // Reset sistemas
        Object.keys(sistemas).forEach(k => sistemas[k] = '');
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
