<template>
    <form @submit.prevent="handleSubmit" class="space-y-4 sm:space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <!-- DNI con consulta automática -->
            <div class="md:col-span-2">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-4 border border-blue-100">
                    <div class="flex items-center gap-2 mb-3">
                        <ScanBarcode class="w-4 h-4 text-blue-600" />
                        <span class="text-sm font-semibold text-blue-900">Datos del Solicitante</span>
                        <span class="text-xs text-blue-500 ml-auto">El DNI autocompleta apellidos y nombres</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Campo DNI con botón búsqueda -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">DNI <span
                                    class="text-red-500">*</span></label>
                            <div class="flex gap-2">
                                <div class="flex-1 relative">
                                    <input v-model="dni" v-bind="dniProps" type="text" maxlength="8"
                                        placeholder="########" @input="handleDniInput"
                                        @keypress.enter.prevent="buscarPorDni"
                                        class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base font-mono tracking-wider"
                                        :class="[
                                            formErrors.dni ? 'border-red-400' : 'border-blue-200 bg-white',
                                            isConsultandoDni ? 'opacity-60' : ''
                                        ]">
                                    <div v-if="isConsultandoDni" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <LoaderCircle class="w-4 h-4 animate-spin text-blue-500" />
                                    </div>
                                </div>
                                <button type="button" @click="buscarPorDni"
                                    :disabled="dni?.length !== 8 || isConsultandoDni"
                                    class="px-3 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1"
                                    title="Buscar datos por DNI">
                                    <Search class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="formErrors.dni" class="mt-1 text-xs text-red-600">{{ formErrors.dni }}</p>
                        </div>

                        <!-- Apellidos (autocompletado) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Apellidos <span
                                    class="text-red-500">*</span></label>
                            <input v-model="apellidos" v-bind="apellidosProps" type="text"
                                @input="apellidos = ($event.target as HTMLInputElement).value.toUpperCase()"
                                :disabled="nombreAutocompletado && !camposEditables" placeholder="Apellidos"
                                class="uppercase w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base disabled:bg-slate-50 disabled:text-slate-600"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-blue-200 bg-white'">
                            <p v-if="formErrors.apellidos" class="mt-1 text-xs text-red-600">{{ formErrors.apellidos }}
                            </p>
                        </div>

                        <!-- Nombres (autocompletado) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">Nombres <span
                                    class="text-red-500">*</span></label>
                            <input v-model="nombres" v-bind="nombresProps" type="text"
                                @input="nombres = ($event.target as HTMLInputElement).value.toUpperCase()"
                                :disabled="nombreAutocompletado && !camposEditables" placeholder="Nombres"
                                class="uppercase w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base disabled:bg-slate-50 disabled:text-slate-600"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-blue-200 bg-white'">
                            <p v-if="formErrors.nombres" class="mt-1 text-xs text-red-600">{{ formErrors.nombres }}</p>
                        </div>
                    </div>

                    <!-- Mensaje de resultado de consulta -->
                    <div v-if="dniConsultaMessage" class="mt-3 p-2.5 rounded-lg flex items-center gap-2"
                        :class="dniConsultaSuccess ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                        <CheckCircle2 v-if="dniConsultaSuccess" class="w-4 h-4 flex-shrink-0" />
                        <AlertCircle v-else class="w-4 h-4 flex-shrink-0" />
                        <span class="text-xs font-medium">{{ dniConsultaMessage }}</span>
                    </div>

                    <!-- Edición manual -->
                    <div v-if="nombreAutocompletado && !camposEditables" class="mt-2">
                        <button type="button" @click="camposEditables = true"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium underline">
                            ¿Necesita corregir los nombres manualmente?
                        </button>
                    </div>
                </div>
            </div>

            <!-- Oficina -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Oficina de Destino <span
                        class="text-red-500">*</span></label>
                <select v-model="oficina" v-bind="oficinaProps"
                    class="w-full px-3 sm:px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none transition-colors text-base"
                    :class="formErrors.oficina ? 'border-red-400' : 'border-slate-200'">
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


            <!-- Celular -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Número de Celular <span
                        class="text-red-500">*</span></label>
                <input v-model="celular" v-bind="celularProps" type="tel" maxlength="9" placeholder="9XXXXXXXX"
                    class="w-full px-3 sm:px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base"
                    :class="formErrors.celular ? 'border-red-400' : 'border-slate-200'">
                <p v-if="formErrors.celular" class="mt-1 text-sm text-red-600">{{ formErrors.celular }}</p>
            </div>

            <!-- Correo -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Correo Electrónico <span
                        class="text-red-500">*</span></label>
                <input v-model="correo" v-bind="correoProps" type="email" placeholder="ejemplo@correo.com"
                    class="w-full px-3 sm:px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base"
                    :class="formErrors.correo ? 'border-red-400' : 'border-slate-200'">
                <p v-if="formErrors.correo" class="mt-1 text-sm text-red-600">{{ formErrors.correo }}</p>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de Cita <span
                        class="text-red-500">*</span></label>
                <input v-model="fecha" v-bind="fechaProps" type="date" :min="today"
                    class="w-full px-3 sm:px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-colors text-base"
                    :class="formErrors.fecha ? 'border-red-400' : 'border-slate-200'">
                <p v-if="formErrors.fecha" class="mt-1 text-sm text-red-600">{{ formErrors.fecha }}</p>
            </div>

            <!-- Hora -->
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">Horario Disponible <span
                        class="text-red-500">*</span></label>
                <select v-model="hora" v-bind="horaProps"
                    class="w-full px-3 sm:px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none transition-colors text-base"
                    :class="formErrors.hora ? 'border-red-400' : 'border-slate-200'">
                    <option value="">Seleccione una hora</option>
                    <option v-for="time in timeSlots" :key="time" :value="time">{{ time }}</option>
                </select>
                <p v-if="formErrors.hora" class="mt-1 text-sm text-red-600">{{ formErrors.hora }}</p>
            </div>

            <!-- Asunto -->
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-slate-700 mb-2">Asunto de la Reserva <span
                        class="text-red-500">*</span></label>
                <textarea v-model="asunto" v-bind="asuntoProps" rows="4" placeholder="Describa el motivo de su cita..."
                    class="w-full px-3 sm:px-4 py-3 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none transition-colors text-base"
                    :class="formErrors.asunto ? 'border-red-400' : 'border-slate-200'"></textarea>
                <p v-if="formErrors.asunto" class="mt-1 text-sm text-red-600">{{ formErrors.asunto }}</p>
            </div>

            <!-- CAPTCHA -->
            <div class="md:col-span-2 bg-gray-50 rounded-lg p-3 sm:p-4 border border-gray-200">
                <div class="flex flex-wrap items-center gap-2 sm:gap-4">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-blue-500" />
                        <span class="text-sm font-medium text-gray-700">Verificación de seguridad</span>
                    </div>
                    <button type="button" @click="generateCaptcha"
                        class="text-blue-600 hover:text-blue-800 text-sm ml-auto" aria-label="Regenerar captcha">
                        <RefreshCw class="w-4 h-4" />
                    </button>
                </div>
                <div class="mt-3 flex flex-col sm:flex-row items-stretch sm:items-center gap-3 sm:gap-4">
                    <div
                        class="bg-white border-2 border-dashed border-gray-300 rounded-lg px-4 py-3 font-mono text-base sm:text-lg font-bold text-gray-700 select-none text-center">
                        {{ captcha.num1 }} {{ captcha.operator }} {{ captcha.num2 }} = ?
                    </div>
                    <div class="flex-1 sm:flex-initial">
                        <input v-model="captchaAnswer" type="number" placeholder="Respuesta" @keyup.enter="handleSubmit"
                            class="w-full sm:w-32 rounded-xl shadow-sm text-base border p-2.5 text-center font-semibold outline-none transition-colors"
                            :class="captchaError ? 'border-red-400 focus:ring-red-400' : 'border-slate-200 focus:ring-blue-500'">
                        <p v-if="captchaError" class="mt-1 text-sm text-red-600">{{ captchaError }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 sm:pt-6 border-t border-slate-100">
            <button type="submit" :disabled="loading"
                class="w-full sm:w-auto px-6 sm:px-8 py-3 sm:py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all shadow-lg shadow-blue-500/20 disabled:opacity-50 transform active:scale-95 flex items-center justify-center text-base"
                :aria-label="loading ? 'Procesando solicitud' : 'Reservar cita'">
                <LoaderCircle v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
                <span>{{ loading ? 'Procesando...' : 'Reservar Cita' }}</span>
            </button>
        </div>
    </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import Swal from 'sweetalert2';
import { ShieldCheck, RefreshCw, LoaderCircle, Search, ScanBarcode, CheckCircle2, AlertCircle } from 'lucide-vue-next';

const emit = defineEmits(['success']);

const loading = ref(false);

// DNI Consultation
const isConsultandoDni = ref(false);
const dniConsultaMessage = ref('');
const dniConsultaSuccess = ref(false);
const nombreAutocompletado = ref(false);
const camposEditables = ref(true);
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

// DNI input handler: limpiar estado y auto-consultar al llegar a 8 dígitos
const handleDniInput = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const cleanValue = input.value.replace(/\D/g, '');
    dni.value = cleanValue;
    dniConsultaMessage.value = '';
    nombreAutocompletado.value = false;
    camposEditables.value = true;

    if (cleanValue.length === 8) {
        setTimeout(() => {
            if (dni.value?.length === 8) buscarPorDni();
        }, 150);
    }
};

