import { ref } from 'vue';
import axios from 'axios';
import type { BoletaPortalFila } from '@/Composables/useBoletasPortal';

/**
 * Apartado "Mis Boletas" del sistema principal (usuario autenticado): la
 * identidad es la sesión, así que no hay consulta por DNI ni salida. El PDF
 * se abre en el visor integrado (`usePdfViewer`) con `urlPdf`.
 */
export function useMisBoletas() {
    const guardando = ref(false);
    const error = ref('');

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
        revisar,
        urlPdf,
    };
}
