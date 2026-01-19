<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Solicitante
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Fecha/Hora
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Oficina
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Asunto
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Observación
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="cita in paginatedCitas" :key="cita.id"
                        class="hover:bg-pink-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div :class="cita.estado === 'ATENDIDO'
                                    ? 'from-green-500 to-emerald-600'
                                    : 'from-red-500 to-rose-600'"
                                    class="h-9 w-9 rounded-full bg-gradient-to-br flex items-center justify-center text-xs font-bold text-white shadow-md mr-3">
                                    {{ getInitials(cita.nombres, cita.apellidos) }}
                                </div>
                                <div>
                                    <div class="text-sm font-semibold text-slate-900">{{ cita.nombres }} {{
                                        cita.apellidos }}</div>
                                    <div class="text-xs text-slate-500 font-medium">DNI: {{ cita.dni }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-900">{{ formatDate(cita.fecha) }}</div>
                            <div class="text-xs text-slate-500 font-medium">{{ cita.hora }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-700 max-w-[180px] truncate" :title="cita.oficina">
                                {{ cita.oficina }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-700 line-clamp-2 max-w-xs">
                                {{ cita.asunto }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                :class="getStatusClass(cita.estado)">
                                {{ cita.estado }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-slate-600 line-clamp-2 max-w-[200px] italic">
                                {{ getLatestObservation(cita) || '-' }}
                            </div>
                        </td>
                    </tr>
                    <tr v-if="paginatedCitas.length === 0">
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-100 rounded-full p-4 mb-4">
                                    <CheckCircle class="h-12 w-12 text-slate-400" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">No hay citas finalizadas</h3>
                                <p class="text-sm text-slate-500">
                                    Aquí se mostrará el histórico de citas atendidas o canceladas.
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
                    <select :value="perPage" @change="$emit('update:perPage', Number($event.target.value))"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-pink-500 bg-white">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span>por página</span>
                </div>
                <div class="text-sm text-slate-600">
                    Página {{ currentPage }} de {{ totalPages }}
                </div>
                <div class="flex items-center gap-1">
                    <button @click="$emit('update:currentPage', 1)" :disabled="currentPage === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <ChevronsLeft class="w-4 h-4" />
                    </button>
                    <button @click="$emit('update:currentPage', currentPage - 1)" :disabled="currentPage === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button @click="$emit('update:currentPage', currentPage + 1)" :disabled="currentPage === totalPages"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <button @click="$emit('update:currentPage', totalPages)" :disabled="currentPage === totalPages"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
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
    CheckCircle,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight
} from 'lucide-vue-next';

const props = defineProps({
    citas: {
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

defineEmits(['update:currentPage', 'update:perPage']);

const paginatedCitas = computed(() => {
    const start = (props.currentPage - 1) * props.perPage;
    const end = start + props.perPage;
    return props.citas.slice(start, end);
});

const totalPages = computed(() => {
    return Math.ceil(props.citas.length / props.perPage) || 1;
});

const getInitials = (nombres, apellidos) => {
    const first = (nombres || '?').charAt(0).toUpperCase();
    const last = (apellidos || '').charAt(0).toUpperCase();
    return first + last;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const date = new Date(dateStr);
        return date.toLocaleDateString('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            timeZone: 'UTC'
        });
    } catch {
        return dateStr;
    }
};

const getStatusClass = (estado) => {
    const classes = {
        'ATENDIDO': 'bg-gradient-to-r from-green-500 to-emerald-600 text-white',
        'CANCELADO': 'bg-gradient-to-r from-red-500 to-rose-600 text-white'
    };
    return classes[estado] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

const getLatestObservation = (cita) => {
    if (cita.historial && Array.isArray(cita.historial) && cita.historial.length > 0) {
        const entriesWithDesc = [...cita.historial]
            .reverse()
            .filter(h => h.descripcion && h.descripcion.trim() !== 'Cita registrada');

        if (entriesWithDesc.length > 0) {
            return entriesWithDesc[0].descripcion;
        }
    }
    return '';
};
</script>
