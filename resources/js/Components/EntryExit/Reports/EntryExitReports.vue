<script setup lang="ts">
import { ref, shallowRef, computed, watch } from 'vue';
import { FileText, Calendar, ChevronDown, Download, Loader2, Tag } from 'lucide-vue-next';
import axios from 'axios';

type ReportType = 'daily' | 'weekly' | 'monthly' | 'custom';

interface ReportStats {
    total: number;
    retornados: number;
    pendientes: number;
    comisiones: number;
    permisos: number;
}

interface DateRange {
    start: string;
    end: string;
}

const reportType = shallowRef<ReportType>('weekly');
const selectedDate = shallowRef('');
const selectedWeek = shallowRef('');
const selectedMonth = shallowRef('');
const customStartDate = shallowRef('');
const customEndDate = shallowRef('');
const isGenerating = shallowRef(false);
const reportData = ref<ReportStats | null>(null);

watch(reportType, () => {
    reportData.value = null;
    selectedDate.value = '';
    selectedWeek.value = '';
    selectedMonth.value = '';
    customStartDate.value = '';
    customEndDate.value = '';
});

const currentRange = computed<DateRange | null>(() => {
    if (reportType.value === 'daily' && selectedDate.value) {
        return { start: selectedDate.value, end: selectedDate.value };
    }
    if (reportType.value === 'weekly' && selectedWeek.value) {
        const [start, end] = selectedWeek.value.split('_');
        return { start, end };
    }
    if (reportType.value === 'monthly' && selectedMonth.value) {
        const [yearStr, monthStr] = selectedMonth.value.split('-');
        const year = parseInt(yearStr);
        const month = parseInt(monthStr);
        const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
        const lastDay = new Date(year, month, 0).getDate();
        const endDate = `${year}-${String(month).padStart(2, '0')}-${lastDay}`;
        return { start: startDate, end: endDate };
    }
    if (reportType.value === 'custom' && customStartDate.value && customEndDate.value) {
        return { start: customStartDate.value, end: customEndDate.value };
    }
    return null;
});

const dateRangeLabel = computed(() => {
    if (!currentRange.value) return '';
    return `${currentRange.value.start} al ${currentRange.value.end}`;
});

