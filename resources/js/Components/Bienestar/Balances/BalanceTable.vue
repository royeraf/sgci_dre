<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            Empleado</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            Días Totales</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            Días Usados</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            Disponibles</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-wider">
                            Carga de Uso</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-if="employeeBalances.length === 0">
                        <td colspan="5" class="px-6 py-20 text-center">
                            <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-slate-500 font-medium">No hay empleados registrados</p>
                        </td>
                    </tr>
                    <tr v-else v-for="emp in employeeBalances" :key="emp.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div
                                    class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-indigo-200">
                                    {{ emp.nombres?.charAt(0) }}
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-bold text-slate-900">
                                        {{ emp.nombres }} {{ emp.apellidos }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">
                                        {{ emp.cargo || 'Sin cargo' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-700">{{ emp.dias_totales }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-rose-600">{{ emp.dias_usados }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1.5 rounded-lg font-bold text-xs"
                                :class="getBalanceBadgeClass(emp.dias_disponibles)">
                                {{ emp.dias_disponibles }} días
                            </span>
                        </td>
                        <td class="px-6 py-4 min-w-[180px]">
                            <div class="w-full bg-slate-100 rounded-full h-2.5">
                                <div class="h-2.5 rounded-full transition-all duration-500"
                                    :class="getProgressClass(emp.dias_usados, emp.dias_totales)"
                                    :style="{ width: Math.min(100, (emp.dias_usados / emp.dias_totales) * 100) + '%' }">
                                </div>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 font-medium">
                                {{ Math.round((emp.dias_usados / emp.dias_totales) * 100) }}% usado
                            </p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
defineProps({
    employeeBalances: {
        type: Array,
        default: () => []
    }
});

const getBalanceBadgeClass = (days) => {
    if (days >= 10) return 'bg-emerald-100 text-emerald-700';
    if (days > 0) return 'bg-amber-100 text-amber-700';
    return 'bg-rose-100 text-rose-700';
};

const getProgressClass = (used, total) => {
    const percent = total > 0 ? (used / total) * 100 : 0;
    if (percent > 90) return 'bg-gradient-to-r from-rose-500 to-rose-600 shadow-[0_0_8px_rgba(225,29,72,0.4)]';
    if (percent > 50) return 'bg-gradient-to-r from-amber-400 to-amber-500';
    return 'bg-gradient-to-r from-emerald-400 to-emerald-500';
};
</script>
