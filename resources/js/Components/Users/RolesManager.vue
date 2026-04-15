<script setup lang="ts">
import { onMounted } from 'vue';
import { Plus, Pencil, Trash2, AlertCircle, Shield } from 'lucide-vue-next';
import { useRoles } from '@/Composables/useRoles';
import RoleFormModal from './RoleFormModal.vue';

const {
    roles,
    loading,
    showModal,
    editingRole,
    form,
    submitting,
    fetchRoles,
    openCreateModal,
    openEditModal,
    submit,
    deleteRole,
    toggleStatus,
} = useRoles();

onMounted(() => {
    fetchRoles();
});
</script>

<template>
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Gestionar Roles</h2>
                <p class="text-sm text-slate-500 font-medium">Define los roles y niveles de acceso del sistema</p>
            </div>
            <button @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-md shadow-indigo-200">
                <Plus class="w-4 h-4 mr-2" />
                Nuevo Rol
            </button>
        </div>

        <div class="p-0">
            <!-- Loading -->
            <div v-if="loading" class="p-12 flex flex-col items-center justify-center space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-600"></div>
                <p class="text-slate-500 font-medium animate-pulse">Cargando roles...</p>
            </div>

            <!-- Empty -->
            <div v-else-if="roles.length === 0" class="p-12 text-center">
                <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4">
                    <AlertCircle class="w-10 h-10 text-slate-300" />
                </div>
                <h3 class="text-lg font-bold text-slate-800">No hay roles registrados</h3>
                <p class="text-slate-500 max-w-xs mx-auto mt-2">Comienza creando los roles necesarios para gestionar el acceso de los usuarios.</p>
            </div>

            <!-- Table -->
            <table v-else class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 text-xs font-bold uppercase tracking-wider">
                        <th class="px-6 py-4">Rol</th>
                        <th class="px-6 py-4">Descripción</th>
                        <th class="px-6 py-4 text-center">Nivel</th>
                        <th class="px-6 py-4 text-center">Usuarios</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="role in roles" :key="role.rol_id" class="hover:bg-slate-50/50 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                    <Shield class="w-4 h-4 text-indigo-500" />
                                </div>
                                <div>
                                    <span class="font-bold text-slate-700 block">{{ role.nombre }}</span>
                                    <span class="text-xs text-slate-400">{{ role.rol_id }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ role.descripcion || 'Sin descripción' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold bg-indigo-50 text-indigo-600">
                                {{ role.nivel_acceso }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-bold text-slate-600">{{ role.users_count }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center">
                                <button @click="toggleStatus(role)"
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold transition-all"
                                    :class="role.activo ? 'bg-emerald-50 text-emerald-600 ring-1 ring-emerald-200' : 'bg-rose-50 text-rose-600 ring-1 ring-rose-200'">
                                    <div class="w-1.5 h-1.5 rounded-full mr-2"
                                        :class="role.activo ? 'bg-emerald-500' : 'bg-rose-500'"></div>
                                    {{ role.activo ? 'Activo' : 'Inactivo' }}
                                </button>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button @click="openEditModal(role)"
                                    class="p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-all shadow-sm group-hover:shadow-md"
                                    title="Editar">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="deleteRole(role)"
                                    class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-all shadow-sm group-hover:shadow-md"
                                    :title="role.users_count > 0 ? 'No se puede eliminar: tiene usuarios asignados' : 'Eliminar'">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <RoleFormModal v-model="showModal" :editing-role="editingRole" :form="form" :submitting="submitting" @submit="submit" />
    </div>
</template>
