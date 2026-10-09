import { ref } from 'vue';
import axios from 'axios';

export interface EmpleadoPortal {
    dni: string;
    apellidos_nombres: string;
    cargo: string | null;
}

export interface BoletaPortalPeriodo {
    id: string;
    anio: number;
    mes: number;
    nombre_periodo: string;
    estado: string;
}

export interface BoletaPortalFila {
    detalle_id: string;
    codigo_boleta: string;
    periodo: BoletaPortalPeriodo;
    dias_laborados: number;
    total_ingresos: number;
    total_descuentos: number;
    neto_pagar: number;
    /** 'YYYY-MM-DD HH:mm:ss' de la confirmación; null = pendiente de recibido. */
    revisada_en: string | null;
}

/**
 * Portal público de boletas (sin login): el trabajador se identifica con su
 * DNI y el servidor fija la identidad en la sesión. Nunca se usan rutas
 * /planillas/... (requieren auth) porque el interceptor de axios redirigiría
 * a /login ante un 401.
 */
export function useBoletasPortal() {
    const empleado = ref<EmpleadoPortal | null>(null);
    const boletas = ref<BoletaPortalFila[]>([]);
    const loading = ref(false);
    const guardando = ref(false);
    const error = ref('');

    const consultar = async (dni: string): Promise<boolean> => {
        loading.value = true;
        error.value = '';
        try {
            const { data } = await axios.post('/boletas/consultar', { dni });
            empleado.value = data.empleado;
            boletas.value = data.boletas;
            return true;
        } catch (err) {
            error.value = (err as { response?: { data?: { message?: string } } }).response?.data?.message
                || 'No se pudo consultar. Inténtelo más tarde.';
            return false;
        } finally {
            loading.value = false;
        }
    };

    const salir = async (): Promise<void> => {
        try {
            await axios.post('/boletas/salir');
        } finally {
            empleado.value = null;
            boletas.value = [];
            error.value = '';
        }
    };

    /** Confirmación de recibido; idempotente en el servidor. */
    const revisar = async (fila: BoletaPortalFila): Promise<string | null> => {
        guardando.value = true;
        try {
            const { data } = await axios.post(`/boletas/${fila.detalle_id}/revisar`);
            fila.revisada_en = data.revisada_en;
            return data.revisada_en as string;
        } catch (err) {
            error.value = (err as { response?: { data?: { message?: string } } }).response?.data?.message
                || 'No se pudo confirmar el recibido. Consulte nuevamente con su DNI.';
            return null;
        } finally {
            guardando.value = false;
        }
    };

    const urlPdf = (detalleId: string): string => `/boletas/${detalleId}/pdf`;

    return {
        empleado,
        boletas,
        loading,
        guardando,
        error,
        consultar,
        salir,
        revisar,
        urlPdf,
    };
}
