<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';
import { X, Calendar, Clock, MapPin, Video, Users, Mic, Clock3, Copy, ExternalLink } from 'lucide-vue-next';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    }
});

const emit = defineEmits(['close', 'copy-link']);

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
const modalidadIcon = (modalidad) => modalidad === 'virtual' ? Video : MapPin;

const estadoClass = (estado) => ({
    'bg-slate-100 text-slate-600 border-slate-200': estado === 'borrador',
    'bg-green-100 text-green-700 border-green-200': estado === 'publicado',
    'bg-red-100 text-red-700 border-red-200': estado === 'cerrado',
});

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
};

const sanitizedDescripcion = computed(() => DOMPurify.sanitize(props.evento.descripcion || '', {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'span'],
    ALLOWED_ATTR: ['href', 'target', 'rel'],
}));

const abrirEnlace = () => {
    window.open(props.evento.enlace_publico, '_blank', 'noopener,noreferrer');
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
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                    appear
                >
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-5 py-4 flex items-center justify-between flex-shrink-0">
                            <h2 class="text-base font-bold text-white">Detalles del Evento</h2>
                            <button @click="emit('close')"
                                class="cursor-pointer bg-white/10 hover:bg-white/25 text-white rounded-lg p-1.5 transition-colors duration-150">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Título y badges -->
                        <div class="px-5 pt-4 pb-3 border-b border-slate-100 flex-shrink-0">
                            <p class="text-sm font-bold text-slate-900">{{ evento.titulo }}</p>
                            <div class="flex items-center flex-wrap gap-2 mt-2">
                                <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded-md bg-slate-100 text-slate-600">
                                    {{ tipoLabel(evento.tipo) }}
                                </span>
                                <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full border"
                                    :class="estadoClass(evento.estado)">
                                    {{ estadoLabel(evento.estado) }}
                                </span>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="px-5 py-4 overflow-y-auto space-y-4 text-sm">
                            <div v-if="evento.descripcion" class="rich-description text-slate-600" v-html="sanitizedDescripcion"></div>

                            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                <div>
                                    <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                        <Calendar class="w-3.5 h-3.5" /> Fecha
                                    </p>
                                    <p class="font-semibold text-slate-800">
                                        {{ formatFecha(evento.fecha_inicio) }}<span v-if="evento.fecha_fin !== evento.fecha_inicio"> al {{ formatFecha(evento.fecha_fin) }}</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                        <Clock class="w-3.5 h-3.5" /> Hora
                                    </p>
                                    <p class="font-semibold text-slate-800">
                                        {{ evento.hora_inicio || '--:--' }}<span v-if="evento.hora_fin"> - {{ evento.hora_fin }}</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                        <component :is="modalidadIcon(evento.modalidad)" class="w-3.5 h-3.5" /> Modalidad
                                    </p>
                                    <p class="font-semibold text-slate-800">{{ modalidadLabel(evento.modalidad) }}</p>
                                    <p v-if="evento.lugar || evento.enlace_virtual" class="text-xs text-slate-500 mt-0.5">
                                        {{ evento.lugar || evento.enlace_virtual }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                        <Users class="w-3.5 h-3.5" /> Inscritos
                                    </p>
                                    <p class="font-semibold text-slate-800">
                                        {{ evento.inscritos_count }}<span v-if="evento.cupo_maximo" class="text-slate-400"> / {{ evento.cupo_maximo }}</span>
                                    </p>
                                </div>
                                <div v-if="evento.horas_educativas">
                                    <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                        <Clock3 class="w-3.5 h-3.5" /> Horas educativas
                                    </p>
                                    <p class="font-semibold text-slate-800">{{ evento.horas_educativas }}</p>
                                </div>
                            </div>

                            <div v-if="evento.expositores?.length">
                                <p class="text-xs text-slate-400 font-medium flex items-center gap-1">
                                    <Mic class="w-3.5 h-3.5" /> Expositor(es)
                                </p>
                                <p class="font-semibold text-slate-800">
                                    <span v-for="(exp, i) in evento.expositores" :key="exp.id ?? i">
                                        {{ exp.nombre }}<span v-if="exp.entidad" class="text-slate-500 font-normal"> ({{ exp.entidad }})</span><span v-if="i < evento.expositores.length - 1">, </span>
                                    </span>
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 font-medium mb-1.5">Enlace de inscripción</p>
                                <div class="flex items-center gap-2">
                                    <input type="text" readonly :value="evento.enlace_publico"
                                        class="flex-1 min-w-0 px-3 py-2 text-xs font-mono text-slate-600 bg-slate-50 border border-slate-200 rounded-lg truncate outline-none" />
                                    <button @click="emit('copy-link', evento)" title="Copiar enlace"
                                        class="cursor-pointer flex-shrink-0 p-2 rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 transition-colors duration-150">
                                        <Copy class="w-4 h-4" />
                                    </button>
                                    <button @click="abrirEnlace" title="Abrir en nueva pestaña"
                                        class="cursor-pointer flex-shrink-0 p-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors duration-150">
                                        <ExternalLink class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>

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

<style scoped>
.rich-description :deep(p) {
    margin: 0 0 0.5em 0;
}

.rich-description :deep(p:last-child) {
    margin-bottom: 0;
}

.rich-description :deep(ul) {
    list-style: disc;
    padding-left: 1.25em;
    margin: 0 0 0.5em 0;
}

.rich-description :deep(ol) {
    list-style: decimal;
    padding-left: 1.25em;
    margin: 0 0 0.5em 0;
}

.rich-description :deep(a) {
    color: #d97706;
    text-decoration: underline;
}
</style>
