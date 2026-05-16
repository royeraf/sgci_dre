<template>
    <div class="bg-white overflow-hidden">


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
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-700 uppercase tracking-wider">
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
                    <tr v-else v-for="user in paginatedUsers" :key="user.id" class="hover:bg-slate-50 transition-colors">
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
                            <div class="flex justify-end gap-2">
                                <button @click="$emit('view', user)" title="Ver detalles"
                                    class="cursor-pointer outline-none p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all shadow-sm">
                                    <Eye class="w-4 h-4" />
                                </button>
                                <button @click="$emit('edit', user)" title="Editar"
                                    class="cursor-pointer outline-none p-2 text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-xl transition-all shadow-sm">
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button @click="$emit('toggleStatus', user)"
                                    :title="user.is_active ? 'Desactivar' : 'Activar'"
                                    :class="user.is_active
                                        ? 'cursor-pointer outline-none p-2 text-orange-600 bg-orange-50 hover:bg-orange-100 rounded-xl transition-all shadow-sm'
                                        : 'cursor-pointer outline-none p-2 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 rounded-xl transition-all shadow-sm'">
                                    <Power class="w-4 h-4" />
                                </button>
                                <button @click="$emit('resetPassword', user)" title="Cambiar contraseña"
                                    class="cursor-pointer outline-none p-2 text-amber-600 bg-amber-50 hover:bg-amber-100 rounded-xl transition-all shadow-sm">
                                    <Key class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="!loading && users.length > 0" class="bg-slate-50 px-4 py-4 border-t border-slate-200">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-sm text-slate-600">
                    <span>Mostrar</span>
                    <select :value="perPage"
                        @change="updatePerPage"
                        class="cursor-pointer border-2 border-slate-200 rounded-xl px-3 py-1.5 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white outline-none transition-all">
                        <option :value="10">10</option>
                        <option :value="25">25</option>
                        <option :value="50">50</option>
                    </select>
                    <span>por página</span>
                </div>
                <div class="flex items-center gap-1 flex-wrap justify-center">
                    <!-- Primera página -->
                    <button @click="changePage(1)" :disabled="currentPage === 1"
                        class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <ChevronsLeft class="w-4 h-4" />
                    </button>
                    <!-- Anterior -->
                    <button @click="changePage(currentPage - 1)" :disabled="currentPage === 1"
                        class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <ChevronLeft class="w-4 h-4" />
                    </button>

                    <!-- Números de página -->
                    <template v-for="item in pageWindow" :key="item">
                        <span v-if="item === '...'" class="px-1 text-slate-400 select-none">…</span>
                        <button v-else
                            @click="changePage(item)"
                            :class="[
                                'cursor-pointer min-w-[36px] h-9 px-2 rounded-xl border text-sm font-semibold transition-all',
                                item === currentPage
                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                    : 'border-slate-200 text-slate-600 hover:bg-slate-100'
                            ]">
                            {{ item }}
                        </button>
                    </template>

                    <!-- Siguiente -->
                    <button @click="changePage(currentPage + 1)"
                        :disabled="currentPage === lastPage"
                        class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <!-- Última página -->
                    <button @click="changePage(lastPage)"
                        :disabled="currentPage === lastPage"
                        class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <ChevronsRight class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Eye, Edit, Power, Key, Shield, UserX, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => []
    },
    loading: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['view', 'edit', 'toggleStatus', 'resetPassword']);

const getInitials = (name, apellidos) => {
    const n = name?.charAt(0) || '';
    const a = apellidos?.charAt(0) || '';
    return (n + a).toUpperCase() || 'U';
};

// Pagination Logic
const currentPage = ref(1);
const perPage = ref(10);

watch(() => props.users, () => {
    currentPage.value = 1;
}, { deep: true });

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return props.users.slice(start, end);
});

const lastPage = computed(() => Math.ceil(props.users.length / perPage.value) || 1);

const pageWindow = computed(() => {
    const current = currentPage.value;
    const last = lastPage.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [1];
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
});

const changePage = (page) => {
    if (page >= 1 && page <= lastPage.value) {
        currentPage.value = page;
    }
};

const updatePerPage = (e) => {
    perPage.value = Number(e.target.value);
    currentPage.value = 1;
};
</script>
