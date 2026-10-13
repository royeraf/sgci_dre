import { ref } from 'vue';
import axios from 'axios';
import type { BoletaPortalFila } from '@/Composables/useBoletasPortal';

/**
 * Apartado "Mis Boletas" del sistema principal (usuario autenticado): la
 * identidad es la sesión, así que no hay consulta por DNI ni salida. Las
 * filas tienen la misma forma que las del portal público y se reutiliza
 * `BoletaPreviewModal` con `urlPdf` apuntando a `/mis-boletas/.../pdf`.
 */
export function useMisBoletas() {
    const guardando = ref(false);
    const error = ref('');

    /** Vista previa de una boleta propia. */
    const ver = async (detalleId: string) => {
        error.value = '';
        const { data } = await axios.get(`/mis-boletas/${detalleId}`);
        return data;
    };

    /** Confirmación de recibido; idempotente en el servidor. */
    const revisar = async (fila: BoletaPortalFila): Promise<string | null> => {
        guardando.value = true;
        try {
            const { data } = await axios.post(`/mis-boletas/${fila.detalle_id}/revisar`);
            fila.revisada_en = data.revisada_en;
            return data.revisada_en as string;
        } catch (err) {
            error.value = (err as { response?: { data?: { message?: string } } }).response?.data?.message
                || 'No se pudo confirmar el recibido. Inténtelo nuevamente.';
            return null;
        } finally {
            guardando.value = false;
        }
    };

    const urlPdf = (detalleId: string): string => `/mis-boletas/${detalleId}/pdf`;

    return {
        guardando,
        error,
        ver,
        revisar,
        urlPdf,
    };
}
