<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 bg-gradient-to-r from-amber-600 to-orange-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xl font-bold text-white flex items-center">
                            <Key class="w-6 h-6 mr-2" />
                            Cambiar Contraseña
                        </h3>
                        <button @click="$emit('close')" class="text-white hover:text-slate-200 transition-colors">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="px-6 py-6">
                    <div class="mb-4">
                        <p class="text-sm text-slate-600">
                            Cambiar contraseña para:
                            <span class="font-bold text-slate-900">
                                {{ user.name }} {{ user.apellidos }}
                            </span>
                        </p>
                    </div>

                    <div class="space-y-4">
                        <!-- Nueva Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Nueva Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" v-model="form.password" required minlength="8"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                placeholder="Mínimo 8 caracteres" />
                            <p class="mt-1 text-xs text-slate-500">La contraseña debe tener al menos 8 caracteres</p>
                        </div>

                        <!-- Confirmar Contraseña -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Confirmar Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" v-model="form.password_confirmation" required minlength="8"
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                placeholder="Repetir contraseña" />
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-slate-200">
                        <button type="button" @click="$emit('close')" :disabled="submitting"
                            class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-amber-600 to-orange-600 rounded-lg hover:from-amber-700 hover:to-orange-700 disabled:opacity-50">
                            <span v-if="!submitting">Actualizar Contraseña</span>
                            <span v-else>Actualizando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { X, Key } from 'lucide-vue-next';

defineProps({
    user: {
        type: Object,
        required: true
    },
    submitting: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    password: '',
    password_confirmation: ''
});

const handleSubmit = () => {
    if (form.value.password !== form.value.password_confirmation) {
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: 'Las contraseñas no coinciden'
        });
        return;
    }
    emit('submit', form.value);
};
</script>
