<script setup lang="ts">
import { ref, computed } from 'vue';
import axios from 'axios';
import { ChevronLeft, ChevronRight, CalendarOff, Plus, Trash2, Loader2 } from 'lucide-vue-next';
import FeriadoFormModal from './FeriadoFormModal.vue';

const props = defineProps<{
    isAdmin: boolean;
    feriados: any[];
    loading: boolean;
}>();

const emit = defineEmits<{
    'feriado-added':   [feriado: any];
    'feriado-removed': [id: number];
    'year-changed':    [year: number];
}>();

const today         = new Date();
const feriadoTabYear = ref(today.getFullYear());
const feriadoModal  = ref<InstanceType<typeof FeriadoFormModal> | null>(null);

const monthNames = ['Enero','Febrero','Marzo','Abril','Mayo','Junio',
                    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

const feriadosByDate = computed(() => {
    const map: Record<string, any> = {};
    for (const f of props.feriados) map[f.fecha] = f;
    return map;
});

const feriadosDel = computed(() =>
    props.feriados.filter(f => f.fecha.startsWith(String(feriadoTabYear.value)))
);

const prevYear = () => { feriadoTabYear.value--; emit('year-changed', feriadoTabYear.value); };
const nextYear = () => { feriadoTabYear.value++; emit('year-changed', feriadoTabYear.value); };

const tipoBadge: Record<string, string> = {
    FERIADO:      'bg-amber-100 text-amber-800 border-amber-300',
    NO_LABORABLE: 'bg-rose-100 text-rose-800 border-rose-300',
};
const tipoLabel: Record<string, string> = {
    FERIADO: 'Feriado Nacional', NO_LABORABLE: 'No Laborable',
};

const formatFecha = (iso: string) => {
    if (!iso) return '';
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
};

const deleteFeriado = async (f: any) => {
    const result = await window.Swal.fire({
        title: '¿Eliminar día no laborable?',
        html: `<b>${f.nombre}</b><br><span style="color:#64748b;font-size:0.85em">${formatFecha(f.fecha)}</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/asistencia/api/feriados/${f.id}`);
        emit('feriado-removed', f.id);
        window.Swal.fire({ icon: 'success', title: 'Eliminado', text: 'El día no laborable fue eliminado.', timer: 1500, showConfirmButton: false });
    } catch {
        window.Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo eliminar.' });
    }
};
</script>

<template>
    <div class="space-y-6">

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Días Festivos y No Laborables</h2>
                <p class="text-sm text-slate-500 mt-0.5">Se reflejan en el calendario de todos los trabajadores.</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl px-3 py-2 shadow-sm">
                    <button @click="prevYear" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <ChevronLeft class="w-4 h-4 text-slate-600" />
                    </button>
                    <span class="text-sm font-bold text-slate-700 w-12 text-center">{{ feriadoTabYear }}</span>
                    <button @click="nextYear" class="p-1 rounded-lg hover:bg-slate-100 transition-colors">
                        <ChevronRight class="w-4 h-4 text-slate-600" />
                    </button>
                </div>
                <button v-if="isAdmin" @click="feriadoModal?.open()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 transition-all shadow-md shadow-amber-500/20">
                    <Plus class="w-4 h-4" />
                    Agregar día
                </button>
            </div>
        </div>

        <!-- Lista -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 bg-slate-50 px-5 py-3">
                <span class="text-xs font-bold text-slate-500 uppercase tracking-wide">
                    {{ feriadosDel.length }} día(s) registrado(s) para {{ feriadoTabYear }}
                </span>
            </div>

            <div v-if="loading" class="flex justify-center py-10">
                <Loader2 class="w-6 h-6 animate-spin text-amber-500" />
            </div>

            <div v-else-if="feriadosDel.length === 0" class="py-14 text-center text-slate-400">
                <CalendarOff class="w-10 h-10 mx-auto mb-3 opacity-30" />
                <p class="font-semibold text-sm">No hay días no laborables registrados para {{ feriadoTabYear }}</p>
                <p v-if="isAdmin" class="text-xs mt-1">Haz clic en <strong>Agregar día</strong> para comenzar.</p>
            </div>

            <div v-else class="divide-y divide-slate-50">
                <div v-for="f in feriadosDel" :key="f.id"
                    class="flex items-center justify-between px-5 py-3.5 hover:bg-slate-50/60 transition-colors group">
                    <div class="flex items-center gap-4">
                        <div class="w-14 text-center bg-amber-50 border border-amber-200 rounded-xl py-1.5">
                            <p class="text-xs font-bold text-amber-700 leading-none">{{ f.fecha.split('-')[2] }}</p>
                            <p class="text-xs text-amber-500 leading-none mt-0.5">
                                {{ monthNames[parseInt(f.fecha.split('-')[1]) - 1]?.substring(0, 3) }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ f.nombre }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ formatFecha(f.fecha) }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span :class="['text-xs font-bold px-2.5 py-1 rounded-full border', tipoBadge[f.tipo] ?? tipoBadge.FERIADO]">
                            {{ tipoLabel[f.tipo] ?? f.tipo }}
                        </span>
                        <button v-if="isAdmin" @click="deleteFeriado(f)"
                            class="p-1.5 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 transition-colors opacity-0 group-hover:opacity-100">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <FeriadoFormModal ref="feriadoModal" :feriados-by-date="feriadosByDate" @added="emit('feriado-added', $event)" />
</template>