// Consulta a la API de DNI
const buscarPorDni = async () => {
    if (!dni.value || dni.value.length !== 8) return;

    isConsultandoDni.value = true;
    dniConsultaMessage.value = '';

    try {
        const response = await axios.get('/visitors/api/consultar-dni', { params: { dni: dni.value } });

        if (response.data.success && response.data.data) {
            const persona = response.data.data;

            nombres.value = persona.nombres;

            if (persona.apellido_paterno || persona.apellido_materno) {
                apellidos.value = `${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
            }

            nombreAutocompletado.value = true;
            camposEditables.value = false;
            dniConsultaMessage.value = `Datos encontrados: ${persona.nombre_completo || (nombres.value + ' ' + apellidos.value)}`;
            dniConsultaSuccess.value = true;
        } else {
            dniConsultaMessage.value = response.data.message || 'DNI no encontrado. Ingrese los datos manualmente.';
            dniConsultaSuccess.value = false;
            camposEditables.value = true;
        }
    } catch {
        dniConsultaMessage.value = 'Error al consultar DNI. Ingrese los datos manualmente.';
        dniConsultaSuccess.value = false;
        camposEditables.value = true;
    } finally {
        isConsultandoDni.value = false;
    }
};

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
        nombreAutocompletado.value = false;
        camposEditables.value = true;
        dniConsultaMessage.value = '';
        dniConsultaSuccess.value = false;
    } catch (error: unknown) {
        const axiosError = error as { response?: { data?: { message?: string } } };
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: axiosError.response?.data?.message || 'Hubo un error al registrar la cita'
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
