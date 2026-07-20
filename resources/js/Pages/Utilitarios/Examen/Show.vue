<template>
    <div class="min-h-screen bg-gradient-to-br from-[var(--accent-05)] via-white to-[var(--accent-05)] py-6 sm:py-8 md:py-12 px-3 sm:px-4 md:px-6 lg:px-8" :style="accentStyle">
        <div class="max-w-xl mx-auto">
            <!-- Header Card -->
            <div class="relative overflow-hidden rounded-3xl sm:rounded-[2rem] text-white mb-6 sm:mb-8 shadow-2xl shadow-black/20">
                <img :src="evento.imagen_fondo_url || '/images/fondo_eventos.jpg'" alt=""
                    class="absolute inset-0 w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950 via-stone-950/85 to-stone-950/40"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-stone-950/70 via-transparent to-stone-950/20"></div>

                <div class="relative z-10 px-6 py-8 sm:px-10 sm:py-10">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="/images/logo.png" alt="DRE Huánuco" class="h-7 sm:h-8 w-auto opacity-90 brightness-0 invert" />
                        <div class="w-px h-5 bg-white/20"></div>
                        <span class="text-[11px] font-bold uppercase tracking-[0.15em] text-[var(--accent-light)]">
                            Examen de Evaluación
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight leading-tight max-w-xl">
                        {{ examen.titulo }}
                    </h1>
                    <p v-if="examen.descripcion" class="text-white/60 text-sm mt-2 max-w-lg">{{ examen.descripcion }}</p>

                    <div class="flex flex-col gap-2 text-xs sm:text-sm text-white/70 mt-6">
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <Calendar class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Fecha:</span>
                            <span>{{ formatFecha(examen.fecha_inicio) }}<template v-if="examen.fecha_fin !== examen.fecha_inicio"> al {{ formatFecha(examen.fecha_fin) }}</template></span>
                        </span>
                        <span v-if="examen.hora_inicio" class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <Clock class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Hora:</span>
                            <span>{{ examen.hora_inicio }}<template v-if="examen.hora_fin"> - {{ examen.hora_fin }}</template></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <Timer class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Duración:</span>
                            <span>{{ examen.duracion_minutos }} minutos</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- No disponible -->
            <div v-if="!disponible" class="bg-white rounded-3xl shadow-xl p-8 text-center border border-slate-100">
                <AlertCircle class="w-14 h-14 text-amber-500 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-slate-800 mb-2">Examen no disponible</h2>
                <p class="text-slate-500">{{ motivo }}</p>
            </div>

            <!-- Formulario de identificación -->
            <div v-else class="bg-white rounded-3xl shadow-xl p-5 sm:p-8 border border-slate-100">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 mb-2 flex items-center gap-2">
                    <ClipboardCheck class="w-5 h-5 text-[var(--accent)]" />
                    Rendir Examen
                </h2>
                <p class="text-sm text-slate-500 mb-6">
                    Ingresa tu número de DNI para comenzar. Una vez que inicies, el temporizador de
                    {{ examen.duracion_minutos }} minutos no se detiene.
                </p>

                <form @submit.prevent="onSubmit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">DNI <span class="text-red-500">*</span></label>
                        <input v-model="dni" type="text" inputmode="numeric" :maxlength="8" placeholder="########"
                            @input="handleDniInput" @keypress.enter.prevent="onSubmit"
                            class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none font-mono tracking-wider text-lg"
                            :class="error ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
                    </div>

                    <button type="submit" :disabled="submitting || dni.length !== 8"
                        class="w-full py-3.5 bg-gradient-to-r from-[var(--accent)] to-[var(--accent-dark)] text-white font-bold rounded-xl hover:brightness-110 transition-all shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
                        <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                        {{ submitting ? 'Iniciando...' : 'Comenzar Examen' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { Calendar, Clock, Timer, ClipboardCheck, Loader2, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    examen: {
        type: Object,
        required: true
    },
    disponible: {
        type: Boolean,
        default: false
    },
    motivo: {
        type: String,
        default: null
    }
});

const dni = ref('');
const submitting = ref(false);
const error = ref('');

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
        '--accent-light': `rgb(${clamp(r * 1.25)}, ${clamp(g * 1.25)}, ${clamp(b * 1.25)})`,
        '--accent-05': `rgba(${r}, ${g}, ${b}, 0.05)`,
        '--accent-20': `rgba(${r}, ${g}, ${b}, 0.2)`,
    };
});

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
};

const handleDniInput = (event) => {
    dni.value = event.target.value.replace(/\D/g, '');
    error.value = '';
};

const onSubmit = async () => {
    if (dni.value.length !== 8 || submitting.value) return;

    submitting.value = true;
    error.value = '';

    try {
        const { data } = await axios.post(`/utilitarios/examen/${props.evento.slug}/${props.examen.slug}/iniciar`, { dni: dni.value });
        router.visit(`/utilitarios/examen/${props.evento.slug}/${props.examen.slug}/${data.intento_id}`);
    } catch (err) {
        error.value = err.response?.data?.message || 'No se pudo iniciar el examen.';
    } finally {
        submitting.value = false;
    }
};
</script>
