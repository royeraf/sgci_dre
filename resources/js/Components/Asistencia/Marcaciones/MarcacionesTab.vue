<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import {
    ChevronLeft, ChevronRight, Loader2, CalendarDays,
    Plus, Pencil, Trash2, Star,
} from 'lucide-vue-next';
import MarcaFormModal from './Modals/MarcaFormModal.vue';

const props = defineProps<{
    employee: any;
    isAdmin: boolean;
    feriadosByDate: Record<string, any>;
}>();

// ── Fecha ──────────────────────────────────────────────────────────────────
const today        = new Date();
const currentYear  = ref(today.getFullYear());
const currentMonth = ref(today.getMonth() + 1);

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
const diaLabel   = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

const monthLabel = computed(() => `${monthNames[currentMonth.value - 1]} ${currentYear.value}`);
const prevMonth  = () => { if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value--; } else currentMonth.value--; };
const nextMonth  = () => { if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++; } else currentMonth.value++; };

// ── Marcas ─────────────────────────────────────────────────────────────────
const marcas  = ref<any[]>([]);
const loading = ref(false);

const fetchMarcas = async () => {
    loading.value = true;
    try {
        const params: Record<string, any> = { year: currentYear.value, month: currentMonth.value };
        if (props.isAdmin && props.employee?.id) params.employee_id = props.employee.id;
        const res = await axios.get('/asistencia/api/marcas', { params });
        marcas.value = res.data;
    } finally {
        loading.value = false;
    }
};

const marcasByDate = computed(() => {
    const map: Record<string, any> = {};
    for (const m of marcas.value) map[m.fecha] = m;
    return map;
});

// ── Definición de los 4 pares ──────────────────────────────────────────────
const PAIRS = [
    { e: 'entrada',          s: 'salida_mediodia',  colorE: 'bg-emerald-100 text-emerald-700 border-emerald-200', colorS: 'bg-orange-100 text-orange-700 border-orange-200',   dotE: 'bg-emerald-500', dotS: 'bg-orange-400' },
    { e: 'retorno_mediodia', s: 'salida',            colorE: 'bg-blue-100 text-blue-700 border-blue-200',         colorS: 'bg-rose-100 text-rose-700 border-rose-200',         dotE: 'bg-blue-500',    dotS: 'bg-rose-500'   },
    { e: 'entrada_3',        s: 'salida_3',          colorE: 'bg-violet-100 text-violet-700 border-violet-200',   colorS: 'bg-pink-100 text-pink-700 border-pink-200',         dotE: 'bg-violet-500',  dotS: 'bg-pink-500'   },
    { e: 'entrada_4',        s: 'salida_4',          colorE: 'bg-teal-100 text-teal-700 border-teal-200',         colorS: 'bg-amber-100 text-amber-700 border-amber-200',      dotE: 'bg-teal-500',    dotS: 'bg-amber-500'  },
] as const;

// Pares visibles de una marca (los que tienen al menos una hora)
const getActivePairs = (marca: any) =>
    marca ? PAIRS.filter(p => marca[p.e] || marca[p.s]) : [];

const fmt = (t: string | null | undefined) => t ? t.substring(0, 5) : null;

// ── Calendario ─────────────────────────────────────────────────────────────
const diasDelMes = computed(() => {
    const dias = [];
    const total = new Date(currentYear.value, currentMonth.value, 0).getDate();
    for (let d = 1; d <= total; d++) {
        const fecha = `${currentYear.value}-${String(currentMonth.value).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const dow   = new Date(fecha).getDay();
        dias.push({
            fecha, dia: d, dow,
            esFinDeSemana: dow === 0 || dow === 6,
            esHoy:    fecha === today.toISOString().split('T')[0],
            esFeriado: !!props.feriadosByDate[fecha],
            feriado:   props.feriadosByDate[fecha] ?? null,
        });
    }
    return dias;
});

const stats = computed(() => ({
    laborables: diasDelMes.value.filter(d => !d.esFinDeSemana && !d.esFeriado).length,
    conEntrada: marcas.value.filter(m => m.entrada).length,
    completas:  marcas.value.filter(m => m.entrada && m.salida).length,
}));

// ── Badges feriados ────────────────────────────────────────────────────────
const tipoLabel: Record<string, string> = { FERIADO: 'Feriado Nacional', NO_LABORABLE: 'No Laborable' };
const tipoBadge: Record<string, string> = {
    FERIADO:      'bg-amber-100 text-amber-800 border-amber-300',
    NO_LABORABLE: 'bg-rose-100 text-rose-800 border-rose-300',
};

// ── Modal ──────────────────────────────────────────────────────────────────
const marcaModal = ref<InstanceType<typeof MarcaFormModal> | null>(null);

const onMarcaSaved = (marca: any) => {
    const idx = marcas.value.findIndex(m => m.fecha === marca.fecha);
    if (idx !== -1) marcas.value[idx] = marca;
    else marcas.value.push(marca);
};

const deleteMarca = async (marca: any) => {
    const ok = await window.Swal.fire({
        title: '¿Eliminar marcas?',
        html: `Todas las marcas del día <b>${marca.fecha}</b> serán eliminadas.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
    });
    if (!ok.isConfirmed) return;
    await axios.delete(`/asistencia/api/marcas/${marca.id}`);
    marcas.value = marcas.value.filter(m => m.id !== marca.id);
};

