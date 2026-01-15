<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Reports Header -->
        <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-8 text-white">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <FileText class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight">Reportes Semanales</h2>
                    <p class="text-indigo-100 font-medium opacity-90">
                        Resumen ejecutivo y descarga de reportes oficiales
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 lg:p-10">
            <!-- Week Selector -->
            <div class="max-w-3xl mx-auto mb-12">
                <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                    Seleccionar Periodo Semanal
                </label>
                <div class="flex flex-col sm:flex-row gap-4 items-stretch">
                    <div class="flex-1 relative">
                        <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                        <select
                            v-model="selectedWeek"
                            class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-indigo-500/10 focus:border-indigo-500 transition-all duration-300 bg-slate-50 font-semibold text-slate-700 appearance-none"
                        >
                            <option value="" disabled>Seleccione una semana...</option>
                            <option v-for="week in availableWeeks" :key="week.value" :value="week.value">
                                {{ week.label }}
                            </option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            <ChevronDown class="w-5 h-5" />
                        </div>
                    </div>
                    <button
                        @click="generateWeeklyReport"
                        :disabled="!selectedWeek || isGeneratingReport"
                        class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-black rounded-2xl shadow-xl shadow-indigo-200 hover:shadow-indigo-300 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5 active:translate-y-0"
                    >
                        <Loader2 v-if="isGeneratingReport" class="animate-spin w-5 h-5 mr-3" />
                        <Download v-else class="w-5 h-5 mr-3" />
                        {{ isGeneratingReport ? 'Generando PDF...' : 'Generar Reporte' }}
                    </button>
                </div>
            </div>

            <!-- Week Summary -->
            <div v-if="weeklyReportData" class="space-y-10">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <div class="bg-white border-2 border-blue-100 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Total</p>
                        <p class="text-3xl font-black text-blue-600">{{ weeklyReportData.total }}</p>
                    </div>
                    <div class="bg-white border-2 border-red-100 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Emergencias</p>
                        <p class="text-3xl font-black text-red-600">{{ weeklyReportData.emergencias }}</p>
                    </div>
                    <div class="bg-white border-2 border-yellow-100 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Incidentes</p>
                        <p class="text-3xl font-black text-yellow-600">{{ weeklyReportData.incidentes }}</p>
                    </div>
                    <div class="bg-white border-2 border-green-100 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Rutina</p>
                        <p class="text-3xl font-black text-green-600">{{ weeklyReportData.rutina }}</p>
                    </div>
                    <div class="bg-white border-2 border-sky-100 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Avisos</p>
                        <p class="text-3xl font-black text-sky-600">{{ weeklyReportData.avisos }}</p>
                    </div>
                    <div class="bg-white border-2 border-slate-200 rounded-2xl p-5 shadow-sm">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Otros</p>
                        <p class="text-3xl font-black text-slate-600">{{ weeklyReportData.otros }}</p>
                    </div>
                </div>

                <!-- Preview Table -->
                <div class="bg-slate-50 rounded-3xl border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-white/50 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                        <h3 class="font-black text-slate-800 tracking-tight">Vista Previa de la Semana</h3>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-slate-500">
                                {{ weeklyReportData.startDate }} al {{ weeklyReportData.endDate }}
                            </span>
                            <span class="px-4 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-black rounded-full uppercase tracking-widest border border-indigo-100">
                                {{ weeklyReportData.occurrences.length }} Registros
                            </span>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Fecha / Hora
                                    </th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Turno
                                    </th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Tipo
                                    </th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Descripcion
                                    </th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Responsable
                                    </th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200 bg-white">
                                <tr
                                    v-for="occ in weeklyReportData.occurrences"
                                    :key="occ.id"
                                    class="hover:bg-slate-50 transition-colors"
                                >
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="text-sm font-bold text-slate-900">{{ occ.fecha }}</div>
                                        <div class="text-xs text-slate-400">{{ occ.hora }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 whitespace-nowrap">
                                        {{ occ.turno }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider"
                                            :class="getTypeClass(occ.tipo)"
                                        >
                                            {{ occ.tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-slate-600 max-w-xs">
                                        <div class="line-clamp-2">{{ occ.descripcion }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-semibold text-slate-900 whitespace-nowrap">
                                        {{ occ.vigilante }}
                                    </td>
                                    <td class="px-4 py-3 text-center whitespace-nowrap">
                                        <span
                                            class="px-2.5 py-1 text-[10px] font-bold rounded-lg"
                                            :class="getStatusClass(occ.estado)"
                                        >
                                            {{ occ.estado }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="weeklyReportData.occurrences.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center">
                                            <div class="bg-slate-100 rounded-full p-3 mb-3">
                                                <ClipboardList class="h-8 w-8 text-slate-400" />
                                            </div>
                                            <p class="text-slate-500 font-medium">
                                                No se encontraron registros para este periodo.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20 px-6">
                <div class="bg-slate-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                    <FileText class="w-10 h-10 text-slate-400" />
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 tracking-tight">
                    Generacion de Reportes
                </h3>
                <p class="text-slate-500 max-w-sm mx-auto font-medium">
                    Seleccione un periodo semanal para visualizar el resumen de actividades y exportar el documento PDF oficial.
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import {
    FileText,
    Calendar,
    ChevronDown,
    Download,
    Loader2,
    ClipboardList
} from 'lucide-vue-next';

const props = defineProps({
    occurrences: {
        type: Array,
        required: true
    }
});

const selectedWeek = ref('');
const isGeneratingReport = ref(false);
const weeklyReportData = ref(null);

// Available weeks for reporting (last 12 weeks, Saturday to Friday)
const availableWeeks = computed(() => {
    const weeks = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    // Find the Friday of the current week (or today if it's Friday)
    // dayOfWeek: 0=Sunday, 1=Monday, 2=Tuesday, 3=Wednesday, 4=Thursday, 5=Friday, 6=Saturday
    let friday = new Date(today);
    const dayOfWeek = friday.getDay();

    // Calculate days until Friday (5)
    // If Saturday (6), Friday was yesterday (-1)
    // If Sunday (0), Friday was 2 days ago (-2)
    // If Friday (5), it's today (0)
    // Otherwise, add days to reach Friday
    let daysToFriday;
    if (dayOfWeek === 6) {
        daysToFriday = -1; // Saturday -> go back 1 day
    } else if (dayOfWeek <= 5) {
        daysToFriday = 5 - dayOfWeek; // Days until this Friday
    }
    friday.setDate(friday.getDate() + daysToFriday);

    for (let i = 0; i < 12; i++) {
        const endDate = new Date(friday);
        endDate.setDate(endDate.getDate() - (i * 7));

        const startDate = new Date(endDate);
        startDate.setDate(startDate.getDate() - 6); // Saturday (6 days before Friday)

        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };
        const formatDisplay = (d) => d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });

        weeks.push({
            value: `${formatDate(startDate)}_${formatDate(endDate)}`,
            label: `${formatDisplay(startDate)} al ${formatDisplay(endDate)}${i === 0 ? ' (Semana Actual)' : ''}`
        });
    }
    return weeks;
});

// Calculate weekly report data when week changes
watch(selectedWeek, (newVal) => {
    if (newVal) {
        const [startDate, endDate] = newVal.split('_');

        const weekOccurrences = props.occurrences.filter(occ => {
            return occ.fecha >= startDate && occ.fecha <= endDate;
        });

        weeklyReportData.value = {
            startDate,
            endDate,
            total: weekOccurrences.length,
            emergencias: weekOccurrences.filter(o => o.tipo === 'Emergencia' || o.tipo === 'Robo').length,
            incidentes: weekOccurrences.filter(o => o.tipo === 'Incidente').length,
            rutina: weekOccurrences.filter(o => o.tipo === 'Rutina').length,
            avisos: weekOccurrences.filter(o => o.tipo === 'Aviso').length,
            otros: weekOccurrences.filter(o => o.tipo === 'Otros').length,
            occurrences: weekOccurrences.sort((a, b) => new Date(a.fecha + ' ' + a.hora) - new Date(b.fecha + ' ' + b.hora))
        };
    } else {
        weeklyReportData.value = null;
    }
});

// Generate PDF Report via backend
const generateWeeklyReport = async () => {
    if (!weeklyReportData.value) return;

    isGeneratingReport.value = true;

    try {
        const { startDate, endDate } = weeklyReportData.value;
        const url = `/occurrences/weekly-report?start_date=${startDate}&end_date=${endDate}`;

        // Create a temporary link to trigger download
        const link = document.createElement('a');
        link.href = url;
        link.download = `SGCI_Reporte_Ocurrencias_${startDate}_${endDate}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error generating report:', error);
    } finally {
        isGeneratingReport.value = false;
    }
};

const getTypeClass = (tipo) => {
    const classes = {
        'Emergencia': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
        'Robo': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
        'Incidente': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'Rutina': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'Aviso': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
        'Otros': 'bg-gradient-to-r from-gray-500 to-gray-600 text-white'
    };
    return classes[tipo] || classes['Otros'];
};

const getStatusClass = (estado) => {
    const classes = {
        'Pendiente': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'Aprobado': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'Cerrado': 'bg-gradient-to-r from-gray-500 to-gray-600 text-white'
    };
    return classes[estado] || classes['Pendiente'];
};
</script>
