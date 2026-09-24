import { ref } from 'vue';
import axios from 'axios';

export interface TardanzaRegistro {
    id: string;
    fecha: string;
    dias: number;
    minutos: number;
    monto_dias: number;
    monto_minutos: number;
    total: number;
    justificado: boolean;
    observacion: string | null;
    origen: 'MANUAL' | 'ASISTENCIA';
}

/** Fila de la hoja «Dscto. Tard.»: una por empleado. */
export interface TardanzaFila {
    employee_id: string;
    dni: string | null;
    nombre_completo: string | null;
    remuneraciones: number;
    valor_dia: number;
    valor_minuto: number;
    dias: number;
    minutos: number;
    monto_dias: number;
    monto_minutos: number;
    total: number;
    base_imponible: number;
    con_registros: boolean;
    registros: TardanzaRegistro[];
}

export interface TardanzaPeriodo {
    id: string;
    nombre_periodo: string;
    estado: string;
    editable: boolean;
    fecha_inicio: string | null;
    fecha_fin: string | null;
}

export interface TardanzaPayload {
    periodo_id: string;
    employee_id: string;
    fecha: string;
    dias: number;
    minutos: number;
    observacion?: string | null;
}

export function usePlanillaTardanzas() {
    const periodo = ref<TardanzaPeriodo | null>(null);
    const filas = ref<TardanzaFila[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchTardanzas = async (periodoId: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/tardanzas', {
                params: { periodo_id: periodoId },
            });
            periodo.value = data.periodo;
            filas.value = data.filas;
        } finally {
            loading.value = false;
        }
    };

    const crearTardanza = async (payload: TardanzaPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/tardanzas', payload);
            await fetchTardanzas(payload.periodo_id);
        } finally {
            saving.value = false;
        }
    };

    const actualizarTardanza = async (
        id: string,
        payload: Partial<{ dias: number; minutos: number; observacion: string | null; justificado: boolean }>,
        periodoId: string
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/tardanzas/${id}`, payload);
            await fetchTardanzas(periodoId);
        } finally {
            saving.value = false;
        }
    };

    const eliminarTardanza = async (id: string, periodoId: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/tardanzas/${id}`);
            await fetchTardanzas(periodoId);
        } finally {
            saving.value = false;
        }
    };

    return {
        periodo,
        filas,
        loading,
        saving,
        fetchTardanzas,
        crearTardanza,
        actualizarTardanza,
        eliminarTardanza,
    };
}
