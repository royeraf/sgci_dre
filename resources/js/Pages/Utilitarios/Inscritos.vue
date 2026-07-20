<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <Link href="/utilitarios" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-amber-600 mb-2">
                        <ArrowLeft class="w-4 h-4" /> Volver a Utilitarios
                    </Link>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                        {{ evento.titulo }}
                    </h1>
                    <p class="mt-2 text-slate-600">
                        {{ inscripcionesList.length }} inscrito(s)<span v-if="evento.cupo_maximo"> de {{ evento.cupo_maximo }} cupos</span>
                    </p>
                </div>

                <div class="flex items-center gap-3 bg-white border border-slate-200 rounded-2xl px-4 py-3 shadow-sm">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700">Enlace de asistencia</span>
                        <span class="text-xs text-slate-500">
                            {{ asistenciaHabilitada ? 'Activo: los inscritos pueden marcar su asistencia' : 'Inactivo: el enlace público no acepta registros' }}
                        </span>
                    </div>
                    <button type="button" @click="toggleEnlaceAsistencia" :disabled="cambiandoEnlace"
                        class="cursor-pointer relative inline-flex h-7 w-12 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none disabled:opacity-50"
                        :class="asistenciaHabilitada ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                            :class="asistenciaHabilitada ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>
            </div>

            <BaseTableCard
                title="Inscritos y Asistencia"
                description="Marque la asistencia de cada participante según el día correspondiente"
                search-placeholder="Buscar por nombre o documento..."
                v-model:searchValue="search"
            >
                <template #subtabs>
                    <div class="flex items-center gap-1 bg-amber-50 rounded-full p-1.5 w-fit shrink-0 flex-wrap">
                        <button v-for="(dia, index) in evento.dias" :key="dia" @click="diaActivo = dia"
                            :class="[
                                diaActivo === dia ? 'bg-white text-amber-900 shadow-md' : 'text-amber-600 hover:text-amber-800',
                                'cursor-pointer px-4 py-2 rounded-full text-xs font-semibold transition-all duration-200 outline-none whitespace-nowrap'
                            ]">
                            Día {{ index + 1 }} · {{ formatFecha(dia) }}
                        </button>
                    </div>
                </template>

                <InscritosTable :inscripciones="filteredInscripciones" :dia-activo="diaActivo" :marcando="marcando"
                    v-model:currentPage="currentPage" v-model:perPage="perPage"
                    @toggle-asistencia="toggleAsistencia" />
            </BaseTableCard>
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
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import InscritosTable from '@/Components/Utilitarios/Asistencia/InscritosTable.vue';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    inscripciones: {
        type: Array,
        default: () => []
    }
});

const inscripcionesList = ref([...props.inscripciones]);

const search = ref('');
const currentPage = ref(1);
const perPage = ref(10);
const marcando = ref(null);

const asistenciaHabilitada = ref(props.evento.asistencia_habilitada);
const cambiandoEnlace = ref(false);

const toggleEnlaceAsistencia = async () => {
    cambiandoEnlace.value = true;
    try {
        const { data } = await axios.patch(`/utilitarios/eventos/${props.evento.id}/asistencia-habilitada`, {
            habilitada: !asistenciaHabilitada.value,
        });
        asistenciaHabilitada.value = data.asistencia_habilitada;
    } catch (error) {
        window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo actualizar el enlace de asistencia.', 'error');
    } finally {
        cambiandoEnlace.value = false;
    }
};

const diaActivo = ref(
    props.evento.dias.includes(new Date().toISOString().slice(0, 10))
        ? new Date().toISOString().slice(0, 10)
        : (props.evento.dias[0] || '')
);

const normalizeText = (text) => {
    if (!text) return '';
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
};

const filteredInscripciones = computed(() => {
    if (!search.value) return inscripcionesList.value;
    const q = normalizeText(search.value);
    return inscripcionesList.value.filter((i) =>
        normalizeText(`${i.nombres} ${i.apellidos}`).includes(q) || i.numero_documento.includes(search.value)
    );
});

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}`;
};

const toggleAsistencia = async (inscripcion) => {
    marcando.value = inscripcion.id;
    const yaAsistio = inscripcion.asistencias.some((a) => a.fecha === diaActivo.value);

    try {
        if (yaAsistio) {
            await axios.delete(`/utilitarios/eventos/${props.evento.id}/inscritos/${inscripcion.id}/asistencia`, {
                data: { fecha: diaActivo.value }
            });
            inscripcion.asistencias = inscripcion.asistencias.filter((a) => a.fecha !== diaActivo.value);
        } else {
            const { data } = await axios.post(`/utilitarios/eventos/${props.evento.id}/inscritos/${inscripcion.id}/asistencia`, {
                fecha: diaActivo.value
            });
            inscripcion.asistencias.push({ fecha: data.fecha, marcado_en: data.marcado_en });
        }
    } catch (error) {
        window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo actualizar la asistencia.', 'error');
    } finally {
        marcando.value = null;
    }
};

const canalInscripciones = `evento.${props.evento.id}.inscripciones`;
let echoChannel = null;
let pollingInterval = null;

const agregarInscripcionNueva = (nuevaInscripcion) => {
    if (inscripcionesList.value.some((i) => i.id === nuevaInscripcion.id)) return;

    inscripcionesList.value.push(nuevaInscripcion);
    window.Swal?.fire?.({
        icon: 'info',
        title: 'Nueva inscripción',
        text: `${nuevaInscripcion.nombres} ${nuevaInscripcion.apellidos} se acaba de inscribir.`,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
    });
};

const agregarAsistenciaAutomarcada = ({ inscripcion_id, asistencia }) => {
    const inscripcion = inscripcionesList.value.find((i) => i.id === inscripcion_id);
    if (!inscripcion || inscripcion.asistencias.some((a) => a.fecha === asistencia.fecha)) return;

    inscripcion.asistencias.push(asistencia);
};

const checkNuevasInscripciones = async () => {
    try {
        const { data } = await axios.get(`/utilitarios/eventos/${props.evento.id}/inscritos/data`);
        (data.inscripciones || []).forEach((inscripcion) => {
            const existente = inscripcionesList.value.find((i) => i.id === inscripcion.id);
            if (!existente) {
                agregarInscripcionNueva(inscripcion);
                return;
            }

            // Fallback sin Echo: sincroniza también las asistencias (incluye auto-marcadas
            // por el propio inscrito desde el link público) de inscripciones ya listadas.
            inscripcion.asistencias.forEach((asistencia) => agregarAsistenciaAutomarcada({
                inscripcion_id: inscripcion.id,
                asistencia,
            }));
        });
    } catch (error) {
        // Silencioso: solo es el fallback de respaldo, no interrumpe la pantalla
    }
};

onMounted(() => {
    if (typeof window.Echo !== 'undefined') {
        try {
            echoChannel = window.Echo.private(canalInscripciones)
                .listen('.new-inscripcion', (nuevaInscripcion) => {
                    agregarInscripcionNueva(nuevaInscripcion);
                })
                .listen('.asistencia-automarcada', (payload) => {
                    agregarAsistenciaAutomarcada(payload);
                });
            return;
        } catch (error) {
            console.warn('Echo no disponible, usando polling de respaldo');
        }
    }

    pollingInterval = setInterval(checkNuevasInscripciones, 15000);
});

onUnmounted(() => {
    if (echoChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave(canalInscripciones);
    }
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>
