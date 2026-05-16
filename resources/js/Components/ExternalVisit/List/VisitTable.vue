<script setup lang="ts">
import { shallowRef } from 'vue';
import { LogIn, LogOut, FileText, Users, Eye, CheckCheck } from 'lucide-vue-next';

import { Visit, PaginatedVisits } from '@/Types/visitor';
import VisitDetailModal from '@/Components/ExternalVisit/List/Modals/VisitDetailModal.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';

const props = defineProps<{
    visits: PaginatedVisits;
    readonly?: boolean;
}>();

defineEmits<{
    exit: [visit: Visit];
    'page-change': [page: number];
    'update:perPage': [perPage: string | number];
}>();



const formatDate = (dateString?: string) => {
    if (!dateString) return '';
    return dateString.split('-').reverse().join('/');
};

/**
 * Formats a full name for public display.
 *
 * Convention (Peruvian naming: nombres + apellido_paterno + apellido_materno):
 *   - 1 word  : shown fully in Title Case
 *   - 2 words : both shown fully (nombre + apellido paterno)
 *   - 3+ words: last word = apellido materno (initial only)
 *               second-to-last = apellido paterno (full)
 *               remaining words = nombres: first one full, rest abbreviated
 *
 * Examples:
 *   "JULIO"                              → "Julio"
 *   "JULIO NARCISO"                      → "Julio Narciso"
 *   "JULIO NARCISO MAMANI"               → "Julio Narciso M."
 *   "JULIO FLORES NARCISO MAMANI"        → "Julio F. Narciso M."
 *   "JULIO CESAR FLORES NARCISO MAMANI"  → "Julio C. F. Narciso M."
 */
const toTitleCase = (word: string) =>
    word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();

const formatName = (fullName?: string): string => {
    if (!fullName) return '';
    const words = fullName.trim().split(/\s+/);

    // 1 word: just title-case it
    if (words.length === 1) return toTitleCase(words[0]);

    // 2 words: nombre + apellido paterno — both full
    if (words.length === 2) return words.map(toTitleCase).join(' ');

    // 3+ words: split into nombres / apellido paterno / apellido materno
    const apellidoMaterno = words[words.length - 1];
    const apellidoPaterno = words[words.length - 2];
    const nombres = words.slice(0, words.length - 2);

    const formattedNombres = nombres.map((w, i) =>
        i === 0 ? toTitleCase(w) : w.charAt(0).toUpperCase() + '.'
    );

    return [
        ...formattedNombres,
        toTitleCase(apellidoPaterno),                    // primer apellido: completo
        apellidoMaterno.charAt(0).toUpperCase() + '.',   // segundo apellido: solo inicial
    ].join(' ');
};

// Detail modal state — shallowRef: entire object is replaced on open/close
const showDetailModal = shallowRef(false);
const selectedVisit = shallowRef<Visit | null>(null);

const openDetails = (visit: Visit) => {
    selectedVisit.value = visit;
    showDetailModal.value = true;
};

const closeDetails = () => {
    showDetailModal.value = false;
    selectedVisit.value = null;
};
</script>

<template>
    <div class="bg-white overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-slate-200 table-fixed">
                <colgroup>
                    <col class="w-24" />       <!-- Fecha -->
                    <col class="w-44" />       <!-- Visitante -->
                    <col class="w-24" />       <!-- Hora Ingreso -->
                    <col class="w-24" />       <!-- Hora Salida -->
                    <col class="w-36" />       <!-- Motivo -->
                    <col class="w-36" />       <!-- Destino -->
                    <col v-if="!readonly" class="w-36" />  <!-- Acciones -->
                </colgroup>
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Visitante
                        </th>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Ingreso
                        </th>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Salida
                        </th>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Motivo
                        </th>
                        <th scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Destino
                        </th>
                        <th v-if="!readonly" scope="col"
                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="visit in visits.data" :key="visit.id"
                        class="hover:bg-purple-50 transition-colors duration-200">
                        <td class="px-3 py-3 whitespace-nowrap">
                            <div class="text-xs font-semibold text-slate-900">{{ formatDate(visit.fecha) }}</div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex items-center gap-2 min-w-0">
                                <!-- <div v-if="!readonly"
                                    class="h-8 w-8 shrink-0 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-xs font-bold text-white shadow-md">
                                    {{ visit.nombres?.charAt(0) || '?' }}
                                </div> -->
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-900 truncate">
                                        {{ readonly ? formatName(visit.nombres) : visit.nombres }}
                                    </div>
                                    <div class="text-xs text-slate-500 font-medium">{{ visit.dni }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <div class="flex items-center text-xs text-slate-700 font-medium">
                                <LogIn class="w-3.5 h-3.5 mr-1 text-purple-500 shrink-0" />
                                {{ visit.hora_ingreso || '--:--' }}
                            </div>
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap">
                            <div class="flex items-center text-xs text-slate-700 font-medium">
                                <LogOut class="w-3.5 h-3.5 mr-1 text-red-500 shrink-0" />
                                {{ visit.hora_salida || '--:--' }}
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="text-xs font-bold text-slate-800 truncate">
                                {{ visit.motivo_nombre || 'No especificado' }}
                            </div>
                            <div v-if="visit.motivo" class="text-xs text-slate-500 truncate">{{ visit.motivo }}</div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="text-xs font-bold text-slate-700 truncate">{{ visit.destino }}</div>
                            <div v-if="visit.a_quien_visita_nombre" class="text-xs text-slate-500 truncate">
                                {{ visit.a_quien_visita_nombre }}
                            </div>
                        </td>
                        <td v-if="!readonly" class="px-3 py-3 whitespace-nowrap text-xs font-medium">
                            <div class="flex flex-col gap-1.5 items-start">
                                <button v-if="visit.is_pending" @click="$emit('exit', visit)"
                                    class="cursor-pointer text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 whitespace-nowrap">
                                    <LogOut class="w-3.5 h-3.5" />
                                    Registrar Salida
                                </button>
                                <span v-else class="text-green-600 bg-green-50 px-2 py-1 rounded-xl whitespace-nowrap flex items-center gap-1">
                                    <CheckCheck class="w-3.5 h-3.5" />
                                    Completado
                                </span>
                                <!-- <a :href="`/visitors/${visit.id}/ticket`" target="_blank"
                                    class="cursor-pointer text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 whitespace-nowrap">
                                    <FileText class="w-3.5 h-3.5" />
                                    Ticket
                                </a> -->
                                <button @click="openDetails(visit)"
                                    class="cursor-pointer text-violet-600 hover:text-violet-900 bg-violet-50 hover:bg-violet-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 whitespace-nowrap">
                                    <Eye class="w-3.5 h-3.5" />
                                    Ver detalles
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="visits.data.length === 0">
                        <td :colspan="readonly ? 6 : 7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="bg-slate-100 rounded-full p-4 mb-4">
                                    <Users class="h-12 w-12 text-slate-400" />
                                </div>
                                <h3 class="text-lg font-bold text-slate-900 mb-1">No hay visitas</h3>
                                <p class="text-sm text-slate-500">Comienza registrando una visita.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <ClientPagination 
            v-if="visits.last_page > 1"
            :total-items="visits.total" 
            :current-page="visits.current_page" 
            :per-page="visits.per_page"
            @update:current-page="$emit('page-change', $event)"
            @update:per-page="$emit('update:perPage', $event)"
        />
    </div>

    <!-- Modal Ver Detalles -->
    <VisitDetailModal
        v-if="showDetailModal && selectedVisit"
        :visit="selectedVisit"
        @close="closeDetails"
    />
</template>
