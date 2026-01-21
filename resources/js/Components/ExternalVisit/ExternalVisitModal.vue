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
                            <LogIn class="w-6 h-6" />
                            Registrar Ingreso de Visita
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">
                            Complete los datos del visitante
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                DNI <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="dni" v-bind="dniProps" maxlength="8" placeholder="12345678"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="errors.dni ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.dni" class="mt-1 text-sm text-red-600">{{ errors.dni }}</p>
                        </div>

                        <!-- Hora Ingreso -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora de Ingreso <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="horaIngreso" v-bind="horaIngresoProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="errors.hora_ingreso ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.hora_ingreso" class="mt-1 text-sm text-red-600">{{
                                errors.hora_ingreso }}</p>
                        </div>
                    </div>

                    <!-- Nombres y Apellidos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="nombres" v-bind="nombresProps" placeholder="Nombres"
                                @input="nombres = $event.target.value.toUpperCase()"
                                class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="errors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.nombres" class="mt-1 text-sm text-red-600">{{ errors.nombres }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="apellidos" v-bind="apellidosProps" placeholder="Apellidos"
                                @input="apellidos = $event.target.value.toUpperCase()"
                                class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors"
                                :class="errors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.apellidos" class="mt-1 text-sm text-red-600">{{ errors.apellidos }}</p>
                        </div>
                    </div>

                    <!-- Área y Persona a visitar -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Área u Oficina <span class="text-red-500">*</span>
                            </label>
                            <select v-model="areaId" v-bind="areaIdProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white"
                                :class="errors.area_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="" disabled>Seleccione un área...</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">
                                    {{ a.nombre }}
                                </option>
                            </select>
                            <p v-if="errors.area_id" class="mt-1 text-sm text-red-600">{{ errors.area_id }}
                            </p>
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
                            :class="errors.motivo ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <p v-if="errors.motivo" class="mt-1 text-sm text-red-600">{{ errors.motivo }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            Registrar Ingreso
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
import { LogIn, X, Loader2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    areas: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close']);

const isSubmitting = ref(false);
const currentTime = new Date().toTimeString().slice(0, 5);

// Validation Schema
const schema = toTypedSchema(
    yup.object({
        dni: yup.string()
            .required('El DNI es obligatorio')
            .matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos numéricos'),
        nombres: yup.string()
            .required('El nombre es obligatorio')
            .min(2, 'El nombre debe tener al menos 2 caracteres'),
        apellidos: yup.string()
            .required('El apellido es obligatorio')
            .min(2, 'El apellido debe tener al menos 2 caracteres'),
        hora_ingreso: yup.string().required('La hora de ingreso es obligatoria'),
        area_id: yup.string().required('El área u oficina es obligatorio'),
        a_quien_visita: yup.string().nullable(),
        motivo: yup.string()
            .required('El motivo de visita es obligatorio')
            .min(5, 'El motivo debe tener al menos 5 caracteres'),
    })
);

const { errors, defineField, handleSubmit, resetForm } = useForm({
    validationSchema: schema,
    initialValues: {
        dni: '',
        nombres: '',
        apellidos: '',
        hora_ingreso: currentTime,
        area_id: '',
        a_quien_visita: '',
        motivo: '',
    }
});

const [dni, dniProps] = defineField('dni');
const [nombres, nombresProps] = defineField('nombres');
const [apellidos, apellidosProps] = defineField('apellidos');
const [horaIngreso, horaIngresoProps] = defineField('hora_ingreso');
const [areaId, areaIdProps] = defineField('area_id');
const [aQuienVisita, aQuienVisitaProps] = defineField('a_quien_visita');
const [motivo, motivoProps] = defineField('motivo');

const onSubmit = handleSubmit(async (values) => {
    isSubmitting.value = true;

    router.post('/visitors', values, {
        onSuccess: (page) => {
            resetForm();
            emit('close');

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
                    confirmButtonColor: '#9333ea',
                    cancelButtonColor: '#64748b',
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
</script>
