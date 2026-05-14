<script setup>
import { ref, onMounted } from 'vue';
import { FileText, ClipboardList, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const papeletas = ref([]);
const loading = ref(false);

const fetchPapeletas = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/papeletas/api/mis');
        papeletas.value = res.data;
    } finally {
        loading.value = false;
    }
};

const estadoBadgeClass = (estado) => {
    const classes = {
        PENDIENTE: 'bg-yellow-100 text-yellow-800',
        PENDIENTE_RRHH: 'bg-orange-100 text-orange-800',
        APROBADO: 'bg-green-100 text-green-800',
        DESAPROBADO: 'bg-red-100 text-red-800',
    };
    return classes[estado] || 'bg-slate-100 text-slate-800';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

defineExpose({ refresh: fetchPapeletas });

onMounted(fetchPapeletas);
</script>

<template>
    <div class="space-y-6">
        <!-- Mini stats -->
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-slate-50 rounded-xl p-3 text-center border border-slate-100">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total</p>
                <p class="text-xl font-black text-slate-800">{{ papeletas.length }}</p>
            </div>
            <div class="bg-green-50 rounded-xl p-3 text-center border border-green-100">
                <p class="text-xs font-semibold text-green-600 uppercase">Aprobadas</p>
                <p class="text-xl font-black text-green-700">
                    {{ papeletas.filter(p => p.estado === 'APROBADO').length }}
                </p>
            </div>
            <div class="bg-red-50 rounded-xl p-3 text-center border border-red-100">
                <p class="text-xs font-semibold text-red-600 uppercase">Rechazadas</p>
                <p class="text-xl font-black text-red-700">
                    {{ papeletas.filter(p => p.estado === 'DESAPROBADO').length }}
                </p>
            </div>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-10 text-slate-400">
            <Loader2 class="h-7 w-7 animate-spin mx-auto mb-2" />
        </div>

        <!-- Empty state -->
        <div v-else-if="papeletas.length === 0" class="text-center py-12 text-slate-400">
            <ClipboardList class="h-12 w-12 mx-auto mb-3 opacity-40" />
            <p class="font-semibold">No tiene papeletas registradas</p>
            <p class="text-sm mt-1">Cree una nueva papeleta para comenzar</p>
        </div>

        <!-- Table -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha Salida</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Horario</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="p in papeletas" :key="p.id" class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ p.numero_papeleta }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ formatDate(p.fecha_salida) }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">
                                {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span :class="estadoBadgeClass(p.estado)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                    {{ p.estado === 'PENDIENTE' ? 'Pendiente (Jefe)' : p.estado === 'PENDIENTE_RRHH' ? 'Pendiente (RRHH)' : p.estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                    class="text-slate-400 hover:text-indigo-600 transition-colors">
                                    <FileText class="h-4 w-4 inline-block" />
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
