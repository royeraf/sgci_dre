<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <Link :href="`/utilitarios/eventos/${evento.id}/examenes`" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-purple-600 mb-2">
                        <ArrowLeft class="w-4 h-4" /> Volver a Exámenes
                    </Link>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-fuchsia-600 bg-clip-text text-transparent">
                        {{ examen.titulo }}
                    </h1>
                    <p class="mt-2 text-slate-600">
                        {{ resumen.total }} intento(s) registrado(s)<span v-if="examen.nota_minima_aprobatoria !== null"> · Nota mínima aprobatoria: {{ examen.nota_minima_aprobatoria }}</span>
                    </p>
                </div>
            </div>

            <!-- Cuadro de resumen -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Total intentos</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ resumen.total }}</p>
                </div>
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Aprobados</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">{{ resumen.aprobados ?? '-' }}</p>
                </div>
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Desaprobados</p>
                    <p class="text-2xl font-bold text-red-600 mt-1">{{ resumen.desaprobados ?? '-' }}</p>
                </div>
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Tasa de aprobación</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ resumen.tasa_aprobacion !== null ? resumen.tasa_aprobacion + '%' : '-' }}</p>
                </div>
                <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                    <p class="text-xs font-bold text-slate-500 uppercase">Promedio de nota</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ resumen.promedio_nota !== null ? resumen.promedio_nota.toFixed(2) : '-' }}</p>
                </div>
            </div>

            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <!-- Barra de filtros -->
                <div class="p-5 sm:p-6 border-b border-slate-200 bg-slate-50 flex flex-col lg:flex-row gap-4 items-start lg:items-end">
                    <div class="flex-1 w-full">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Buscar</label>
                        <input type="text" v-model="form.search" placeholder="Nombre, apellido o documento"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div class="w-full lg:w-auto">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Resultado</label>
                        <select v-model="form.resultado" @change="aplicarFiltros"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500 bg-white">
                            <option value="">Todos</option>
                            <option value="aprobado">Aprobado</option>
                            <option value="desaprobado">Desaprobado</option>
                            <option value="finalizado">Finalizado</option>
                            <option value="en_curso">En curso</option>
                        </select>
                    </div>
                    <div class="w-full lg:w-auto">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Desde</label>
                        <input type="date" v-model="form.fecha_desde" @change="aplicarFiltros"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <div class="w-full lg:w-auto">
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Hasta</label>
                        <input type="date" v-model="form.fecha_hasta" @change="aplicarFiltros"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-purple-500" />
                    </div>
                    <button @click="limpiarFiltros" type="button"
                        class="w-full lg:w-auto inline-flex items-center justify-center gap-1.5 px-4 py-2 text-sm font-bold rounded-lg text-slate-600 border border-slate-200 hover:bg-slate-100 transition-colors">
                        <FilterX class="w-4 h-4" /> Limpiar
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Participante</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Documento</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Intento</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Aciertos</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Nota</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Resultado</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Fecha</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="intento in intentos.data" :key="intento.id" class="hover:bg-purple-50 transition-colors">
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-slate-900">{{ intento.nombres }} {{ intento.apellidos }}</p>
                                </td>
                                <td class="px-6 py-4 font-mono text-slate-700">{{ intento.numero_documento }}</td>
                                <td class="px-6 py-4 text-center text-sm text-slate-700">{{ intento.numero_intento }}</td>
                                <td class="px-6 py-4 text-center text-sm text-slate-700">
                                    <span v-if="intento.total_preguntas !== null">{{ intento.aciertos }} / {{ intento.total_preguntas }}</span>
                                    <span v-else class="text-slate-400">En curso</span>
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-900">
                                    {{ intento.puntaje !== null ? intento.puntaje.toFixed(2) : '-' }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span v-if="intento.aprobado === true" class="px-3 py-1 text-xs font-bold rounded-lg bg-green-100 text-green-700">Aprobado</span>
                                    <span v-else-if="intento.aprobado === false" class="px-3 py-1 text-xs font-bold rounded-lg bg-red-100 text-red-700">Desaprobado</span>
                                    <span v-else-if="intento.finalizado_en" class="px-3 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-600">Finalizado</span>
                                    <span v-else class="px-3 py-1 text-xs font-bold rounded-lg bg-amber-100 text-amber-700">En curso</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <p>Inicio: {{ intento.iniciado_en || '-' }}</p>
                                    <p v-if="intento.finalizado_en">Fin: {{ intento.finalizado_en }}</p>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button @click="verDetalle(intento)" title="Ver detalle de respuestas"
                                        class="text-purple-600 hover:text-purple-800 p-2 rounded-lg hover:bg-purple-50 transition-colors">
                                        <Eye class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="intentos.data.length === 0">
                                <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <ClipboardX class="w-16 h-16 text-slate-300 mb-4" />
                                        <p>No se encontraron intentos con los filtros aplicados.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <Pagination :links="intentos.links" />
            </div>

            <DetalleIntentoModal v-if="detalleIntentoId" :evento="evento" :examen="examen"
                :intento-id="detalleIntentoId" @close="detalleIntentoId = null" />
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup>
import { reactive, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardX, FilterX, Eye } from 'lucide-vue-next';
import Pagination from '@/Components/Common/Pagination.vue';
import DetalleIntentoModal from '@/Components/Utilitarios/Examenes/DetalleIntentoModal.vue';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    examen: {
        type: Object,
        required: true
    },
    intentos: {
        type: Object,
        default: () => ({ data: [], links: [] })
    },
    resumen: {
        type: Object,
        default: () => ({ total: 0, finalizados: 0, en_curso: 0, aprobados: null, desaprobados: null, tasa_aprobacion: null, promedio_nota: null })
    },
    filtros: {
        type: Object,
        default: () => ({})
    }
});

const form = reactive({
    search: props.filtros.search || '',
    resultado: props.filtros.resultado || '',
    fecha_desde: props.filtros.fecha_desde || '',
    fecha_hasta: props.filtros.fecha_hasta || '',
});

const aplicarFiltros = () => {
    router.get(`/utilitarios/eventos/${props.evento.id}/examenes/${props.examen.id}/resultados`, {
        search: form.search || undefined,
        resultado: form.resultado || undefined,
        fecha_desde: form.fecha_desde || undefined,
        fecha_hasta: form.fecha_hasta || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['intentos', 'resumen', 'filtros'],
    });
};

let debounceTimer = null;
watch(() => form.search, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(aplicarFiltros, 300);
});

const limpiarFiltros = () => {
    form.search = '';
    form.resultado = '';
    form.fecha_desde = '';
    form.fecha_hasta = '';
    aplicarFiltros();
};

const detalleIntentoId = ref(null);

const verDetalle = (intento) => {
    detalleIntentoId.value = intento.id;
};
</script>
