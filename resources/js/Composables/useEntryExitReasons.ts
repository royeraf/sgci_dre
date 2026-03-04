import { ref, shallowRef } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';

export type ReasonTipo = 'comision' | 'permiso' | 'ambos';

export interface EntryExitReason {
    id: string;
    nombre: string;
    descripcion: string | null;
    tipo: ReasonTipo;
    is_active: boolean;
}

interface ReasonForm {
    nombre: string;
    descripcion: string;
    tipo: ReasonTipo;
    is_active: boolean;
    processing: boolean;
    errors: Partial<Record<keyof Omit<ReasonForm, 'processing' | 'errors'>, string>>;
}

export function useEntryExitReasons() {
    const reasons = ref<EntryExitReason[]>([]);
    const loading = shallowRef(true);
    const showModal = shallowRef(false);
    const editingReason = shallowRef<EntryExitReason | null>(null);

    const form = ref<ReasonForm>({
        nombre: '',
        descripcion: '',
        tipo: 'ambos',
        is_active: true,
        processing: false,
        errors: {},
    });

    const resetForm = (): void => {
        form.value = { nombre: '', descripcion: '', tipo: 'ambos', is_active: true, processing: false, errors: {} };
    };

    const fetchReasons = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get<EntryExitReason[]>('/entry-exits/reasons');
            reasons.value = data;
        } catch {
            Swal.fire({ title: 'Error', text: 'No se pudieron cargar los motivos.', icon: 'error' });
        } finally {
            loading.value = false;
        }
    };

    const openCreateModal = (): void => {
        editingReason.value = null;
        resetForm();
        showModal.value = true;
    };

    const openEditModal = (reason: EntryExitReason): void => {
        editingReason.value = reason;
        form.value = {
            nombre: reason.nombre,
            descripcion: reason.descripcion ?? '',
            tipo: reason.tipo ?? 'ambos',
            is_active: reason.is_active,
            processing: false,
            errors: {},
        };
        showModal.value = true;
    };

    const submit = async (): Promise<void> => {
        form.value.processing = true;
        form.value.errors = {};

        const url = editingReason.value
            ? `/entry-exits/reasons/${editingReason.value.id}`
            : '/entry-exits/reasons';
        const method = editingReason.value ? 'put' : 'post';

        try {
            await axios[method](url, {
                nombre: form.value.nombre,
                descripcion: form.value.descripcion,
                tipo: form.value.tipo,
                is_active: form.value.is_active,
            });
            showModal.value = false;
            resetForm();
            await fetchReasons();
            Swal.fire({
                title: '¡Éxito!',
                text: editingReason.value ? 'Motivo actualizado.' : 'Motivo creado correctamente.',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });
        } catch (error: any) {
            if (error.response?.status === 422) {
                form.value.errors = error.response.data.errors ?? {};
            } else {
                Swal.fire({ title: 'Error', text: 'Hubo un error al procesar la solicitud.', icon: 'error' });
            }
        } finally {
            form.value.processing = false;
        }
    };

    const deleteReason = (reason: EntryExitReason): void => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.delete(`/entry-exits/reasons/${reason.id}`)
                    .then(() => {
                        Swal.fire({ title: '¡Eliminado!', text: 'El motivo ha sido eliminado.', icon: 'success', timer: 2000, showConfirmButton: false });
                        fetchReasons();
                    })
                    .catch((error: any) => {
                        Swal.fire('Error', error.response?.data?.error ?? 'No se pudo eliminar el motivo.', 'error');
                    });
            }
        });
    };

    const toggleStatus = (reason: EntryExitReason): void => {
        const newStatus = !reason.is_active;
        const statusText = newStatus ? 'activar' : 'desactivar';

        Swal.fire({
            title: `¿Desea ${statusText} este motivo?`,
            text: `El motivo "${reason.nombre}" será ${newStatus ? 'visible' : 'oculto'}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#059669',
            cancelButtonColor: '#ef4444',
            confirmButtonText: `Sí, ${statusText}`,
            cancelButtonText: 'Cancelar',
            reverseButtons: true,
        }).then((result) => {
            if (result.isConfirmed) {
                axios.put(`/entry-exits/reasons/${reason.id}`, {
                    nombre: reason.nombre,
                    descripcion: reason.descripcion,
                    tipo: reason.tipo,
                    is_active: newStatus,
                }).then(() => {
                    fetchReasons();
                    Swal.fire({
                        title: '¡Estado Actualizado!',
                        text: `"${reason.nombre}" ahora está ${newStatus ? 'activo' : 'inactivo'}.`,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false,
                    });
                }).catch(() => {
                    Swal.fire({ title: 'Error', text: 'No se pudo actualizar el estado.', icon: 'error' });
                });
            }
        });
    };

    return { reasons, loading, showModal, editingReason, form, fetchReasons, openCreateModal, openEditModal, submit, deleteReason, toggleStatus };
}
