<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">
                        Gestión de Usuarios
                    </h1>
                    <p class="mt-2 text-slate-600">Administración de usuarios del sistema y sus roles</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button @click="createNewUser"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 transition-all">
                        <UserPlus class="w-5 h-5 mr-2" />
                        Nuevo Usuario
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Filters -->
            <UserFilters :filters="localFilters" :result-count="filteredUsers.length" :roles="roles" :areas="areas"
                :positions="positions" @update:filters="localFilters = $event" @clear="clearFilters" />

            <!-- Users Table -->
            <UserTable :users="filteredUsers" :loading="isLoading" @view="viewUser" @edit="editUser"
                @toggle-status="toggleUserStatus" @reset-password="resetUserPassword" @delete="handleDeleteUser" />

            <!-- User Modal -->
            <UserModal v-if="showUserModal" :user="selectedUser" :is-editing="isEditing" :submitting="isSubmitting"
                :roles="roles" :areas="areas" :positions="positions" :employees="employees" @close="closeUserModal"
                @submit="saveUser" />

            <!-- User Detail Modal -->
            <UserDetailModal v-if="showViewUserModal" :user="selectedUser" @close="closeViewUserModal"
                @edit="editFromView" />

            <!-- Password Reset Modal -->
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { UserPlus } from 'lucide-vue-next';

// Components
import SummaryCards from '@/Components/Users/SummaryCards.vue';
import UserFilters from '@/Components/Users/UserFilters.vue';
import UserTable from '@/Components/Users/UserTable.vue';
import UserModal from '@/Components/Users/UserModal.vue';
import UserDetailModal from '@/Components/Users/UserDetailModal.vue';
import PasswordModal from '@/Components/Users/PasswordModal.vue';

const isLoading = ref(false);
const isSubmitting = ref(false);
const localFilters = ref({
    search: '',
    role: '',
    area: '',
    position: '',
    status: ''
});

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

const filteredUsers = computed(() => {
    let result = [...users.value];

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

    // Status filter
    if (localFilters.value.status) {
        const shouldBeActive = localFilters.value.status === 'active';
        result = result.filter(u => {
            // Manejar 1, "1", true como activo y 0, "0", false como inactivo
            const userIsActive = u.is_active == 1 || u.is_active === true || u.is_active === 'true';
            return userIsActive === shouldBeActive;
        });
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
});
</script>
