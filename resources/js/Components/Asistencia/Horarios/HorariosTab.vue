<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { Plus, Pencil, Trash2, Users, Clock, Loader2, Search } from 'lucide-vue-next';
import HorarioFormModal from './HorarioFormModal.vue';

const props = defineProps<{
    isAdmin: boolean;
    employees: any[];
}>();

const horarios    = ref<any[]>([]);
const loading     = ref(false);
const formModal   = ref<InstanceType<typeof HorarioFormModal> | null>(null);

// Asignación de empleado
const empSearch       = ref('');
const selectedHorarioId = ref<number | null>(null);
const assigning       = ref(false);
const assignEmpId     = ref<string | null>(null);

const filteredEmps = computed(() => {
    if (!empSearch.value) return props.employees.slice(0, 15);
    const q = empSearch.value.toLowerCase();
    return props.employees.filter(e => e.nombre_completo.toLowerCase().includes(q) || e.dni.includes(q)).slice(0, 15);
});

// Mapa empleado → horario actual
const empHorarioMap = computed(() => {
    const map: Record<string, number | null> = {};
    for (const e of props.employees) map[e.id] = e.horario_id ?? null;
    return map;
});

const fetchHorarios = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/asistencia/api/horarios');
        horarios.value = res.data;
    } finally {
        loading.value = false;
    }
};

const onSaved = (horario: any) => {
    const idx = horarios.value.findIndex(h => h.id === horario.id);
    if (idx !== -1) horarios.value[idx] = horario;
    else horarios.value.push(horario);
};

const deleteHorario = async (h: any) => {
    if (h.employees_count > 0) {
        window.Swal.fire({ icon: 'warning', title: 'No se puede eliminar', text: `Este horario tiene ${h.employees_count} empleado(s) asignado(s). Reasígnalos primero.` });
        return;
    }
    const ok = await window.Swal.fire({
        title: '¿Eliminar horario?', html: `<b>${h.nombre}</b>`,
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
        confirmButtonColor: '#ef4444', cancelButtonColor: '#64748b',
    });
    if (!ok.isConfirmed) return;
    await axios.delete(`/asistencia/api/horarios/${h.id}`);
    horarios.value = horarios.value.filter(x => x.id !== h.id);
    window.Swal.fire({ icon: 'success', title: 'Eliminado', timer: 1500, showConfirmButton: false });
};

const assignHorario = async (empId: string, horarioId: number | null) => {
    assigning.value = true;
    assignEmpId.value = empId;
    try {
        await axios.post('/asistencia/api/horarios/assign', { employee_id: empId, horario_id: horarioId });
        // Actualizar localmente
        const emp = props.employees.find(e => e.id === empId);
        if (emp) emp.horario_id = horarioId;
        // Actualizar contadores
        await fetchHorarios();
    } finally {
        assigning.value = false;
        assignEmpId.value = null;
    }
};

onMounted(fetchHorarios);
</script>

<template>
    <div class="space-y-6">

        <!-- Encabezado -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Horarios de Trabajo</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    Define los horarios de entrada y salida. La tardanza se calcula respecto al horario de cada empleado.
                </p>
            </div>
            <button v-if="isAdmin" @click="formModal?.open()"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 transition-all shadow-md shadow-indigo-500/20">
                <Plus class="w-4 h-4" /> Nuevo horario
            </button>
        </div>

        <!-- Lista de horarios -->
        <div v-if="loading" class="flex justify-center py-10"><Loader2 class="w-6 h-6 animate-spin text-indigo-500" /></div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="h in horarios" :key="h.id"
                class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="font-bold text-slate-800">{{ h.nombre }}</h3>
                        <p v-if="h.descripcion" class="text-xs text-slate-500 mt-0.5">{{ h.descripcion }}</p>
                    </div>
                    <span :class="['text-xs font-bold px-2 py-0.5 rounded-full border', h.activo ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-500 border-slate-200']">
                        {{ h.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <!-- Franjas horarias -->
                <div class="space-y-1.5 mb-4">
                    <div class="flex items-center gap-2 text-sm">
                        <Clock class="w-3.5 h-3.5 text-blue-500 flex-shrink-0" />
                        <span class="text-slate-600">Mañana:</span>
                        <span class="font-bold text-slate-800">{{ h.entrada_manana }} – {{ h.salida_manana }}</span>
                    </div>
                    <div v-if="h.entrada_tarde" class="flex items-center gap-2 text-sm">
                        <Clock class="w-3.5 h-3.5 text-orange-500 flex-shrink-0" />
                        <span class="text-slate-600">Tarde:</span>
                        <span class="font-bold text-slate-800">{{ h.entrada_tarde }} – {{ h.salida_tarde }}</span>
                    </div>
                    <div v-else class="flex items-center gap-2 text-xs text-slate-400">
                        <Clock class="w-3 h-3 flex-shrink-0" />
                        Turno continuo (sin jornada tarde)
                    </div>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <div class="flex items-center gap-1.5 text-xs text-slate-500">
                        <Users class="w-3.5 h-3.5" />
                        <span><b>{{ h.employees_count }}</b> empleado(s)</span>
                    </div>
                    <div v-if="isAdmin" class="flex gap-1">
                        <button @click="formModal?.open(h)"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                        <button @click="deleteHorario(h)"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-500 hover:bg-red-50 transition-colors">
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Asignación de horarios por empleado (solo admin) -->
        <div v-if="isAdmin && horarios.length > 0" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-200 px-5 py-3 flex items-center gap-2">
                <Users class="w-4 h-4 text-indigo-600" />
                <h3 class="text-sm font-bold text-slate-700">Asignar horario por empleado</h3>
            </div>
            <div class="p-4">
                <div class="relative max-w-sm mb-4">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                    <input v-model="empSearch" type="text" placeholder="Buscar empleado…"
                        class="pl-10 pr-4 py-2.5 w-full border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                </div>

                <div class="divide-y divide-slate-50 max-h-80 overflow-y-auto">
                    <div v-for="emp in filteredEmps" :key="emp.id"
                        class="flex items-center justify-between py-2.5 px-1 hover:bg-slate-50/60 rounded-lg transition-colors">
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ emp.nombre_completo }}</p>
                            <p class="text-xs text-slate-400">{{ emp.dni }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <Loader2 v-if="assigning && assignEmpId === emp.id" class="w-4 h-4 animate-spin text-indigo-500" />
                            <select v-else :value="empHorarioMap[emp.id] ?? ''"
                                @change="assignHorario(emp.id, ($event.target as HTMLSelectElement).value ? parseInt(($event.target as HTMLSelectElement).value) : null)"
                                class="px-2.5 py-1.5 border border-slate-200 rounded-lg text-xs font-semibold focus:ring-2 focus:ring-indigo-500 outline-none bg-white">
                                <option value="">— Sin asignar —</option>
                                <option v-for="h in horarios.filter(x => x.activo)" :key="h.id" :value="h.id">
                                    {{ h.nombre }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <HorarioFormModal ref="formModal" @saved="onSaved" />
</template>
