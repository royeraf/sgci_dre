<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Empleado</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Período</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Fecha Inicio</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Fecha Fin</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Días</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="vac in vacations" :key="vac.id" class="hover:bg-orange-50">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900">
                                {{ vac.empleado ? `${vac.empleado.nombres} ${vac.empleado.apellidos}` : `DNI:
                                ${vac.dni}` }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-slate-700">{{ vac.periodo }}</td>
                        <td class="px-6 py-4 text-slate-700">{{ formatDate(vac.fecha_inicio) }}</td>
                        <td class="px-6 py-4 text-slate-700">{{ formatDate(vac.fecha_fin) }}</td>
                        <td class="px-6 py-4 text-center font-bold text-orange-600">{{ vac.dias_tomados }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-bold rounded-lg" :class="statusClass(vac.estado)">
                                {{ vac.estado }}
                            </span>
                        </td>
                    </tr>
                    <tr v-if="vacations.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <Calendar class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay vacaciones registradas.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Calendar } from 'lucide-vue-next';

defineProps({
    vacations: {
        type: Array,
        default: () => []
    }
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE');
};

const statusClass = (estado) => ({
    'bg-yellow-100 text-yellow-700': estado === 'PROGRAMADO',
    'bg-green-100 text-green-700': estado === 'COMPLETADO',
    'bg-blue-100 text-blue-700': estado === 'EN CURSO',
    'bg-red-100 text-red-700': estado === 'CANCELADO'
});
</script>
