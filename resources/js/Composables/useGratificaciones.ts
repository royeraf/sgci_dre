import { ref } from 'vue';
import axios from 'axios';

export type PeriodoGratificacion = 'JULIO' | 'DICIEMBRE';

export interface GratificacionRow {
    id?: string;
    employee_id: string;
    dni: string | null;
    nombre_completo: string;
    cargo: string | null;
    fecha_ingreso: string | null;
    remuneracion_corte: number;
    porcentaje_aplicado: number;
    meses_completos: number;
    dias: number;
    base_semestral: number;
    monto_proporcional: number;
    monto_minimo: number | null;
    monto_final: number;
    aporte_essalud: number;
}

export interface ParametroGratificacion {
    id: string;
    anio_fiscal: number;
    porcentaje: number;
    monto_minimo: number | null;
    fecha_vigencia_norma: string | null;
    activo: boolean;
}

export function useGratificaciones() {
    const rows = ref<GratificacionRow[]>([]);
    const parametros = ref<ParametroGratificacion[]>([]);
    const total = ref(0);
    const totalEssalud = ref(0);
    const esPreview = ref(false);
    const loading = ref(false);
    const previewing = ref(false);
    const saving = ref(false);

    const fetchParametros = async (): Promise<void> => {
        const { data } = await axios.get('/planillas/gratificaciones/parametros');
        parametros.value = data.map((p) => ({ ...p, porcentaje: Number(p.porcentaje) * 100 }));
    };

    const fetchListado = async (anio: number, periodo: PeriodoGratificacion): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/gratificaciones', { params: { anio, periodo } });
            rows.value = data;
            total.value = data.reduce((sum, row) => sum + Number(row.monto_final), 0);
            totalEssalud.value = data.reduce((sum, row) => sum + Number(row.aporte_essalud), 0);
            esPreview.value = false;
        } finally {
            loading.value = false;
        }
    };

    const previsualizar = async (anio: number, periodo: PeriodoGratificacion): Promise<void> => {
        previewing.value = true;
        try {
            const { data } = await axios.post('/planillas/gratificaciones/preview', { anio, periodo });
            rows.value = data.rows;
            total.value = data.total;
            totalEssalud.value = data.total_essalud;
            esPreview.value = true;
        } finally {
            previewing.value = false;
        }
    };

    const generar = async (anio: number, periodo: PeriodoGratificacion): Promise<string> => {
        saving.value = true;
        try {
            const { data } = await axios.post('/planillas/gratificaciones/generar', { anio, periodo });
            await fetchListado(anio, periodo);
            return data.message;
        } finally {
            saving.value = false;
        }
    };

    const actualizarParametro = async (id: string, payload: { porcentaje: number; monto_minimo: number | null; activo: boolean }): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/gratificaciones/parametros/${id}`, payload);
            await fetchParametros();
        } finally {
            saving.value = false;
        }
    };

    return {
        rows,
        parametros,
        total,
        totalEssalud,
        esPreview,
        loading,
        previewing,
        saving,
        fetchParametros,
        fetchListado,
        previsualizar,
        generar,
        actualizarParametro,
    };
}
