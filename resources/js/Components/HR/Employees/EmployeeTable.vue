<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Search -->
        <div class="p-4 border-b border-slate-200 bg-slate-50">
            <div class="relative">
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-slate-400" />
                <input :value="searchQuery" @input="$emit('update:searchQuery', $event.target.value)" type="text"
                    placeholder="Buscar por nombre, DNI o área..."
                    class="w-full md:w-96 pl-10 pr-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Empleado</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">DNI</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Cargo</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Área</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Contrato</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="7" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <Loader2 class="animate-spin h-12 w-12 text-emerald-600 mb-4" />
                                <p class="text-lg font-medium text-slate-600">Cargando personal...</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-else v-for="emp in employees" :key="emp.id" class="hover:bg-emerald-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div
                                    class="h-10 w-10 flex-shrink-0 aspect-square rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-sm mr-3">
                                    {{ getInitials(emp.nombres + ' ' + emp.apellidos) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-900">{{ emp.nombres }} {{ emp.apellidos }}</p>
                                    <p class="text-xs text-slate-500">{{ emp.correo || '-' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-mono text-slate-700">{{ emp.dni }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ emp.cargo || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ emp.area || '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs font-bold rounded-lg"
                                :class="contractClass(emp.tipo_contrato)">
                                {{ emp.tipo_contrato || '-' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-bold rounded-lg" :class="statusClass(emp.estado)">
                                {{ emp.estado }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="$emit('view', emp)" title="Ver detalle del empleado"
                                class="text-emerald-600 hover:text-emerald-800 p-2 rounded-lg hover:bg-emerald-50 transition-colors">
                                <Eye class="w-5 h-5" />
                            </button>
                            <button @click="$emit('edit', emp)" title="Editar empleado"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors ml-1">
                                <Pencil class="w-5 h-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!loading && employees.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <UserX class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay empleados registrados.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Search, Loader2, Eye, Pencil, UserX } from 'lucide-vue-next';

defineProps({
    employees: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    },
    searchQuery: {
        type: String,
        default: ''
    }
});

defineEmits(['view', 'edit', 'update:searchQuery']);

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const contractClass = (tipo) => ({
    'bg-blue-100 text-blue-700': tipo === 'Nombrado',
    'bg-yellow-100 text-yellow-700': tipo === 'CAS',
    'bg-purple-100 text-purple-700': tipo === 'Locador',
    'bg-pink-100 text-pink-700': tipo === 'Practicante',
    'bg-gray-100 text-gray-700': !tipo
});

const statusClass = (estado) => ({
    'bg-green-100 text-green-700': estado === 'ACTIVO',
    'bg-orange-100 text-orange-700': estado === 'VACACIONES',
    'bg-yellow-100 text-yellow-700': estado === 'LICENCIA',
    'bg-red-100 text-red-700': estado === 'INACTIVO'
});
</script>
