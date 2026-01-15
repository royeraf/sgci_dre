<template>
  <div class="p-4 sm:p-6 lg:p-8">
    <!-- Welcome Card -->
    <div
      class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-3xl p-8 text-white mb-10 shadow-xl shadow-blue-500/20 relative overflow-hidden">
      <div class="relative z-10">
        <h2 class="text-3xl md:text-4xl font-extrabold mb-3 tracking-tight">¡Bienvenido al SGCI!</h2>
        <p class="text-blue-100 text-lg md:text-xl font-medium opacity-90">Gestión y Control Institucional - DRE Huánuco
        </p>
        <div class="flex items-center gap-4 mt-8">
          <div
            class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl text-sm font-bold flex items-center border border-white/20">
            <Clock class="w-4 h-4 mr-2 text-blue-300" />
            {{ currentDateTime }}
          </div>
          <div
            class="bg-white/10 backdrop-blur-md px-4 py-2 rounded-xl text-sm font-bold flex items-center border border-white/20 uppercase">
            <Sun class="w-4 h-4 mr-2 text-yellow-300" />
            Turno: {{ currentShift }}
          </div>
        </div>
      </div>
      <!-- Background Decorative Elements -->
      <div class="absolute -right-20 -top-20 w-64 h-64 bg-white/10 rounded-full blur-3xl"></div>
      <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl"></div>
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
        <Link href="/occurrences"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-blue-50/50 border-2 border-slate-100 hover:border-blue-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <ClipboardList class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Libro de Ocurrencias</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Registrar incidentes y novedades</span>
        </Link>

        <Link href="/entry-exits"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-emerald-50/50 border-2 border-slate-100 hover:border-emerald-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-emerald-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-emerald-600/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
            <UserCheck class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Control de Personal</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Entradas y salidas (Papeletas)</span>
        </Link>

        <Link href="/visitors"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-purple-50/50 border-2 border-slate-100 hover:border-purple-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-purple-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-purple-600/30 group-hover:scale-110 group-hover:-rotate-3 transition-all duration-300">
            <Users class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Visitas Externas</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Registro de ciudadanos visitantes</span>
        </Link>

        <Link href="/vehicles"
          class="flex flex-col items-center p-6 bg-white rounded-2xl hover:bg-cyan-50/50 border-2 border-slate-100 hover:border-cyan-200 transition-all group shadow-sm hover:shadow-md">
          <div
            class="w-16 h-16 bg-cyan-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-cyan-600/30 group-hover:scale-110 group-hover:rotate-3 transition-all duration-300">
            <Car class="w-8 h-8 text-white" />
          </div>
          <span class="text-base font-bold text-slate-900">Control Vehicular</span>
          <span class="text-xs text-slate-500 mt-1 font-medium text-center">Salida y retorno de unidades</span>
        </Link>
      </div>
    </div>

    <!-- Section: Libro de Ocurrencias Stats -->
    <div class="mb-10">
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
    <div class="mb-10">
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
    <div class="mb-10">
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
  Calendar
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

const logout = () => {
  router.post('/logout');
};
</script>
