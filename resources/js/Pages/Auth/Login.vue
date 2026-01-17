<template>
  <div class="flex min-h-screen w-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Left Side Image -->
    <div class="w-1/2 hidden lg:flex relative overflow-hidden">
      <img src="/images/login-bg.png" alt="Background" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 via-indigo-900/80 to-purple-900/80"></div>

      <!-- Decorative Elements -->
      <div class="absolute top-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-blue-400/20 rounded-full blur-3xl"></div>

      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <div
            class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md rounded-full mb-6 border border-white/30">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <span class="text-white text-sm font-semibold">Sistema Integral Activo</span>
          </div>
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Sistema de Gestión y Control Institucional</h2>
          <p class="text-xl text-blue-100 leading-relaxed font-light">SGCI-DREH | Administración Integral</p>
          <div class="mt-8 flex items-center gap-4">
            <div class="flex items-center gap-2 text-white/80">
              <Shield class="w-5 h-5" />
              <span class="text-sm font-medium">Seguro</span>
            </div>
            <div class="flex items-center gap-2 text-white/80">
              <Zap class="w-5 h-5" />
              <span class="text-sm font-medium">Eficiente</span>
            </div>
            <div class="flex items-center gap-2 text-white/80">
              <Lock class="w-5 h-5" />
              <span class="text-sm font-medium">Centralizado</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Side Form -->
    <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-8 bg-white/50 backdrop-blur-sm">
      <div class="w-full max-w-md">
        <!-- Logo/Header Card -->
        <div class="text-center mb-10">
          <div
            class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl shadow-2xl mb-6 ring-4 ring-blue-50 transform hover:scale-110 transition-transform duration-300">
            <img src="/images/logo.png" alt="DRE Logo"
              class="w-full h-full object-contain drop-shadow-md brightness-0 invert p-3" />
          </div>
          <h2
            class="text-3xl font-extrabold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent mb-2">
            Bienvenido de nuevo
          </h2>
          <p class="text-slate-500 text-sm font-medium">SGCI-DREH | Sistema de Gestión y Control Institucional</p>
        </div>

        <!-- Error Alert -->
        <div class="mb-6 min-h-[60px]">
          <div v-if="form.errors.credentials" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <div class="flex-shrink-0">
                <AlertCircle class="h-5 w-5 text-red-600" />
              </div>
              <p class="text-sm font-semibold text-red-800">{{ form.errors.credentials }}</p>
            </div>
          </div>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border-2 border-slate-200 p-8">
          <form class="space-y-8" @submit.prevent="handleLogin">
            <!-- DNI Field -->
            <div class="group relative">
              <label for="dni" class="block text-sm font-semibold text-slate-700 mb-2">
                DNI <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <IdCard class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                </div>
                <input v-model="form.dni" type="text" id="dni" maxlength="8" :class="[
                  'block w-full pl-10 pr-4 py-3 border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4',
                  form.errors.dni
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100'
                    : 'border-slate-200 bg-white hover:border-blue-300 focus:border-blue-500 focus:ring-blue-100'
                ]" placeholder="DNI (8 dígitos)">
              </div>
              <p v-if="form.errors.dni" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" />
                {{ form.errors.dni }}
              </p>
            </div>

            <!-- Password Field -->
            <div class="group relative">
              <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                Contraseña <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-slate-400 group-focus-within:text-blue-500 transition-colors" />
                </div>
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password" :class="[
                  'block w-full pl-10 pr-12 py-3 border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4',
                  form.errors.password
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100'
                    : 'border-slate-200 bg-white hover:border-blue-300 focus:border-blue-500 focus:ring-blue-100'
                ]" placeholder="••••••••">
                <!-- Toggle Password Visibility Button -->
                <button type="button" @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-600 transition-colors duration-200">
                  <Eye v-if="!showPassword" class="h-5 w-5" />
                  <EyeOff v-else class="h-5 w-5" />
                </button>
              </div>
              <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" />
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="form.processing"
              class="w-full inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg hover:shadow-xl transition-all duration-300 ease-in-out transform hover:scale-105 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed">
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              <LogIn v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Ingresando...' : 'Ingresar al Sistema' }}
            </button>
          </form>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <p class="text-sm text-slate-500">
            © 2026 DRE Huánuco. Todos los derechos reservados.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
  Shield,
  Zap,
  Lock,
  Building2,
  AlertCircle,
  IdCard,
  Eye,
  EyeOff,
  LogIn,
  Loader2
} from 'lucide-vue-next';

const showPassword = ref(false);

const form = useForm({
  dni: '',
  password: '',
});

const handleLogin = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
