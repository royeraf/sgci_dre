<template>
  <div
    class="flex flex-col lg:flex-row min-h-screen lg:h-screen w-full overflow-y-auto lg:overflow-hidden bg-gradient-to-br from-amber-50 via-orange-50/40 to-amber-100/70">
    <!-- Left Side Image -->
    <div class="w-full lg:w-1/2 hidden lg:flex h-full relative overflow-hidden flex-shrink-0">
      <img src="/images/login-bg.png" alt="Fondo" class="absolute inset-0 w-full h-full object-cover" />
      <div class="absolute inset-0 bg-gradient-to-br from-stone-950/85 via-amber-900/75 to-amber-600/55"></div>
      <div class="absolute top-10 right-10 w-32 h-32 bg-amber-300/10 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-amber-500/20 rounded-full blur-3xl"></div>

      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight drop-shadow-md">Complete sus datos</h2>
          <p class="text-xl text-amber-100 leading-relaxed font-light drop-shadow">
            Necesitamos su correo y número de celular para mantenerlo informado.
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
            Complete sus datos
          </h2>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">
            {{ nombre }} <span v-if="dni">· DNI {{ dni }}</span>
          </p>
        </div>

        <!-- Paso 2 (solo si vino del cambio de contraseña) -->
        <div v-if="es_paso2" class="mb-5 flex items-center gap-2">
          <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-700 text-white text-xs font-bold">2</span>
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wide">Paso 2 de 2</span>
        </div>

        <div class="mb-5 bg-sky-50 border-2 border-sky-100 rounded-xl p-4 flex items-start gap-3">
          <ShieldCheck class="h-5 w-5 text-sky-600 flex-shrink-0 mt-0.5" />
          <p class="text-xs sm:text-sm text-sky-900 font-medium">
            <template v-if="requiere_email && requiere_telefono">
              Registre su correo electrónico y número de celular para continuar.
            </template>
            <template v-else-if="requiere_email">
              Registre su correo electrónico para continuar.
            </template>
            <template v-else>
              Registre su número de celular para continuar.
            </template>
          </p>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-950/5 border-2 border-slate-200 p-6 sm:p-8">
          <form class="space-y-5" @submit.prevent="submit">
            <!-- Email -->
            <div v-if="requiere_email" class="group relative">
              <label for="email" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Correo electrónico <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Mail class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.email" type="email" id="email" autocomplete="email"
                  class="block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.email
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="usuario@drehuanuco.gob.pe" />
              </div>
              <p v-if="form.errors.email" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.email }}
              </p>
            </div>

            <!-- Confirmar correo -->
            <div v-if="requiere_email" class="group relative">
              <label for="email_confirmation" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Confirmar correo electrónico <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <MailCheck class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.email_confirmation" type="email" id="email_confirmation" autocomplete="email"
                  @blur="emailTouched = true"
                  class="block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.email_confirmation || emailMismatch
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="Repita su correo electrónico" />
              </div>
              <p v-if="form.errors.email_confirmation || emailMismatch"
                class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.email_confirmation || emailMismatch }}
              </p>
            </div>

            <!-- Celular -->
            <div v-if="requiere_telefono" class="group relative">
              <label for="telefono" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Nro. de celular <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Phone class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.telefono" type="text" id="telefono" maxlength="9" inputmode="numeric"
                  class="block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer"
                  :class="form.errors.telefono
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'"
                  placeholder="999888777" @input="form.telefono = ($event.target.value || '').replace(/\D/g, '')" />
              </div>
              <p v-if="form.errors.telefono" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" /> {{ form.errors.telefono }}
              </p>
            </div>

            <!-- Submit -->
            <button type="submit" :disabled="form.processing"
              class="cursor-pointer w-full inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20 shadow-lg shadow-blue-900/20 hover:shadow-xl transition-all duration-300 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed">
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              <ShieldCheck v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Guardando...' : 'Guardar y continuar' }}
            </button>
          </form>
        </div>

        <!-- Cancelar: cierra la sesión sin completar los datos -->
        <div class="mt-4 text-center">
          <button type="button" @click="cancelar" :disabled="saliendo"
            class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-red-600 transition-colors disabled:opacity-50 cursor-pointer">
            <Loader2 v-if="saliendo" class="w-4 h-4 animate-spin" />
            <LogOut v-else class="w-4 h-4" />
            {{ saliendo ? 'Cerrando sesión...' : 'Cancelar y cerrar sesión' }}
          </button>
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
import { computed, ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Mail, MailCheck, Phone, ShieldCheck, AlertCircle, Loader2, LogOut } from 'lucide-vue-next';

const props = defineProps({
  nombre: { type: String, default: '' },
  dni: { type: String, default: '' },
  email: { type: String, default: '' },
  telefono: { type: String, default: '' },
  requiere_email: { type: Boolean, default: false },
  requiere_telefono: { type: Boolean, default: false },
  es_paso2: { type: Boolean, default: false },
});

const form = useForm({
  email: props.email || '',
  email_confirmation: '',
  telefono: props.telefono || '',
});

// La comparación solo se evalúa al salir del campo (blur) o al enviar,
// para no mostrar el aviso mientras el usuario escribe.
const emailTouched = ref(false);

const emailMismatch = computed(() => {
  if (!emailTouched.value) return '';
  if (!form.email_confirmation) return '';

  const a = (form.email || '').trim().toLowerCase();
  const b = (form.email_confirmation || '').trim().toLowerCase();

  return a && a !== b ? 'Los correos electrónicos no coinciden.' : '';
});

// Al corregir los campos, se limpia el error anterior.
watch(() => form.email, () => form.clearErrors('email', 'email_confirmation'));
watch(() => form.email_confirmation, () => form.clearErrors('email', 'email_confirmation'));

const submit = () => {
  emailTouched.value = true;

  if (props.requiere_email) {
    form.email = (form.email || '').trim();
    form.email_confirmation = (form.email_confirmation || '').trim();

    if (emailMismatch.value) return;
  }

  form.post('/completar-datos');
};

const saliendo = ref(false);

const cancelar = () => {
  saliendo.value = true;
  router.post('/logout');
};
</script>
