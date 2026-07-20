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
                        {{ intentos.length }} intento(s) registrado(s)<span v-if="examen.nota_minima_aprobatoria !== null"> · Nota mínima aprobatoria: {{ examen.nota_minima_aprobatoria }}</span>
                    </p>
                </div>
            </div>

            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="intento in intentos" :key="intento.id" class="hover:bg-purple-50 transition-colors">
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
                            </tr>
                            <tr v-if="intentos.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <ClipboardX class="w-16 h-16 text-slate-300 mb-4" />
                                        <p>Nadie ha rendido este examen todavía.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardX } from 'lucide-vue-next';

defineProps({
    evento: {
        type: Object,
        required: true
    },
    examen: {
        type: Object,
        required: true
    },
    intentos: {
        type: Array,
        default: () => []
    }
});
</script>
