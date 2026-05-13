<template>
  <div class="flex min-h-screen w-full bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
    <!-- Left Side -->
    <div class="w-1/2 hidden lg:flex relative overflow-hidden">
      <img src="/images/login-bg.png" alt="Background" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 via-indigo-900/80 to-purple-900/80"></div>
      <div class="absolute top-10 right-10 w-32 h-32 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-blue-400/20 rounded-full blur-3xl"></div>
      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-md rounded-full mb-6 border border-white/30">
            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
            <span class="text-white text-sm font-semibold">Verificación de Seguridad</span>
          </div>
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight">Autenticación de dos factores</h2>
          <p class="text-xl text-blue-100 leading-relaxed font-light">SGCI-DREH | Capa adicional de seguridad</p>
        </div>
      </div>
    </div>

    <!-- Right Side Form -->
    <div class="w-full lg:w-1/2 flex flex-col items-center justify-center p-8 bg-white/50 backdrop-blur-sm">
      <div class="w-full max-w-md">
        <!-- Header -->
        <div class="text-center mb-10">
          <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-blue-600 to-indigo-700 rounded-3xl shadow-2xl mb-6 ring-4 ring-blue-50">
            <component :is="useRecovery ? KeyRound : ShieldCheck" class="w-12 h-12 text-white" />
          </div>
          <h2 class="text-3xl font-extrabold bg-gradient-to-r from-slate-800 to-slate-600 bg-clip-text text-transparent mb-2">
            {{ useRecovery ? 'Código de recuperación' : 'Verificación 2FA' }}
          </h2>
          <p class="text-slate-500 text-sm font-medium">
            {{ useRecovery ? 'Ingrese uno de sus códigos de recuperación' : 'Ingrese el código de su aplicación autenticadora' }}
          </p>
        </div>

        <!-- Error -->
        <div class="mb-6 min-h-[56px]">
          <div v-if="form.errors.code" class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
            <div class="flex items-start gap-3">
              <AlertCircle class="h-5 w-5 text-red-600 flex-shrink-0 mt-0.5" />
              <p class="text-sm font-semibold text-red-800">{{ form.errors.code }}</p>
            </div>
          </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border-2 border-slate-200 p-8">
          <form class="space-y-6" @submit.prevent="handleVerify">
            <!-- Instructions -->
            <div class="rounded-xl p-4 flex gap-3"
              :class="useRecovery ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200'">
              <component :is="useRecovery ? KeyRound : Smartphone"
                class="w-5 h-5 flex-shrink-0 mt-0.5"
                :class="useRecovery ? 'text-amber-600' : 'text-blue-600'" />
              <p class="text-sm" :class="useRecovery ? 'text-amber-700' : 'text-blue-700'">
                <template v-if="useRecovery">
                  Ingrese uno de los códigos de recuperación que guardó al activar el 2FA. <strong>Cada código solo puede usarse una vez.</strong>
                </template>
                <template v-else>
                  Abra <strong>FreeOTP</strong> o <strong>Google Authenticator</strong> e ingrese el código de 6 dígitos.
                </template>
              </p>
            </div>

            <!-- Code Input -->
            <div>
              <label for="code" class="block text-sm font-semibold text-slate-700 mb-2">
                {{ useRecovery ? 'Código de recuperación' : 'Código de verificación' }}
                <span class="text-red-500">*</span>
              </label>
              <input
                v-model="form.code"
                ref="codeInput"
                type="text"
                id="code"
                :inputmode="useRecovery ? 'text' : 'numeric'"
                :maxlength="useRecovery ? 9 : 6"
                :autocomplete="useRecovery ? 'off' : 'one-time-code'"
                @input="onCodeInput"
                :class="[
                  'block w-full px-4 border-2 rounded-xl font-mono tracking-widest transition-all duration-200 focus:outline-none focus:ring-4',
                  useRecovery ? 'py-3 text-xl text-center' : 'py-4 text-3xl text-center',
                  form.errors.code
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100'
                    : 'border-slate-200 bg-white hover:border-blue-300 focus:border-blue-500 focus:ring-blue-100'
                ]"
                :placeholder="useRecovery ? 'XXXX-XXXX' : '000000'"
              />
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="form.processing || !isCodeReady"
              class="w-full inline-flex items-center justify-center px-8 py-3 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105 active:scale-95 disabled:opacity-70 disabled:cursor-not-allowed disabled:transform-none"
            >
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5" />
              <ShieldCheck v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Verificando...' : 'Verificar' }}
            </button>
          </form>
        </div>

        <!-- Toggle mode -->
        <div class="mt-5 text-center space-y-2">
          <button
            type="button"
            @click="toggleMode"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium underline underline-offset-2"
          >
            {{ useRecovery ? 'Usar código de la aplicación autenticadora' : '¿Perdiste acceso a tu app? Usar código de recuperación' }}
          </button>
          <div>
            <a href="/login" class="text-sm text-slate-400 hover:text-slate-600">Volver al inicio de sesión</a>
          </div>
        </div>

        <div class="mt-4 text-center">
          <p class="text-sm text-slate-500">© 2026 DRE Huánuco. Todos los derechos reservados.</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { ShieldCheck, AlertCircle, Smartphone, Loader2, KeyRound } from 'lucide-vue-next';

const codeInput = ref(null);
const useRecovery = ref(false);

const form = useForm({ code: '' });

onMounted(() => codeInput.value?.focus());

const isCodeReady = computed(() => {
  if (useRecovery.value) return form.code.length === 9; // XXXX-XXXX
  return form.code.length === 6;
});

const onCodeInput = (e) => {
  if (useRecovery.value) {
    // Allow alphanumeric + dash, auto-insert dash after 4 chars
    let val = e.target.value.replace(/[^A-Za-z0-9-]/g, '').toUpperCase();
    if (val.length > 4 && val[4] !== '-') val = val.slice(0, 4) + '-' + val.slice(4);
    form.code = val.slice(0, 9);
  } else {
    form.code = e.target.value.replace(/\D/g, '');
  }
};

const toggleMode = () => {
  useRecovery.value = !useRecovery.value;
  form.code = '';
  form.clearErrors();
  codeInput.value?.focus();
};

const handleVerify = () => {
  if (!isCodeReady.value) return;
  form.post('/2fa/verify');
};
</script>