// ── Watchers ───────────────────────────────────────────────────────────────
watch([currentYear, currentMonth, () => props.employee?.id], fetchMarcas);
onMounted(() => { if (props.employee?.id || !props.isAdmin) fetchMarcas(); });
</script>

<template>
    <!-- Info del empleado -->
    <div class="bg-gradient-to-r from-cyan-600 to-blue-600 rounded-2xl p-5 mb-6 text-white shadow-lg shadow-cyan-500/20">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-cyan-100 text-xs font-semibold uppercase tracking-wide mb-0.5">Empleado</p>
                <h2 class="text-xl font-black">
                    {{ isAdmin ? employee?.nombre_completo : (employee?.person?.apellidos + ', ' + employee?.person?.nombres) }}
                </h2>
                <p class="text-cyan-100 text-sm mt-0.5">{{ isAdmin ? employee?.dni : employee?.direction?.nombre }}</p>
            </div>
            <div class="flex gap-4">
                <div class="text-center"><p class="text-2xl font-black">{{ stats.conEntrada }}</p><p class="text-cyan-100 text-xs font-semibold">Con entrada</p></div>
                <div class="text-center"><p class="text-2xl font-black">{{ stats.completas }}</p><p class="text-cyan-100 text-xs font-semibold">Completas</p></div>
                <div class="text-center"><p class="text-2xl font-black">{{ stats.laborables }}</p><p class="text-cyan-100 text-xs font-semibold">Laborables</p></div>
            </div>
        </div>
    </div>

    <!-- Navegación de mes -->
    <div class="flex items-center justify-between mb-5">
        <button @click="prevMonth" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
            <ChevronLeft class="w-5 h-5 text-slate-600" />
        </button>
        <div class="flex items-center gap-2">
            <CalendarDays class="w-5 h-5 text-cyan-600" />
            <span class="text-lg font-bold text-slate-800">{{ monthLabel }}</span>
        </div>
        <button @click="nextMonth" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
            <ChevronRight class="w-5 h-5 text-slate-600" />
        </button>
    </div>

    <div v-if="loading" class="flex justify-center py-16">
        <Loader2 class="w-8 h-8 animate-spin text-cyan-500" />
    </div>

    <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Cabecera fija -->
        <div class="grid grid-cols-[80px_1fr_auto] border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wide">
            <div class="px-4 py-3">Día</div>
            <div class="px-4 py-3">Marcas del día</div>
            <div class="px-4 py-3 text-right pr-5">Obs.</div>
        </div>

        <div class="divide-y divide-slate-50">
            <div v-for="dia in diasDelMes" :key="dia.fecha"
                :class="['transition-colors',
                    dia.esFeriado     ? 'bg-amber-50/60' :
                    dia.esFinDeSemana ? 'bg-slate-50/70' : 'hover:bg-cyan-50/20',
                    dia.esHoy ? 'ring-1 ring-inset ring-cyan-400' : '']">

                <div class="grid grid-cols-[80px_1fr_auto]">
                    <!-- Columna: Día -->
                    <div class="px-4 py-3 flex flex-col justify-center">
                        <span :class="['text-xs font-bold', dia.esFeriado ? 'text-amber-500' : dia.esFinDeSemana ? 'text-slate-400' : 'text-slate-500']">
                            {{ diaLabel[dia.dow] }}
                        </span>
                        <span :class="['text-lg font-black leading-tight', dia.esFeriado ? 'text-amber-600' : dia.esFinDeSemana ? 'text-slate-300' : 'text-slate-800']">
                            {{ dia.dia }}
                        </span>
                    </div>

                    <!-- Columna: Marcas -->
                    <div class="px-3 py-3 flex items-center">

                        <!-- Feriado -->
                        <template v-if="dia.esFeriado">
                            <Star class="w-3.5 h-3.5 text-amber-500 mr-2 flex-shrink-0" />
                            <span class="text-sm font-semibold text-amber-700 mr-2">{{ dia.feriado.nombre }}</span>
                            <span :class="['text-xs font-bold px-2 py-0.5 rounded-full border', tipoBadge[dia.feriado.tipo] ?? tipoBadge.FERIADO]">
                                {{ tipoLabel[dia.feriado.tipo] ?? dia.feriado.tipo }}
                            </span>
                        </template>

                        <!-- Fin de semana -->
                        <template v-else-if="dia.esFinDeSemana">
                            <span class="text-xs text-slate-300 font-medium">—</span>
                        </template>

                        <!-- Día laborable: mostrar todos los pares -->
                        <template v-else>
                            <div v-if="!marcasByDate[dia.fecha]" class="text-xs text-slate-400 italic">Sin marcas</div>
                            <div v-else class="flex flex-wrap gap-1.5">
                                <template v-for="pair in PAIRS" :key="pair.e">
                                    <!-- Entrada del par -->
                                    <span v-if="fmt(marcasByDate[dia.fecha]?.[pair.e])"
                                        :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold border', pair.colorE]">
                                        <span :class="['w-1.5 h-1.5 rounded-full inline-block', pair.dotE]"></span>
                                        {{ fmt(marcasByDate[dia.fecha]?.[pair.e]) }}
                                    </span>
                                    <!-- Salida del par -->
                                    <span v-if="fmt(marcasByDate[dia.fecha]?.[pair.s])"
                                        :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold border', pair.colorS]">
                                        <span :class="['w-1.5 h-1.5 rounded-full inline-block', pair.dotS]"></span>
                                        {{ fmt(marcasByDate[dia.fecha]?.[pair.s]) }}
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Columna: Observaciones -->
                    <div class="px-4 py-3 flex items-center justify-end">
                        <span v-if="marcasByDate[dia.fecha]?.observaciones"
                            class="text-xs text-slate-500 max-w-[120px] truncate" :title="marcasByDate[dia.fecha].observaciones">
                            {{ marcasByDate[dia.fecha].observaciones }}
                        </span>
                    </div>
                </div>

                <!-- Botones admin -->
                <div v-if="isAdmin && !dia.esFinDeSemana && !dia.esFeriado"
                    class="flex justify-end px-3 py-1 border-t border-slate-50 bg-slate-50/50">
                    <button v-if="marcasByDate[dia.fecha]" @click="marcaModal?.open(dia.fecha, marcasByDate[dia.fecha])"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                        <Pencil class="w-3 h-3" />Editar
                    </button>
                    <button v-if="marcasByDate[dia.fecha]" @click="deleteMarca(marcasByDate[dia.fecha])"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors ml-1">
                        <Trash2 class="w-3 h-3" />Eliminar
                    </button>
                    <button v-if="!marcasByDate[dia.fecha]" @click="marcaModal?.open(dia.fecha)"
                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors">
                        <Plus class="w-3 h-3" />Registrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leyenda -->
    <div class="mt-4 flex flex-wrap gap-3 text-xs text-slate-500 font-medium">
        <span v-for="p in PAIRS" :key="p.e" class="flex items-center gap-3">
            <span class="flex items-center gap-1"><span :class="['w-2.5 h-2.5 rounded-full', p.dotE]"></span> Entrada</span>
            <span class="flex items-center gap-1"><span :class="['w-2.5 h-2.5 rounded-full', p.dotS]"></span> Salida</span>
        </span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span> Feriado</span>
        <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full border border-cyan-400 inline-block"></span> Hoy</span>
    </div>

    <MarcaFormModal ref="marcaModal" :employee-id="employee?.id ?? null" @saved="onMarcaSaved" />
</template>
