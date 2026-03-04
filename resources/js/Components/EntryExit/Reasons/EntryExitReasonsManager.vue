<script setup lang="ts">
import { onMounted } from 'vue';
import { Plus, Pencil, Trash2, AlertCircle } from 'lucide-vue-next';
import { useEntryExitReasons, type EntryExitReason } from '@/Composables/useEntryExitReasons';
import ExitReasonFormModal from './ExitReasonFormModal.vue';

const {
    reasons, loading, showModal, editingReason, form,
    fetchReasons, openCreateModal, openEditModal, submit, deleteReason, toggleStatus
} = useEntryExitReasons();

const tipoLabels: Record<string, string> = { comision: 'Comisión', permiso: 'Permiso', ambos: 'Ambos' };
const tipoClasses: Record<string, string> = {
    comision: 'bg-purple-50 text-purple-700 border-purple-100',
    permiso:  'bg-teal-50 text-teal-700 border-teal-100',
    ambos:    'bg-slate-100 text-slate-600 border-slate-200',
};

onMounted(fetchReasons);
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Gestionar Motivos de Salida</h2>
                <p class="text-sm text-slate-500 font-medium">Define los motivos predeterminados para papeletas de salida</p>
            </div>
            <button @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all shadow-md shadow-emerald-200">
                <Plus class="w-4 h-4 mr-2" />
                Nuevo Motivo
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="p-12 flex flex-col items-center justify-center space-y-4">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-600"></div>
            <p class="text-slate-500 font-medium animate-pulse">Cargando motivos...</p>
        </div>

        <!-- Empty -->
        <div v-else-if="reasons.length === 0" class="p-12 text-center">
            <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                <AlertCircle class="w-10 h-10 text-slate-300" />
            </div>
            <h3 class="text-lg font-bold text-slate-800">No hay motivos registrados</h3>
            <p class="text-slate-500 max-w-xs mx-auto mt-2">Agrega los motivos más comunes para agilizar el registro de papeletas.</p>
        </div>

        <!-- Table -->
        <table v-else class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                    <th class="px-6 py-4">Nombre</th>
                    <th class="px-6 py-4">Descripción</th>
                    <th class="px-6 py-4 text-center">Aplica para</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr v-for="reason in reasons" :key="reason.id"
                    class="hover:bg-slate-50/50 transition-colors group">
                    <td class="px-6 py-4">
                        <span class="font-bold text-slate-700">{{ reason.nombre }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-500">
                        {{ reason.descripcion || 'Sin descripción' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold border"
                            :class="tipoClasses[reason.tipo] || tipoClasses.ambos">
                            {{ tipoLabels[reason.tipo] || reason.tipo }}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex justify-center">
                            <button @click="toggleStatus(reason)"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold transition-all"
                                :class="reason.is_active
                                    ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200'
                                    : 'bg-rose-50 text-rose-600 ring-1 ring-rose-200'">
                                <div class="w-1.5 h-1.5 rounded-full mr-2"
                                    :class="reason.is_active ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                                {{ reason.is_active ? 'Activo' : 'Inactivo' }}
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <button @click="openEditModal(reason)"
                                class="p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-all shadow-sm group-hover:shadow-md"
                                title="Editar">
                                <Pencil class="w-4 h-4" />
                            </button>
                            <button @click="deleteReason(reason)"
                                class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-all shadow-sm group-hover:shadow-md"
                                title="Eliminar">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>

        <ExitReasonFormModal v-model="showModal" :editing-reason="editingReason" :form="form" @submit="submit" />
    </div>
</template>
