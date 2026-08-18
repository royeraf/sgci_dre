<template>
    <div class="bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Evento</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Modalidad</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Fechas</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Inscritos</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="6" class="px-6 py-24 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <Loader2 class="animate-spin h-12 w-12 text-amber-600 mb-4" />
                                <p class="text-lg font-medium text-slate-600">Cargando eventos...</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-else v-for="evento in paginatedEventos" :key="evento.id"
                        class="hover:bg-amber-50 transition-colors">
                        <td class="px-6 py-4 max-w-xs">
                            <p class="font-semibold text-slate-900 truncate">{{ evento.titulo }}</p>
                            <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 text-slate-600">
                                {{ tipoLabel(evento.tipo) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <p class="font-medium">{{ modalidadLabel(evento.modalidad) }}</p>
                            <p class="text-xs text-slate-500 truncate max-w-[160px]">{{ evento.lugar || evento.enlace_virtual || '-' }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <p>{{ formatFecha(evento.fecha_inicio) }}</p>
                            <p v-if="evento.fecha_fin !== evento.fecha_inicio" class="text-xs text-slate-500">
                                al {{ formatFecha(evento.fecha_fin) }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-semibold text-slate-700">
                            {{ evento.inscritos_count }}<span v-if="evento.cupo_maximo" class="text-slate-400"> / {{ evento.cupo_maximo }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 text-xs font-bold rounded-lg" :class="estadoClass(evento.estado)">
                                {{ estadoLabel(evento.estado) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <button @click="$emit('view-detalle', evento)" title="Ver detalles y enlace de inscripción"
                                class="text-amber-600 hover:text-amber-800 p-2 rounded-lg hover:bg-amber-50 transition-colors">
                                <Link2 class="w-5 h-5" />
                            </button>
                            <button @click="$emit('view-inscritos', evento)" title="Ver inscritos y asistencia"
                                class="text-emerald-600 hover:text-emerald-800 p-2 rounded-lg hover:bg-emerald-50 transition-colors">
                                <Users class="w-5 h-5" />
                            </button>
                            <button @click="$emit('view-examenes', evento)" title="Ver exámenes"
                                class="text-purple-600 hover:text-purple-800 p-2 rounded-lg hover:bg-purple-50 transition-colors">
                                <ClipboardCheck class="w-5 h-5" />
                            </button>
                            <button @click="$emit('edit', evento)" title="Editar evento"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button v-if="evento.estado === 'borrador'" @click="$emit('change-estado', evento, 'publicado')"
                                title="Publicar evento"
                                class="text-green-600 hover:text-green-800 p-2 rounded-lg hover:bg-green-50 transition-colors">
                                <CheckCircle class="w-5 h-5" />
                            </button>
                            <button v-if="evento.estado === 'publicado'" @click="$emit('change-estado', evento, 'cerrado')"
                                title="Cerrar inscripciones"
                                class="text-orange-600 hover:text-orange-800 p-2 rounded-lg hover:bg-orange-50 transition-colors">
                                <Lock class="w-5 h-5" />
                            </button>
                            <button @click="$emit('delete', evento)" title="Eliminar evento"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="!loading && eventos.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <CalendarX class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay eventos registrados.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ClientPagination
            :total-items="eventos.length"
            :current-page="currentPage"
            :per-page="perPage"
            @update:current-page="$emit('update:currentPage', $event)"
            @update:per-page="$emit('update:perPage', $event)"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Loader2, Pencil, Trash2, Link2, Users, CheckCircle, Lock, CalendarX, ClipboardCheck } from 'lucide-vue-next';
import ClientPagination from '@/Components/Common/ClientPagination.vue';

const props = defineProps({
    eventos: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
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

defineEmits(['edit', 'delete', 'view-detalle', 'view-inscritos', 'view-examenes', 'change-estado', 'update:currentPage', 'update:perPage']);

const paginatedEventos = computed(() => {
    const start = (props.currentPage - 1) * props.perPage;
    const end = start + props.perPage;
    return props.eventos.slice(start, end);
});

const TIPO_LABELS = {
    curso: 'Curso',
    seminario: 'Seminario',
    capacitacion: 'Capacitación',
    taller: 'Taller',
    conferencia: 'Conferencia',
    otro: 'Otro',
};

const MODALIDAD_LABELS = {
    presencial: 'Presencial',
    virtual: 'Virtual',
    hibrida: 'Híbrida',
};

const ESTADO_LABELS = {
    borrador: 'Borrador',
    publicado: 'Publicado',
    cerrado: 'Cerrado',
};

const tipoLabel = (tipo) => TIPO_LABELS[tipo] || tipo;
const modalidadLabel = (modalidad) => MODALIDAD_LABELS[modalidad] || modalidad;
const estadoLabel = (estado) => ESTADO_LABELS[estado] || estado;

const estadoClass = (estado) => ({
    'bg-slate-100 text-slate-600': estado === 'borrador',
    'bg-green-100 text-green-700': estado === 'publicado',
    'bg-red-100 text-red-700': estado === 'cerrado',
});

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
};
</script>
