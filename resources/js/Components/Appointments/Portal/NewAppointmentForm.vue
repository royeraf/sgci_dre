<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- DNI -->
            <div>
                <label class="block text-sm font-medium text-gray-700">DNI <span class="text-red-500">*</span></label>
                <input v-model="dni" v-bind="dniProps" type="text" maxlength="8"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.dni ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.dni" class="mt-1 text-sm text-red-600">{{ formErrors.dni }}</p>
            </div>

            <!-- Oficina -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Oficina de Destino <span
                        class="text-red-500">*</span></label>
                <select v-model="oficina" v-bind="oficinaProps"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.oficina ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                    <option value="">Seleccione una oficina</option>
                    <option>Dirección</option>
                    <option>Administración</option>
                    <option>Gestión Pedagógica</option>
                    <option>Gestión Institucional</option>
                    <option>Asesoría Jurídica</option>
                    <option>Recursos Humanos</option>
                    <option>Mesa de Partes</option>
                </select>
                <p v-if="formErrors.oficina" class="mt-1 text-sm text-red-600">{{ formErrors.oficina }}</p>
            </div>

            <!-- Apellidos -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Apellidos <span
                        class="text-red-500">*</span></label>
                <input v-model="apellidos" v-bind="apellidosProps" type="text"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.apellidos ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}</p>
            </div>

            <!-- Nombres -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Nombres <span
                        class="text-red-500">*</span></label>
                <input v-model="nombres" v-bind="nombresProps" type="text"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.nombres ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}</p>
            </div>

            <!-- Celular -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Número de Celular <span
                        class="text-red-500">*</span></label>
                <input v-model="celular" v-bind="celularProps" type="tel" maxlength="9" placeholder="9XXXXXXXX"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.celular ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.celular" class="mt-1 text-sm text-red-600">{{ formErrors.celular }}</p>
            </div>

            <!-- Correo -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Correo Electrónico <span
                        class="text-red-500">*</span></label>
                <input v-model="correo" v-bind="correoProps" type="email" placeholder="ejemplo@correo.com"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.correo ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.correo" class="mt-1 text-sm text-red-600">{{ formErrors.correo }}</p>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha de Cita <span
                        class="text-red-500">*</span></label>
                <input v-model="fecha" v-bind="fechaProps" type="date" :min="today"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.fecha ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                <p v-if="formErrors.fecha" class="mt-1 text-sm text-red-600">{{ formErrors.fecha }}</p>
            </div>

            <!-- Hora -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Horario Disponible <span
                        class="text-red-500">*</span></label>
                <select v-model="hora" v-bind="horaProps"
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.hora ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                    <option value="">Seleccione una hora</option>
                    <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                </select>
                <p v-if="formErrors.hora" class="mt-1 text-sm text-red-600">{{ formErrors.hora }}</p>
            </div>

            <!-- Asunto -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Asunto de la Reserva <span
                        class="text-red-500">*</span></label>
                <textarea v-model="asunto" v-bind="asuntoProps" rows="3" placeholder="Describa el motivo de su cita..."
                    :class="['mt-1 block w-full rounded-md shadow-sm sm:text-sm border p-2 transition-colors',
                        formErrors.asunto ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']"></textarea>
                <p v-if="formErrors.asunto" class="mt-1 text-sm text-red-600">{{ formErrors.asunto }}</p>
            </div>

            <!-- CAPTCHA -->
            <div class="md:col-span-2 bg-gray-50 rounded-lg p-4 border border-gray-200">
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-blue-500" />
                        <span class="text-sm font-medium text-gray-700">Verificación de seguridad</span>
                    </div>
                    <button type="button" @click="generateCaptcha" class="text-blue-600 hover:text-blue-800 text-sm">
                        <RefreshCw class="w-4 h-4" />
                    </button>
                </div>
                <div class="mt-3 flex items-center gap-4">
                    <div
                        class="bg-white border-2 border-dashed border-gray-300 rounded-lg px-4 py-2 font-mono text-lg font-bold text-gray-700 select-none">
                        {{ captcha.num1 }} {{ captcha.operator }} {{ captcha.num2 }} = ?
                    </div>
                    <div>
                        <input v-model="captchaAnswer" type="number" placeholder="Respuesta" @keyup.enter="handleSubmit"
                            :class="['w-28 rounded-md shadow-sm sm:text-sm border p-2 text-center font-semibold transition-colors',
                                captchaError ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500']">
                        <p v-if="captchaError" class="mt-1 text-sm text-red-600">{{ captchaError }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <button type="submit" :disabled="loading"
                class="ml-3 inline-flex justify-center py-3 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50">
                <LoaderCircle v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
                {{ loading ? 'Procesando...' : 'Reservar Cita' }}
            </button>
        </div>
    </form>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import Swal from 'sweetalert2';
import { ShieldCheck, RefreshCw, LoaderCircle } from 'lucide-vue-next';

const emit = defineEmits(['success']);

const loading = ref(false);
const today = new Date().toISOString().split('T')[0];

// Validation Schema
const registrationSchema = toTypedSchema(
    yup.object({
        dni: yup.string().required('El DNI es obligatorio').matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos'),
        oficina: yup.string().required('Debe seleccionar una oficina'),
        apellidos: yup.string().required('Los apellidos son obligatorios').min(2, 'Mínimo 2 caracteres'),
        nombres: yup.string().required('Los nombres son obligatorios').min(2, 'Mínimo 2 caracteres'),
        celular: yup.string().required('El celular es obligatorio').matches(/^9\d{8}$/, 'El celular debe tener 9 dígitos y empezar con 9'),
        correo: yup.string().required('El correo es obligatorio').email('Ingrese un correo válido'),
        fecha: yup.string().required('Debe seleccionar una fecha'),
        hora: yup.string().required('Debe seleccionar una hora'),
        asunto: yup.string().required('El asunto es obligatorio').min(10, 'El asunto debe tener al menos 10 caracteres')
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm } = useForm({
    validationSchema: registrationSchema,
});

const [dni, dniProps] = defineField('dni');
const [oficina, oficinaProps] = defineField('oficina');
const [apellidos, apellidosProps] = defineField('apellidos');
const [nombres, nombresProps] = defineField('nombres');
const [celular, celularProps] = defineField('celular');
const [correo, correoProps] = defineField('correo');
const [fecha, fechaProps] = defineField('fecha');
const [hora, horaProps] = defineField('hora');
const [asunto, asuntoProps] = defineField('asunto');

// CAPTCHA
const captcha = ref({ num1: 0, num2: 0, operator: '+', answer: 0 });
const captchaAnswer = ref('');
const captchaError = ref('');

const generateCaptcha = () => {
    const operators = ['+', '-', 'x'];
    const operator = operators[Math.floor(Math.random() * operators.length)];
    let num1, num2, answer;

    if (operator === '+') {
        num1 = Math.floor(Math.random() * 20) + 1;
        num2 = Math.floor(Math.random() * 20) + 1;
        answer = num1 + num2;
    } else if (operator === '-') {
        num1 = Math.floor(Math.random() * 20) + 10;
        num2 = Math.floor(Math.random() * num1);
        answer = num1 - num2;
    } else {
        num1 = Math.floor(Math.random() * 10) + 1;
        num2 = Math.floor(Math.random() * 10) + 1;
        answer = num1 * num2;
    }

    captcha.value = { num1, num2, operator, answer };
    captchaAnswer.value = '';
    captchaError.value = '';
};

generateCaptcha();

// Time Slots
const timeSlots = computed(() => {
    const slots = [];
    let startHour = 8;
    let startMin = 0;
    const endHour = 17;
    const endMin = 30;

    while (startHour < endHour || (startHour === endHour && startMin <= endMin)) {
        const h = startHour.toString().padStart(2, '0');
        const m = startMin.toString().padStart(2, '0');
        slots.push(`${h}:${m}`);
        startMin += 30;
        if (startMin >= 60) {
            startMin = 0;
            startHour++;
        }
    }
    return slots;
});

// CAPTCHA Validation
const validateCaptcha = () => {
    captchaError.value = '';
    if (!captchaAnswer.value) {
        captchaError.value = 'Por favor resuelva la operación matemática';
        return false;
    }
    if (parseInt(captchaAnswer.value) !== captcha.value.answer) {
        captchaError.value = 'Respuesta incorrecta. Intente nuevamente.';
        generateCaptcha();
        return false;
    }
    return true;
};

// Form Submit
const onSubmitForm = validateForm(async (values) => {
    loading.value = true;
    try {
        const response = await axios.post('/citas/request', values);
        const citaData = { ...values, id: response.data.id, estado: 'PENDIENTE' };

        await Swal.fire({
            icon: 'success',
            title: 'Cita Registrada',
            text: 'Su cita ha sido registrada con éxito. Se generará su constancia.',
            confirmButtonText: 'Descargar Voucher',
        }).then((result) => {
            if (result.isConfirmed) {
                emit('success', citaData);
            }
        });

        resetForm();
        generateCaptcha();
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'Hubo un error al registrar la cita'
        });
    } finally {
        loading.value = false;
    }
});

const handleSubmit = () => {
    if (!validateCaptcha()) return;
    onSubmitForm();
};
</script>
