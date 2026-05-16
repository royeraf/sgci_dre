<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-indigo-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Usuarios</p>
                    <p class="text-3xl font-bold text-slate-900">{{ summary.total || 0 }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-lg">
                    <Users class="w-8 h-8 text-indigo-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-emerald-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Activos</p>
                    <p class="text-3xl font-bold text-slate-900">{{ summary.active || 0 }}</p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-lg">
                    <UserCheck class="w-8 h-8 text-emerald-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-slate-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Inactivos</p>
                    <p class="text-3xl font-bold text-slate-900">{{ summary.inactive || 0 }}</p>
                </div>
                <div class="p-3 bg-slate-100 rounded-lg">
                    <UserX class="w-8 h-8 text-slate-600" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Roles</p>
                    <p class="text-3xl font-bold text-slate-900">{{ (summary.by_role || []).length }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-lg">
                    <Shield class="w-8 h-8 text-purple-600" />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Users, UserCheck, UserX, Shield } from 'lucide-vue-next';

defineProps({
    summary: {
        type: Object,
        default: () => ({})
    }
});
</script>
