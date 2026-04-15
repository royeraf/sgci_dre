import { ref, shallowRef } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export interface Role {
    rol_id: string;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    nivel_acceso: number;
    activo: boolean;
    users_count: number;
}

export function useRoles() {
    const roles = ref<Role[]>([]);
    const loading = shallowRef(true);
    const showModal = shallowRef(false);
    const editingRole = shallowRef<Role | null>(null);
    const submitting = shallowRef(false);

    const form = ref({
        nombre: '',
        descripcion: '',
        nivel_acceso: 5,
        activo: true,
        errors: {} as Record<string, string>,
    });

    const fetchRoles = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/users/roles/all');
            roles.value = response.data;
        } catch (error) {
            Swal.fire({ title: 'Error', text: 'No se pudieron cargar los roles.', icon: 'error' });
        } finally {
            loading.value = false;
        }
    };

    const openCreateModal = () => {
        editingRole.value = null;
        form.value = { nombre: '', descripcion: '', nivel_acceso: 5, activo: true, errors: {} };
        showModal.value = true;
    };

    const openEditModal = (role: Role) => {
        editingRole.value = role;
        form.value = {
            nombre: role.nombre,
            descripcion: role.descripcion || '',
            nivel_acceso: role.nivel_acceso,
            activo: role.activo,
            errors: {},
        };
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        editingRole.value = null;
    };

    const submit = async () => {
        form.value.errors = {};
        submitting.value = true;
        try {
            if (editingRole.value) {
                await axios.put(`/users/roles/${editingRole.value.rol_id}`, form.value);
            } else {
                await axios.post('/users/roles', form.value);
            }
            closeModal();
            fetchRoles();
            Swal.fire({
                title: '¡Éxito!',
                text: editingRole.value ? 'Rol actualizado correctamente.' : 'Rol creado correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });
        } catch (error: any) {
            if (error.response?.status === 422) {
                const serverErrors = error.response.data.errors || {};
                form.value.errors = Object.fromEntries(
                    Object.entries(serverErrors).map(([k, v]) => [k, (v as string[])[0]])
                );
            } else {
                Swal.fire({ title: 'Error', text: 'Hubo un error al procesar la solicitud.', icon: 'error' });
            }
        } finally {
            submitting.value = false;
        }
    };

    const deleteRole = (role: Role) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: role.users_count > 0
                ? `Este rol tiene ${role.users_count} usuario(s) asignado(s) y no podrá eliminarse.`
                : 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#818cf8',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/users/roles/${role.rol_id}`)
                    .then(() => {
                        Swal.fire({ title: '¡Eliminado!', text: 'El rol ha sido eliminado.', icon: 'success', timer: 2000, showConfirmButton: false });
                        fetchRoles();
                    })
                    .catch((error) => {
                        Swal.fire('Error', error.response?.data?.message || 'No se pudo eliminar el rol.', 'error');
                    });
            }
        });
    };

    const toggleStatus = (role: Role) => {
        const action = role.activo ? 'desactivar' : 'activar';
        Swal.fire({
            title: `¿Desea ${action} este rol?`,
            text: `El rol "${role.nombre}" ${role.activo ? 'dejará de estar disponible' : 'estará disponible nuevamente'}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#818cf8',
            cancelButtonColor: '#ef4444',
            confirmButtonText: `Sí, ${action}`,
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.patch(`/users/roles/${role.rol_id}/toggle-status`)
                    .then(() => {
                        fetchRoles();
                        Swal.fire({
                            title: '¡Estado Actualizado!',
                            text: `El rol "${role.nombre}" ahora está ${role.activo ? 'inactivo' : 'activo'}.`,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                    })
                    .catch(() => {
                        Swal.fire({ title: 'Error', text: 'No se pudo actualizar el estado.', icon: 'error' });
                    });
            }
        });
    };

    return {
        roles,
        loading,
        showModal,
        editingRole,
        form,
        submitting,
        fetchRoles,
        openCreateModal,
        openEditModal,
        closeModal,
        submit,
        deleteRole,
        toggleStatus,
    };
}
