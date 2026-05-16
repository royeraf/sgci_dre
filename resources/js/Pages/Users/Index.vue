<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Gestión de Usuarios
                    </h1>
                    <p class="mt-2 text-slate-600">Administración de usuarios del sistema y sus roles</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button @click="activeTab = 'usuarios'" :class="[
                        activeTab === 'usuarios'
                            ? 'text-indigo-600 active-tab'
                            : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Users class="w-5 h-5" />
                        Usuarios
                    </button>
                    <button @click="activeTab = 'roles'" :class="[
                        activeTab === 'roles'
                            ? 'text-purple-600 active-tab'
                            : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Shield class="w-5 h-5" />
                        Roles
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">
                    <!-- Tab: Usuarios -->
                    <template v-if="activeTab === 'usuarios'">
                        <!-- Unified Card Container -->
                        <div class="bg-white shadow-md rounded-2xl border border-slate-200 overflow-hidden">
                            <!-- Table Title & Sub-tabs -->
                            <div class="p-5 sm:p-6 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h2 class="text-lg font-bold text-slate-800">Listado de Usuarios</h2>
                                    <p class="text-sm text-slate-500 font-medium mt-1">Gestión y control de cuentas de acceso</p>
                                </div>
                                <!-- Sub-tabs: Todos / Activos / Inactivos -->
                                <div class="flex items-center gap-1 bg-indigo-50 rounded-full p-1.5 w-fit shrink-0">
                                    <button
                                        v-for="tab in statusTabs" :key="tab.key"
                                        @click="activeStatusTab = tab.key"
                                        :class="[
                                            activeStatusTab === tab.key
                                                ? 'bg-white text-indigo-900 shadow-md'
                                                : 'text-indigo-600 hover:text-indigo-800',
                                            'cursor-pointer px-4 py-2 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-2 outline-none'
                                        ]">
                                        {{ tab.label }}
                                        <span :class="[
                                            activeStatusTab === tab.key
                                                ? 'bg-indigo-500 text-white'
                                                : 'bg-indigo-200 text-indigo-700',
                                            'text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center transition-colors'
                                        ]">{{ tab.count }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Top row: Actions -->
                            <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-end gap-4 bg-white">
                                <div class="flex items-center justify-end gap-3 shrink-0">
                                    <button
                                        @click="filtersVisible = !filtersVisible"
                                        class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm"
                                    >
                                        <SlidersHorizontal class="w-4 h-4" />
                                        Filtros
                                        <ChevronDown
                                            class="w-4 h-4 transition-transform duration-300"
                                            :class="{ 'rotate-180': filtersVisible }"
                                        />
                                    </button>

                                    <button @click="createNewUser"
                                        class="cursor-pointer outline-none active:scale-95 inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-indigo-500/30 text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 hover:-translate-y-0.5">
                                        <UserPlus class="w-4 h-4 mr-2" />
                                        Nuevo Usuario
                                    </button>
                                </div>
                            </div>

                            <!-- Filters toggle + collapsible panel -->
                            <div
                                class="filters-collapse bg-slate-50 border-b border-slate-100"
                                :class="{ 'filters-collapse--open': filtersVisible }"
                            >
                                <div class="p-4 sm:p-5">
                                    <UserFilters :filters="localFilters" :result-count="filteredUsers.length" :roles="roles" :areas="areas"
                                        :positions="positions" @update:filters="localFilters = $event" @clear="clearFilters" />
                                </div>
                            </div>

                            <!-- Users Table -->
                            <UserTable :users="filteredUsers" :loading="isLoading" @view="viewUser" @edit="editUser"
                                @toggle-status="toggleUserStatus" @reset-password="resetUserPassword" @delete="handleDeleteUser" />
                        </div>
                    </template>

                    <!-- Tab: Roles -->
                    <template v-if="activeTab === 'roles'">
                        <RolesManager />
                    </template>
                </div>
            </Transition>

            <!-- Modals -->
            <UserModal v-if="showUserModal" :user="selectedUser" :is-editing="isEditing" :submitting="isSubmitting"
                :roles="roles" :areas="areas" :positions="positions" :employees="employees" @close="closeUserModal"
                @submit="saveUser" />

            <UserDetailModal v-if="showViewUserModal" :user="selectedUser" @close="closeViewUserModal"
                @edit="editFromView" />

            <PasswordModal v-if="showPasswordModal" :user="selectedUser" :submitting="isSubmitting"
                @close="closePasswordModal" @submit="savePassword" />
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup>
import { ref, onMounted, computed, nextTick, watch } from 'vue';
import axios from 'axios';
import { UserPlus, Users, Shield, SlidersHorizontal, ChevronDown } from 'lucide-vue-next';

