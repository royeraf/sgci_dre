<template>
  <div class="flex flex-col lg:flex-row min-h-screen lg:h-screen w-full overflow-y-auto lg:overflow-hidden bg-gradient-to-br from-slate-50 via-sky-50/40 to-blue-50/80">
    <!-- Left Side Image (Height Fixed to 100vh on Desktop) -->
    <div class="w-full lg:w-1/2 hidden lg:flex h-full relative overflow-hidden flex-shrink-0">
      <img :src="selectedBg" alt="Fondo Emblemático Perú" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-700" />
      <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-blue-950/75 to-sky-950/55"></div>

      <!-- Decorative Elements -->
      <div class="absolute top-10 right-10 w-32 h-32 bg-sky-400/5 rounded-full blur-3xl"></div>
      <div class="absolute bottom-20 left-20 w-40 h-40 bg-blue-600/10 rounded-full blur-3xl"></div>

      <div class="absolute inset-0 flex flex-col justify-end p-12">
        <div class="max-w-lg">
          <h2 class="text-4xl font-bold text-white mb-4 leading-tight drop-shadow-md">Sistema de Gestión y Control Institucional</h2>
          <p class="text-xl text-sky-100 leading-relaxed font-light drop-shadow">SGCI-DREH | Administración Integral</p>
        </div>
      </div>
    </div>

    <!-- Right Side Form (Independent Scroll on Desktop, Natural Flow on Mobile) -->
    <div class="w-full lg:w-1/2 min-h-0 lg:h-full overflow-y-auto flex flex-col items-center justify-center p-4 sm:p-8 bg-white/60 backdrop-blur-md flex-1">
      <div class="w-full max-w-md py-4 sm:py-6">
        <!-- Logo/Header Card -->
        <div class="text-center mb-4 sm:mb-6">
          <div
            class="inline-flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 bg-gradient-to-br from-blue-700 to-sky-900 rounded-3xl shadow-2xl shadow-blue-900/20 mb-4 sm:mb-6 ring-4 ring-blue-50 transform hover:scale-105 transition-transform duration-300">
            <img src="/images/logo.png" alt="DRE Logo"
              class="w-full h-full object-contain drop-shadow-md brightness-0 invert p-3" />
          </div>
          <h2
            class="text-2xl sm:text-3xl font-extrabold bg-gradient-to-r from-blue-950 to-slate-800 bg-clip-text text-transparent mb-1.5 sm:mb-2">
            Bienvenido de nuevo
          </h2>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">SGCI-DREH | Sistema de Gestión y Control Institucional</p>
        </div>

        <!-- Error Alert -->
        <div v-if="form.errors.credentials || flashError" class="mb-6 bg-red-50 border-2 border-red-200 rounded-xl p-4">
          <div class="flex items-start gap-3">
            <div class="flex-shrink-0">
              <AlertCircle class="h-5 w-5 text-red-600" />
            </div>
            <p class="text-sm font-semibold text-red-800">{{ form.errors.credentials || flashError }}</p>
          </div>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-xl shadow-blue-950/5 border-2 border-slate-200 p-6 sm:p-8">
          <form class="space-y-6 sm:space-y-8" @submit.prevent="handleLogin">
            <!-- DNI Field -->
            <div class="group relative">
              <label for="dni" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                DNI <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <IdCard class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.dni" type="text" id="dni" maxlength="8" inputmode="numeric" autocomplete="username"
                  @input="form.dni = $event.target.value.replace(/[\D\s]/g, ''); dniError = ''" :class="[
                    'block w-full pl-10 pr-4 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer',
                    dniError || form.errors.dni
                      ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                      : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'
                  ]" placeholder="DNI (8 dígitos)">
              </div>
              <p v-if="dniError || form.errors.dni" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" />
                {{ dniError || form.errors.dni }}
              </p>
            </div>

            <!-- Password Field -->
            <div class="group relative">
              <label for="password" class="block text-sm font-semibold text-slate-700 mb-2 cursor-pointer">
                Contraseña <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <Lock class="h-5 w-5 text-slate-400 group-focus-within:text-blue-600 transition-colors" />
                </div>
                <input v-model="form.password" :type="showPassword ? 'text' : 'password'" id="password" autocomplete="current-password"
                  @input="passwordError = ''" :class="[
                  'block w-full pl-10 pr-12 py-3 text-sm sm:text-base border-2 rounded-xl transition-all duration-200 ease-in-out focus:outline-none focus:ring-4 cursor-pointer',
                  passwordError || form.errors.password
                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-slate-200 bg-white hover:border-blue-400 focus:border-blue-600 focus:ring-blue-500/20'
                ]" placeholder="••••••••">
                <!-- Toggle Password Visibility Button -->
                <button type="button" @click="showPassword = !showPassword"
                  class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-blue-700 transition-colors duration-200 cursor-pointer">
                  <Eye v-if="!showPassword" class="h-5 w-5" />
                  <EyeOff v-else class="h-5 w-5" />
                </button>
              </div>
              <p v-if="passwordError || form.errors.password" class="mt-1 text-xs sm:text-sm text-red-600 flex items-center gap-1">
                <AlertCircle class="w-4 h-4" />
                {{ passwordError || form.errors.password }}
              </p>
            </div>

            <!-- Submit Button -->
            <button type="submit" :disabled="form.processing"
              class="cursor-pointer w-full inline-flex items-center justify-center px-6 sm:px-8 py-3 sm:py-3.5 border border-transparent rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-500/20 shadow-lg shadow-blue-900/20 hover:shadow-xl transition-all duration-300 ease-in-out transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed">
              <Loader2 v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" />
              <LogIn v-else class="w-5 h-5 mr-2" />
              {{ form.processing ? 'Ingresando...' : 'Ingresar al Sistema' }}
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
import { ref, computed, onMounted } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {
  Lock,
  AlertCircle,
  IdCard,
  Eye,
  EyeOff,
  LogIn,
  Loader2
} from 'lucide-vue-next';

