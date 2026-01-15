<template>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Personal</p>
                    <p class="text-3xl font-bold text-slate-900">{{ summary.total_empleados || 0 }}</p>
                </div>
                <div class="bg-emerald-100 p-3 rounded-xl">
                    <Users class="w-8 h-8 text-emerald-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">Activos</p>
                    <p class="text-3xl font-bold text-green-600">{{ summary.empleados_activos || 0 }}</p>
                </div>
                <div class="bg-green-100 p-3 rounded-xl">
                    <CheckCircle class="w-8 h-8 text-green-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-500">En Vacaciones</p>
                    <p class="text-3xl font-bold text-orange-600">{{ summary.en_vacaciones || 0 }}</p>
                </div>
                <div class="bg-orange-100 p-3 rounded-xl">
                    <Palmtree class="w-8 h-8 text-orange-600" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Users, CheckCircle, Palmtree } from 'lucide-vue-next';

defineProps({
    summary: {
        type: Object,
        default: () => ({})
    }
});
</script>
