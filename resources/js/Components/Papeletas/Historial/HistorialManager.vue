<script setup>
import { ref, reactive, onMounted } from 'vue';
import { FileText, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const historial = ref([]);
const loading = ref(false);

const filtros = reactive({
    estado: 'TODOS',
    fecha_desde: '',
    fecha_hasta: '',
});

const fetchHistorial = async () => {
    loading.value = true;
    try {
        const params = {};
        if (filtros.estado && filtros.estado !== 'TODOS') params.estado = filtros.estado;
        if (filtros.fecha_desde) params.fecha_desde = filtros.fecha_desde;
        if (filtros.fecha_hasta) params.fecha_hasta = filtros.fecha_hasta;

        const { data } = await axios.get('/papeletas/api/historial', { params });
        historial.value = data;
    } catch (error) {
        console.error('Error fetching historial:', error);
    } finally {
        loading.value = false;
    }
};

const resetFiltros = () => {
    filtros.estado = 'TODOS';
    filtros.fecha_desde = '';
    filtros.fecha_hasta = '';
    fetchHistorial();
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

onMounted(fetchHistorial);
</script>

<template>
    <div>
        <!-- Filters -->
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-6">
            <select v-model="filtros.estado" @change="fetchHistorial"
                class="rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20 bg-white">
                <option value="TODOS">Todos los estados</option>
                <option value="PENDIENTE">Pendiente (Jefe)</option>
                <option value="PENDIENTE_RRHH">Pendiente RRHH</option>
                <option value="APROBADO">Aprobado</option>
                <option value="DESAPROBADO">Desaprobado</option>
            </select>
            <input type="date" v-model="filtros.fecha_desde" @change="fetchHistorial"
                class="rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20" />
            <input type="date" v-model="filtros.fecha_hasta" @change="fetchHistorial"
                class="rounded-xl border border-slate-200 text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/20" />
            <button @click="resetFiltros"
                class="rounded-xl border border-slate-200 text-sm px-3 py-2.5 text-slate-500 hover:bg-slate-50 font-semibold transition-colors">
                Limpiar Filtros
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="text-center py-8 text-slate-400">
            <Loader2 class="h-6 w-6 animate-spin mx-auto" />
        </div>

        <!-- Empty state -->
        <div v-else-if="historial.length === 0" class="text-center py-12 text-slate-400">
            <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
            <p class="font-semibold">No se encontraron papeletas</p>
        </div>

        <!-- Table -->
        <div v-else class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Servidor</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Dirección</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">PDF</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="p in historial" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ p.numero_papeleta }}</td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 hidden md:table-cell">
                                {{ p.employee?.direction?.nombre ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-slate-600">{{ formatDate(p.fecha_salida) }}</td>
                            <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span :class="estadoBadgeClass(p.estado)"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                    {{ p.estado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                    class="text-slate-400 hover:text-blue-600 transition-colors">
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
