<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    Check,
    X,
    AlertCircle,
    CheckCircle2,
    ToggleLeft,
    ToggleRight,
    Settings,
    Loader2
} from 'lucide-vue-next';
import axios from 'axios';

interface VisitReason {
    id: string;
    nombre: string;
    descripcion: string | null;
    is_active: boolean;
}

declare global {
    interface Window {
        Swal: any;
    }
}

const reasons = ref<VisitReason[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingReason = ref<VisitReason | null>(null);

const form = useForm({
    nombre: '',
    descripcion: '',
    is_active: true
});

const modalTitle = computed(() => editingReason.value ? 'Editar Motivo' : 'Nuevo Motivo');
const modalMessage = computed(() => editingReason.value ? 'Modifique los datos del motivo' : 'Defina un nuevo motivo para las visitas');
const statusMessage = computed(() => form.is_active ? 'Visible en el registro' : 'Oculto en el registro');

const fetchReasons = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/visitors/reasons/list');
        reasons.value = response.data;
    } catch (error) {
        console.error('Error fetching reasons:', error);
    } finally {
        loading.value = false;
    }
};

const openCreateModal = () => {
    editingReason.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    showModal.value = true;
};

const openEditModal = (reason: VisitReason) => {
    editingReason.value = reason;
    form.nombre = reason.nombre;
    form.descripcion = reason.descripcion || '';
    form.is_active = reason.is_active;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editingReason.value) {
        form.put(`/visitors/reasons/${editingReason.value.id}`, {
            onSuccess: () => {
                showModal.value = false;
                fetchReasons();
            }
        });
    } else {
        form.post('/visitors/reasons', {
            onSuccess: () => {
                showModal.value = false;
                fetchReasons();
            }
        });
    }
};

const deleteReason = (reason: VisitReason) => {
    window.Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#7c3aed',
        cancelButtonColor: '#ef4444',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result: any) => {
        if (result.isConfirmed) {
            axios.delete(`/visitors/reasons/${reason.id}`)
                .then(() => {
                    window.Swal.fire(
                        '¡Eliminado!',
                        'El motivo ha sido eliminado.',
                        'success'
                    );
                    fetchReasons();
                })
                .catch((error) => {
                    window.Swal.fire(
                        'Error',
                        error.response?.data?.error || 'No se pudo eliminar el motivo.',
                        'error'
                    );
                });
        }
    });
};

const toggleStatus = (reason: VisitReason) => {
    axios.put(`/visitors/reasons/${reason.id}`, {
        nombre: reason.nombre,
        descripcion: reason.descripcion,
        is_active: !reason.is_active
    }).then(() => {
        fetchReasons();
    });
};

onMounted(() => {
    fetchReasons();
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Gestionar Motivos de Visita</h2>
                <p class="text-sm text-slate-500 font-medium">Define los motivos predeterminados para el registro de
                    visitas</p>
            </div>
            <button @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-sm font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-md shadow-purple-200">
                <Plus class="w-4 h-4 mr-2" />
                Nuevo Motivo
            </button>
        </div>

        <div class="p-0">
            <div v-if="loading" class="p-12 flex flex-col items-center justify-center space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600"></div>
                <p class="text-slate-500 font-medium animate-pulse">Cargando motivos...</p>
            </div>

            <div v-else-if="reasons.length === 0" class="p-12 text-center">
                <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <AlertCircle class="w-10 h-10 text-slate-300" />
                </div>
                <h3 class="text-lg font-bold text-slate-800">No hay motivos registrados</h3>
                <p class="text-slate-500 max-w-xs mx-auto mt-2">Comienza agregando los motivos más comunes para
                    facilitar el registro de visitas.</p>
            </div>

            <table v-else class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4">Nombre</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="reason in reasons" :key="reason.id" class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700">{{ reason.nombre }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ reason.descripcion || 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center">
                                <button @click="toggleStatus(reason)"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold transition-all"
                                    :class="reason.is_active ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-600 ring-1 ring-rose-200'">
                                    <div class="w-1.5 h-1.5 rounded-full mr-2"
                                        :class="reason.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                                    {{ reason.is_active ? 'Activo' : 'Inactivo' }}
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="openEditModal(reason)"
                                    class="p-2 text-slate-400 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-all"
                                    title="Editar">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="deleteReason(reason)"
                                    class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all"
                                    title="Eliminar">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showModal = false"></div>
            <div
                class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Settings class="w-6 h-6" />
                            {{ modalTitle }}
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">{{ modalMessage }}</p>
                    </div>
                    <button @click="showModal = false"
                        class="bg-white/10 rounded-md p-2 inline-flex items-center justify-center text-white hover:text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white/50 transition-colors">
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Nombre del Motivo</label>
                        <input v-model="form.nombre" type="text"
                            class="w-full px-4 py-3 rounded-xl border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-medium text-slate-600 outline-none"
                            placeholder="Ej. Trámites de Títulos"
                            :class="{ 'border-rose-400 bg-rose-50': form.errors.nombre }" />
                        <p v-if="form.errors.nombre"
                            class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                            <AlertCircle class="w-4 h-4" /> {{ form.errors.nombre }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Descripción (Opcional)</label>
                        <textarea v-model="form.descripcion" rows="3"
                            class="w-full px-4 py-3 rounded-xl border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-medium text-slate-600 resize-none outline-none"
                            placeholder="Agregue una breve descripción sobre este motivo..."></textarea>
                    </div>

                    <div v-if="editingReason"
                        class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                        <div class="flex flex-col">
                            <span class="text-sm font-bold text-slate-700">Estado del Motivo</span>
                            <span class="text-xs text-slate-500">{{ statusMessage }}</span>
                        </div>
                        <button type="button" @click="form.is_active = !form.is_active"
                            class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                            :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'">
                            <span
                                class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" />
                        </button>
                    </div>

                    <div class="flex gap-3 pt-4 border-t border-slate-100">
                        <button type="button" @click="showModal = false"
                            class="flex-1 px-4 py-3 border-2 border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-sm font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-lg shadow-purple-200 disabled:opacity-50 active:scale-95 flex items-center justify-center gap-2">
                            <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                            <span>{{ editingReason ? 'Actualizar' : 'Guardar Motivo' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
