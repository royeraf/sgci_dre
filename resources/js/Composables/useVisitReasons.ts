import { ref, shallowRef } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import Swal from 'sweetalert2';

export interface VisitReason {
    id: string;
    nombre: string;
    descripcion: string | null;
    is_active: boolean;
}

export function useVisitReasons() {
    const reasons = ref<VisitReason[]>([]);
    const loading = shallowRef(true);
    const showModal = shallowRef(false);
    const editingReason = shallowRef<VisitReason | null>(null);

    const form = useForm({
        nombre: '',
        descripcion: '',
        is_active: true
    });

    const fetchReasons = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/visitors/reasons/list');
            reasons.value = response.data;
        } catch (error) {
            console.error('Error fetching reasons:', error);
            Swal.fire({
                title: 'Error',
                text: 'No se pudieron cargar los motivos.',
                icon: 'error'
            });
        } finally {
            loading.value = false;
        }
    };

    const openCreateModal = () => {
        editingReason.value = null;
        form.reset();
        // Force explicit reset just in case
        form.nombre = '';
        form.descripcion = '';
        form.is_active = true;
        form.clearErrors();
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
        const url = editingReason.value 
            ? `/visitors/reasons/${editingReason.value.id}` 
            : '/visitors/reasons';
        
        const method = editingReason.value ? 'put' : 'post';

        form[method](url, {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
                fetchReasons();
                Swal.fire({
                    title: '¡Éxito!',
                    text: editingReason.value ? 'Motivo actualizado correctamente.' : 'Motivo creado correctamente.',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false,
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error',
                    text: 'Hubo un error al procesar la solicitud.',
                    icon: 'error'
                });
            }
        });
    };

    const deleteReason = (reason: VisitReason) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#818cf8',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result: any) => {
            if (result.isConfirmed) {
                axios.delete(`/visitors/reasons/${reason.id}`)
                    .then(() => {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El motivo ha sido eliminado.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false,
                        });
                        fetchReasons();
                    })
                    .catch((error) => {
                        Swal.fire(
                            'Error',
                            error.response?.data?.error || 'No se pudo eliminar el motivo.',
                            'error'
                        );
                    });
            }
        });
    };

    const toggleStatus = (reason: VisitReason) => {
        const newStatus = !reason.is_active;
        const statusText = newStatus ? 'activar' : 'desactivar';

        Swal.fire({
            title: `¿Desea ${statusText} este motivo?`,
            text: `El motivo "${reason.nombre}" será ${newStatus ? 'visible' : 'oculto'} en el registro.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#818cf8',
            cancelButtonColor: '#ef4444',
            confirmButtonText: `Sí, ${statusText}`,
            cancelButtonText: 'Cancelar',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                axios.put(`/visitors/reasons/${reason.id}`, {
                    nombre: reason.nombre,
                    descripcion: reason.descripcion,
                    is_active: newStatus
                }).then(() => {
                    fetchReasons();
                    Swal.fire({
                        title: '¡Estado Actualizado!',
                        text: `El motivo "${reason.nombre}" ahora está ${newStatus ? 'activo' : 'inactivo'}.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }).catch((error) => {
                    Swal.fire({
                        title: 'Error',
                        text: 'No se pudo actualizar el estado.',
                        icon: 'error',
                    });
                });
            }
        });
    };

    return {
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
    };
}