// Components
import SummaryCards from '@/Components/Users/SummaryCards.vue';
import UserFilters from '@/Components/Users/UserFilters.vue';
import UserTable from '@/Components/Users/UserTable.vue';
import UserModal from '@/Components/Users/UserModal.vue';
import UserDetailModal from '@/Components/Users/UserDetailModal.vue';
import PasswordModal from '@/Components/Users/PasswordModal.vue';
import RolesManager from '@/Components/Users/RolesManager.vue';

const activeTab = ref('usuarios');
const activeStatusTab = ref('todos');

const isLoading = ref(false);
const isSubmitting = ref(false);
const localFilters = ref({
    search: '',
    role: '',
    area: '',
    position: '',
    status: ''
});

const FILTERS_STORAGE_KEY = 'users_filters_open';
const filtersVisible = ref(
    localStorage.getItem(FILTERS_STORAGE_KEY) === 'true'
);
watch(filtersVisible, (val) => localStorage.setItem(FILTERS_STORAGE_KEY, String(val)));

const users = ref([]);
const roles = ref([]);
const areas = ref([]);
const positions = ref([]);
const employees = ref([]);
const summary = ref({});

const showUserModal = ref(false);
const showViewUserModal = ref(false);
const showPasswordModal = ref(false);

const selectedUser = ref(null);
const isEditing = ref(false);

const isUserActive = (u) => u.is_active == 1 || u.is_active === true || u.is_active === 'true';

const statusTabs = computed(() => [
    { key: 'todos', label: 'Todos', count: users.value.length },
    { key: 'activos', label: 'Activos', count: users.value.filter(u => isUserActive(u)).length },
    { key: 'inactivos', label: 'Inactivos', count: users.value.filter(u => !isUserActive(u)).length },
]);

const filteredUsers = computed(() => {
    let result = [...users.value];

    // Sub-tab status filter
    if (activeStatusTab.value === 'activos') {
        result = result.filter(u => isUserActive(u));
    } else if (activeStatusTab.value === 'inactivos') {
        result = result.filter(u => !isUserActive(u));
    }

    // Search filter
    if (localFilters.value.search) {
        const q = localFilters.value.search.toLowerCase();
        result = result.filter(u =>
            (u.full_name || '').toLowerCase().includes(q) ||
            (u.name + ' ' + (u.apellidos || '')).toLowerCase().includes(q) ||
            String(u.dni).includes(q) ||
            (u.email || '').toLowerCase().includes(q) ||
            (u.cargo || '').toLowerCase().includes(q) ||
            (u.area || '').toLowerCase().includes(q) ||
            (u.rol_nombre || '').toLowerCase().includes(q)
        );
    }

    // Role filter
    if (localFilters.value.role) {
        result = result.filter(u => u.rol_id == localFilters.value.role);
    }

    // Area filter
    if (localFilters.value.area) {
        const selectedArea = areas.value.find(a => a.id == localFilters.value.area);
        if (selectedArea) {
            const filterName = selectedArea.nombre.trim().toLowerCase();
            result = result.filter(u => u.area && u.area.trim().toLowerCase() === filterName);
        }
    }

    // Position filter
    if (localFilters.value.position) {
        const selectedPosition = positions.value.find(p => p.id == localFilters.value.position);
        if (selectedPosition) {
            const filterName = selectedPosition.nombre.trim().toLowerCase();
            result = result.filter(u => u.cargo && u.cargo.trim().toLowerCase() === filterName);
        }
    }

    // Status filter (from UserFilters panel)
    if (localFilters.value.status) {
        const shouldBeActive = localFilters.value.status === 'active';
        result = result.filter(u => isUserActive(u) === shouldBeActive);
    }

    return result;
});

const clearFilters = () => {
    localFilters.value = {
        search: '',
        role: '',
        area: '',
        position: '',
        status: ''
    };
};

