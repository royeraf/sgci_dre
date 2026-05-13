<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <User class="w-6 h-6 mr-2" />
                            Detalles del Usuario
                        </h3>
                        <button @click="$emit('close')" class="text-white hover:text-slate-200 transition-colors">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-6">
                    <!-- User Avatar and Name -->
                    <div class="flex items-center mb-6 pb-6 border-b border-slate-200">
                        <div
                            class="flex-shrink-0 h-20 w-20 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white text-2xl font-bold">
                            {{ getInitials(user.name, user.apellidos) }}
                        </div>
                        <div class="ml-4">
                            <h4 class="text-2xl font-bold text-slate-900">
                                {{ user.titulo ? user.titulo + ' ' : '' }}{{ user.name }} {{ user.apellidos }}
                            </h4>
                            <div class="flex items-center mt-1 space-x-2">
                                <span
                                    :class="[user.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                    <span :class="[user.is_active ? 'bg-emerald-400' : 'bg-slate-400', 'w-2 h-2 rounded-full mr-1.5']"></span>
                                    {{ user.is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    <Shield class="w-3 h-3 mr-1" />
                                    {{ user.rol_nombre || 'Sin rol' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- User Information Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">DNI</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.dni }}</p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Email</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.email }}</p>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Teléfono</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.telefono || '-' }}</p>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cargo</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.cargo || '-' }}</p>
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Área</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.area || '-' }}</p>
                        </div>

                        <!-- Último Acceso -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Último Acceso</label>
                            <p class="text-sm text-slate-900 font-medium">
                                {{ user.ultimo_acceso ? formatDate(user.ultimo_acceso) : 'Nunca' }}
                            </p>
                        </div>

                        <!-- Fecha de Registro -->
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Registrado</label>
                            <p class="text-sm text-slate-900 font-medium">{{ user.created_at || '-' }}</p>
                        </div>
                    </div>

                    <!-- 2FA status indicator -->
                    <div v-if="user.two_factor_enabled !== undefined" class="mt-6 flex items-center gap-2 text-xs font-semibold"
                        :class="user.two_factor_enabled ? 'text-emerald-700' : 'text-slate-400'">
                        <ShieldCheck class="w-4 h-4" />
                        {{ user.two_factor_enabled ? '2FA activo en esta cuenta' : '2FA no configurado' }}
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap justify-between gap-3 mt-6 pt-6 border-t border-slate-200">
                        <button v-if="user.two_factor_enabled"
                            @click="resetTwoFactor"
                            :disabled="resetting2fa"
                            class="flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-lg hover:bg-amber-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                            <Loader2 v-if="resetting2fa" class="w-4 h-4 animate-spin" />
                            <ShieldOff v-else class="w-4 h-4" />
                            {{ resetting2fa ? 'Procesando...' : 'Desactivar 2FA' }}
                        </button>
                        <div class="flex gap-3 ml-auto">
                            <button @click="$emit('close')"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                                Cerrar
                            </button>
                            <button @click="$emit('edit', user)"
                                class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-indigo-600 to-purple-600 rounded-lg hover:from-indigo-700 hover:to-purple-700">
                                <Edit class="w-4 h-4 inline mr-1" />
                                Editar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, User, Shield, Edit, ShieldCheck, ShieldOff, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    user: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'edit', '2fa-reset']);

const resetting2fa = ref(false);

const resetTwoFactor = async () => {
    if (!confirm(`¿Desactivar el 2FA de ${props.user.full_name || props.user.name}? El usuario podrá ingresar sin código de verificación.`)) return;

    resetting2fa.value = true;
    try {
        await fetch(`/admin/users/${props.user.id}/2fa`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
        });
        emit('2fa-reset', props.user.id);
        emit('close');
        window.Swal?.fire({
            icon: 'success',
            title: '2FA desactivado',
            text: `El 2FA de ${props.user.full_name || props.user.name} ha sido desactivado. Ya puede ingresar sin código.`,
            confirmButtonColor: '#2563eb',
        });
    } catch {
        window.Swal?.fire({ icon: 'error', title: 'Error', text: 'No se pudo desactivar el 2FA.', confirmButtonColor: '#dc2626' });
    } finally {
        resetting2fa.value = false;
    }
};

const getInitials = (name, apellidos) => {
    const n = name?.charAt(0) || '';
    const a = apellidos?.charAt(0) || '';
    return (n + a).toUpperCase() || 'U';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>
