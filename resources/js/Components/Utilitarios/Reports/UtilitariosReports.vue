<script setup>
import { ref, computed, watch } from 'vue';
import { FileText, Calendar, ChevronDown, Download, Tag, Users, ClipboardCheck, GraduationCap } from 'lucide-vue-next';

const props = defineProps({
    eventos: {
        type: Array,
        default: () => [],
    },
});

const REPORT_TYPES = [
    { value: 'inscritos', label: 'Inscritos por Evento', icon: Users, description: 'Listado de participantes inscritos al evento.' },
    { value: 'asistencia', label: 'Asistencia por Evento', icon: ClipboardCheck, description: 'Marcas de asistencia por día del evento.' },
    { value: 'resultados', label: 'Resultados de Examen', icon: GraduationCap, description: 'Notas y aprobación de un examen del evento.' },
];

const reportType = ref('inscritos');
const selectedEventoId = ref('');
const selectedExamenId = ref('');

watch(reportType, () => {
    selectedEventoId.value = '';
    selectedExamenId.value = '';
});

watch(selectedEventoId, () => {
    selectedExamenId.value = '';
});

const eventosOrdenados = computed(() => {
    return [...props.eventos].sort((a, b) => (b.fecha_inicio || '').localeCompare(a.fecha_inicio || ''));
});

const selectedEvento = computed(() => {
    return props.eventos.find((e) => e.id === selectedEventoId.value) || null;
});

const examenesDelEvento = computed(() => selectedEvento.value?.examenes || []);

const canGenerate = computed(() => {
    if (!selectedEventoId.value) return false;
    if (reportType.value === 'resultados') return !!selectedExamenId.value;
    return true;
});

const generateReport = () => {
    if (!canGenerate.value) return;

    if (reportType.value === 'inscritos') {
        window.open(`/utilitarios/eventos/${selectedEventoId.value}/reportes/inscritos/pdf`, '_blank');
    } else if (reportType.value === 'asistencia') {
        window.open(`/utilitarios/eventos/${selectedEventoId.value}/reportes/asistencia/pdf`, '_blank');
    } else if (reportType.value === 'resultados') {
        window.open(`/utilitarios/eventos/${selectedEventoId.value}/examenes/${selectedExamenId.value}/reportes/resultados/pdf`, '_blank');
    }
};
</script>

<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-8 text-white">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <FileText class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight">Reportes de Utilitarios</h2>
                    <p class="text-amber-100 font-medium opacity-90">
                        Genere reportes PDF de inscritos, asistencia y resultados de exámenes por evento
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 lg:p-10">
            <div class="max-w-4xl mx-auto mb-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Report Type -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                            Tipo de Reporte
                        </label>
                        <div class="relative">
                            <Tag class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <select v-model="reportType"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all bg-slate-50 font-semibold text-slate-700 appearance-none outline-none">
                                <option v-for="type in REPORT_TYPES" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Evento -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                            Evento
                        </label>
                        <div class="relative">
                            <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <select v-model="selectedEventoId"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all bg-slate-50 font-semibold text-slate-700 appearance-none outline-none">
                                <option value="" disabled>Seleccione un evento...</option>
                                <option v-for="evento in eventosOrdenados" :key="evento.id" :value="evento.id">
                                    {{ evento.titulo }}
                                </option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Examen selector -->
                <div v-if="reportType === 'resultados' && selectedEventoId" class="mt-6">
                    <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                        Examen
                    </label>
                    <div class="relative">
                        <GraduationCap class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                        <select v-model="selectedExamenId"
                            class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 transition-all bg-slate-50 font-semibold text-slate-700 appearance-none outline-none">
                            <option value="" disabled>Seleccione un examen...</option>
                            <option v-for="examen in examenesDelEvento" :key="examen.id" :value="examen.id">
                                {{ examen.titulo }}
                            </option>
                        </select>
                        <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                    </div>
                    <p v-if="!examenesDelEvento.length" class="mt-2 text-xs font-semibold text-amber-600">
                        Este evento no tiene exámenes registrados.
                    </p>
                </div>
            </div>

            <!-- Generate -->
            <div v-if="canGenerate" class="flex flex-col items-center gap-3">
                <button @click="generateReport"
                    class="cursor-pointer inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white font-black rounded-2xl shadow-xl shadow-amber-200 hover:shadow-amber-300 transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0">
                    <Download class="w-5 h-5 mr-3" />
                    Descargar Reporte PDF
                </button>
                <p class="text-xs text-slate-500 font-medium opacity-80">
                    {{ selectedEvento?.titulo }}
                </p>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-16 px-6">
                <div class="bg-slate-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                    <FileText class="w-10 h-10 text-slate-400" />
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 tracking-tight">
                    Generación de Reportes
                </h3>
                <p class="text-slate-500 max-w-sm mx-auto font-medium">
                    Seleccione un tipo de reporte{{ reportType === 'resultados' ? ', un evento y un examen' : ' y un evento' }} para generar el PDF.
                </p>
            </div>
        </div>
    </div>
</template>
