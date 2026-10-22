<template>
  <div
    class="flex flex-col lg:flex-row min-h-screen lg:h-screen w-full overflow-y-auto lg:overflow-hidden bg-gradient-to-br from-slate-50 via-sky-50/40 to-blue-50/80">
    <!-- Left Side Image -->
    <div class="w-full lg:w-1/2 hidden lg:flex h-full relative overflow-hidden flex-shrink-0">
      <img src="/images/recuperar-contrasena.jpg" alt="Fondo" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-blue-950/75 to-sky-950/55"></div>
      <div class="absolute top-10 right-10 w-32 h-32 bg-sky-400/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-blue-600/10 rounded-full blur-3xl"></div>

      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight drop-shadow-md">Recupera tu acceso</h2>
          <p class="text-xl text-sky-100 leading-relaxed font-light drop-shadow">
            Te enviaremos una nueva contraseña a tu correo registrado.
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
            Olvidé mi contraseña
          </h2>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">
            SGCI-DREH | Sistema de Gestión y Control Institucional
          </p>
        </div>

        <!-- Success Alert -->
        <div v-if="flashSuccess" class="mb-6 bg-emerald-50 border-2 border-emerald-200 rounded-xl p-4 flex items-start gap-3">
          <CheckCircle2 class="h-5 w-5 text-emerald-600 flex-shrink-0 mt-0.5" />
          <p class="text-sm font-semibold text-emerald-800">{{ flashSuccess }}</p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-950/5 border-2 border-slate-200 p-6 sm:p-8">
          <p class="text-sm text-slate-600 mb-6">
            Ingresa tu DNI, usuario o correo electrónico. Si tu cuenta tiene un correo registrado, recibirás una
            nueva contraseña temporal que deberás cambiar al ingresar.
          </p>

          <form class="space-y-6" autocomplete="off" @submit.prevent="submit">
            <div class="group relative">
              <label for="identifier" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                DNI, usuario o correo electrónico <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Mail class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.identifier" type="text" id="identifier" name="identificador_recuperacion"
                  maxlength="150" autocomplete="off" spellcheck="false" autocapitalize="none" autocorrect="off"
                  data-1p-ignore data-bwignore="true" data-lpignore="true" data-protonpass-ignore="true"
                  data-form-type="other"
                  class="block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.identifier
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="DNI (8 dígitos), usuario o correo" />
              </div>
              <p v-if="form.errors.identifier" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.identifier }}
              </p>
            </div>

            <button type="submit" :disabled="form.processing"
              class="cursor-pointer w-full inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20 shadow-lg shadow-blue-900/20 hover:shadow-xl transition-all duration-300 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed">
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              <Send v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Enviando...' : 'Enviar nueva contraseña' }}
            </button>
          </form>

          <div class="mt-6 pt-5 border-t border-slate-200 text-center">
            <a href="/login" class="inline-flex items-center gap-1.5 text-sm font-semibold text-blue-700 hover:text-blue-900 hover:underline">
              <ArrowLeft class="w-4 h-4" /> Volver al inicio de sesión
            </a>
          </div>
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
import { computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { Mail, AlertCircle, Loader2, Send, ArrowLeft, CheckCircle2 } from 'lucide-vue-next';

const page = usePage();
const flashSuccess = computed(() => page.props.flash?.success);

const form = useForm({
  identifier: '',
});

const submit = () => {
  form.identifier = (form.identifier || '').trim();
  form.post('/recuperar-contrasena');
};
</script>
