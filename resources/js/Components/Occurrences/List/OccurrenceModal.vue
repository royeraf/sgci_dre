<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Plus class="w-6 h-6" />
                            Registrar Nueva Ocurrencia
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">Complete los datos del evento</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="fecha" v-bind="fechaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.fecha ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha" class="mt-1 text-sm text-red-600">{{ formErrors.fecha }}</p>
                        </div>

                        <!-- Hora -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="hora" v-bind="horaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                :class="formErrors.hora ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.hora" class="mt-1 text-sm text-red-600">{{ formErrors.hora }}</p>
                        </div>

                        <!-- Turno (Automático) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Turno <span class="text-blue-500 text-xs font-normal">(automático)</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 flex items-center gap-2">
                                    <Clock class="w-4 h-4 text-slate-400" />
                                    <span class="font-medium" :class="{
                                        'text-amber-600': turno === 'Mañana',
                                        'text-orange-600': turno === 'Tarde',
                                        'text-indigo-600': turno === 'Noche'
                                    }">{{ turno || 'Seleccione una hora' }}</span>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">El turno se determina según la hora ingresada</p>
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="tipo" v-bind="tipoProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors"
                                :class="formErrors.tipo ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione tipo</option>
                                <option value="Rutina">Rutina</option>
                                <option value="Aviso">Aviso</option>
                                <option value="Incidente">Incidente</option>
                                <option value="Emergencia">Emergencia</option>
                                <option value="Robo">Robo</option>
                                <option value="Otros">Otros</option>
                            </select>
                            <p v-if="formErrors.tipo" class="mt-1 text-sm text-red-600">{{ formErrors.tipo }}</p>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Descripción <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="descripcion" v-bind="descripcionProps" rows="4"
                            placeholder="Describa detalladamente el evento ocurrido..."
                            class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors"
                            :class="formErrors.descripcion ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <p v-if="formErrors.descripcion" class="mt-1 text-sm text-red-600">{{ formErrors.descripcion }}
                        </p>
                    </div>

                    <!-- Acciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Acciones Tomadas
                        </label>
                        <textarea v-model="acciones" v-bind="accionesProps" rows="3"
                            placeholder="Describa las acciones tomadas ante el evento (opcional)..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ isSubmitting ? 'Guardando...' : 'Registrar Ocurrencia' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { Plus, X, Loader2, Clock } from 'lucide-vue-next';

const emit = defineEmits(['close']);

const isSubmitting = ref(false);

// Get shift based on hour (HH:mm format or hour number)
const getShiftFromHour = (timeValue) => {
    let hour;
    if (typeof timeValue === 'string' && timeValue.includes(':')) {
        hour = parseInt(timeValue.split(':')[0], 10);
    } else {
        hour = typeof timeValue === 'number' ? timeValue : new Date().getHours();
    }

    if (hour >= 6 && hour < 14) return 'Mañana';
    if (hour >= 14 && hour < 22) return 'Tarde';
    return 'Noche';
};

// Get current shift based on current time
const getCurrentShift = () => getShiftFromHour(new Date().getHours());

// Get current date and time
const now = new Date();
const currentDate = now.toISOString().split('T')[0];
const currentTime = now.toTimeString().slice(0, 5);

// Validation Schema
const occurrenceSchema = toTypedSchema(
    yup.object({
        fecha: yup.string().required('La fecha es obligatoria'),
        hora: yup.string().required('La hora es obligatoria'),
        turno: yup.string().required('Debe seleccionar un turno'),
        tipo: yup.string().required('Debe seleccionar un tipo de ocurrencia'),
        descripcion: yup.string()
            .required('La descripción es obligatoria')
            .min(10, 'La descripción debe tener al menos 10 caracteres'),
        acciones: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm } = useForm({
    validationSchema: occurrenceSchema,
    initialValues: {
        fecha: currentDate,
        hora: currentTime,
        turno: getCurrentShift(),
        tipo: '',
        descripcion: '',
        acciones: '',
    }
});

const [fecha, fechaProps] = defineField('fecha');
const [hora, horaProps] = defineField('hora');
const [turno, turnoProps] = defineField('turno');
const [tipo, tipoProps] = defineField('tipo');
const [descripcion, descripcionProps] = defineField('descripcion');
const [acciones, accionesProps] = defineField('acciones');

// Watch for hour changes and automatically update the shift
watch(hora, (newHora) => {
    if (newHora) {
        turno.value = getShiftFromHour(newHora);
    }
});

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;

    router.post('/occurrences', values, {
        onSuccess: () => {
            resetForm();
            emit('close');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
