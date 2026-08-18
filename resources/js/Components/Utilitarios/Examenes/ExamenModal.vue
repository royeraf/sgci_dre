<template>
    <div class="fixed inset-0 z-50 bg-white flex flex-col">
        <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center flex-shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <ClipboardCheck v-if="!isEditing" class="w-6 h-6" />
                        <Pencil v-else class="w-6 h-6" />
                        {{ isEditing ? 'Editar Examen' : 'Nuevo Examen' }}
                    </h3>
                    <p class="text-purple-50 text-sm mt-1">
                        {{ isEditing ? 'Modifique la programación y las preguntas del examen' : 'Programe un examen de alternativas para este evento' }}
                    </p>
                </div>
                <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                    <X class="w-6 h-6" />
                </button>
            </div>

            <!-- Form -->
            <form @submit.prevent="onSubmit" class="flex-1 flex flex-col min-h-0">
                <div class="flex-1 overflow-y-auto p-6 space-y-6 custom-scrollbar">
                    <p v-if="isEditing && examen?.intentos_count > 0"
                        class="text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                        Este examen ya tiene {{ examen.intentos_count }} intento(s) registrado(s): los cambios a las
                        preguntas no se guardarán, solo la programación (fechas, horario, duración e intentos).
                    </p>

                    <!-- Datos básicos -->
                    <div class="bg-gradient-to-br from-purple-50 to-fuchsia-50 rounded-xl p-4 border border-purple-100 space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Título <span class="text-red-500">*</span>
                            </label>
                            <input v-model="titulo" v-bind="tituloProps" type="text"
                                placeholder="Ej. Examen final del curso"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none"
                                :class="formErrors.titulo ? 'border-red-400' : 'border-purple-200'" />
                            <p v-if="formErrors.titulo" class="mt-1 text-sm text-red-600">{{ formErrors.titulo }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Descripción</label>
                            <textarea v-model="descripcion" rows="2" placeholder="Instrucciones o detalles adicionales..."
                                class="w-full px-4 py-2.5 border-2 border-purple-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Programación -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaInicio" v-bind="fechaInicioProps" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.fecha_inicio ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_inicio" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_inicio }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de fin <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaFin" v-bind="fechaFinProps" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.fecha_fin ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_fin" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_fin }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Hora de inicio</label>
                            <input v-model="horaInicio" v-bind="horaInicioProps" type="time"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none cursor-pointer" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Hora de fin</label>
                            <input v-model="horaFin" v-bind="horaFinProps" type="time"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none cursor-pointer" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Duración (minutos) <span class="text-red-500">*</span>
                            </label>
                            <input v-model="duracionMinutos" v-bind="duracionMinutosProps" type="number" min="1" max="600"
                                placeholder="Ej. 60"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none"
                                :class="formErrors.duracion_minutos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.duracion_minutos" class="mt-1 text-sm text-red-600">{{ formErrors.duracion_minutos }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Intentos permitidos <span class="text-red-500">*</span>
                            </label>
                            <input v-model="intentosPermitidos" v-bind="intentosPermitidosProps" type="number" min="1" max="20"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none"
                                :class="formErrors.intentos_permitidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.intentos_permitidos" class="mt-1 text-sm text-red-600">{{ formErrors.intentos_permitidos }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nota mínima aprobatoria</label>
                            <input v-model="notaMinima" v-bind="notaMinimaProps" type="number" min="0" max="20" step="0.5"
                                placeholder="Ej. 11 (opcional)"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none" />
                        </div>
                    </div>

                    <!-- Preguntas -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Preguntas <span class="text-red-500">*</span>
                            </label>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(pregunta, pIndex) in preguntas" :key="pIndex"
                                class="border-2 border-purple-100 rounded-xl p-4 space-y-3 bg-purple-50/30">
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-xs font-bold text-purple-600 uppercase mt-2">Pregunta {{ pIndex + 1 }}</span>
                                    <button type="button" @click="quitarPregunta(pIndex)" :disabled="preguntas.length === 1"
                                        class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>

                                <textarea v-model="pregunta.enunciado" rows="2" placeholder="Escriba el enunciado de la pregunta..."
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none resize-none"></textarea>

                                <div class="space-y-2">
                                    <div v-for="(alternativa, aIndex) in pregunta.alternativas" :key="aIndex"
                                        class="flex items-center gap-2">
                                        <input type="radio" :name="'correcta-' + pIndex" :checked="alternativa.es_correcta"
                                            @change="marcarCorrecta(pIndex, aIndex)"
                                            class="w-4 h-4 accent-purple-600 flex-shrink-0" title="Marcar como correcta" />
                                        <input v-model="alternativa.texto" type="text" :placeholder="`Alternativa ${aIndex + 1}`"
                                            class="flex-1 px-3 py-2 border-2 border-slate-200 rounded-lg text-sm text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 bg-white transition-all duration-200 outline-none" />
                                        <button type="button" @click="quitarAlternativa(pIndex, aIndex)"
                                            :disabled="pregunta.alternativas.length === 2"
                                            class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-30 disabled:cursor-not-allowed flex-shrink-0">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <button type="button" @click="agregarAlternativa(pIndex)"
                                        class="text-xs font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-1">
                                        <Plus class="w-3.5 h-3.5" /> Agregar alternativa
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" @click="agregarPregunta"
                            class="mt-3 w-full py-2.5 border-2 border-dashed border-purple-300 rounded-xl text-purple-600 font-semibold text-sm hover:bg-purple-50 transition-colors flex items-center justify-center gap-2">
                            <Plus class="w-4 h-4" /> Agregar pregunta
                        </button>
                        <p v-if="preguntasError" class="mt-2 text-sm text-red-600">{{ preguntasError }}</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 font-bold flex-shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-lg shadow-purple-600/20 disabled:opacity-50 flex items-center gap-2">
                        <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                        {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Examen' : 'Registrar Examen') }}
                    </button>
                </div>
            </form>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, ClipboardCheck, Pencil, Loader2, Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    examen: {
        type: Object,
        default: null
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    submitting: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit']);

const examenSchema = toTypedSchema(
    yup.object({
        titulo: yup.string().required('El título es obligatorio').max(200),
        descripcion: yup.string().transform((v) => v || null).nullable(),
        fecha_inicio: yup.string().required('La fecha de inicio es obligatoria'),
        fecha_fin: yup.string().required('La fecha de fin es obligatoria')
            .test('after-inicio', 'La fecha de fin debe ser igual o posterior a la de inicio', function (value) {
                return !this.parent.fecha_inicio || !value || value >= this.parent.fecha_inicio;
            }),
        hora_inicio: yup.string().transform((v) => v || null).nullable(),
        hora_fin: yup.string().transform((v) => v || null).nullable(),
        duracion_minutos: yup.number().typeError('Ingrese un número de minutos válido')
            .required('La duración es obligatoria').min(1, 'Debe ser al menos 1 minuto').max(600),
        intentos_permitidos: yup.number().typeError('Ingrese un número válido')
            .required('Los intentos permitidos son obligatorios').min(1).max(20),
        nota_minima_aprobatoria: yup.string().transform((v) => v || null).nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: examenSchema,
    initialValues: {
        titulo: '',
        descripcion: '',
        fecha_inicio: '',
        fecha_fin: '',
        hora_inicio: '',
        hora_fin: '',
        duracion_minutos: '',
        intentos_permitidos: 1,
        nota_minima_aprobatoria: '',
    }
});

const [titulo, tituloProps] = defineField('titulo');
const [descripcion] = defineField('descripcion');
const [fechaInicio, fechaInicioProps] = defineField('fecha_inicio');
const [fechaFin, fechaFinProps] = defineField('fecha_fin');
const [horaInicio, horaInicioProps] = defineField('hora_inicio');
const [horaFin, horaFinProps] = defineField('hora_fin');
const [duracionMinutos, duracionMinutosProps] = defineField('duracion_minutos');
const [intentosPermitidos, intentosPermitidosProps] = defineField('intentos_permitidos');
const [notaMinima, notaMinimaProps] = defineField('nota_minima_aprobatoria');

const preguntaVacia = () => ({
    enunciado: '',
    alternativas: [{ texto: '', es_correcta: true }, { texto: '', es_correcta: false }],
});

const preguntas = ref([preguntaVacia()]);
const preguntasError = ref('');

const agregarPregunta = () => {
    preguntas.value.push(preguntaVacia());
};

const quitarPregunta = (index) => {
    if (preguntas.value.length > 1) {
        preguntas.value.splice(index, 1);
    }
};

const agregarAlternativa = (pIndex) => {
    preguntas.value[pIndex].alternativas.push({ texto: '', es_correcta: false });
};

const quitarAlternativa = (pIndex, aIndex) => {
    const alternativas = preguntas.value[pIndex].alternativas;
    if (alternativas.length > 2) {
        const eraCorrecta = alternativas[aIndex].es_correcta;
        alternativas.splice(aIndex, 1);
        if (eraCorrecta) {
            alternativas[0].es_correcta = true;
        }
    }
};

const marcarCorrecta = (pIndex, aIndex) => {
    preguntas.value[pIndex].alternativas.forEach((alternativa, i) => {
        alternativa.es_correcta = i === aIndex;
    });
};

watch(() => props.examen, (examen) => {
    if (examen && props.isEditing) {
        setValues({
            titulo: examen.titulo || '',
            descripcion: examen.descripcion || '',
            fecha_inicio: examen.fecha_inicio || '',
            fecha_fin: examen.fecha_fin || '',
            hora_inicio: examen.hora_inicio || '',
            hora_fin: examen.hora_fin || '',
            duracion_minutos: examen.duracion_minutos ?? '',
            intentos_permitidos: examen.intentos_permitidos ?? 1,
            nota_minima_aprobatoria: examen.nota_minima_aprobatoria ?? '',
        });
        preguntas.value = examen.preguntas && examen.preguntas.length
            ? examen.preguntas.map((p) => ({
                enunciado: p.enunciado,
                alternativas: p.alternativas.map((a) => ({ texto: a.texto, es_correcta: a.es_correcta })),
            }))
            : [preguntaVacia()];
    } else {
        resetForm();
        preguntas.value = [preguntaVacia()];
    }
}, { immediate: true });

const onSubmit = validateForm((values) => {
    const preguntasValidas = preguntas.value
        .map((p) => ({
            enunciado: p.enunciado.trim(),
            alternativas: p.alternativas
                .filter((a) => a.texto.trim() !== '')
                .map((a) => ({ texto: a.texto.trim(), es_correcta: a.es_correcta })),
        }))
        .filter((p) => p.enunciado !== '' && p.alternativas.length >= 2);

    if (preguntasValidas.length === 0) {
        preguntasError.value = 'Debe registrar al menos una pregunta con 2 alternativas.';
        return;
    }

    const sinCorrecta = preguntasValidas.find((p) => !p.alternativas.some((a) => a.es_correcta));
    if (sinCorrecta) {
        preguntasError.value = 'Cada pregunta debe tener exactamente una alternativa marcada como correcta.';
        return;
    }
    preguntasError.value = '';

    emit('submit', {
        ...values,
        duracion_minutos: Number(values.duracion_minutos),
        intentos_permitidos: Number(values.intentos_permitidos),
        nota_minima_aprobatoria: values.nota_minima_aprobatoria || null,
        preguntas: preguntasValidas,
    });
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>
