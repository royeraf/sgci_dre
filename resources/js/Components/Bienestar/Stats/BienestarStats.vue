<template>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-lg transition-all duration-300 group">
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 p-3 rounded-xl shadow-lg shadow-purple-200 group-hover:scale-110 transition-transform">
                <Users class="w-6 h-6 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500">Total Personal</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ summary.total_empleados || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-lg transition-all duration-300 group">
            <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 p-3 rounded-xl shadow-lg shadow-indigo-200 group-hover:scale-110 transition-transform">
                <FileText class="w-6 h-6 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500">Licencias este año</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ summary.licencias_este_año || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-lg transition-all duration-300 group">
            <div class="bg-gradient-to-br from-rose-500 to-rose-600 p-3 rounded-xl shadow-lg shadow-rose-200 group-hover:scale-110 transition-transform">
                <Activity class="w-6 h-6 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500">Por Enfermedad</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ summary.por_tipo?.enfermedad || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-lg transition-all duration-300 group">
            <div class="bg-gradient-to-br from-amber-500 to-amber-600 p-3 rounded-xl shadow-lg shadow-amber-200 group-hover:scale-110 transition-transform">
                <AlertTriangle class="w-6 h-6 text-white" />
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500">Sin Saldo</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ summary.empleados_sin_dias || 0 }}</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Users, FileText, Activity, AlertTriangle } from 'lucide-vue-next';

defineProps({
    summary: {
        type: Object,
        required: true,
        default: () => ({})
    }
});
</script>
