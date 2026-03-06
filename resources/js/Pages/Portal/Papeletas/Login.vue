<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <div class="flex justify-center mb-4">
                    <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-4 rounded-2xl shadow-lg ring-2 ring-blue-400/30">
                        <img src="/images/logo.png" alt="DRE Huanuco" class="h-16 w-16 object-contain brightness-0 invert" />
                    </div>
                </div>
                <h2 class="text-3xl font-extrabold text-slate-900">Portal del Empleado</h2>
                <p class="mt-2 text-sm text-slate-500">Direccion Regional de Educacion Huanuco</p>
            </div>

            <!-- Login Form -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white p-8">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Error message -->
                    <div v-if="form.errors.credentials" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                        {{ form.errors.credentials }}
                    </div>

                    <!-- Flash error -->
                    <div v-if="$page.props.flash?.error" class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                        {{ $page.props.flash.error }}
                    </div>

                    <!-- DNI -->
                    <div>
                        <label for="dni" class="block text-sm font-semibold text-slate-700 mb-1.5">DNI</label>
                        <div class="relative">
                            <CreditCard class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                            <input id="dni" v-model="form.dni" type="text" maxlength="8" inputmode="numeric"
                                class="pl-10 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                placeholder="Ingrese su DNI (8 digitos)" :class="{ 'border-red-300': form.errors.dni }" />
                        </div>
                        <p v-if="form.errors.dni" class="mt-1 text-xs text-red-500">{{ form.errors.dni }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contrasena</label>
                        <div class="relative">
                            <Lock class="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />
                            <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                                class="pl-10 pr-10 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                placeholder="Ingrese su contrasena" :class="{ 'border-red-300': form.errors.password }" />
                            <button type="button" @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <EyeOff v-if="showPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>

                    <!-- Submit -->
                    <button type="submit" :disabled="form.processing"
                        class="w-full flex justify-center py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/30 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                        <Loader2 v-if="form.processing" class="h-5 w-5 animate-spin mr-2" />
                        {{ form.processing ? 'Ingresando...' : 'Ingresar al Portal' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-xs text-slate-400">
                Sistema de Gestion y Control Institucional - DRE Huanuco
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { CreditCard, Lock, Eye, EyeOff, Loader2 } from 'lucide-vue-next';

const $page = usePage();
const showPassword = ref(false);

const form = useForm({
    dni: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/portal/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>
