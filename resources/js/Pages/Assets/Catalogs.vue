<template>
    <MainLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 to-gray-100 p-6">
            <!-- Header -->
            <div class="max-w-7xl mx-auto mb-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                            <Settings class="w-8 h-8 text-slate-600" />
                            Catálogos de Patrimonio
                        </h1>
                        <p class="text-slate-500 mt-1">Administre los catálogos del módulo de bienes patrimoniales</p>
                    </div>
                    <a href="/assets"
                        class="px-4 py-2 bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200 transition-colors flex items-center gap-2 font-medium">
                        <ArrowLeft class="w-4 h-4" />
                        Volver a Bienes
                    </a>
                </div>
            </div>

            <!-- Tabs -->
            <div class="max-w-7xl mx-auto mb-6">
                <div class="flex gap-2 bg-white p-1.5 rounded-xl shadow-sm border border-slate-200 w-fit">
                    <button v-for="tab in tabs" :key="tab.key" @click="activeTab = tab.key" :class="[
                        'px-5 py-2.5 rounded-lg font-semibold text-sm transition-all flex items-center gap-2',
                        activeTab === tab.key
                            ? 'bg-slate-700 text-white shadow-md'
                            : 'text-slate-600 hover:bg-slate-100'
                    ]">
                        <component :is="tab.icon" class="w-4 h-4" />
                        {{ tab.label }}
                        <span :class="[
                            'ml-1 px-2 py-0.5 rounded-full text-xs font-bold',
                            activeTab === tab.key ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-600'
                        ]">
                            {{ getCatalogCount(tab.key) }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                    <!-- Toolbar -->
                    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                <input v-model="searchQuery" type="text" placeholder="Buscar..."
                                    class="pl-10 pr-4 py-2 rounded-lg border border-slate-200 focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none text-sm w-64" />
                            </div>
                        </div>
                        <button @click="openModal('create')"
                            class="px-4 py-2 bg-gradient-to-r from-slate-700 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-800 hover:to-gray-800 transition-all shadow-md flex items-center gap-2">
                            <Plus class="w-4 h-4" />
                            Agregar {{ currentTabLabel }}
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Nombre
                                    </th>
                                    <th v-if="activeTab === 'colors'"
                                        class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Color
                                    </th>
                                    <th v-if="activeTab === 'states'"
                                        class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Orden
                                    </th>
                                    <th v-if="['states', 'origins'].includes(activeTab)"
                                        class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Descripción
                                    </th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th
                                        class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="item in filteredItems" :key="item.id"
                                    class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="font-semibold text-slate-800">{{ item.nombre }}</span>
                                    </td>
                                    <td v-if="activeTab === 'colors'" class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <div v-if="item.codigo_hex"
                                                class="w-6 h-6 rounded-md border border-slate-200"
                                                :style="{ backgroundColor: item.codigo_hex }"></div>
                                            <span class="text-sm text-slate-500">{{ item.codigo_hex || 'N/A' }}</span>
                                        </div>
                                    </td>
                                    <td v-if="activeTab === 'states'" class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-slate-200 text-slate-700 font-bold text-sm">
                                            {{ item.orden }}
                                        </span>
                                    </td>
                                    <td v-if="['states', 'origins'].includes(activeTab)" class="px-6 py-4">
                                        <p class="text-sm text-slate-500 line-clamp-2 max-w-xs">
                                            {{ item.descripcion || '-' }}
                                        </p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span v-if="item.activo !== undefined" :class="[
                                            'px-3 py-1 rounded-full text-xs font-bold',
                                            item.activo
                                                ? 'bg-emerald-100 text-emerald-700'
                                                : 'bg-red-100 text-red-700'
                                        ]">
                                            {{ item.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                        <span v-else class="text-slate-400">-</span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="openModal('edit', item)"
                                                class="p-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                                                title="Editar">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="confirmDelete(item)"
                                                class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Eliminar">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="filteredItems.length === 0">
                                    <td :colspan="getColSpan()" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <Package class="w-12 h-12 text-slate-300" />
                                            <p class="text-slate-500 font-medium">No se encontraron registros</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <Teleport to="body">
                <div v-if="showModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-modal-in">
                        <!-- Modal Header -->
                        <div class="bg-gradient-to-r from-slate-700 to-gray-700 px-6 py-4">
                            <h3 class="text-lg font-bold text-white">
                                {{ modalMode === 'create' ? 'Agregar' : 'Editar' }} {{ currentTabLabel }}
                            </h3>
                        </div>

                        <!-- Modal Body -->
                        <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Nombre <span class="text-red-500">*</span>
                                </label>
                                <input v-model="formData.nombre" type="text" required
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none transition-colors"
                                    placeholder="Ingrese el nombre" />
                            </div>

                            <div v-if="activeTab === 'colors'">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Código Hexadecimal
                                </label>
                                <div class="flex items-center gap-3">
                                    <input v-model="formData.codigo_hex" type="color"
                                        class="w-12 h-12 rounded-lg border border-slate-200 cursor-pointer" />
                                    <input v-model="formData.codigo_hex" type="text" maxlength="7"
                                        class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none transition-colors"
                                        placeholder="#000000" />
                                </div>
                            </div>

                            <div v-if="activeTab === 'states'">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Orden
                                </label>
                                <input v-model.number="formData.orden" type="number" min="0"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none transition-colors"
                                    placeholder="0" />
                            </div>

                            <div v-if="['states', 'origins'].includes(activeTab)">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Descripción
                                </label>
                                <textarea v-model="formData.descripcion" rows="3"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 outline-none transition-colors resize-none"
                                    placeholder="Descripción opcional..."></textarea>
                            </div>

                            <div v-if="activeTab !== 'categories'" class="flex items-center gap-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input v-model="formData.activo" type="checkbox" class="sr-only peer" />
                                    <div
                                        class="w-11 h-6 bg-slate-200 peer-focus:ring-2 peer-focus:ring-slate-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500">
                                    </div>
                                </label>
                                <span class="text-sm font-medium text-slate-700">Activo</span>
                            </div>

                            <div v-if="formError"
                                class="p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                                {{ formError }}
                            </div>

                            <!-- Modal Footer -->
                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                                <button type="button" @click="closeModal"
                                    class="px-5 py-2.5 border-2 border-slate-300 text-slate-600 font-semibold rounded-xl hover:bg-slate-50 transition-all">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-5 py-2.5 bg-gradient-to-r from-slate-700 to-gray-700 text-white font-semibold rounded-xl hover:from-slate-800 hover:to-gray-800 transition-all disabled:opacity-50 shadow-md flex items-center gap-2">
                                    <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                                    {{ isSubmitting ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- Delete Confirmation Modal -->
            <Teleport to="body">
                <div v-if="showDeleteModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden animate-modal-in">
                        <div class="p-6 text-center">
                            <div
                                class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <AlertTriangle class="w-8 h-8 text-red-500" />
                            </div>
                            <h3 class="text-xl font-bold text-slate-800 mb-2">¿Eliminar registro?</h3>
                            <p class="text-slate-500 mb-6">
                                ¿Está seguro de eliminar <strong>{{ itemToDelete?.nombre }}</strong>?
                                Esta acción no se puede deshacer.
                            </p>
                            <div v-if="deleteError"
                                class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-sm">
                                {{ deleteError }}
                            </div>
                            <div class="flex justify-center gap-3">
                                <button @click="closeDeleteModal"
                                    class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-semibold rounded-xl hover:bg-slate-50 transition-all">
                                    Cancelar
                                </button>
                                <button @click="handleDelete" :disabled="isDeleting"
                                    class="px-6 py-2.5 bg-red-500 text-white font-semibold rounded-xl hover:bg-red-600 transition-all disabled:opacity-50 flex items-center gap-2">
                                    <Loader2 v-if="isDeleting" class="w-4 h-4 animate-spin" />
                                    {{ isDeleting ? 'Eliminando...' : 'Eliminar' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import {
    Settings,
    ArrowLeft,
    Search,
    Plus,
    Pencil,
    Trash2,
    Package,
    Loader2,
    AlertTriangle,
    Tag,
    Palette,
    Activity,
    MapPin,
    Layers
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    brands: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    states: { type: Array, default: () => [] },
    origins: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

// State
const activeTab = ref('brands');
const searchQuery = ref('');
const showModal = ref(false);
const modalMode = ref('create');
const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const isSubmitting = ref(false);
const isDeleting = ref(false);
const formError = ref('');
const deleteError = ref('');

const formData = ref({
    nombre: '',
    codigo_hex: '#000000',
    descripcion: '',
    orden: 0,
    activo: true,
});

// Local data copies
const localBrands = ref([...props.brands]);
const localColors = ref([...props.colors]);
const localStates = ref([...props.states]);
const localOrigins = ref([...props.origins]);
const localCategories = ref([...props.categories]);

const tabs = [
    { key: 'brands', label: 'Marcas', icon: Tag },
    { key: 'colors', label: 'Colores', icon: Palette },
    { key: 'states', label: 'Estados', icon: Activity },
    { key: 'origins', label: 'Orígenes', icon: MapPin },
    { key: 'categories', label: 'Categorías', icon: Layers },
];

const currentTabLabel = computed(() => {
    const tab = tabs.find(t => t.key === activeTab.value);
    return tab ? tab.label.slice(0, -1) : ''; // Remove plural 's'
});

const currentItems = computed(() => {
    switch (activeTab.value) {
        case 'brands': return localBrands.value;
        case 'colors': return localColors.value;
        case 'states': return localStates.value;
        case 'origins': return localOrigins.value;
        case 'categories': return localCategories.value;
        default: return [];
    }
});

const filteredItems = computed(() => {
    if (!searchQuery.value) return currentItems.value;
    const query = searchQuery.value.toLowerCase();
    return currentItems.value.filter(item =>
        item.nombre.toLowerCase().includes(query)
    );
});

const getCatalogCount = (key) => {
    switch (key) {
        case 'brands': return localBrands.value.length;
        case 'colors': return localColors.value.length;
        case 'states': return localStates.value.length;
        case 'origins': return localOrigins.value.length;
        case 'categories': return localCategories.value.length;
        default: return 0;
    }
};

const getColSpan = () => {
    let cols = 3; // nombre, estado, acciones
    if (activeTab.value === 'colors') cols++;
    if (activeTab.value === 'states') cols += 2;
    if (activeTab.value === 'origins') cols++;
    return cols;
};

const getApiEndpoint = () => {
    return `/assets/catalogs/${activeTab.value}`;
};

const openModal = (mode, item = null) => {
    modalMode.value = mode;
    formError.value = '';

    if (mode === 'edit' && item) {
        formData.value = { ...item };
    } else {
        formData.value = {
            nombre: '',
            codigo_hex: '#000000',
            descripcion: '',
            orden: currentItems.value.length + 1,
            activo: true,
        };
    }

    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    formError.value = '';
};

const handleSubmit = async () => {
    isSubmitting.value = true;
    formError.value = '';

    try {
        const endpoint = getApiEndpoint();
        let response;

        if (modalMode.value === 'create') {
            response = await axios.post(endpoint, formData.value);
            addToLocalList(response.data);
        } else {
            response = await axios.put(`${endpoint}/${formData.value.id}`, formData.value);
            updateInLocalList(response.data);
        }

        closeModal();
    } catch (error) {
        formError.value = error.response?.data?.message || error.response?.data?.error || 'Error al guardar';
        if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            formError.value = errors.join(', ');
        }
    } finally {
        isSubmitting.value = false;
    }
};

const confirmDelete = (item) => {
    itemToDelete.value = item;
    deleteError.value = '';
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    itemToDelete.value = null;
    deleteError.value = '';
};

const handleDelete = async () => {
    if (!itemToDelete.value) return;

    isDeleting.value = true;
    deleteError.value = '';

    try {
        await axios.delete(`${getApiEndpoint()}/${itemToDelete.value.id}`);
        removeFromLocalList(itemToDelete.value.id);
        closeDeleteModal();
    } catch (error) {
        deleteError.value = error.response?.data?.error || 'Error al eliminar';
    } finally {
        isDeleting.value = false;
    }
};

const addToLocalList = (item) => {
    switch (activeTab.value) {
        case 'brands': localBrands.value.push(item); break;
        case 'colors': localColors.value.push(item); break;
        case 'states': localStates.value.push(item); break;
        case 'origins': localOrigins.value.push(item); break;
        case 'categories': localCategories.value.push(item); break;
    }
};

const updateInLocalList = (item) => {
    const list = getCurrentLocalList();
    const index = list.findIndex(i => i.id === item.id);
    if (index !== -1) {
        list[index] = item;
    }
};

const removeFromLocalList = (id) => {
    const list = getCurrentLocalList();
    const index = list.findIndex(i => i.id === id);
    if (index !== -1) {
        list.splice(index, 1);
    }
};

const getCurrentLocalList = () => {
    switch (activeTab.value) {
        case 'brands': return localBrands.value;
        case 'colors': return localColors.value;
        case 'states': return localStates.value;
        case 'origins': return localOrigins.value;
        case 'categories': return localCategories.value;
        default: return [];
    }
};
</script>

<style scoped>
@keyframes modal-in {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-10px);
    }

    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.animate-modal-in {
    animation: modal-in 0.2s ease-out;
}
</style>
