<script setup lang="ts">
import { onMounted } from 'vue';
import {
    Plus,
    Pencil,
    Trash2,
    AlertCircle,
    Settings
} from 'lucide-vue-next';
import { useVisitReasons } from '@/Composables/useVisitReasons';
import ReasonFormModal from './ReasonFormModal.vue';

const {
    reasons,
    loading,
    showModal,
    editingReason,
    form,
    fetchReasons,
    openCreateModal,
    openEditModal,
    submit,
    deleteReason,
    toggleStatus
} = useVisitReasons();

onMounted(() => {
    fetchReasons();
});
</script>

<template>
    <div class="bg-white shadow-lg rounded-2xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-slate-800">Gestionar Motivos de Visita</h2>
                <p class="text-xs text-slate-500 font-medium">Define los motivos predeterminados para el registro de visitas</p>
            </div>
            <button @click="openCreateModal"
                class="cursor-pointer inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-sm font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-lg shadow-purple-200 active:scale-95">
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

            <table v-else class="w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Descripción</th>
                        <th class="px-4 py-3 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100">
                    <tr v-for="reason in reasons" :key="reason.id" class="hover:bg-purple-50 transition-colors duration-200 group">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700">{{ reason.nombre }}</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ reason.descripcion || 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center">
                                <button @click="toggleStatus(reason)"
                                    class="cursor-pointer inline-flex items-center px-3 py-1 rounded-full text-xs font-bold transition-all"
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
                                    class="cursor-pointer p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-all shadow-sm group-hover:shadow-md active:scale-95"
                                    title="Editar">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="deleteReason(reason)"
                                    class="cursor-pointer p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition-all shadow-sm group-hover:shadow-md active:scale-95"
                                    title="Eliminar">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Componente Modal Descompuesto -->
        <ReasonFormModal v-model="showModal" :editing-reason="editingReason" :form="form" @submit="submit" />
    </div>
</template>
