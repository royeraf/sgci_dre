import { ref } from 'vue';
import axios from 'axios';

export function usePapeletaApproval() {
    const processing = ref(false);
    const error = ref<string | null>(null);

    const aprobar = async (papeletaId: string, comentario: string = '') => {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.patch(`/papeletas/${papeletaId}/aprobar`, { comentario });
            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al aprobar la papeleta';
            throw err;
        } finally {
            processing.value = false;
        }
    };

    const desaprobar = async (papeletaId: string, comentario: string) => {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.patch(`/papeletas/${papeletaId}/desaprobar`, { comentario });
            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al desaprobar la papeleta';
            throw err;
        } finally {
            processing.value = false;
        }
    };

    return {
        processing,
        error,
        aprobar,
        desaprobar,
    };
}
