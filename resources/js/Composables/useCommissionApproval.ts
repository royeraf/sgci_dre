import { ref } from 'vue';
import axios from 'axios';

export function useCommissionApproval() {
    const processing = ref(false);
    const error = ref<string | null>(null);

    const autorizar = async (commissionId: string, comentario: string = '', signingPin: string = '') => {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.patch(`/vehicles/commissions/${commissionId}/authorize`, { comentario, signing_pin: signingPin });
            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al autorizar la salida vehicular';
            throw err;
        } finally {
            processing.value = false;
        }
    };

    const rechazar = async (commissionId: string, comentario: string) => {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.patch(`/vehicles/commissions/${commissionId}/reject`, { comentario });
            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al rechazar la salida vehicular';
            throw err;
        } finally {
            processing.value = false;
        }
    };

    const confirmar = async (commissionId: string, signingPin: string = '') => {
        processing.value = true;
        error.value = null;
        try {
            const { data } = await axios.patch(`/vehicles/commissions/${commissionId}/confirm-conductor`, { signing_pin: signingPin });
            return data;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Error al confirmar la salida';
            throw err;
        } finally {
            processing.value = false;
        }
    };

    return {
        processing,
        error,
        autorizar,
        rechazar,
        confirmar,
    };
}
