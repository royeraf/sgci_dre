<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
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
                        <th scope="col" class="relative px-6 py-4">
                            <span class="sr-only">Acciones</span>
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
                            <div class="text-sm font-semibold text-slate-900">{{ occurrence.fecha }}</div>
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
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button
                                @click="$emit('view', occurrence)"
                                class="text-slate-400 hover:text-blue-600 transition-colors duration-200 p-2 hover:bg-blue-50 rounded-lg"
                            >
                                <Eye class="h-5 w-5" />
                            </button>
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
        <div v-if="totalPages > 1" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span>Mostrar</span>
                    <select
                        :value="perPage"
                        @change="$emit('update:perPage', Number($event.target.value))"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white"
                    >
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span>por pagina</span>
                </div>
                <div class="text-sm text-slate-600">
                    Pagina {{ currentPage }} de {{ totalPages }}
                </div>
                <div class="flex items-center gap-1">
                    <button
                        @click="$emit('update:currentPage', 1)"
                        :disabled="currentPage === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronsLeft class="w-4 h-4" />
                    </button>
                    <button
                        @click="$emit('update:currentPage', currentPage - 1)"
                        :disabled="currentPage === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button
                        @click="$emit('update:currentPage', currentPage + 1)"
                        :disabled="currentPage === totalPages"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <button
                        @click="$emit('update:currentPage', totalPages)"
                        :disabled="currentPage === totalPages"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <ChevronsRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    Eye,
    ClipboardList,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight
} from 'lucide-vue-next';

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

defineEmits(['view', 'update:currentPage', 'update:perPage']);

const paginatedOccurrences = computed(() => {
    const start = (props.currentPage - 1) * props.perPage;
    const end = start + props.perPage;
    return props.occurrences.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(props.occurrences.length / props.perPage) || 1;
});

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
