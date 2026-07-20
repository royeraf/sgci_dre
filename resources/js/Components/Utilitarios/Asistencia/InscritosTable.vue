<template>
    <div class="bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Participante</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Documento</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Género</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Dirección / Oficina</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Profesión / Cargo</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Régimen</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Asistencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="inscripcion in paginatedInscripciones" :key="inscripcion.id"
                        class="hover:bg-amber-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-semibold text-slate-900">{{ inscripcion.nombres }} {{ inscripcion.apellidos }}</p>
                            <p class="text-xs text-slate-500">{{ inscripcion.correo }}</p>
                        </td>
                        <td class="px-6 py-4 font-mono text-slate-700">{{ inscripcion.numero_documento }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ inscripcion.genero || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <p>{{ inscripcion.direccion || '-' }}</p>
                            <p v-if="inscripcion.oficina" class="text-xs text-slate-500">{{ inscripcion.oficina }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">
                            <p>{{ inscripcion.profesion || '-' }}</p>
                            <p v-if="inscripcion.cargo" class="text-xs text-slate-500">{{ inscripcion.cargo }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-700">{{ inscripcion.regimen || '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <button @click="$emit('toggle-asistencia', inscripcion)"
                                :disabled="marcando === inscripcion.id"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-bold transition-all disabled:opacity-50"
                                :class="asistioEnDiaActivo(inscripcion)
                                    ? 'bg-green-100 text-green-700 hover:bg-green-200'
                                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'">
                                <Loader2 v-if="marcando === inscripcion.id" class="w-4 h-4 animate-spin" />
                                <CheckCircle2 v-else-if="asistioEnDiaActivo(inscripcion)" class="w-4 h-4" />
                                <Circle v-else class="w-4 h-4" />
                                {{ asistioEnDiaActivo(inscripcion) ? 'Presente' : 'Marcar presente' }}
                            </button>
                            <p v-if="horaAsistencia(inscripcion)" class="text-[11px] text-slate-400 mt-1 flex items-center justify-center gap-1">
                                {{ horaAsistencia(inscripcion) }}
                                <span v-if="esAutoMarcada(inscripcion)" title="Marcada por el propio inscrito desde el enlace público"
                                    class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-600 font-semibold">
                                    <Smartphone class="w-2.5 h-2.5" /> auto
                                </span>
                            </p>
                        </td>
                    </tr>
                    <tr v-if="inscripciones.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <UserX class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay inscritos registrados para este evento.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <ClientPagination
            :total-items="inscripciones.length"
            :current-page="currentPage"
            :per-page="perPage"
            @update:current-page="$emit('update:currentPage', $event)"
            @update:per-page="$emit('update:perPage', $event)"
        />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Loader2, CheckCircle2, Circle, UserX, Smartphone } from 'lucide-vue-next';
import ClientPagination from '@/Components/Common/ClientPagination.vue';

const props = defineProps({
    inscripciones: {
        type: Array,
        default: () => []
    },
    diaActivo: {
        type: String,
        required: true
    },
    marcando: {
        type: String,
        default: null
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

defineEmits(['toggle-asistencia', 'update:currentPage', 'update:perPage']);

const paginatedInscripciones = computed(() => {
    const start = (props.currentPage - 1) * props.perPage;
    const end = start + props.perPage;
    return props.inscripciones.slice(start, end);
});

const asistenciaDelDia = (inscripcion) => {
    return inscripcion.asistencias.find((a) => a.fecha === props.diaActivo);
};

const asistioEnDiaActivo = (inscripcion) => !!asistenciaDelDia(inscripcion);

const horaAsistencia = (inscripcion) => {
    const asistencia = asistenciaDelDia(inscripcion);
    return asistencia ? asistencia.marcado_en : null;
};

const esAutoMarcada = (inscripcion) => {
    const asistencia = asistenciaDelDia(inscripcion);
    return !!asistencia?.auto;
};
</script>
