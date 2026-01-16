<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Empleado</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Tipo / Periodo</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Días</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Motivo</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                        Estado</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
                <tr v-if="loading" class="animate-pulse">
                    <td colspan="5" class="px-6 py-10 text-center text-slate-400 font-medium">Cargando
                        registros...</td>
                </tr>
                <tr v-else v-for="lic in licenses" :key="lic.id" class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <div
                                class="h-10 w-10 rounded-xl bg-gradient-to-br from-purple-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xs shadow-sm">
                                {{ lic.nombres?.charAt(0) }}{{ lic.apellidos?.charAt(0) }}
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-bold text-slate-900">
                                    {{ lic.nombres }} {{ lic.apellidos }}
                                </div>
                                <div class="text-xs text-slate-500 font-medium font-mono">
                                    DNI: {{ lic.dni }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex flex-col">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold w-fit mb-1"
                                :class="getTypeBadgeClass(lic.tipo_licencia)">
                                {{ lic.tipo_licencia }}
                            </span>
                            <span class="text-xs text-slate-600 font-medium">
                                {{ formatDate(lic.fecha_inicio) }} al {{ formatDate(lic.fecha_fin) }}
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div
                            class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-100 text-slate-700 font-bold text-sm">
                            {{ lic.dias_solicitados }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-600 max-w-xs truncate" :title="lic.motivo">
                            {{ lic.motivo || '-' }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center text-sm font-bold">
                        <span class="px-3 py-1 rounded-lg bg-green-100 text-green-700 text-xs">
                            {{ lic.estado }}
                        </span>
                    </td>
                </tr>
                <tr v-if="!loading && licenses.length === 0">
                    <td colspan="5" class="px-6 py-20 text-center flex flex-col items-center">
                        <div class="bg-slate-50 p-4 rounded-full mb-3">
                            <FolderOpen class="w-10 h-10 text-slate-300" />
                        </div>
                        <p class="text-slate-500 font-medium">No hay licencias registradas aún.</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
import { FolderOpen } from 'lucide-vue-next';

defineProps({
    licenses: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const getTypeBadgeClass = (type) => {
    const classes = {
        'Enfermedad': 'bg-red-100 text-red-700',
        'Maternidad': 'bg-rose-100 text-rose-700',
        'Paternidad': 'bg-blue-100 text-blue-700',
        'Personal': 'bg-amber-100 text-amber-700',
        'Otros': 'bg-slate-100 text-slate-700'
    };
    return classes[type] || 'bg-slate-100 text-slate-700';
};

const formatDate = (date) => {
    if (!date) return '-';
    const [year, month, day] = date.split('-');
    return `${day}/${month}/${year}`;
};
</script>
