<script setup lang="ts">
import { LogIn, LogOut, FileText, Users, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

import { Visit, PaginatedVisits } from '@/Types/visitor';

defineProps<{
    visits: PaginatedVisits;
}>();

defineEmits<{
    (e: 'exit', visit: Visit): void;
    (e: 'page-change', page: number): void;
    (e: 'update:perPage', perPage: string | number): void;
}>();
</script>

<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Fecha
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Visitante</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Hora
                            Ingreso</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Hora
                            Salida</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Motivo
                        </th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Destino</th>
                        <th scope="col"
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="visit in visits.data" :key="visit.id"
                        class="hover:bg-purple-50 transition-colors duration-200">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-slate-900">{{ visit.fecha }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-sm font-bold text-white shadow-md mr-3">
                                    {{ visit.nombres?.charAt(0) || '?' }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-slate-900">{{ visit.nombres }}</div>
                                    <div class="text-xs text-slate-500 font-medium">DNI: {{ visit.dni }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm text-slate-700 font-medium">
                                <LogIn class="w-4 h-4 mr-1.5 text-purple-500" />
                                {{ visit.hora_ingreso || '--:--' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center text-sm text-slate-700 font-medium">
                                <LogOut class="w-4 h-4 mr-1.5 text-red-500" />
                                {{ visit.hora_salida || '--:--' }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ visit.motivo_nombre || 'No especificado' }}
                            </div>
                            <div v-if="visit.motivo" class="text-xs text-slate-500 line-clamp-1 max-w-xs">{{
                                visit.motivo }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-700">{{ visit.destino }}</div>
                            <div v-if="visit.a_quien_visita" class="text-xs text-slate-500">Visita a: {{
                                visit.a_quien_visita }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex flex-col gap-2 items-end">
                                <button v-if="visit.is_pending" @click="$emit('exit', visit)"
                                    class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-3 py-1 rounded-lg transition-colors duration-200">
                                    Registrar Salida
                                </button>
                                <span v-else class="text-green-600 bg-green-50 px-3 py-1 rounded-lg">
                                    Completado
                                </span>
                                <a :href="`/visitors/${visit.id}/ticket`" target="_blank"
                                    class="text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-3 py-1 rounded-lg transition-colors duration-200 flex items-center gap-1">
                                    <FileText class="w-4 h-4" />
                                    Ticket
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="visits.data.length === 0">
                        <td colspan="7" class="px-6 py-16 text-center">
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
        <div v-if="visits.last_page > 1" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span>Mostrar</span>
                    <select :value="visits.per_page"
                        @change="$emit('update:perPage', ($event.target as HTMLSelectElement).value)"
                        class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 bg-white">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span>por página</span>
                </div>
                <div class="text-sm text-slate-600">
                    Página {{ visits.current_page }} de {{ visits.last_page }}
                </div>
                <div class="flex items-center gap-1">
                    <button @click="$emit('page-change', 1)" :disabled="visits.current_page === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronsLeft class="w-4 h-4" />
                    </button>
                    <button @click="$emit('page-change', visits.current_page - 1)" :disabled="visits.current_page === 1"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                    <button @click="$emit('page-change', visits.current_page + 1)"
                        :disabled="visits.current_page === visits.last_page"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <button @click="$emit('page-change', visits.last_page)"
                        :disabled="visits.current_page === visits.last_page"
                        class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                        <ChevronsRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
