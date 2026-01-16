<template>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="bg-purple-100 p-3 rounded-xl">
                <Users class="w-6 h-6 text-purple-600" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Total Personal</p>
                <p class="text-2xl font-bold text-slate-900">{{ summary.total_empleados || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="bg-indigo-100 p-3 rounded-xl">
                <FileText class="w-6 h-6 text-indigo-600" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Licencias este año</p>
                <p class="text-2xl font-bold text-slate-900">{{ summary.licencias_este_año || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="bg-red-100 p-3 rounded-xl">
                <Activity class="w-6 h-6 text-red-600" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Por Enfermedad</p>
                <p class="text-2xl font-bold text-slate-900">{{ summary.por_tipo?.enfermedad || 0 }}</p>
            </div>
        </div>

        <div
            class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 flex items-center gap-4 hover:shadow-md transition-shadow">
            <div class="bg-amber-100 p-3 rounded-xl">
                <AlertTriangle class="w-6 h-6 text-amber-600" />
            </div>
            <div>
                <p class="text-sm font-semibold text-slate-500">Sin Saldo</p>
                <p class="text-2xl font-bold text-slate-900">{{ summary.empleados_sin_dias || 0 }}</p>
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
