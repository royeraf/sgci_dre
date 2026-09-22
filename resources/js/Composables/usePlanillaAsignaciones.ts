import { ref } from 'vue';
import axios from 'axios';

export type DestinoAsignacion = 'REGIMEN' | 'EMPLEADO';

export interface Asignacion {
    id: string;
    concepto_id: string;
    concepto: string | null;
    employee_id: string | null;
    contract_type_id: string | null;
    destino: DestinoAsignacion;
    destino_nombre: string | null;
    monto: number | null;
    porcentaje: number | null;
    desde: string | null;
    hasta: string | null;
    activo: boolean;
}

export interface AsignacionPayload {
    concepto_id: string;
    employee_id?: string | null;
    contract_type_id?: string | null;
    monto?: number | null;
    porcentaje?: number | null;
    desde?: string | null;
    hasta?: string | null;
    activo?: boolean;
}

export interface AsignacionParametros {
    contract_types: { id: string; nombre: string }[];
    employees: { id: string; dni: string | null; nombre_completo: string }[];
}

export function usePlanillaAsignaciones() {
    const asignaciones = ref<Asignacion[]>([]);
    const parametros = ref<AsignacionParametros>({ contract_types: [], employees: [] });
    const loading = ref(false);
    const saving = ref(false);

    const fetchAsignaciones = async (conceptoId?: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/asignaciones', {
                params: conceptoId ? { concepto_id: conceptoId } : {},
            });
            asignaciones.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchParametros = async (): Promise<void> => {
        const { data } = await axios.get('/planillas/asignaciones/parametros');
        parametros.value = data;
    };

    const crearAsignacion = async (payload: AsignacionPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/asignaciones', payload);
            await fetchAsignaciones(payload.concepto_id);
        } finally {
            saving.value = false;
        }
    };

    const actualizarAsignacion = async (
        id: string,
        payload: Partial<AsignacionPayload>,
        conceptoId?: string,
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/asignaciones/${id}`, payload);
            await fetchAsignaciones(conceptoId);
        } finally {
            saving.value = false;
        }
    };

    const eliminarAsignacion = async (id: string, conceptoId?: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/asignaciones/${id}`);
            await fetchAsignaciones(conceptoId);
        } finally {
            saving.value = false;
        }
    };

    return {
        asignaciones,
        parametros,
        loading,
        saving,
        fetchAsignaciones,
        fetchParametros,
        crearAsignacion,
        actualizarAsignacion,
        eliminarAsignacion,
    };
}
