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
import { X, User, Lock, Eye, EyeOff } from 'lucide-vue-next';

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
};

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
