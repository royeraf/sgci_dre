import { ref } from 'vue';
import axios from 'axios';

export type EstadoPeriodo = 'BORRADOR' | 'CALCULADA' | 'APROBADA' | 'PAGADA' | 'CERRADA';

export interface PlanillaPeriodo {
    id: string;
    anio: number;
    mes: number;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    estado: EstadoPeriodo;
    total_empleados: number;
    total_neto: number;
    nombre_periodo: string;
    editable: boolean;
}

export interface PlanillaDetalleItem {
    id: string;
    tipo: 'INGRESO' | 'DESCUENTO' | 'APORTACION';
    descripcion: string;
    base_calculo: number;
    porcentaje: number | null;
    monto: number;
}

export interface PlanillaDetalle {
    id: string;
    employee_id: string;
    dni: string | null;
    nombre_completo: string | null;
    remuneracion_base: number;
    total_ingresos: number;
    total_descuentos: number;
    total_aportaciones: number;
    neto_pagar: number;
    items: PlanillaDetalleItem[];
}

export interface PeriodoDetalleData {
    periodo: {
        id: string;
        nombre_periodo: string;
        estado: EstadoPeriodo;
        editable: boolean;
        fecha_inicio: string | null;
        fecha_fin: string | null;
        total_empleados: number;
        total_neto: number;
    };
    detalles: PlanillaDetalle[];
}

export function usePlanillaPeriodos() {
    const periodos = ref<PlanillaPeriodo[]>([]);
    const detalle = ref<PeriodoDetalleData | null>(null);
    const loading = ref(false);
    const saving = ref(false);

    const fetchPeriodos = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/periodos');
            periodos.value = data;
        } finally {
            loading.value = false;
        }
    };

    const crearPeriodo = async (payload: { anio: number; mes: number }): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/periodos', payload);
            await fetchPeriodos();
        } finally {
            saving.value = false;
        }
    };

    const generarPeriodo = async (id: string): Promise<{ empleados: number; total_neto: number }> => {
        saving.value = true;
        try {
            const { data } = await axios.post(`/planillas/periodos/${id}/generar`);
            await fetchPeriodos();
            return data;
        } finally {
            saving.value = false;
        }
    };

    const eliminarPeriodo = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/periodos/${id}`);
            await fetchPeriodos();
        } finally {
            saving.value = false;
        }
    };

    const fetchDetalle = async (id: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get(`/planillas/periodos/${id}/detalle`);
            detalle.value = data;
        } finally {
            loading.value = false;
        }
    };

    return {
        periodos,
        detalle,
        loading,
        saving,
        fetchPeriodos,
        crearPeriodo,
        generarPeriodo,
        eliminarPeriodo,
        fetchDetalle,
    };
}
