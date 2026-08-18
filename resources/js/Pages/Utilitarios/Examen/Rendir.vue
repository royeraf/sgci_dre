<template>
    <div class="min-h-screen bg-gradient-to-br from-[var(--accent-05)] via-white to-[var(--accent-05)] py-6 sm:py-8 md:py-12 px-3 sm:px-4 md:px-6 lg:px-8" :style="accentStyle">
        <div class="max-w-2xl mx-auto">
            <!-- Resultado final -->
            <div v-if="yaFinalizado" class="bg-white rounded-3xl shadow-xl p-8 text-center border border-slate-100">
                <CheckCircle2 v-if="resultadoFinal?.aprobado !== false" class="w-14 h-14 text-green-500 mx-auto mb-4" />
                <XCircle v-else class="w-14 h-14 text-red-500 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-slate-800 mb-1">Examen finalizado</h2>
                <p class="text-5xl font-black my-4" style="color: var(--accent);">{{ formatNota(resultadoFinal?.puntaje) }}</p>
                <p class="text-slate-500">{{ resultadoFinal?.aciertos }} de {{ resultadoFinal?.total_preguntas }} respuestas correctas</p>
                <p v-if="resultadoFinal?.aprobado === true"
                    class="mt-3 inline-block px-4 py-1.5 rounded-full bg-green-100 text-green-700 font-bold text-sm">Aprobado</p>
                <p v-else-if="resultadoFinal?.aprobado === false"
                    class="mt-3 inline-block px-4 py-1.5 rounded-full bg-red-100 text-red-700 font-bold text-sm">Desaprobado</p>
            </div>

            <!-- Rendir examen -->
            <template v-else>
                <div class="sticky top-3 z-20 mb-6">
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 px-5 py-4 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-400 uppercase truncate">{{ examen.titulo }}</p>
                            <p class="text-sm text-slate-600">{{ respondidasCount }} / {{ preguntas.length }} respondidas</p>
                        </div>
                        <div class="flex items-center gap-2 px-4 py-2 rounded-xl font-mono text-xl font-bold flex-shrink-0"
                            :class="segundosRestantes <= 60 ? 'bg-red-100 text-red-700' : 'bg-[var(--accent-10)]'"
                            :style="segundosRestantes > 60 ? { color: 'var(--accent-dark)' } : {}">
                            <Timer class="w-5 h-5" />
                            {{ formatTiempo(segundosRestantes) }}
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-for="(pregunta, index) in preguntas" :key="pregunta.id"
                        class="bg-white rounded-2xl shadow-lg border border-slate-100 p-5 sm:p-6">
                        <p class="font-bold text-slate-800 mb-4">
                            <span style="color: var(--accent);">{{ index + 1 }}.</span> {{ pregunta.enunciado }}
                        </p>
                        <div class="space-y-2">
                            <label v-for="alternativa in pregunta.alternativas" :key="alternativa.id"
                                class="flex items-center gap-3 px-4 py-3 border-2 rounded-xl cursor-pointer transition-all"
                                :class="respuestas[pregunta.id] === alternativa.id ? 'border-[var(--accent)] bg-[var(--accent-05)]' : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" :name="'pregunta-' + pregunta.id" :value="alternativa.id"
                                    :checked="respuestas[pregunta.id] === alternativa.id"
                                    @change="seleccionarRespuesta(pregunta.id, alternativa.id)"
                                    class="w-4 h-4 flex-shrink-0" />
                                <span class="text-sm text-slate-700 flex-1">{{ alternativa.texto }}</span>
                                <Loader2 v-if="guardando === pregunta.id" class="w-4 h-4 animate-spin flex-shrink-0" style="color: var(--accent);" />
                            </label>
                        </div>
                    </div>
                </div>

                <button type="button" @click="confirmarFinalizar" :disabled="finalizando"
                    class="w-full mt-6 py-3.5 bg-gradient-to-r from-[var(--accent)] to-[var(--accent-dark)] text-white font-bold rounded-xl hover:brightness-110 transition-all shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
                    <Loader2 v-if="finalizando" class="w-5 h-5 animate-spin" />
                    {{ finalizando ? 'Enviando...' : 'Finalizar Examen' }}
                </button>
            </template>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { Timer, Loader2, CheckCircle2, XCircle } from 'lucide-vue-next';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    examen: {
        type: Object,
        required: true
    },
    finalizado: {
        type: Boolean,
        default: false
    },
    resultado: {
        type: Object,
        default: null
    },
    intento_id: {
        type: String,
        default: null
    },
    segundos_restantes: {
        type: Number,
        default: 0
    },
    preguntas: {
        type: Array,
        default: () => []
    },
    respuestas_guardadas: {
        type: Object,
        default: () => ({})
    }
});