const page = usePage();
const flashError = computed(() => page.props.flash?.error);

const bgImages = [
  '/images/login-bg.png',
  '/images/felipeguerrero85-machu-pichu-1058510.jpg',
  '/images/karlomanson-cusco-party-2912872.jpg',
  '/images/loggawiggler-arequipa-43282.jpg',
  '/images/nazca.jpg',
  '/images/Ushno.jpg',
];

const selectedBg = ref(bgImages[0]);

const getEqualRandomBg = () => {
  const STORAGE_KEY = 'sgci_login_bg_queue';
  let queue = [];

  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      queue = JSON.parse(stored);
    }
  } catch (e) {
    queue = [];
  }

  // Reiniciar la cola con todas las imágenes si está vacía o inválida
  if (!Array.isArray(queue) || queue.length === 0 || queue.some(i => i < 0 || i >= bgImages.length)) {
    queue = Array.from({ length: bgImages.length }, (_, i) => i);
  }

  // Seleccionar aleatoriamente un elemento restante de la cola
  const randomIndexInQueue = Math.floor(Math.random() * queue.length);
  const selectedImageIndex = queue[randomIndexInQueue];

  // Remover de la cola y actualizar localStorage
  queue.splice(randomIndexInQueue, 1);
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(queue));
  } catch (e) {}

  return bgImages[selectedImageIndex];
};

onMounted(() => {
  selectedBg.value = getEqualRandomBg();
});

const showPassword = ref(false);
const dniError = ref('');
const passwordError = ref('');

const form = useForm({
  dni: '',
  password: '',
});

const handleLogin = () => {
  dniError.value = '';
  passwordError.value = '';

  if (!form.dni) {
    dniError.value = 'El DNI es requerido';
  } else if (form.dni.length !== 8) {
    dniError.value = 'El DNI debe tener 8 dígitos';
  }

  if (!form.password) {
    passwordError.value = 'La contraseña es requerida';
  }

  if (dniError.value || passwordError.value) return;

  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
