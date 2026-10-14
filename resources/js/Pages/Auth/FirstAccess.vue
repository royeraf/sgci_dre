<template>
  <div
    class="flex flex-col lg:flex-row min-h-screen lg:h-screen w-full overflow-y-auto lg:overflow-hidden bg-gradient-to-br from-slate-50 via-sky-50/40 to-blue-50/80">
    <!-- Left Side Image -->
    <div class="w-full lg:w-1/2 hidden lg:flex h-full relative overflow-hidden flex-shrink-0">
      <img src="/images/login-bg.png" alt="Fondo" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-blue-950/75 to-sky-950/55"></div>
      <div class="absolute top-10 right-10 w-32 h-32 bg-sky-400/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-blue-600/10 rounded-full blur-3xl"></div>

      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight drop-shadow-md">Cambio de contraseña</h2>
          <p class="text-xl text-sky-100 leading-relaxed font-light drop-shadow">
            Por seguridad, debe actualizar la contraseña inicial antes de continuar.
          </p>
        </div>
      </div>
    </div>

    <!-- Right Side Form -->
    <div
      class="w-full lg:w-1/2 min-h-0 lg:h-full overflow-y-auto flex flex-col items-center justify-center p-4 sm:p-8 bg-white/60 backdrop-blur-md flex-1">
      <div class="w-full max-w-md py-4 sm:py-6">
        <!-- Header -->
        <div class="text-center mb-6">
          <div
            class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-blue-700 to-sky-900 rounded-3xl shadow-2xl shadow-blue-900/20 mb-4 ring-4 ring-blue-50">
            <img src="/images/logo.png" alt="DRE Logo"
              class="w-full h-full object-contain drop-shadow-md brightness-0 invert p-3" />
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold bg-gradient-to-r from-blue-950 to-slate-800 bg-clip-text text-transparent mb-1.5">
            Actualice su contraseña
          </h2>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">
            {{ nombre }} <span v-if="dni">· DNI {{ dni }}</span>
          </p>
        </div>

        <!-- Paso 1 (solo si hay un segundo paso) -->
        <div v-if="datos_pendientes" class="mb-5 flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-700 text-white text-xs font-bold">1</span>
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Paso 1 de 2</span>
        </div>

        <div class="mb-5 bg-sky-50 border-2 border-sky-100 rounded-xl p-4 flex items-start gap-3">
          <ShieldCheck class="h-5 w-5 text-sky-600 flex-shrink-0 mt-0.5" />
          <p class="text-xs sm:text-sm text-sky-900 font-medium">
            Ingrese una nueva contraseña.
            <template v-if="datos_pendientes"> Luego deberá registrar su correo y número de celular.</template>
          </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-950/5 border-2 border-slate-200 p-6 sm:p-8">
          <form class="space-y-5" @submit.prevent="submit">
            <!-- Nueva contraseña -->
            <div class="group relative">
              <label for="password" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Nueva contraseña <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password"
                  autocomplete="new-password"
                  class="block w-full pl-10 pr-12 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.password
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="Mínimo 8 caracteres" />
                <button type="button" @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-700 transition-colors duration-200 cursor-pointer">
                  <Eye v-if="!showPassword" class="h-5 w-5" />
                  <EyeOff v-else class="h-5 w-5" />
                </button>
              </div>
              <p v-if="form.errors.password" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.password }}
              </p>
            </div>

            <!-- Confirmar contraseña -->
            <div class="group relative">
              <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Confirmar contraseña <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.password_confirmation" :type="showPassword ? 'text' : 'password'"
                  id="password_confirmation" autocomplete="new-password"
                  class="block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.password_confirmation
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="Repita la contraseña" />
              </div>
              <p v-if="form.errors.password_confirmation" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.password_confirmation }}
              </p>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="form.processing"
              class="cursor-pointer w-full inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20 shadow-lg shadow-blue-900/20 hover:shadow-xl transition-all duration-300 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed">
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              <ArrowRight v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Guardando...' : (datos_pendientes ? 'Continuar' : 'Guardar y continuar') }}
            </button>
          </form>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <p class="text-xs sm:text-sm text-slate-500">
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
import { Lock, ShieldCheck, AlertCircle, Eye, EyeOff, Loader2, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
  nombre: { type: String, default: '' },
  dni: { type: String, default: '' },
  datos_pendientes: { type: Boolean, default: false },
});

const showPassword = ref(false);

const form = useForm({
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/primer-acceso', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>