const accentStyle = computed(() => {
    const color = props.evento.color_primario || '#7c3aed';
    const hexToRgb = (hex) => {
        const clean = (hex || '#7c3aed').replace('#', '');
        const bigint = parseInt(clean, 16);
        return { r: (bigint >> 16) & 255, g: (bigint >> 8) & 255, b: bigint & 255 };
    };
    const { r, g, b } = hexToRgb(color);
    const clamp = (v) => Math.max(0, Math.min(255, Math.round(v)));
    return {
        '--accent': color,
        '--accent-dark': `rgb(${clamp(r * 0.75)}, ${clamp(g * 0.75)}, ${clamp(b * 0.75)})`,
        '--accent-05': `rgba(${r}, ${g}, ${b}, 0.05)`,
        '--accent-10': `rgba(${r}, ${g}, ${b}, 0.1)`,
    };
});

const respuestas = ref({ ...props.respuestas_guardadas });
const segundosRestantes = ref(props.segundos_restantes);
const guardando = ref(null);
const finalizando = ref(false);
const yaFinalizado = ref(props.finalizado);
const resultadoFinal = ref(props.finalizado ? props.resultado : null);

const respondidasCount = computed(() => Object.keys(respuestas.value).length);

let timerInterval = null;

const formatTiempo = (segundos) => {
    const m = Math.floor(segundos / 60).toString().padStart(2, '0');
    const s = (segundos % 60).toString().padStart(2, '0');
    return `${m}:${s}`;
};

const formatNota = (nota) => (typeof nota === 'number' ? nota.toFixed(2) : '-');

const baseUrl = `/utilitarios/examen/${props.evento.slug}/${props.examen.slug}/${props.intento_id}`;

const seleccionarRespuesta = async (preguntaId, alternativaId) => {
    respuestas.value[preguntaId] = alternativaId;
    guardando.value = preguntaId;
    try {
        await axios.post(`${baseUrl}/responder`, {
            pregunta_id: preguntaId,
            alternativa_id: alternativaId,
        });
    } catch (error) {
        window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo guardar la respuesta.', 'error');
    } finally {
        guardando.value = null;
    }
};

const handleBeforeUnload = (e) => {
    if (!yaFinalizado.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

const finalizarExamen = async () => {
    if (finalizando.value || yaFinalizado.value) return;
    finalizando.value = true;
    try {
        const { data } = await axios.post(`${baseUrl}/finalizar`);
        resultadoFinal.value = data;
        yaFinalizado.value = true;
        if (timerInterval) clearInterval(timerInterval);
        window.removeEventListener('beforeunload', handleBeforeUnload);
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo finalizar el examen.', 'error');
    } finally {
        finalizando.value = false;
    }
};

const confirmarFinalizar = () => {
    window.Swal?.fire?.({
        title: '¿Finalizar examen?',
        text: `Has respondido ${respondidasCount.value} de ${props.preguntas.length} preguntas. Una vez enviado no podrás cambiar tus respuestas.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, finalizar',
        cancelButtonText: 'Seguir respondiendo'
    }).then((result) => {
        if (result.isConfirmed) finalizarExamen();
    });
};

onMounted(() => {
    if (!yaFinalizado.value) {
        timerInterval = setInterval(() => {
            segundosRestantes.value = Math.max(0, segundosRestantes.value - 1);
            if (segundosRestantes.value === 0) {
                finalizarExamen();
            }
        }, 1000);
        window.addEventListener('beforeunload', handleBeforeUnload);
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    window.removeEventListener('beforeunload', handleBeforeUnload);
});
</script>
