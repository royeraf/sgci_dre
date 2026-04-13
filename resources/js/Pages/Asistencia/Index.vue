<script lang="ts">
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import {
    ChevronLeft,
    ChevronRight,
    Clock,
    LogIn,
    LogOut,
    Coffee,
    Loader2,
    CalendarDays,
    Plus,
    Pencil,
    Trash2,
    AlertTriangle,
    User,
    Search,
} from 'lucide-vue-next';

// ===== PROPS =====
const props = defineProps<{
    myEmployee: any | null;
    isAdmin: boolean;
    employees: any[];
}>();

// ===== FECHA ACTUAL =====
const today = new Date();
const currentYear  = ref(today.getFullYear());
const currentMonth = ref(today.getMonth() + 1); // 1-12

const monthNames = [
    'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

const monthLabel = computed(() => `${monthNames[currentMonth.value - 1]} ${currentYear.value}`);

const prevMonth = () => {
    if (currentMonth.value === 1) { currentMonth.value = 12; currentYear.value--; }
    else currentMonth.value--;
};
const nextMonth = () => {
    if (currentMonth.value === 12) { currentMonth.value = 1; currentYear.value++; }
    else currentMonth.value++;
};

// ===== EMPLEADO SELECCIONADO (admin) =====
const selectedEmployeeId = ref<number | null>(props.myEmployee?.id ?? null);
const empSearch = ref('');

const filteredEmployees = computed(() => {
    if (!empSearch.value) return props.employees.slice(0, 20);
    const q = empSearch.value.toLowerCase();
    return props.employees.filter(e =>
        e.nombre_completo.toLowerCase().includes(q) || e.dni.includes(q)
    ).slice(0, 20);
});

const selectedEmployee = computed(() =>
    props.isAdmin
        ? props.employees.find(e => e.id === selectedEmployeeId.value) ?? null
        : props.myEmployee
);

// ===== MARCAS =====
const marcas = ref<any[]>([]);
const loading = ref(false);

const fetchMarcas = async () => {
    loading.value = true;
    try {
        const params: Record<string, any> = {
            year: currentYear.value,
            month: currentMonth.value,
        };
        if (props.isAdmin && selectedEmployeeId.value) {
            params.employee_id = selectedEmployeeId.value;
        }
        const res = await axios.get('/asistencia/api/marcas', { params });
        marcas.value = res.data;
    } finally {
        loading.value = false;
    }
};

// Indexar marcas por fecha para acceso O(1)
const marcasByDate = computed(() => {
    const map: Record<string, any> = {};
    for (const m of marcas.value) {
        map[m.fecha] = m;
    }
    return map;
});

// ===== CALENDARIO DEL MES =====
const diasDelMes = computed(() => {
    const dias = [];
    const total = new Date(currentYear.value, currentMonth.value, 0).getDate();
    for (let d = 1; d <= total; d++) {
        const fecha = `${currentYear.value}-${String(currentMonth.value).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const dow = new Date(fecha).getDay(); // 0=dom, 6=sab
        const esHoy = fecha === new Date().toISOString().split('T')[0];
        dias.push({ fecha, dia: d, dow, esFinDeSemana: dow === 0 || dow === 6, esHoy });
    }
    return dias;
});

// ===== ESTADÍSTICAS =====
const stats = computed(() => {
    const laborables = diasDelMes.value.filter(d => !d.esFinDeSemana).length;
    const conEntrada = marcas.value.filter(m => m.entrada).length;
    const completas = marcas.value.filter(m => m.entrada && m.salida).length;
    return { laborables, conEntrada, completas };
});

// ===== MODAL REGISTRO (admin) =====
const showModal = ref(false);
const modalMarca = ref<any>(null); // null = nuevo, object = editar
const formData = ref({
    employee_id: null as number | null,
    fecha: '',
    entrada: '',
    salida_mediodia: '',
    retorno_mediodia: '',
    salida: '',
    observaciones: '',
});
const formErrors = ref<Record<string, string[]>>({});
const formSubmitting = ref(false);

const openCreate = (fecha: string) => {
    modalMarca.value = null;
    formData.value = {
        employee_id: selectedEmployeeId.value,
        fecha,
        entrada: '',
        salida_mediodia: '',
        retorno_mediodia: '',
        salida: '',
        observaciones: '',
    };
    formErrors.value = {};
    showModal.value = true;
};

const openEdit = (marca: any) => {
    modalMarca.value = marca;
    formData.value = {
        employee_id: marca.employee_id,
        fecha: marca.fecha,
        entrada: marca.entrada?.substring(0, 5) ?? '',
        salida_mediodia: marca.salida_mediodia?.substring(0, 5) ?? '',
        retorno_mediodia: marca.retorno_mediodia?.substring(0, 5) ?? '',
        salida: marca.salida?.substring(0, 5) ?? '',
        observaciones: marca.observaciones ?? '',
    };
    formErrors.value = {};
    showModal.value = true;
};

const handleSubmit = async () => {
    formSubmitting.value = true;
    formErrors.value = {};
    try {
        const res = await axios.post('/asistencia/api/marcas', formData.value);
        // Actualizar local
        const idx = marcas.value.findIndex(m => m.fecha === res.data.fecha);
        if (idx !== -1) marcas.value[idx] = res.data;
        else marcas.value.push(res.data);
        showModal.value = false;
    } catch (err: any) {
        if (err.response?.data?.errors) {
            formErrors.value = err.response.data.errors;
        }
    } finally {
        formSubmitting.value = false;
    }
};

// ===== ELIMINAR =====
const showDeleteConfirm = ref(false);
const marcaToDelete = ref<any>(null);
const deleting = ref(false);

const confirmDelete = (marca: any) => {
    marcaToDelete.value = marca;
    showDeleteConfirm.value = true;
};

const handleDelete = async () => {
    if (!marcaToDelete.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/asistencia/api/marcas/${marcaToDelete.value.id}`);
        marcas.value = marcas.value.filter(m => m.id !== marcaToDelete.value.id);
        showDeleteConfirm.value = false;
    } finally {
        deleting.value = false;
    }
};

// ===== HELPERS =====
const fmt = (t: string | null) => t ? t.substring(0, 5) : null;

const diaLabel = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

// ===== WATCHERS =====
watch([currentYear, currentMonth, selectedEmployeeId], fetchMarcas);

onMounted(() => {
    if (selectedEmployeeId.value) fetchMarcas();
});
</script>

<template>
    <div class="p-4 sm:p-6 lg:p-8 max-w-6xl mx-auto">

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent tracking-tight">
                    Marcas de Asistencia
                </h1>
                <p class="mt-1 text-slate-500 font-medium">
                    {{ isAdmin ? 'Registro y consulta de marcas por empleado' : 'Consulta tus marcas de entrada y salida' }}
                </p>
            </div>
        </div>

        <!-- Admin: selector de empleado -->
        <div v-if="isAdmin" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
            <label class="block text-sm font-bold text-slate-700 mb-2">Empleado</label>
            <div class="relative max-w-sm">
                <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <input v-model="empSearch" type="text" placeholder="Buscar por nombre o DNI..."
                    class="pl-10 pr-4 py-2.5 w-full border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" />
            </div>
            <div class="mt-2 flex flex-wrap gap-2">
                <button v-for="emp in filteredEmployees" :key="emp.id"
                    @click="selectedEmployeeId = emp.id; empSearch = ''"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all',
                        selectedEmployeeId === emp.id
                            ? 'bg-cyan-600 text-white border-cyan-600 shadow-md'
                            : 'bg-white text-slate-600 border-slate-200 hover:border-cyan-300 hover:text-cyan-700'
                    ]">
                    {{ emp.nombre_completo }} <span class="opacity-60 ml-1">{{ emp.dni }}</span>
                </button>
            </div>
        </div>

        <!-- Sin empleado vinculado -->
        <div v-if="!myEmployee && !isAdmin" class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
            <User class="w-12 h-12 mx-auto text-amber-400 mb-3" />
            <p class="font-bold text-amber-800">No tiene un empleado vinculado a su cuenta.</p>
            <p class="text-sm text-amber-600 mt-1">Contacte al administrador para asociar su perfil.</p>
        </div>

        <template v-else-if="selectedEmployee || !isAdmin">

            <!-- Info del empleado -->
            <div class="bg-gradient-to-r from-cyan-600 to-blue-600 rounded-2xl p-5 mb-6 text-white shadow-lg shadow-cyan-500/20">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <p class="text-cyan-100 text-xs font-semibold uppercase tracking-wide mb-0.5">Empleado</p>
                        <h2 class="text-xl font-black">
                            {{ isAdmin ? selectedEmployee?.nombre_completo : (myEmployee?.person?.apellidos + ', ' + myEmployee?.person?.nombres) }}
                        </h2>
                        <p class="text-cyan-100 text-sm mt-0.5">
                            {{ isAdmin ? selectedEmployee?.dni : myEmployee?.direction?.nombre }}
                        </p>
                    </div>
                    <!-- Stats -->
                    <div class="flex gap-4">
                        <div class="text-center">
                            <p class="text-2xl font-black">{{ stats.conEntrada }}</p>
                            <p class="text-cyan-100 text-xs font-semibold">Con entrada</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black">{{ stats.completas }}</p>
                            <p class="text-cyan-100 text-xs font-semibold">Completas</p>
                        </div>
                        <div class="text-center">
                            <p class="text-2xl font-black">{{ stats.laborables }}</p>
                            <p class="text-cyan-100 text-xs font-semibold">Laborables</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navegación de mes -->
            <div class="flex items-center justify-between mb-5">
                <button @click="prevMonth"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                    <ChevronLeft class="w-5 h-5 text-slate-600" />
                </button>
                <div class="flex items-center gap-2">
                    <CalendarDays class="w-5 h-5 text-cyan-600" />
                    <span class="text-lg font-bold text-slate-800">{{ monthLabel }}</span>
                </div>
                <button @click="nextMonth"
                    class="p-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors">
                    <ChevronRight class="w-5 h-5 text-slate-600" />
                </button>
            </div>

            <!-- Cargando -->
            <div v-if="loading" class="flex justify-center py-16">
                <Loader2 class="w-8 h-8 animate-spin text-cyan-500" />
            </div>

            <!-- Tabla de marcas -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <!-- Cabecera -->
                <div class="grid grid-cols-[auto_1fr_1fr_1fr_1fr] sm:grid-cols-[80px_1fr_1fr_1fr_1fr] gap-0 border-b border-slate-100 bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wide">
                    <div class="px-4 py-3">Día</div>
                    <div class="px-4 py-3 flex items-center gap-1.5 text-emerald-600">
                        <LogIn class="w-3.5 h-3.5" />Entrada
                    </div>
                    <div class="px-4 py-3 flex items-center gap-1.5 text-orange-500">
                        <Coffee class="w-3.5 h-3.5" />S. Mediodía
                    </div>
                    <div class="px-4 py-3 flex items-center gap-1.5 text-blue-500">
                        <Clock class="w-3.5 h-3.5" />R. Mediodía
                    </div>
                    <div class="px-4 py-3 flex items-center gap-1.5 text-rose-500">
                        <LogOut class="w-3.5 h-3.5" />Salida
                    </div>
                </div>

                <!-- Filas por día -->
                <div class="divide-y divide-slate-50">
                    <div v-for="dia in diasDelMes" :key="dia.fecha"
                        :class="[
                            'grid grid-cols-[auto_1fr_1fr_1fr_1fr] sm:grid-cols-[80px_1fr_1fr_1fr_1fr] gap-0 transition-colors',
                            dia.esFinDeSemana ? 'bg-slate-50/70' : 'hover:bg-cyan-50/30',
                            dia.esHoy ? 'ring-1 ring-inset ring-cyan-400' : ''
                        ]">

                        <!-- Columna: Día -->
                        <div class="px-4 py-3 flex flex-col justify-center">
                            <span :class="[
                                'text-xs font-bold',
                                dia.esFinDeSemana ? 'text-slate-400' : 'text-slate-500'
                            ]">{{ diaLabel[dia.dow] }}</span>
                            <span :class="[
                                'text-lg font-black leading-tight',
                                dia.esFinDeSemana ? 'text-slate-300' : 'text-slate-800'
                            ]">{{ dia.dia }}</span>
                        </div>

                        <!-- Columnas de marcas -->
                        <template v-if="!dia.esFinDeSemana">
                            <!-- Entrada -->
                            <div class="px-4 py-3 flex items-center">
                                <span v-if="fmt(marcasByDate[dia.fecha]?.entrada)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 text-sm font-bold border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                                    {{ fmt(marcasByDate[dia.fecha]?.entrada) }}
                                </span>
                                <span v-else class="text-slate-300 text-sm font-medium">—</span>
                            </div>
                            <!-- Salida mediodía -->
                            <div class="px-4 py-3 flex items-center">
                                <span v-if="fmt(marcasByDate[dia.fecha]?.salida_mediodia)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-orange-50 text-orange-700 text-sm font-bold border border-orange-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-400 inline-block"></span>
                                    {{ fmt(marcasByDate[dia.fecha]?.salida_mediodia) }}
                                </span>
                                <span v-else class="text-slate-300 text-sm font-medium">—</span>
                            </div>
                            <!-- Retorno mediodía -->
                            <div class="px-4 py-3 flex items-center">
                                <span v-if="fmt(marcasByDate[dia.fecha]?.retorno_mediodia)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 text-sm font-bold border border-blue-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 inline-block"></span>
                                    {{ fmt(marcasByDate[dia.fecha]?.retorno_mediodia) }}
                                </span>
                                <span v-else class="text-slate-300 text-sm font-medium">—</span>
                            </div>
                            <!-- Salida -->
                            <div class="px-4 py-3 flex items-center">
                                <span v-if="fmt(marcasByDate[dia.fecha]?.salida)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-rose-50 text-rose-700 text-sm font-bold border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 inline-block"></span>
                                    {{ fmt(marcasByDate[dia.fecha]?.salida) }}
                                </span>
                                <span v-else class="text-slate-300 text-sm font-medium">—</span>
                            </div>
                        </template>
                        <template v-else>
                            <div v-for="n in 4" :key="n" class="px-4 py-3 flex items-center">
                                <span class="text-xs text-slate-300 font-medium">—</span>
                            </div>
                        </template>

                        <!-- Botón editar (solo admin, días no-fin de semana) -->
                        <div v-if="isAdmin && !dia.esFinDeSemana" class="col-span-full flex justify-end px-3 py-1 border-t border-slate-50 bg-slate-50/50">
                            <button v-if="marcasByDate[dia.fecha]"
                                @click="openEdit(marcasByDate[dia.fecha])"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                <Pencil class="w-3 h-3" />Editar
                            </button>
                            <button v-if="marcasByDate[dia.fecha]"
                                @click="confirmDelete(marcasByDate[dia.fecha])"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors ml-1">
                                <Trash2 class="w-3 h-3" />Eliminar
                            </button>
                            <button v-if="!marcasByDate[dia.fecha]"
                                @click="openCreate(dia.fecha)"
                                class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-cyan-600 hover:bg-cyan-50 rounded-lg transition-colors">
                                <Plus class="w-3 h-3" />Registrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-500 font-medium">
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span> Entrada
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-orange-400 inline-block"></span> Salida mediodía
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span> Retorno mediodía
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span> Salida
                </span>
                <span class="flex items-center gap-1.5">
                    <span class="w-3 h-3 rounded-full border border-cyan-400 inline-block"></span> Hoy
                </span>
            </div>
        </template>

        <!-- Admin: sin empleado seleccionado -->
        <div v-else class="bg-slate-50 border border-slate-200 rounded-2xl p-12 text-center text-slate-400">
            <User class="w-12 h-12 mx-auto mb-3 opacity-40" />
            <p class="font-semibold">Seleccione un empleado para ver sus marcas</p>
        </div>

    </div>

    <!-- Modal registro de marcas (admin) -->
    <Teleport to="body">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showModal = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">
                        {{ modalMarca ? 'Editar Marcas' : 'Registrar Marcas' }}
                    </h3>
                    <p class="text-cyan-100 text-sm">{{ formData.fecha }}</p>
                </div>
                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>
                                Entrada
                            </label>
                            <input type="time" v-model="formData.entrada"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" />
                            <p v-if="formErrors.entrada" class="mt-1 text-xs text-red-500">{{ formErrors.entrada[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-orange-400 inline-block"></span>
                                Salida mediodía
                            </label>
                            <input type="time" v-model="formData.salida_mediodia"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>
                                Retorno mediodía
                            </label>
                            <input type="time" v-model="formData.retorno_mediodia"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-1.5">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-500 inline-block"></span>
                                Salida
                            </label>
                            <input type="time" v-model="formData.salida"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Observaciones</label>
                        <textarea v-model="formData.observaciones" rows="2" maxlength="500"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none resize-none"
                            placeholder="Observación opcional..."></textarea>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="showModal = false"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="formSubmitting"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 disabled:opacity-50 transition-all shadow-md">
                            <Loader2 v-if="formSubmitting" class="w-4 h-4 animate-spin" />
                            {{ formSubmitting ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal confirmación eliminar -->
    <Teleport to="body">
        <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showDeleteConfirm = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6 text-center">
                <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <AlertTriangle class="w-7 h-7 text-red-500" />
                </div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">¿Eliminar marcas?</h3>
                <p class="text-sm text-slate-500 mb-6">
                    Se eliminarán todas las marcas del día <strong>{{ marcaToDelete?.fecha }}</strong>. Esta acción no se puede deshacer.
                </p>
                <div class="flex justify-center gap-3">
                    <button @click="showDeleteConfirm = false"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 border border-slate-200 hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                    <button @click="handleDelete" :disabled="deleting"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-red-500 hover:bg-red-600 disabled:opacity-50 transition-colors">
                        <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
