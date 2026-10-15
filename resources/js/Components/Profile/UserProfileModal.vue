<template>
    <Teleport to="body">
        <Transition name="modal" @after-leave="handleAfterLeave">
            <div v-if="show" class="fixed inset-0 z-[9999] overflow-y-auto">
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

                <!-- Modal Container -->
                <div class="flex min-h-full items-center justify-center p-4 sm:p-6">
                    <div
                        class="relative w-full max-w-4xl transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all flex flex-col md:flex-row md:h-[600px] border border-slate-100">

                        <!-- Sidebar -->
                        <div class="w-full md:w-72 bg-slate-50 border-r border-slate-200/60 flex-shrink-0 flex flex-col relative overflow-hidden">
                            <!-- Background decoration -->
                            <div class="absolute top-0 left-0 w-full h-32 bg-gradient-to-br from-blue-600 to-indigo-700 opacity-10"></div>

                            <!-- User Profile Summary -->
                            <div class="px-6 py-8 text-center border-b border-slate-200/60 bg-transparent relative z-10">
                                <div class="relative inline-block">
                                    <div class="h-24 w-24 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-4xl font-bold text-white shadow-lg shadow-blue-500/30 ring-4 ring-white transform transition-transform hover:scale-105 duration-300">
                                        {{ user?.name?.charAt(0) || 'U' }}
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 h-6 w-6 bg-emerald-500 border-4 border-white rounded-full shadow-sm"></div>
                                </div>
                                <h3 class="mt-5 text-xl font-bold text-slate-900 tracking-tight leading-tight">{{ user?.full_name || user?.name || 'Usuario' }}</h3>
                                <p class="text-sm font-semibold text-blue-600 mt-1">{{ user?.customRole?.nombre || 'Rol no asignado' }}</p>
                                <div class="flex items-center justify-center gap-1 mt-2 text-xs text-slate-500 bg-white py-1 px-3 rounded-full border border-slate-200 shadow-sm inline-flex">
                                    <span class="truncate max-w-[150px]">{{ user?.email || 'Sin correo' }}</span>
                                </div>
                            </div>

                            <!-- Navigation Menu -->
                            <div class="p-4 flex-1 overflow-y-auto hidden md:block z-10">
                                <nav class="space-y-1.5">
                                    <button @click="activeTab = 'info'" :class="[
                                        'w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all duration-200',
                                        activeTab === 'info'
                                            ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200/50 scale-[1.02]'
                                            : 'text-slate-600 hover:bg-slate-200/50 hover:text-slate-900'
                                    ]">
                                        <div :class="['p-2 rounded-lg transition-colors', activeTab === 'info' ? 'bg-blue-50' : 'bg-slate-100']">
                                            <User class="h-4 w-4" :class="activeTab === 'info' ? 'text-blue-600' : 'text-slate-500'" />
                                        </div>
                                        Información Personal
                                    </button>
                                    <button @click="activeTab = 'password'" :class="[
                                        'w-full flex items-center gap-3 px-4 py-3.5 text-sm font-semibold rounded-xl transition-all duration-200',
                                        activeTab === 'password'
                                            ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200/50 scale-[1.02]'
                                            : 'text-slate-600 hover:bg-slate-200/50 hover:text-slate-900'
                                    ]">
                                        <div :class="['p-2 rounded-lg transition-colors', activeTab === 'password' ? 'bg-blue-50' : 'bg-slate-100']">
                                            <Lock class="h-4 w-4" :class="activeTab === 'password' ? 'text-blue-600' : 'text-slate-500'" />
                                        </div>
                                        Seguridad
                                    </button>
                                </nav>
                            </div>

                            <!-- Mobile Navigation Tabs -->
                            <div class="md:hidden flex overflow-x-auto border-b border-slate-200 bg-slate-50 p-3 gap-2 hide-scrollbar">
                                <button @click="activeTab = 'info'" :class="[
                                    'whitespace-nowrap px-4 py-2.5 text-sm font-semibold rounded-xl transition-all flex-1 text-center',
                                    activeTab === 'info' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-600 hover:bg-slate-200/50'
                                ]">Información</button>
                                <button @click="activeTab = 'password'" :class="[
                                    'whitespace-nowrap px-4 py-2.5 text-sm font-semibold rounded-xl transition-all flex-1 text-center',
                                    activeTab === 'password' ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200/50' : 'text-slate-600 hover:bg-slate-200/50'
                                ]">Seguridad</button>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="flex-1 flex flex-col h-full bg-white relative">
                            <!-- Close Button -->
                            <button @click="closeModal"
                                class="absolute top-5 right-5 z-20 p-2 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors bg-white/80 backdrop-blur-sm shadow-sm ring-1 ring-slate-200/50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <X class="h-5 w-5" />
                            </button>

                            <!-- Dynamic Header based on active tab -->
                            <div class="px-8 pt-8 pb-6 border-b border-slate-100 bg-white/95 backdrop-blur-md sticky top-0 z-10">
                                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">
                                    <span v-if="activeTab === 'info'">Información Personal</span>
                                    <span v-else-if="activeTab === 'password'">Seguridad de la Cuenta</span>
                                </h2>
                                <p class="text-sm font-medium text-slate-500 mt-1.5">
                                    <span v-if="activeTab === 'info'">Revise los datos personales asociados a su cuenta en el sistema.</span>
                                    <span v-else-if="activeTab === 'password'">Actualice su contraseña periódicamente para mantener su cuenta protegida.</span>
                                </p>
                            </div>

                            <!-- Scrollable Content -->
                            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar bg-[#f8fafc]">
                                <Transition name="fade" mode="out-in">

                                    <!-- Personal Information Tab -->
                                    <div v-if="activeTab === 'info'" class="space-y-6 max-w-2xl mx-auto" key="info">
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-blue-50 rounded-lg text-blue-600"><User class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Nombres</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.name || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-indigo-50 rounded-lg text-indigo-600"><Users class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Apellidos</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.apellidos || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600"><CreditCard class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">DNI</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.dni || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-sky-50 rounded-lg text-sky-600"><AtSign class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Usuario</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10 break-all">{{ user?.username || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-purple-50 rounded-lg text-purple-600"><GraduationCap class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Título</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.titulo || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-amber-50 rounded-lg text-amber-600"><Briefcase class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cargo</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.cargo || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-rose-50 rounded-lg text-rose-600"><Building class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Área</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.area || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-cyan-50 rounded-lg text-cyan-600"><Phone class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Teléfono</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.telefono || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-orange-50 rounded-lg text-orange-600"><Shield class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rol</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10">{{ user?.customRole?.nombre || 'N/A' }}</p>
                                            </div>

                                            <div class="bg-white rounded-xl p-4 border border-slate-200/60 shadow-sm hover:shadow-md transition-shadow sm:col-span-2">
                                                <div class="flex items-center gap-3 mb-1">
                                                    <div class="p-1.5 bg-slate-100 rounded-lg text-slate-600"><Mail class="h-4 w-4" /></div>
                                                    <label class="text-xs font-bold text-slate-500 uppercase tracking-wider">Correo Electrónico</label>
                                                </div>
                                                <p class="text-[15px] font-semibold text-slate-900 mt-2 ml-10 break-all">{{ user?.email || 'N/A' }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Change Password Tab -->
                                    <div v-else-if="activeTab === 'password'" class="max-w-xl mx-auto" key="password">
                                        <form @submit.prevent="submitPasswordChange" class="space-y-5 bg-white p-6 rounded-2xl border border-slate-200/60 shadow-sm">
                                            <!-- Current Password -->
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                                    Contraseña Actual
                                                </label>
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                        <Lock class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                                                    </div>
                                                    <input
                                                        v-model="passwordForm.current_password"
                                                        :type="showCurrentPassword ? 'text' : 'password'"
                                                        class="w-full pl-11 pr-11 py-3 bg-slate-50/50 border rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                                        :class="errors.current_password ? 'border-red-400 bg-red-50/30 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200'"
                                                        placeholder="Ingrese su contraseña actual"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="showCurrentPassword = !showCurrentPassword"
                                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                                    >
                                                        <Eye v-if="!showCurrentPassword" class="h-5 w-5" />
                                                        <EyeOff v-else class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <p v-if="errors.current_password" class="text-sm font-medium text-red-500 mt-1.5 flex items-center gap-1.5">
                                                    <AlertCircle class="h-4 w-4" /> {{ errors.current_password }}
                                                </p>
                                            </div>

                                            <div class="border-t border-slate-100 my-2"></div>

                                            <!-- New Password -->
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                                    Nueva Contraseña
                                                </label>
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                        <Key class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                                                    </div>
                                                    <input
                                                        v-model="passwordForm.new_password"
                                                        :type="showNewPassword ? 'text' : 'password'"
                                                        class="w-full pl-11 pr-11 py-3 bg-slate-50/50 border rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 outline-none"
                                                        :class="errors.new_password ? 'border-red-400 bg-red-50/30 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200'"
                                                        placeholder="Ingrese su nueva contraseña"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="showNewPassword = !showNewPassword"
                                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                                    >
                                                        <Eye v-if="!showNewPassword" class="h-5 w-5" />
                                                        <EyeOff v-else class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <p v-if="errors.new_password" class="text-sm font-medium text-red-500 mt-1.5 flex items-center gap-1.5">
                                                    <AlertCircle class="h-4 w-4" /> {{ errors.new_password }}
                                                </p>
                                                <p v-else class="text-xs text-slate-500 mt-1.5 flex items-center gap-1">
                                                    <Info class="h-3.5 w-3.5" /> Mínimo 8 caracteres
                                                </p>
                                            </div>

                                            <!-- Confirm Password -->
                                            <div>
                                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                                    Confirmar Nueva Contraseña
                                                </label>
                                                <div class="relative group">
                                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                                        <Key class="h-5 w-5 text-slate-400 group-focus-within:text-indigo-500 transition-colors" />
                                                    </div>
                                                    <input
                                                        v-model="passwordForm.new_password_confirmation"
                                                        :type="showConfirmPassword ? 'text' : 'password'"
                                                        class="w-full pl-11 pr-11 py-3 bg-slate-50/50 border rounded-xl text-slate-800 placeholder-slate-400 focus:bg-white focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 outline-none"
                                                        :class="errors.new_password_confirmation ? 'border-red-400 bg-red-50/30 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-200'"
                                                        placeholder="Confirme su nueva contraseña"
                                                    />
                                                    <button
                                                        type="button"
                                                        @click="showConfirmPassword = !showConfirmPassword"
                                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                                                    >
                                                        <Eye v-if="!showConfirmPassword" class="h-5 w-5" />
                                                        <EyeOff v-else class="h-5 w-5" />
                                                    </button>
                                                </div>
                                                <p v-if="errors.new_password_confirmation" class="text-sm font-medium text-red-500 mt-1.5 flex items-center gap-1.5">
                                                    <AlertCircle class="h-4 w-4" /> {{ errors.new_password_confirmation }}
                                                </p>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="flex justify-end gap-3 pt-6 mt-2">
                                                <button
                                                    type="button"
                                                    @click="resetPasswordForm"
                                                    class="px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-slate-200"
                                                >
                                                    Limpiar
                                                </button>
                                                <button
                                                    type="submit"
                                                    :disabled="isSubmitting"
                                                    class="px-6 py-2.5 text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl hover:from-blue-700 hover:to-indigo-700 disabled:opacity-70 disabled:cursor-not-allowed transition-all duration-200 shadow-lg shadow-blue-500/30 hover:shadow-xl hover:shadow-blue-500/40 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 flex items-center"
                                                >
                                                    <span v-if="!isSubmitting">Actualizar Contraseña</span>
                                                    <span v-else class="flex items-center gap-2">
                                                        <Loader2 class="animate-spin h-4 w-4" />
                                                        Procesando...
                                                    </span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </Transition>
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
import {
    X, User, Lock, Eye, EyeOff, AlertCircle, Loader2, Users, CreditCard,
    GraduationCap, Briefcase, Building, Phone, Shield, Mail, Key, Info, AtSign
} from 'lucide-vue-next';

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
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.modal-enter-from > div > div,
.modal-leave-to > div > div {
    transform: scale(0.95) translateY(10px);
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
    opacity: 0;
    transform: translateY(5px);
}

.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
