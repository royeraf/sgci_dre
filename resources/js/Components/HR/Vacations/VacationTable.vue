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
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Acciones</th>
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
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button @click="$emit('viewDetail', vac)"
                                    class="p-2 text-orange-600 hover:bg-orange-100 rounded-lg transition-colors"
                                    title="Ver detalle de vacación">
                                    <Eye class="w-5 h-5" />
                                </button>
                                <button @click="$emit('edit', vac)"
                                    class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                                    title="Editar vacación">
                                    <Pencil class="w-5 h-5" />
                                </button>
                                <button @click="$emit('delete', vac.id)"
                                    class="p-2 text-red-600 hover:bg-red-100 rounded-lg transition-colors"
                                    title="Eliminar vacación">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="vacations.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
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
import { Calendar, Eye, Pencil, Trash2 } from 'lucide-vue-next';

defineEmits(['viewDetail', 'edit', 'delete']);

defineProps({
    vacations: {
        type: Array,
        default: () => []
    }
});

const formatDate = (date) => {
    if (!date) return '-';
    // Forzar interpretación local agregando 'T00:00:00' para evitar problemas de zona horaria
    const [year, month, day] = date.split('-');
    const localDate = new Date(year, month - 1, day);
    return localDate.toLocaleDateString('es-PE');
};

const statusClass = (estado) => ({
    'bg-yellow-100 text-yellow-700': estado === 'PROGRAMADO',
    'bg-green-100 text-green-700': estado === 'COMPLETADO',
    'bg-blue-100 text-blue-700': estado === 'EN CURSO',
    'bg-red-100 text-red-700': estado === 'CANCELADO'
});
</script>
