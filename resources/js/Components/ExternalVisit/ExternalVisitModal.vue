<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="visit ? LogOut : LogIn" class="w-6 h-6" />
                            {{ visit ? 'Registrar Salida de Visita' : 'Registrar Ingreso de Visita' }}
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">
                            {{ visit ? `Registrar hora de salida` : 'Complete los datos del visitante' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Exit mode: just show visit info and ask for exit time -->
                    <template v-if="visit">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                                    {{ (visit.nombres || '?').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-lg">{{ visit.nombres }}</p>
                                    <p class="text-sm text-slate-600">DNI: {{ visit.dni }}</p>
                                    <p class="text-sm text-slate-500">Ingresó a las {{ visit.hora_ingreso }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora de Salida <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="horaSalida" v-bind="horaSalidaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="exitFormErrors.hora_salida ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="exitFormErrors.hora_salida" class="mt-1 text-sm text-red-600">{{
                                exitFormErrors.hora_salida }}</p>
                        </div>
                    </template>

                    <!-- New visit mode -->
                    <template v-else>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- DNI -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    DNI <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="dni" v-bind="dniProps" maxlength="8" placeholder="12345678"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                    :class="formErrors.dni ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.dni" class="mt-1 text-sm text-red-600">{{ formErrors.dni }}</p>
                            </div>

                            <!-- Hora Ingreso -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Hora de Ingreso <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="horaIngreso" v-bind="horaIngresoProps"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                    :class="formErrors.hora_ingreso ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.hora_ingreso" class="mt-1 text-sm text-red-600">{{
                                    formErrors.hora_ingreso }}</p>
                            </div>
                        </div>

                        <!-- Nombre Visitante -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre del Visitante <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="nombres" v-bind="nombresProps" placeholder="Nombres y Apellidos"
                                @input="nombres = $event.target.value.toUpperCase()"
                                class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{
                                formErrors.nombres }}</p>
                        </div>

                        <!-- Área y Persona a visitar -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Área u Oficina <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="area" v-bind="areaProps" placeholder="Ej. Tesorería"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                    :class="formErrors.area ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.area" class="mt-1 text-sm text-red-600">{{ formErrors.area }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    A quién visita
                                </label>
                                <input type="text" v-model="aQuienVisita" v-bind="aQuienVisitaProps"
                                    placeholder="Nombre personal (Opcional)"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors" />
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Motivo de Visita <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="motivo" v-bind="motivoProps" rows="3"
                                placeholder="Indique el motivo de la visita..."
                                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none transition-colors"
                                :class="formErrors.motivo ? 'border-red-400' : 'border-slate-200'"></textarea>
                            <p v-if="formErrors.motivo" class="mt-1 text-sm text-red-600">{{ formErrors.motivo }}</p>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ visit ? 'Registrar Salida' : 'Registrar Ingreso' }}
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
import { router } from '@inertiajs/vue3';
import { LogIn, LogOut, X, Loader2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    visit: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const isSubmitting = ref(false);
const currentTime = new Date().toTimeString().slice(0, 5);

// Validation Schema for entry form
const entrySchema = toTypedSchema(
    yup.object({
        dni: yup.string()
            .required('El DNI es obligatorio')
            .matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos numéricos'),
        nombres: yup.string()
            .required('El nombre es obligatorio')
            .min(3, 'El nombre debe tener al menos 3 caracteres'),
        hora_ingreso: yup.string().required('La hora de ingreso es obligatoria'),
        area: yup.string().required('El área u oficina es obligatorio'),
        a_quien_visita: yup.string().nullable(),
        motivo: yup.string()
            .required('El motivo de visita es obligatorio')
            .min(5, 'El motivo debe tener al menos 5 caracteres'),
    })
);

// Validation Schema for exit form
const exitSchema = toTypedSchema(
    yup.object({
        hora_salida: yup.string().required('La hora de salida es obligatoria'),
    })
);

// Entry form
const { errors: formErrors, defineField: defineEntryField, handleSubmit: validateEntryForm, resetForm } = useForm({
    validationSchema: entrySchema,
    initialValues: {
        dni: '',
        nombres: '',
        hora_ingreso: currentTime,
        area: '',
        a_quien_visita: '',
        motivo: '',
    }
});

const [dni, dniProps] = defineEntryField('dni');
const [nombres, nombresProps] = defineEntryField('nombres');
const [horaIngreso, horaIngresoProps] = defineEntryField('hora_ingreso');
const [area, areaProps] = defineEntryField('area');
const [aQuienVisita, aQuienVisitaProps] = defineEntryField('a_quien_visita');
const [motivo, motivoProps] = defineEntryField('motivo');

// Exit form
const { errors: exitFormErrors, defineField: defineExitField, handleSubmit: validateExitForm, resetForm: resetExitForm } = useForm({
    validationSchema: exitSchema,
    initialValues: {
        hora_salida: currentTime,
    }
});

const [horaSalida, horaSalidaProps] = defineExitField('hora_salida');

// Submit entry form
const onSubmitEntry = validateEntryForm(async (values) => {
    isSubmitting.value = true;

    router.post('/visitors', values, {
        onSuccess: (page) => {
            resetForm();
            emit('close');

            // Show SweetAlert confirmation to print
            const newVisitId = page.props.flash.new_visit_id;

            if (newVisitId) {
                Swal.fire({
                    title: 'Visita Registrada',
                    html: `
                        <p class="mb-2">La visita se registró correctamente.</p>
                        <p class="text-sm text-slate-500">¿Desea imprimir el ticket de ingreso?</p>
                    `,
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#9333ea', // purple-600
                    cancelButtonColor: '#64748b', // slate-500
                    confirmButtonText: 'Sí, Imprimir Ticket',
                    cancelButtonText: 'No, gracias',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        const url = `/visitors/${newVisitId}/ticket`;
                        const win = window.open(url, '_blank');
                        if (!win) {
                            window.location.href = url;
                        }
                    }
                });
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

// Submit exit form
const onSubmitExit = validateExitForm(async (values) => {
    isSubmitting.value = true;

    router.patch(`/visitors/${props.visit.id}/exit`, values, {
        onSuccess: () => {
            resetExitForm();
            emit('close');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

const handleSubmit = () => {
    if (props.visit) {
        onSubmitExit();
    } else {
        onSubmitEntry();
    }
};
</script>
