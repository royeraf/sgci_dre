<template>
    <div class="bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Tipo
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Descripcion
                        </th>
                        <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Responsable
                        </th>
                        <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr
                        v-for="occurrence in paginatedOccurrences"
                        :key="occurrence.id"
                        class="hover:bg-blue-50 transition-colors duration-200"
                    >
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-900">{{ formatDate(occurrence.fecha) }}</div>
                            <div class="text-xs text-slate-500 font-medium">{{ occurrence.hora }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                :class="getTypeClass(occurrence.tipo)"
                            >
                                {{ occurrence.tipo }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-700 line-clamp-2 max-w-xs">
                                {{ occurrence.descripcion }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3">
                                    {{ (occurrence.vigilante || '?').charAt(0) }}
                                </div>
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ occurrence.vigilante }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span
                                class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                :class="getStatusClass(occurrence.estado)"
                            >
                                {{ occurrence.estado }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex justify-end gap-2">
                                <button @click="$emit('view', occurrence)" title="Ver detalles"
                                    class="cursor-pointer p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all shadow-sm">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button @click="$emit('edit', occurrence)" title="Editar"
                                    class="cursor-pointer p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-all shadow-sm">
                                    <Pencil class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="paginatedOccurrences.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-100 rounded-full p-4 mb-4">
                                    <ClipboardList class="h-12 w-12 text-slate-400" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">No hay ocurrencias</h3>
                                <p class="text-sm text-slate-500">
                                    Registra una nueva ocurrencia para comenzar.
                                </p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <ClientPagination 
            :total-items="occurrences.length" 
            :current-page="currentPage" 
            :per-page="perPage"
            @update:current-page="$emit('update:currentPage', $event)"
            @update:per-page="$emit('update:perPage', $event)"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    Eye,
    Pencil,
    ClipboardList
} from 'lucide-vue-next';
import ClientPagination from '@/Components/Common/ClientPagination.vue';

const props = defineProps({
    occurrences: {
        type: Array,
        required: true
    },
    currentPage: {
        type: Number,
        default: 1
    },
    perPage: {
        type: Number,
        default: 10
    }
});

defineEmits(['view', 'edit', 'update:currentPage', 'update:perPage']);

const paginatedOccurrences = computed(() => {
    const start = (props.currentPage - 1) * props.perPage;
    const end = start + props.perPage;
    return props.occurrences.slice(start, end);
});

const formatDate = (fecha) => {
    if (!fecha) return '';
    const [y, m, d] = fecha.split('-');
    return `${d}/${m}/${y}`;
};

const getTypeClass = (tipo) => {
    const classes = {
        'Emergencia': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
        'Robo': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
        'Incidente': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'Rutina': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'Aviso': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
        'Otros': 'bg-gradient-to-r from-gray-500 to-gray-600 text-white'
    };
    return classes[tipo] || classes['Otros'];
};

const getStatusClass = (estado) => {
    const classes = {
        'Pendiente': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'Aprobado': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'Cerrado': 'bg-gradient-to-r from-gray-500 to-gray-600 text-white'
    };
    return classes[estado] || classes['Pendiente'];
};
</script>
