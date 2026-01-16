<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Empleado</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Días Totales</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Días Usados</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Disponibles</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Carga de Uso</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <tr v-for="emp in employeeBalances" :key="emp.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div
                                class="h-9 w-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs ring-1 ring-indigo-100">
                                {{ emp.nombres?.charAt(0) }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-bold text-slate-900">
                                    {{ emp.nombres }} {{ emp.apellidos }}
                                </div>
                                <div class="text-xs text-slate-500 font-medium">
                                    {{ emp.cargo || 'Sin cargo' }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-bold text-slate-700">
                        {{ emp.dias_totales }}
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-bold text-rose-600">
                        {{ emp.dias_usados }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-3 py-1 rounded-lg font-bold text-xs"
                            :class="getBalanceBadgeClass(emp.dias_disponibles)">
                            {{ emp.dias_disponibles }} días
                        </span>
                    </td>
                    <td class="px-6 py-4 min-w-[150px]">
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="h-2 rounded-full transition-all duration-500"
                                :class="getProgressClass(emp.dias_usados, emp.dias_totales)"
                                :style="{ width: Math.min(100, (emp.dias_usados / emp.dias_totales) * 100) + '%' }">
                            </div>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
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
    if (percent > 90) return 'bg-rose-600 shadow-[0_0_8px_rgba(225,29,72,0.4)]';
    if (percent > 50) return 'bg-amber-500';
    return 'bg-emerald-500';
};
</script>
