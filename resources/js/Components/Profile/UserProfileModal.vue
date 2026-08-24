<template>
    <Teleport to="body">
        <Transition name="modal" @after-leave="handleAfterLeave">
            <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div
                        class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-5">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="h-16 w-16 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center text-2xl font-bold text-white shadow-lg ring-2 ring-white/30 uppercase">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </div>
                                    <div>
                                        <h2 class="text-2xl font-bold text-white">Perfil de Usuario</h2>
                                        <p class="text-sm text-blue-100">{{ user?.email || '' }}</p>
                                    </div>
                                </div>
                                <button @click="closeModal"
                                    class="rounded-lg p-2 text-white/80 hover:bg-white/20 hover:text-white transition-all duration-200">
                                    <X class="h-6 w-6" />
                                </button>
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div class="border-b border-gray-200 bg-gray-50">
                            <div class="flex gap-2 px-6">
                                <button @click="activeTab = 'info'" :class="[
                                    'px-6 py-3 text-sm font-semibold transition-all duration-200 border-b-2',
                                    activeTab === 'info'
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                ]">
                                    <User class="h-4 w-4 inline mr-2" />
                                    Información Personal
                                </button>
                                <button @click="activeTab = 'password'" :class="[
                                    'px-6 py-3 text-sm font-semibold transition-all duration-200 border-b-2',
                                    activeTab === 'password'
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                ]">
                                    <Lock class="h-4 w-4 inline mr-2" />
                                    Cambiar Contraseña
                                </button>
                                <button @click="activeTab = 'certificate'" :class="[
                                    'px-6 py-3 text-sm font-semibold transition-all duration-200 border-b-2',
                                    activeTab === 'certificate'
                                        ? 'border-blue-600 text-blue-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                ]">
                                    <ShieldCheck class="h-4 w-4 inline mr-2" />
                                    Certificado RENIEC
                                </button>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-6 max-h-[60vh] overflow-y-auto">
                            <!-- Personal Information Tab -->
                            <div v-if="activeTab === 'info'" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">DNI</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.dni || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Título</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.titulo || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nombres</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.name || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Apellidos</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.apellidos || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cargo</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.cargo || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Área</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.area || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Teléfono</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.telefono || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.customRole?.nombre || 'N/A' }}</p>
                                    </div>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 md:col-span-2">
                                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Correo Electrónico</label>
                                        <p class="text-base font-medium text-gray-900 mt-1">{{ user?.email || 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Change Password Tab -->
                            <div v-if="activeTab === 'password'" class="space-y-4">
                                <form @submit.prevent="submitPasswordChange" class="space-y-4">
                                    <!-- Current Password -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Contraseña Actual
                                        </label>
                                        <div class="relative">
                                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                v-model="passwordForm.current_password"
                                                :type="showCurrentPassword ? 'text' : 'password'"
                                                class="w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                :class="errors.current_password ? 'border-red-500' : 'border-gray-300'"
                                                placeholder="Ingrese su contraseña actual"
                                            />
                                            <button
                                                type="button"
                                                @click="showCurrentPassword = !showCurrentPassword"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                <Eye v-if="!showCurrentPassword" class="h-5 w-5" />
                                                <EyeOff v-else class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <p v-if="errors.current_password" class="text-sm text-red-600 mt-1">{{ errors.current_password }}</p>
                                    </div>

                                    <!-- New Password -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Nueva Contraseña
                                        </label>
                                        <div class="relative">
                                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                v-model="passwordForm.new_password"
                                                :type="showNewPassword ? 'text' : 'password'"
                                                class="w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                :class="errors.new_password ? 'border-red-500' : 'border-gray-300'"
                                                placeholder="Ingrese su nueva contraseña"
                                            />
                                            <button
                                                type="button"
                                                @click="showNewPassword = !showNewPassword"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                <Eye v-if="!showNewPassword" class="h-5 w-5" />
                                                <EyeOff v-else class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <p v-if="errors.new_password" class="text-sm text-red-600 mt-1">{{ errors.new_password }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Mínimo 8 caracteres</p>
                                    </div>

                                    <!-- Confirm Password -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                                            Confirmar Nueva Contraseña
                                        </label>
                                        <div class="relative">
                                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                            <input
                                                v-model="passwordForm.new_password_confirmation"
                                                :type="showConfirmPassword ? 'text' : 'password'"
                                                class="w-full pl-10 pr-10 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                :class="errors.new_password_confirmation ? 'border-red-500' : 'border-gray-300'"
                                                placeholder="Confirme su nueva contraseña"
                                            />
                                            <button
                                                type="button"
                                                @click="showConfirmPassword = !showConfirmPassword"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                            >
                                                <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                                                <EyeOff v-else class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <p v-if="errors.new_password_confirmation" class="text-sm text-red-600 mt-1">{{ errors.new_password_confirmation }}</p>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="flex justify-end gap-3 pt-4">
                                        <button
                                            type="button"
                                            @click="resetPasswordForm"
                                            class="px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition-all duration-200"
                                        >
                                            Cancelar
                                        </button>
                                        <button
                                            type="submit"
                                            :disabled="isSubmitting"
                                            class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl"
                                        >
                                            <span v-if="!isSubmitting">Cambiar Contraseña</span>
                                            <span v-else class="flex items-center gap-2">
                                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                Procesando...
                                            </span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Certificado RENIEC Tab -->
                            <div v-if="activeTab === 'certificate'" class="space-y-4">
                                <div v-if="certificateLoading" class="text-center py-8 text-gray-400">
                                    <svg class="animate-spin h-6 w-6 mx-auto" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <template v-else>
                                    <div class="rounded-xl border p-4 flex items-start gap-3"
                                        :class="registeredCertificate ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                                        <ShieldCheck class="h-6 w-6 mt-0.5 shrink-0" :class="registeredCertificate ? 'text-emerald-600' : 'text-amber-600'" />
                                        <div>
                                            <p class="font-bold" :class="registeredCertificate ? 'text-emerald-900' : 'text-amber-900'">
                                                {{ registeredCertificate ? 'Certificado RENIEC vinculado' : 'Aún no ha vinculado su certificado RENIEC' }}
                                            </p>
                                            <p class="text-xs mt-0.5" :class="registeredCertificate ? 'text-emerald-700' : 'text-amber-700'">
                                                {{ registeredCertificate ? `Vigente hasta ${formatCertDate(registeredCertificate.valid_to)}` : 'Es obligatorio para firmar y enviar documentos digitalmente (por ejemplo, papeletas de salida).' }}
                                            </p>
                                        </div>
                                    </div>

                                    <form @submit.prevent="uploadCertificate" class="space-y-4">
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Archivo .pfx o .p12</label>
                                            <input ref="certificateFileInput" type="file" accept=".pfx,.p12" @change="handleCertificateFileChange"
                                                class="block w-full text-sm border rounded-lg p-2"
                                                :class="certificateErrors.pfx_file ? 'border-red-500' : 'border-gray-300'" />
                                            <p v-if="certificateErrors.pfx_file" class="text-sm text-red-600 mt-1">{{ certificateErrors.pfx_file }}</p>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Contraseña original del PFX</label>
                                            <input v-model="pfxPassword" v-bind="pfxPasswordProps" type="password" autocomplete="off"
                                                class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                :class="certificateErrors.pfx_password ? 'border-red-500' : 'border-gray-300'" />
                                            <p v-if="certificateErrors.pfx_password" class="text-sm text-red-600 mt-1">{{ certificateErrors.pfx_password }}</p>
                                        </div>
                                        <div class="grid sm:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Crear clave de firma</label>
                                                <input v-model="signingPin" v-bind="signingPinProps" type="password" autocomplete="new-password"
                                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                    :class="certificateErrors.signing_pin ? 'border-red-500' : 'border-gray-300'" />
                                                <p v-if="certificateErrors.signing_pin" class="text-sm text-red-600 mt-1">{{ certificateErrors.signing_pin }}</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmar clave</label>
                                                <input v-model="signingPinConfirmation" v-bind="signingPinConfirmationProps" type="password" autocomplete="new-password"
                                                    class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                                    :class="certificateErrors.signing_pin_confirmation ? 'border-red-500' : 'border-gray-300'" />
                                                <p v-if="certificateErrors.signing_pin_confirmation" class="text-sm text-red-600 mt-1">{{ certificateErrors.signing_pin_confirmation }}</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500">La nueva clave debe contener letras y números. Se solicitará en cada firma y no se almacena.</p>
                                        <div>
                                            <label class="flex items-start gap-2 rounded-lg border p-3 text-sm text-gray-700"
                                                :class="certificateErrors.consent ? 'border-red-500 bg-red-50' : 'border-gray-200 bg-gray-50'">
                                                <input v-model="consent" v-bind="consentProps" type="checkbox" class="mt-1 rounded border-gray-300" />
                                                <span>Autorizo voluntariamente el uso de este certificado para firmar mis documentos. Confirmo que soy su titular y que conozco que cada firma requerirá mi clave personal.</span>
                                            </label>
                                            <p v-if="certificateErrors.consent" class="text-sm text-red-600 mt-1">{{ certificateErrors.consent }}</p>
                                        </div>
                                        <div class="flex justify-end gap-3 pt-2">
                                            <button type="submit" :disabled="certificateUploading"
                                                class="px-6 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-lg hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-lg hover:shadow-xl">
                                                {{ certificateUploading ? 'Validando...' : (registeredCertificate ? 'Renovar certificado' : 'Vincular certificado') }}
                                            </button>
                                        </div>
                                    </form>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import { X, User, Lock, Eye, EyeOff, ShieldCheck } from 'lucide-vue-next';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const page = usePage();
const user = ref(page.props.auth?.user || {});
const activeTab = ref('info');
const isSubmitting = ref(false);

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const passwordForm = ref({
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
});

const errors = ref({});

// Clear specific error when user types in the field
watch(() => passwordForm.value.current_password, () => {
    if (errors.value.current_password) {
        delete errors.value.current_password;
    }
});

watch(() => passwordForm.value.new_password, () => {
    if (errors.value.new_password) {
        delete errors.value.new_password;
    }
});

watch(() => passwordForm.value.new_password_confirmation, () => {
    if (errors.value.new_password_confirmation) {
        delete errors.value.new_password_confirmation;
    }
});

const closeModal = () => {
    emit('close');
};

const handleAfterLeave = () => {
    activeTab.value = 'info';
    resetPasswordForm();
    resetCertificateFormState();
};

watch(() => props.show, (isOpen) => {
    if (isOpen) fetchCertificate();
});

const resetPasswordForm = () => {
    passwordForm.value = {
        current_password: '',
        new_password: '',
        new_password_confirmation: ''
    };
    errors.value = {};
    showCurrentPassword.value = false;
    showNewPassword.value = false;
    showConfirmPassword.value = false;
};

// ===== Certificado RENIEC =====
const registeredCertificate = ref(null);
const certificateLoading = ref(false);
const certificateUploading = ref(false);
const certificateFileInput = ref(null);

const MAX_PFX_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB, igual al límite del backend (10240 KB)
const PFX_EXTENSIONS = ['pfx', 'p12'];

const certificateSchema = toTypedSchema(yup.object({
    pfx_file: yup.mixed()
        .required('Seleccione el archivo .pfx o .p12.')
        .test('extension', 'El archivo debe tener extensión .pfx o .p12.', (file) => {
            if (!file) return false;
            const ext = file.name?.split('.').pop()?.toLowerCase();
            return PFX_EXTENSIONS.includes(ext);
        })
        .test('size', 'El archivo no debe superar los 10 MB.', (file) => !file || file.size <= MAX_PFX_SIZE_BYTES),
    pfx_password: yup.string().required('Ingrese la contraseña original del PFX.'),
    signing_pin: yup.string()
        .required('Cree una clave de firma.')
        .min(6, 'La clave debe tener al menos 6 caracteres.')
        .max(20, 'La clave no debe superar los 20 caracteres.')
        .matches(/^(?=.*[A-Za-z])(?=.*\d).+$/, 'La nueva clave debe contener letras y números.'),
    signing_pin_confirmation: yup.string()
        .required('Confirme la clave de firma.')
        .oneOf([yup.ref('signing_pin')], 'La confirmación de la nueva clave no coincide.'),
    consent: yup.boolean().oneOf([true], 'Debe autorizar el uso de su certificado.'),
}));

const {
    errors: certificateErrors,
    defineField: defineCertificateField,
    handleSubmit: handleCertificateSubmit,
    resetForm: resetCertificateForm,
} = useForm({
    validationSchema: certificateSchema,
    initialValues: { pfx_file: null, pfx_password: '', signing_pin: '', signing_pin_confirmation: '', consent: false },
});

const [pfxFile] = defineCertificateField('pfx_file');
const [pfxPassword, pfxPasswordProps] = defineCertificateField('pfx_password');
const [signingPin, signingPinProps] = defineCertificateField('signing_pin');
const [signingPinConfirmation, signingPinConfirmationProps] = defineCertificateField('signing_pin_confirmation');
const [consent, consentProps] = defineCertificateField('consent');

const handleCertificateFileChange = (e) => {
    pfxFile.value = e.target.files?.[0] || null;
};

const formatCertDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const fetchCertificate = async () => {
    certificateLoading.value = true;
    try {
        const res = await axios.get('/profile/certificado');
        registeredCertificate.value = res.data.certificate;
    } finally {
        certificateLoading.value = false;
    }
};

const resetCertificateFormState = () => {
    resetCertificateForm({ values: { pfx_file: null, pfx_password: '', signing_pin: '', signing_pin_confirmation: '', consent: false } });
    if (certificateFileInput.value) certificateFileInput.value.value = '';
};

const uploadCertificate = handleCertificateSubmit(async (values) => {
    certificateUploading.value = true;
    const data = new FormData();
    data.append('pfx_file', values.pfx_file);
    data.append('pfx_password', values.pfx_password);
    data.append('signing_pin', values.signing_pin);
    data.append('signing_pin_confirmation', values.signing_pin_confirmation);
    data.append('consent', values.consent ? '1' : '0');
    try {
        const response = await axios.post('/profile/certificado', data, { headers: { 'Content-Type': 'multipart/form-data' } });
        registeredCertificate.value = response.data.certificate;
        resetCertificateFormState();
        window.Swal?.fire({ icon: 'success', title: 'Certificado vinculado', text: 'Ya puede firmar sus documentos con la nueva clave.' });
    } catch (error) {
        const serverErrors = error.response?.data?.errors;
        const message = serverErrors ? Object.values(serverErrors).flat().join('\n') : (error.response?.data?.message || 'No se pudo registrar el certificado.');
        window.Swal?.fire({ icon: 'error', title: 'Certificado no registrado', text: message });
    } finally {
        certificateUploading.value = false;
    }
});

const submitPasswordChange = async () => {
    errors.value = {};

    // Basic validation
    if (!passwordForm.value.current_password) {
        errors.value.current_password = 'La contraseña actual es requerida';
        return;
    }

    if (!passwordForm.value.new_password) {
        errors.value.new_password = 'La nueva contraseña es requerida';
        return;
    }

    if (passwordForm.value.new_password.length < 8) {
        errors.value.new_password = 'La contraseña debe tener al menos 8 caracteres';
        return;
    }

    if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
        errors.value.new_password_confirmation = 'Las contraseñas no coinciden';
        return;
    }

    isSubmitting.value = true;

    router.post('/profile/password', passwordForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            resetPasswordForm();
            closeModal();
            window.Swal.fire({
                icon: 'success',
                title: 'Contraseña Actualizada',
                text: 'Su contraseña ha sido cambiada exitosamente. Por seguridad, debe iniciar sesión nuevamente.',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Logout after password change
                    router.post('/logout');
                }
            });
        },
        onError: (serverErrors) => {
            errors.value = serverErrors;
            window.Swal.fire({
                icon: 'error',
                title: 'Error',
                text: serverErrors.current_password || serverErrors.new_password || 'No se pudo cambiar la contraseña',
                confirmButtonColor: '#dc2626'
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
};

// Watch for user changes
watch(() => page.props.auth?.user, (newUser) => {
    user.value = newUser || {};
}, { deep: true });
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active > div > div,
.modal-leave-active > div > div {
    transition: transform 0.3s ease;
}

.modal-enter-from > div > div,
.modal-leave-to > div > div {
    transform: scale(0.95);
}
</style>
