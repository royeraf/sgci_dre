<script setup>
import { ref, computed, watch } from 'vue';
import {
    FileText,
    Download,
    User,
    Search,
    X,
    Loader2
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    employees: { type: Array, default: () => [] }
});

const employeeSearch = ref('');
const selectedEmployee = ref(null);
const showResults = ref(false);
const generating = ref(false);
const errorMsg = ref('');

// Filtrar empleados localmente
const filteredEmployees = computed(() => {
    if (!employeeSearch.value || employeeSearch.value.length < 2) return [];

    const term = employeeSearch.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

    return props.employees.filter(emp => {
        const name = (emp.nombre_completo || '').toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        const dni = (emp.dni || '').toLowerCase();

        return name.includes(term) || dni.includes(term);
    }).slice(0, 10);
});

const selectEmployee = (employee) => {
    selectedEmployee.value = employee;
    showResults.value = false;
    employeeSearch.value = '';
};

const clearSelection = () => {
    selectedEmployee.value = null;
    employeeSearch.value = '';
    errorMsg.value = '';
};

const generateReport = async () => {
    if (!selectedEmployee.value) return;

    generating.value = true;
    errorMsg.value = '';

    try {
        // Primero obtener o crear el responsible para este empleado
        const resp = await axios.post('/assets/responsibles', {
            employee_id: selectedEmployee.value.id
        });

        const responsibleId = resp.data.id;

        // Abrir reporte en nueva pestaña
        window.open(`/assets/reports/responsible/${responsibleId}`, '_blank');

    } catch (e) {
        console.error(e);
        errorMsg.value = 'Error al generar el reporte: No se pudo obtener el responsable.';
    } finally {
        generating.value = false;
    }
};

// Cerrar resultados al hacer click fuera
/* Esto idealmente se haría con un v-on-click-outside o similar, pero por simplicidad
   podemos confiar en que al seleccionar se cierre */

</script>

<template>
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Card: Bienes por Responsable -->
            <div
                class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-blue-100 text-blue-600 rounded-xl">
                                <User class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">Bienes por Responsable</h3>
                                <p class="text-sm text-slate-500 mt-1">Genera un PDF con todos los bienes asignados a un
                                    servidor</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 space-y-4">
                    <div v-if="!selectedEmployee" class="relative">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar
                            Servidor</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 w-4 h-4" />
                            <input v-model="employeeSearch" type="text" placeholder="Buscar por nombre o DNI..."
                                @focus="showResults = true"
                                class="w-full pl-9 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition-all text-sm font-medium text-slate-700" />
                        </div>

                        <!-- Resultados de búsqueda -->
                        <div v-if="showResults && filteredEmployees.length > 0"
                            class="absolute z-10 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-slate-100 max-h-60 overflow-y-auto">
                            <button v-for="employee in filteredEmployees" :key="employee.id"
                                @click="selectEmployee(employee)"
                                class="w-full text-left px-4 py-3 hover:bg-slate-50 transition-colors border-b border-slate-50 last:border-0 group">
                                <div
                                    class="font-bold text-slate-700 text-sm group-hover:text-blue-700 transition-colors">
                                    {{ employee.nombre_completo }}</div>
                                <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5">
                                    <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-slate-500">{{
                                        employee.dni }}</span>
                                </div>
                            </button>
                        </div>
                        <div v-if="showResults && employeeSearch.length > 1 && filteredEmployees.length === 0"
                            class="absolute z-10 left-0 right-0 mt-2 bg-white rounded-xl shadow-xl border border-slate-100 p-4 text-center text-sm text-slate-500">
                            No se encontraron servidores con ese nombre.
                        </div>
                    </div>

                    <div v-else
                        class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex items-center justify-between group">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm border border-blue-200">
                                {{ selectedEmployee.nombre_completo.charAt(0) }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-700 text-sm">{{ selectedEmployee.nombre_completo }}
                                </div>
                                <div
                                    class="text-xs text-blue-600 font-medium font-mono bg-blue-100 inline-block px-1.5 rounded mt-0.5">
                                    {{ selectedEmployee.dni }}</div>
                            </div>
                        </div>
                        <button @click="clearSelection"
                            class="text-slate-400 hover:text-red-500 transition-colors p-1.5 hover:bg-red-50 rounded-lg">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div v-if="errorMsg"
                        class="text-xs text-red-600 font-medium bg-red-50 border border-red-100 p-3 rounded-lg flex gap-2 items-center">
                        <div class="w-1.5 h-1.5 rounded-full bg-red-500 mb-0.5"></div>
                        {{ errorMsg }}
                    </div>

                    <button @click="generateReport" :disabled="!selectedEmployee || generating"
                        class="w-full py-3 px-4 bg-gradient-to-r from-slate-800 to-slate-700 hover:from-slate-900 hover:to-slate-800 text-white font-bold rounded-xl shadow-lg shadow-slate-200 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed group">
                        <Loader2 v-if="generating" class="w-5 h-5 animate-spin" />
                        <Download v-else class="w-5 h-5 group-hover:scale-110 transition-transform" />
                        {{ generating ? 'Generando...' : 'Descargar Reporte PDF' }}
                    </button>
                </div>
            </div>

            <!-- Card: Coming Soon -->
            <div
                class="bg-slate-50/50 rounded-2xl border-2 border-dashed border-slate-200 p-6 flex flex-col items-center justify-center text-center">
                <div class="p-4 bg-white rounded-full mb-3 shadow-sm">
                    <FileText class="w-8 h-8 text-slate-300" />
                </div>
                <h3 class="font-bold text-slate-400">Más reportes próximamente</h3>
                <p class="text-sm text-slate-400 max-w-xs mt-2">Pronto podrás generar reportes por oficina, estado y
                    tipo de bien.</p>
            </div>
        </div>
    </div>
</template>
