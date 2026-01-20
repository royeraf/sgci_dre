<template>
    <div class="bg-white rounded-xl shadow-md overflow-hidden">


        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Usuario
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            DNI
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Cargo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Rol
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    <tr v-if="loading">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex justify-center items-center space-x-2">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
                                <span class="text-slate-600">Cargando usuarios...</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-else-if="users.length === 0">
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <UserX class="w-12 h-12 text-slate-400 mb-2" />
                                <p class="text-slate-600">No se encontraron usuarios</p>
                            </div>
                        </td>
                    </tr>
                    <tr v-else v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div
                                        class="h-10 w-10 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-bold">
                                        {{ getInitials(user.name, user.apellidos) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-slate-900">
                                        {{ user.titulo ? user.titulo + ' ' : '' }}{{ user.name }}
                                        {{ user.apellidos }}
                                    </div>
                                    <div class="text-sm text-slate-500">{{ user.area || 'Sin área' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ user.dni }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ user.email }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-slate-900">{{ user.cargo || '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <Shield class="w-3 h-3 mr-1" />
                                {{ user.rol_nombre || 'Sin rol' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="[user.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800', 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium']">
                                <span
                                    :class="[user.is_active ? 'bg-emerald-400' : 'bg-slate-400', 'w-2 h-2 rounded-full mr-1.5']"></span>
                                {{ user.is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button @click="$emit('view', user)" title="Ver detalles"
                                    class="text-blue-600 hover:text-blue-900 transition-colors">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button @click="$emit('edit', user)" title="Editar"
                                    class="text-indigo-600 hover:text-indigo-900 transition-colors">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="$emit('toggleStatus', user)"
                                    :title="user.is_active ? 'Desactivar' : 'Activar'"
                                    :class="[user.is_active ? 'text-orange-600 hover:text-orange-900' : 'text-emerald-600 hover:text-emerald-900', 'transition-colors']">
                                    <Power class="w-4 h-4" />
                                </button>
                                <button @click="$emit('resetPassword', user)" title="Cambiar contraseña"
                                    class="text-amber-600 hover:text-amber-900 transition-colors">
                                    <Key class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div v-if="!loading && users.length > 0" class="px-6 py-4 bg-slate-50 border-t border-slate-200">
            <p class="text-sm text-slate-600">
                Mostrando <span class="font-medium">{{ users.length }}</span> usuario(s)
            </p>
        </div>
    </div>
</template>

<script setup>
import { Eye, Edit, Power, Key, Shield, UserX } from 'lucide-vue-next';

defineProps({
    users: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

defineEmits(['view', 'edit', 'toggleStatus', 'resetPassword']);

const getInitials = (name, apellidos) => {
    const n = name?.charAt(0) || '';
    const a = apellidos?.charAt(0) || '';
    return (n + a).toUpperCase() || 'U';
};
</script>
