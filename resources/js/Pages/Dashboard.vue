<template>
  <div class="p-4 sm:p-6 lg:p-8">
    <!-- Welcome Card -->
    <div
      class="relative overflow-hidden rounded-[2rem] p-8 md:p-12 text-white mb-10 shadow-2xl shadow-blue-500/20 border border-white/10 ring-1 ring-white/5">
      <!-- Background Image with Premium Overlay -->
      <img src="/images/login-bg.png" alt="Background"
        class="absolute inset-0 w-full h-full object-cover transform scale-105 transition-transform duration-1000 ease-out" />
      <div class="absolute inset-0 bg-gradient-to-r from-blue-900/95 via-blue-900/70 to-indigo-900/40"></div>

      <!-- Glossy Effect Overlay -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-white/10"></div>

      <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
        <div class="max-w-2xl">
          <h2 class="text-4xl md:text-5xl font-black mb-4 tracking-tight drop-shadow-lg">
            ¡Bienvenido al <span
              class="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">SGCI</span>!
          </h2>
          <p class="text-blue-100/90 text-lg md:text-xl font-semibold mb-8 max-w-lg leading-relaxed">
            Gestión y Control Institucional - DRE Huánuco
          </p>

          <div class="flex flex-wrap items-center gap-4">
            <div
              class="bg-white/10 backdrop-blur-xl px-5 py-2.5 rounded-2xl text-sm font-bold flex items-center border border-white/20 shadow-xl shadow-black/10">
              <Clock class="w-4 h-4 mr-2.5 text-blue-300" />
              {{ currentDateTime }}
            </div>
            <div
              class="bg-white/10 backdrop-blur-xl px-5 py-2.5 rounded-2xl text-sm font-bold flex items-center border border-white/20 shadow-xl shadow-black/10 uppercase">
              <Sun class="w-4 h-4 mr-2.5 text-yellow-300" />
              Turno: {{ currentShift }}
            </div>
          </div>
        </div>

        <!-- Decorative Logo/Icon for balance -->
        <div class="hidden lg:block opacity-80 drop-shadow-2xl">
          <img src="/images/logo.png" alt="Logo" class="w-40 h-40 object-contain brightness-0 invert" />
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-10">
      <div class="flex items-center gap-3 mb-6">
        <div class="bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl p-2.5 shadow-lg shadow-orange-200">
          <Zap class="w-6 h-6 text-white fill-white/20" />
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Accesos Rápidos</h2>
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <Link v-if="hasModulePermission('vigilancia')" href="/occurrences"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-blue-50/50 border-2 border-slate-100 hover:border-blue-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <ClipboardList class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Libro de Ocurrencias</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Registrar incidentes y novedades</span>
        </Link>

        <Link v-if="hasModulePermission('vigilancia')" href="/entry-exits"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-emerald-50/50 border-2 border-slate-100 hover:border-emerald-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-emerald-600/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
            <UserCheck class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Control de Personal</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Entradas y salidas (Papeletas)</span>
        </Link>

        <Link v-if="hasModulePermission('vigilancia')" href="/visitors"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-purple-50/50 border-2 border-slate-100 hover:border-purple-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-purple-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <Users class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Visitas Externas</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Registro de ciudadanos visitantes</span>
        </Link>

        <Link v-if="hasModulePermission('vigilancia')" href="/vehicles"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-cyan-50/50 border-2 border-slate-100 hover:border-cyan-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-cyan-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-cyan-600/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
            <Car class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Control Vehicular</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Salida y retorno de unidades</span>
        </Link>

        <Link v-if="hasModulePermission('secretaria')" href="/citas"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-pink-50/50 border-2 border-slate-100 hover:border-pink-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-pink-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-pink-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <Calendar class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Gestión de Citas</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Administrar solicitudes de citas</span>
        </Link>

        <Link v-if="hasModulePermission('bienestar')" href="/bienestar"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-purple-50/50 border-2 border-slate-100 hover:border-purple-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-purple-600/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
            <Heart class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Bienestar Social</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Gestión de licencias y permisos</span>
        </Link>

        <Link v-if="hasModulePermission('recursos_humanos')" href="/hr"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-teal-50/50 border-2 border-slate-100 hover:border-teal-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-teal-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-teal-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <UserCheck class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Recursos Humanos</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Empleados, vacaciones y áreas</span>
        </Link>
      </div>
    </div>

    <!-- Section: Libro de Ocurrencias Stats -->
    <div v-if="hasModulePermission('vigilancia')" class="mb-10">
      <div class="flex items-center gap-3 mb-6">
        <div class="bg-blue-100 rounded-xl p-2.5">
          <ClipboardList class="w-6 h-6 text-blue-600" />
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Estadísticas de Seguridad</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-2xl p-6 border-2 border-blue-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Ocurrencias</p>
              <p class="text-3xl font-black text-slate-900">{{ stats.occurrences.total }}</p>
            </div>
            <div
              class="w-12 h-12 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform">
              <ClipboardList class="w-6 h-6" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-yellow-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Incidentes</p>
              <p class="text-3xl font-black text-yellow-600">{{ stats.occurrences.incidentes }}</p>
            </div>
            <div
              class="w-12 h-12 bg-yellow-50 rounded-xl flex items-center justify-center text-yellow-600 group-hover:scale-110 transition-transform">
              <AlertTriangle class="w-6 h-6" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-red-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Emergencias</p>
              <p class="text-3xl font-black text-red-600">{{ stats.occurrences.emergencias }}</p>
            </div>
            <div
              class="w-12 h-12 bg-red-50 rounded-xl flex items-center justify-center text-red-600 group-hover:scale-110 transition-transform">
              <Activity class="w-6 h-6" />
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-emerald-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Rutinas</p>
              <p class="text-3xl font-black text-emerald-600">{{ stats.occurrences.rutinas }}</p>
            </div>
            <div
              class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform">
              <Clock class="w-6 h-6" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Section: Control de Personal Stats -->
    <div v-if="hasModulePermission('vigilancia')" class="mb-10">
      <div class="flex items-center gap-3 mb-6">
        <div class="bg-emerald-100 rounded-xl p-2.5">
          <UserCheck class="w-6 h-6 text-emerald-600" />
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Control de Personal</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border-2 border-emerald-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
              <FileText class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Papeletas</p>
              <p class="text-3xl font-black text-slate-900">{{ stats.personnel.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-orange-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-orange-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-orange-200">
              <Clock class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Pendiente Retorno</p>
              <p class="text-3xl font-black text-orange-600">{{ stats.personnel.pending_return }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-blue-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-blue-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
              <Calendar class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Registrados Hoy</p>
              <p class="text-3xl font-black text-blue-600">{{ stats.personnel.today }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Section: Visitantes Externos Stats -->
    <div v-if="hasModulePermission('vigilancia')" class="mb-10">
      <div class="flex items-center gap-3 mb-6">
        <div class="bg-purple-100 rounded-xl p-2.5">
          <Users class="w-6 h-6 text-purple-600" />
        </div>
        <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Visitas Externas</h2>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl p-6 border-2 border-purple-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-purple-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-purple-200">
              <Users class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Visitas</p>
              <p class="text-3xl font-black text-slate-900">{{ stats.visitors.total }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-pink-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-pink-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-pink-200">
              <Activity class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">En Instalaciones</p>
              <p class="text-3xl font-black text-pink-600">{{ stats.visitors.active }}</p>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-6 border-2 border-indigo-50 shadow-sm hover:shadow-md transition-all group">
          <div class="flex items-center gap-4">
            <div
              class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white shadow-lg shadow-indigo-200">
              <Calendar class="w-7 h-7" />
            </div>
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Hoy</p>
              <p class="text-3xl font-black text-indigo-600">{{ stats.visitors.today }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- System Info -->
    <div class="text-center text-sm text-slate-400 mt-12 pb-8">
      <p class="font-medium tracking-tight">SGCI v1.0.0 | Plataforma DREH</p>
      <p class="mt-1">© 2026 Dirección Regional de Educación Huánuco</p>
    </div>
  </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
  layout: MainLayout
}
</script>

<script setup>
import { computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
  Clock,
  ClipboardList,
  Users,
  AlertTriangle,
  Plus,
  UserCheck,
  FileText,
  Activity,
  Zap,
  Sun,
  Car,
  Calendar,
  Heart
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      occurrences: { total: 0, incidentes: 0, emergencies: 0, rutinas: 0 },
      personnel: { total: 0, pending_return: 0, today: 0 },
      visitors: { total: 0, active: 0, today: 0 }
    })
  }
});

const page = usePage();

const laravelVersion = computed(() => '10.x');

const currentDateTime = computed(() => {
  const now = new Date();
  return now.toLocaleDateString('es-PE', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
});

const currentShift = computed(() => {
  const hour = new Date().getHours();
  if (hour >= 6 && hour < 14) return 'Mañana';
  if (hour >= 14 && hour < 22) return 'Tarde';
  return 'Noche';
});

const hasModulePermission = (module, action = 'ver') => {
  const user = page.props.auth?.user;
  if (!user) return false;

  // Admin has access to everything
  if (user.rol_id === 'ROL001' || user.rol_id === '1') return true;

  // Special case for Jefe de Bienestar Social (ROL008)
  if (user.rol_id === 'ROL008' || user.customRole?.codigo === 'jefe_bienestar') {
    return module === 'bienestar';
  }

  // Special case for Jefe de RRHH (ROL009)
  if (user.rol_id === 'ROL009' || user.customRole?.codigo === 'jefe_rrhh') {
    return module === 'recursos_humanos';
  }

  // Special case for Gestor de Citas (ROL010)
  if (user.rol_id === 'ROL010' || user.customRole?.codigo === 'gestor_citas') {
    return module === 'secretaria';
  }

  const permisos = user.customRole?.permisos_json || {};

  // Mapping dashboard modules to database permission keys
  const mapping = {
    'vigilancia': ['ocurrencias', 'personal', 'visitas', 'vehiculos'],
    'secretaria': ['citas'],
    'recursos_humanos': ['recursos_humanos', 'vacaciones', 'areas', 'cargos'],
    'bienestar': ['licencias'],
  };

  const dbKeys = mapping[module];
  if (!dbKeys) return false;

  return dbKeys.some(key => permisos[key] !== undefined);
};

const logout = () => {
  router.post('/logout');
};
</script>