const availableWeeks = computed(() => {
    const weeks = [];
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    let friday = new Date(today);
    const dayOfWeek = friday.getDay();
    const daysToFriday = dayOfWeek === 6 ? -1 : 5 - dayOfWeek;
    friday.setDate(friday.getDate() + daysToFriday);

    for (let i = 0; i < 12; i++) {
        const endDate = new Date(friday);
        endDate.setDate(endDate.getDate() - i * 7);
        const startDate = new Date(endDate);
        startDate.setDate(startDate.getDate() - 6);

        const fmt = (d: Date): string => {
            const y = d.getFullYear();
            const m = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${y}-${m}-${day}`;
        };
        const disp = (d: Date): string => d.toLocaleDateString('es-PE', { day: '2-digit', month: 'short', year: 'numeric' });

        weeks.push({
            value: `${fmt(startDate)}_${fmt(endDate)}`,
            label: `${disp(startDate)} al ${disp(endDate)}`
        });
    }
    return weeks;
});

watch(currentRange, async (range: DateRange | null) => {
    if (!range) { reportData.value = null; return; }
    try {
        const { data } = await axios.get<ReportStats>('/entry-exits/api/report-stats', {
            params: { start_date: range.start, end_date: range.end }
        });
        reportData.value = data;
    } catch {
        reportData.value = null;
    }
});

const generateReport = () => {
    if (!currentRange.value || isGenerating.value) return;
    const { start, end } = currentRange.value;
    window.open(`/entry-exits/report/pdf?start_date=${start}&end_date=${end}`, '_blank');
};
</script>

<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-8 text-white">
            <div class="flex items-center gap-4 mb-2">
                <div class="bg-white/20 p-3 rounded-2xl backdrop-blur-sm">
                    <FileText class="w-8 h-8 text-white" />
                </div>
                <div>
                    <h2 class="text-2xl font-black tracking-tight">Reportes de Control de Personal</h2>
                    <p class="text-emerald-100 font-medium opacity-90">
                        Resumen y descarga de papeletas de salida por periodo
                    </p>
                </div>
            </div>
        </div>

        <div class="p-6 lg:p-10">
            <!-- Controls -->
            <div class="max-w-4xl mx-auto mb-12">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Report Type -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                            Tipo de Reporte
                        </label>
                        <div class="relative">
                            <Tag class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <select v-model="reportType"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 appearance-none outline-none">
                                <option value="daily">Reporte Diario</option>
                                <option value="weekly">Reporte Semanal</option>
                                <option value="monthly">Reporte Mensual</option>
                                <option value="custom">Reporte Personalizado</option>
                            </select>
                            <ChevronDown class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    <!-- Period -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">
                            Seleccionar Periodo
                        </label>
                        <div class="relative">
                            <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />

                            <input v-if="reportType === 'daily'" type="date" v-model="selectedDate"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 outline-none" />

                            <select v-else-if="reportType === 'weekly'" v-model="selectedWeek"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 appearance-none outline-none">
                                <option value="" disabled>Seleccione una semana...</option>
                                <option v-for="week in availableWeeks" :key="week.value" :value="week.value">
                                    {{ week.label }}
                                </option>
                            </select>

                            <input v-else-if="reportType === 'monthly'" type="month" v-model="selectedMonth"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 outline-none" />

                            <div v-else
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl bg-slate-50 font-semibold text-slate-500 flex items-center">
                                Seleccione fechas abajo
                            </div>

                            <ChevronDown v-if="reportType === 'weekly'"
                                class="absolute right-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                </div>

                <!-- Custom date range -->
                <div v-if="reportType === 'custom'" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Fecha de Inicio</label>
                        <div class="relative">
                            <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <input type="date" v-model="customStartDate"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-3 uppercase tracking-wider">Fecha de Fin</label>
                        <div class="relative">
                            <Calendar class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                            <input type="date" v-model="customEndDate" :min="customStartDate"
                                class="w-full pl-12 pr-4 py-3.5 border border-slate-200 rounded-2xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 transition-all bg-slate-50 font-semibold text-slate-700 outline-none" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats + Download -->
            <div v-if="reportData" class="space-y-10">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white border-2 border-emerald-100 rounded-2xl p-5 shadow-sm text-center">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Total</p>
                        <p class="text-3xl font-black text-emerald-600">{{ reportData.total }}</p>
                    </div>
                    <div class="bg-white border-2 border-green-100 rounded-2xl p-5 shadow-sm text-center">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Retornaron</p>
                        <p class="text-3xl font-black text-green-600">{{ reportData.retornados }}</p>
                    </div>
                    <div class="bg-white border-2 border-yellow-100 rounded-2xl p-5 shadow-sm text-center">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Pendientes</p>
                        <p class="text-3xl font-black text-yellow-600">{{ reportData.pendientes }}</p>
                    </div>
                    <div class="bg-white border-2 border-purple-100 rounded-2xl p-5 shadow-sm text-center">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Comisiones</p>
                        <p class="text-3xl font-black text-purple-600">{{ reportData.comisiones }}</p>
                    </div>
                    <div class="bg-white border-2 border-sky-100 rounded-2xl p-5 shadow-sm text-center">
                        <p class="text-slate-500 text-[10px] font-bold uppercase tracking-widest mb-1">Permisos</p>
                        <p class="text-3xl font-black text-sky-600">{{ reportData.permisos }}</p>
                    </div>
                </div>

                <div class="flex flex-col items-center gap-3">
                    <button @click="generateReport" :disabled="isGenerating"
                        class="inline-flex items-center justify-center px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-black rounded-2xl shadow-xl shadow-emerald-200 hover:shadow-emerald-300 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5 active:translate-y-0">
                        <Loader2 v-if="isGenerating" class="animate-spin w-5 h-5 mr-3" />
                        <Download v-else class="w-5 h-5 mr-3" />
                        {{ isGenerating ? 'Generando PDF...' : 'Descargar Reporte PDF' }}
                    </button>
                    <p class="text-xs text-slate-500 font-medium opacity-80">
                        Periodo: {{ dateRangeLabel }}
                    </p>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="text-center py-20 px-6">
                <div class="bg-slate-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                    <FileText class="w-10 h-10 text-slate-400" />
                </div>
                <h3 class="text-xl font-black text-slate-800 mb-2 tracking-tight">
                    Generación de Reportes
                </h3>
                <p class="text-slate-500 max-w-sm mx-auto font-medium">
                    Seleccione un tipo de reporte y un periodo para visualizar el resumen.
                </p>
            </div>
        </div>
    </div>
</template>