const fetchData = async () => {
    isLoading.value = true;
    try {
        const [usersRes, rolesRes, areasRes, positionsRes, summaryRes, empRes] = await Promise.all([
            axios.get('/users/list'),
            axios.get('/users/roles'),
            axios.get('/users/areas'),
            axios.get('/users/positions'),
            axios.get('/users/summary'),
            axios.get('/hr/employees')
        ]);
        users.value = usersRes.data;
        roles.value = rolesRes.data;
        areas.value = areasRes.data;
        positions.value = positionsRes.data;
        summary.value = summaryRes.data;
        employees.value = empRes.data;
    } catch (error) {
        console.error('Error fetching users data:', error);
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los datos de usuarios'
        });
    } finally {
        isLoading.value = false;
    }
};

const createNewUser = () => {
    isEditing.value = false;
    selectedUser.value = null;
    showUserModal.value = true;
};

const closeUserModal = () => {
    showUserModal.value = false;
    selectedUser.value = null;
    isEditing.value = false;
};

const saveUser = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value && selectedUser.value) {
            await axios.put(`/users/${selectedUser.value.id}`, formData);
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Usuario Actualizado',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        } else {
            await axios.post('/users', formData);
            window.Swal?.fire?.({
                icon: 'success',
                title: 'Usuario Creado',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000
            });
        }
        closeUserModal();
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo guardar el usuario'
        });
    } finally {
        isSubmitting.value = false;
    }
};

const viewUser = (user) => {
    selectedUser.value = user;
    showViewUserModal.value = true;
};

const editUser = (user) => {
    isEditing.value = true;
    selectedUser.value = user;
    showUserModal.value = true;
};

const editFromView = (user) => {
    closeViewUserModal();
    editUser(user);
};

const closeViewUserModal = () => {
    showViewUserModal.value = false;
    selectedUser.value = null;
};

const toggleUserStatus = async (user) => {
    try {
        const result = await window.Swal?.fire?.({
            title: user.is_active ? '¿Desactivar usuario?' : '¿Activar usuario?',
            text: user.is_active
                ? 'El usuario no podrá iniciar sesión'
                : 'El usuario podrá iniciar sesión nuevamente',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });

        if (result.isConfirmed) {
            await axios.patch(`/users/${user.id}/toggle-status`);
            window.Swal?.fire?.({
                icon: 'success',
                title: user.is_active ? 'Usuario Desactivado' : 'Usuario Activado',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500
            });
            fetchData();
        }
    } catch (error) {
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo actualizar el estado del usuario'
        });
    }
};

const resetUserPassword = (user) => {
    selectedUser.value = user;
    showPasswordModal.value = true;
};

const closePasswordModal = () => {
    showPasswordModal.value = false;
    selectedUser.value = null;
};

const savePassword = async (formData) => {
    isSubmitting.value = true;
    try {
        await axios.patch(`/users/${selectedUser.value.id}/password`, formData);
        window.Swal?.fire?.({
            icon: 'success',
            title: 'Contraseña Actualizada',
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
        closePasswordModal();
    } catch (error) {
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: error.response?.data?.message || 'No se pudo actualizar la contraseña'
        });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteUser = (id) => {
    window.Swal?.fire?.({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/users/${id}`);
                window.Swal?.fire?.('Eliminado', 'El usuario ha sido eliminado.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo eliminar el usuario.', 'error');
            }
        }
    });
};

onMounted(() => {
    fetchData();
    nextTick(updateIndicator);
});

watch(activeTab, () => {
    nextTick(updateIndicator);
});

// Tab indicator logic
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    switch (tab) {
        case 'usuarios': return '#4f46e5'; // indigo-600
        case 'roles': return '#9333ea'; // purple-600
        default: return '#4f46e5';
    }
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const activeBtn = tabsRef.value.querySelector('.active-tab');
    if (activeBtn) {
        indicatorStyle.value = {
            left: `${activeBtn.offsetLeft}px`,
            width: `${activeBtn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value)
        };
    }
};
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

/* Filters collapse animation */
.filters-collapse {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height 0.35s ease, opacity 0.3s ease;
}

.filters-collapse--open {
    max-height: 500px;
    opacity: 1;
}
</style>
