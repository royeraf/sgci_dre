<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 mb-3 group-hover:scale-110 transition-transform">
                        <ClipboardList class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total Inventarios</p>
                    <h3 class="text-2xl font-black text-slate-900">{{ stats.total }}</h3>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-3 group-hover:scale-110 transition-transform">
                        <Clock class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Pendientes</p>
                    <h3 class="text-2xl font-black text-blue-600">{{ stats.pendientes }}</h3>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-3 group-hover:scale-110 transition-transform">
                        <Loader2 class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">En Proceso</p>
                    <h3 class="text-2xl font-black text-amber-600">{{ stats.en_proceso }}</h3>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div class="absolute right-0 top-0 w-16 h-16 bg-green-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                <div class="relative">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 mb-3 group-hover:scale-110 transition-transform">
                        <CheckCircle class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Cerrados</p>
                    <h3 class="text-2xl font-black text-green-600">{{ stats.cerrados }}</h3>
                </div>
            </div>
        </div>

        <!-- Filters + New button -->
        <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Año</label>
                    <select v-model="filterAnio"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos los años</option>
                        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                    <select v-model="filterEstado"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="EN_PROCESO">En Proceso</option>
                        <option value="CERRADO">Cerrado</option>
                    </select>
                </div>

                <div class="lg:col-span-2 flex items-end justify-end">
                    <button @click="openCreateModal"
                        class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300">
                        <Plus class="w-4 h-4 mr-2" />
                        Nuevo Inventario
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    <span class="font-semibold text-slate-700">{{ total }}</span>
                    inventarios encontrados
                </p>
                <button @click="clearFilters"
                    class="text-sm font-semibold text-slate-500 hover:text-purple-600 transition-colors duration-200 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
            <div v-if="loading" class="p-12 text-center">
                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                <p class="text-sm text-slate-400 mt-2">Cargando inventarios...</p>
            </div>

            <template v-else-if="inventarios.length === 0">
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                            <ClipboardList class="h-12 w-12 text-slate-400" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">No hay inventarios registrados</h3>
                        <p class="text-sm text-slate-500">Crea el primer inventario para comenzar.</p>
                    </div>
                </div>
            </template>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Año</th>
                            <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Nombre</th>
                            <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Fecha Inicio</th>
                            <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Fecha Fin</th>
                            <th class="px-5 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Estado</th>
                            <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Creado por</th>
                            <th class="px-5 py-4 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-for="inv in inventarios" :key="inv.id" class="hover:bg-purple-50 transition-colors duration-200">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="text-lg font-black text-slate-800">{{ inv.anio }}</span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-sm font-semibold text-slate-800">{{ inv.nombre }}</div>
                                <div v-if="inv.descripcion" class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ inv.descripcion }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-slate-600">{{ formatDate(inv.fecha_inicio) }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                                <div class="text-sm text-slate-600">{{ inv.fecha_fin ? formatDate(inv.fecha_fin) : '—' }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full"
                                    :class="estadoClass(inv.estado)">
                                    {{ estadoLabel(inv.estado) }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div class="text-sm text-slate-600">{{ inv.creado_por?.name || '—' }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right">
                                <div class="flex items-center gap-1 justify-end">
                                    <!-- Cambiar estado rápido -->
                                    <button v-if="inv.estado === 'PENDIENTE'"
                                        @click="changeEstado(inv, 'EN_PROCESO')"
                                        class="text-amber-500 hover:text-amber-700 transition-colors p-2 hover:bg-amber-50 rounded-lg"
                                        title="Iniciar proceso">
                                        <Play class="h-4 w-4" />
                                    </button>
                                    <button v-if="inv.estado === 'EN_PROCESO'"
                                        @click="changeEstado(inv, 'CERRADO')"
                                        class="text-green-600 hover:text-green-800 transition-colors p-2 hover:bg-green-50 rounded-lg"
                                        title="Cerrar inventario">
                                        <CheckCircle class="h-4 w-4" />
                                    </button>
                                    <button v-if="inv.estado === 'CERRADO'"
                                        @click="changeEstado(inv, 'EN_PROCESO')"
                                        class="text-slate-400 hover:text-amber-600 transition-colors p-2 hover:bg-amber-50 rounded-lg"
                                        title="Reabrir inventario">
                                        <RotateCcw class="h-4 w-4" />
                                    </button>
                                    <!-- Editar -->
                                    <button @click="openEditModal(inv)"
                                        class="text-slate-400 hover:text-blue-600 transition-colors p-2 hover:bg-blue-50 rounded-lg"
                                        title="Editar">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <!-- Eliminar (solo no cerrados) -->
                                    <button v-if="inv.estado !== 'CERRADO'"
                                        @click="confirmDelete(inv)"
                                        class="text-slate-400 hover:text-red-600 transition-colors p-2 hover:bg-red-50 rounded-lg"
                                        title="Eliminar">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="inventarios.length > 0" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Mostrar</span>
                        <select v-model="perPage"
                            class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 bg-white">
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>por página</span>
                    </div>
                    <div class="text-sm text-slate-600">Página {{ currentPage }} de {{ lastPage }}</div>
                    <div class="flex items-center gap-1">
                        <button @click="fetchInventarios(1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronsLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchInventarios(currentPage - 1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchInventarios(currentPage + 1)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <button @click="fetchInventarios(lastPage)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                            <ChevronsRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Create / Edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800">
                        {{ editingId ? 'Editar Inventario' : 'Nuevo Inventario' }}
                    </h2>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <form @submit.prevent="saveInventario" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Año *</label>
                            <input v-model.number="form.anio" type="number" min="2000" max="2100" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                            <select v-if="editingId" v-model="form.estado"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm bg-white transition-all">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="EN_PROCESO">En Proceso</option>
                                <option value="CERRADO">Cerrado</option>
                            </select>
                            <div v-else class="px-4 py-2.5 border border-slate-100 rounded-xl bg-slate-50 text-sm text-slate-500">
                                Pendiente (inicial)
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nombre *</label>
                        <input v-model="form.nombre" type="text" required maxlength="200"
                            placeholder="Ej: Inventario Anual 2025"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Inicio *</label>
                            <input v-model="form.fecha_inicio" type="date" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Fin</label>
                            <input v-model="form.fecha_fin" type="date"
                                :min="form.fecha_inicio"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Descripción</label>
                        <textarea v-model="form.descripcion" rows="3"
                            placeholder="Observaciones o notas sobre este inventario..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeModal"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="saving" class="w-4 h-4 mr-2 animate-spin" />
                            {{ saving ? 'Guardando...' : (editingId ? 'Guardar cambios' : 'Crear inventario') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import {
    ClipboardList,
    Clock,
    CheckCircle,
    Loader2,
    Plus,
    X,
    Pencil,
    Trash2,
    Play,
    RotateCcw,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

// Stats
const stats = ref({ total: 0, pendientes: 0, en_proceso: 0, cerrados: 0, por_anio: [] });

const fetchStats = async () => {
    try {
        const res = await axios.get('/assets/inventarios/stats');
        stats.value = res.data;
    } catch (e) {
        console.error(e);
    }
};

// Años disponibles para el filtro (año actual ± 5)
const currentYear = new Date().getFullYear();
const availableYears = computed(() => {
    const years = [];
    for (let y = currentYear + 1; y >= 2020; y--) {
        years.push(y);
    }
    return years;
});

// Filters
const filterAnio  = ref('');
const filterEstado = ref('');

const clearFilters = () => {
    filterAnio.value   = '';
    filterEstado.value = '';
    fetchInventarios(1);
};

// Table
const inventarios  = ref([]);
const loading      = ref(false);
const currentPage  = ref(1);
const lastPage     = ref(1);
const total        = ref(0);
const perPage      = ref(15);

const fetchInventarios = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get('/assets/inventarios', {
            params: {
                anio:     filterAnio.value   || undefined,
                estado:   filterEstado.value || undefined,
                per_page: perPage.value,
                page,
            },
        });
        inventarios.value = res.data.data;
        currentPage.value = res.data.current_page;
        lastPage.value    = res.data.last_page;
        total.value       = res.data.total;
    } catch (e) {
        console.error(e);
    } finally {
        loading.value = false;
    }
};

watch([filterAnio, filterEstado], () => fetchInventarios(1));
watch(perPage, () => fetchInventarios(1));

// Modal
const showModal  = ref(false);
const editingId  = ref(null);
const saving     = ref(false);

const emptyForm = () => ({
    anio:        currentYear,
    nombre:      `Inventario Anual ${currentYear}`,
    descripcion: '',
    fecha_inicio: new Date().toISOString().split('T')[0],
    fecha_fin:   '',
    estado:      'PENDIENTE',
});

const form = ref(emptyForm());

const openCreateModal = () => {
    editingId.value = null;
    form.value = emptyForm();
    showModal.value = true;
};

const openEditModal = (inv) => {
    editingId.value  = inv.id;
    form.value = {
        anio:        inv.anio,
        nombre:      inv.nombre,
        descripcion: inv.descripcion || '',
        fecha_inicio: inv.fecha_inicio,
        fecha_fin:   inv.fecha_fin || '',
        estado:      inv.estado,
    };
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingId.value = null;
};

const saveInventario = async () => {
    saving.value = true;
    try {
        const payload = { ...form.value };
        if (!payload.fecha_fin) delete payload.fecha_fin;
        if (!payload.descripcion) delete payload.descripcion;

        if (editingId.value) {
            await axios.put(`/assets/inventarios/${editingId.value}`, payload);
        } else {
            await axios.post('/assets/inventarios', payload);
        }

        Swal.fire({
            icon: 'success',
            title: editingId.value ? 'Inventario actualizado' : 'Inventario creado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2500,
        });

        closeModal();
        fetchInventarios(currentPage.value);
        fetchStats();
    } catch (e) {
        const msg = e.response?.data?.message || 'Ocurrió un error al guardar.';
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    } finally {
        saving.value = false;
    }
};

const changeEstado = async (inv, nuevoEstado) => {
    const labels = { PENDIENTE: 'Pendiente', EN_PROCESO: 'En Proceso', CERRADO: 'Cerrado' };
    const result = await Swal.fire({
        title: `Cambiar a "${labels[nuevoEstado]}"`,
        text: nuevoEstado === 'CERRADO'
            ? 'Se cerrará el inventario. Se registrará la fecha actual como fecha de cierre si no tiene una.'
            : `El inventario pasará al estado "${labels[nuevoEstado]}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado === 'CERRADO' ? '#16a34a' : '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.put(`/assets/inventarios/${inv.id}`, { estado: nuevoEstado });
        Swal.fire({
            icon: 'success',
            title: 'Estado actualizado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
        });
        fetchInventarios(currentPage.value);
        fetchStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'No se pudo actualizar.' });
    }
};

const confirmDelete = async (inv) => {
    const result = await Swal.fire({
        title: '¿Eliminar inventario?',
        html: `<p class="text-sm text-gray-500">Se eliminará permanentemente:</p>
               <p class="font-bold mt-2">${inv.nombre} (${inv.anio})</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (!result.isConfirmed) return;

    try {
        await axios.delete(`/assets/inventarios/${inv.id}`);
        Swal.fire({
            icon: 'success',
            title: 'Inventario eliminado',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 2000,
        });
        fetchInventarios(currentPage.value);
        fetchStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'No se pudo eliminar.' });
    }
};

// Helpers
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
};

const estadoLabel = (estado) => ({
    PENDIENTE: 'Pendiente',
    EN_PROCESO: 'En Proceso',
    CERRADO: 'Cerrado',
}[estado] || estado);

const estadoClass = (estado) => ({
    PENDIENTE:  'bg-blue-100 text-blue-700',
    EN_PROCESO: 'bg-amber-100 text-amber-700',
    CERRADO:    'bg-green-100 text-green-700',
}[estado] || 'bg-slate-100 text-slate-600');

onMounted(() => {
    fetchStats();
    fetchInventarios(1);
});
</script>
