<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { X, CheckCircle2, XCircle, MinusCircle, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    examen: {
        type: Object,
        required: true
    },
    intentoId: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['close']);

const loading = ref(true);
const error = ref(null);
const detalle = ref(null);

const cargarDetalle = async () => {
    loading.value = true;
    error.value = null;
    try {
        const { data } = await axios.get(`/utilitarios/eventos/${props.evento.id}/examenes/${props.examen.id}/resultados/${props.intentoId}/detalle`);
        detalle.value = data;
    } catch (e) {
        error.value = 'No se pudo cargar el detalle de este intento.';
    } finally {
        loading.value = false;
    }
};

onMounted(cargarDetalle);

const claseAlternativa = (pregunta, alternativa) => {
    const esLaMarcada = alternativa.id === pregunta.alternativa_marcada_id;

    if (alternativa.es_correcta) {
        return 'border-green-300 bg-green-50 text-green-800';
    }
    if (esLaMarcada) {
        return 'border-red-300 bg-red-50 text-red-800';
    }
    return 'border-slate-200 bg-slate-50 text-slate-600';
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="emit('close')">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                    appear
                >
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-5 py-4 flex items-center justify-between flex-shrink-0">
                            <h2 class="text-base font-bold text-white">Detalle del intento</h2>
                            <button @click="emit('close')"
                                class="cursor-pointer bg-white/10 hover:bg-white/25 text-white rounded-lg p-1.5 transition-colors duration-150">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Loading -->
                        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center py-16 gap-3">
                            <Loader2 class="w-8 h-8 text-purple-500 animate-spin" />
                            <p class="text-sm text-slate-500">Cargando detalle...</p>
                        </div>

                        <!-- Error -->
                        <div v-else-if="error" class="flex-1 flex flex-col items-center justify-center py-16 gap-2">
                            <XCircle class="w-8 h-8 text-red-400" />
                            <p class="text-sm text-slate-500">{{ error }}</p>
                        </div>

                        <template v-else-if="detalle">
                            <!-- Participante y resumen -->
                            <div class="px-5 pt-4 pb-3 border-b border-slate-100 flex-shrink-0">
                                <p class="text-sm font-bold text-slate-900">
                                    {{ detalle.participante.nombres }} {{ detalle.participante.apellidos }}
                                </p>
                                <p class="text-xs text-slate-500 font-mono">{{ detalle.participante.numero_documento }}</p>
                                <div class="flex items-center flex-wrap gap-2 mt-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 text-slate-600">
                                        Intento {{ detalle.numero_intento }}
                                    </span>
                                    <span v-if="detalle.puntaje !== null" class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-purple-100 text-purple-700">
                                        Nota: {{ detalle.puntaje.toFixed(2) }}
                                    </span>
                                    <span v-if="detalle.total_preguntas !== null" class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 text-slate-600">
                                        {{ detalle.aciertos }} / {{ detalle.total_preguntas }} aciertos
                                    </span>
                                </div>
                            </div>

                            <!-- Preguntas -->
                            <div class="px-5 py-4 overflow-y-auto space-y-4 text-sm">
                                <div v-for="(pregunta, index) in detalle.preguntas" :key="pregunta.id"
                                    class="border border-slate-100 rounded-xl p-3">
                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <p class="font-semibold text-slate-800">{{ index + 1 }}. {{ pregunta.enunciado }}</p>
                                        <span v-if="!pregunta.respondida"
                                            class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-amber-100 text-amber-700">
                                            <MinusCircle class="w-3 h-3" /> No respondió
                                        </span>
                                        <span v-else-if="pregunta.es_correcta"
                                            class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-green-100 text-green-700">
                                            <CheckCircle2 class="w-3 h-3" /> Correcta
                                        </span>
                                        <span v-else
                                            class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-red-100 text-red-700">
                                            <XCircle class="w-3 h-3" /> Incorrecta
                                        </span>
                                    </div>
                                    <ul class="space-y-1.5">
                                        <li v-for="alternativa in pregunta.alternativas" :key="alternativa.id"
                                            class="flex items-center gap-2 px-3 py-2 rounded-lg border text-sm"
                                            :class="claseAlternativa(pregunta, alternativa)">
                                            <CheckCircle2 v-if="alternativa.es_correcta" class="w-4 h-4 flex-shrink-0" />
                                            <XCircle v-else-if="alternativa.id === pregunta.alternativa_marcada_id" class="w-4 h-4 flex-shrink-0" />
                                            <span v-else class="w-4 h-4 flex-shrink-0"></span>
                                            <span>{{ alternativa.texto }}</span>
                                            <span v-if="alternativa.id === pregunta.alternativa_marcada_id" class="ml-auto text-[10px] font-bold uppercase text-slate-500">
                                                Marcada
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </template>

                        <!-- Footer -->
                        <div class="px-5 pb-4 pt-3 flex justify-end gap-3 flex-shrink-0 border-t border-slate-100">
                            <button @click="emit('close')"
                                class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-semibold transition-colors duration-150">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
