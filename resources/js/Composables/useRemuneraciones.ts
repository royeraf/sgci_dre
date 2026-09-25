import { ref } from 'vue';
import axios from 'axios';

export type TipoConcepto = 'INGRESO' | 'DESCUENTO' | 'APORTACION';
export type TipoPension = 'ONP' | 'AFP';
export type TipoComision = 'FLUJO' | 'MIXTA' | 'SALDO';

export interface RemuneracionRow {
    id: string;
    dni: string | null;
    nombre_completo: string;
    cargo: string | null;
    direction: string | null;
    regimen: string | null;
    fecha_ingreso: string | null;
    fecha_inicio_contrato: string | null;
    fecha_fin_contrato: string | null;
    remuneracion_base: number | null;
    remuneracion_id: string | null;
    remuneracion_desde: string | null;
    regimen_pensionario_id: string | null;
    regimen_pensionario: string | null;
    tipo_pension: TipoPension | null;
    cuspp: string | null;
    tipo_comision: TipoComision | null;
    banco_id: string | null;
    banco: string | null;
    cuenta_ahorro: string | null;
}

export interface PlanillaConcepto {
    id: string;
    codigo: string;
    nombre: string;
    tipo: TipoConcepto;
    categoria: string | null;
    es_porcentaje: boolean;
    valor: number | null;
    orden: number;
    activo: boolean;
    asignaciones_count?: number;
}

export interface RegimenPensionario {
    id: string;
    nombre: string;
    tipo: TipoPension;
}

export interface RemuneracionPayload {
    employee_id: string;
    monto: number;
    tipo?: string;
    desde: string;
    motivo?: string | null;
}

export interface PerfilPayload {
    regimen_pensionario_id: string | null;
    cuspp: string | null;
    tipo_comision: TipoComision | null;
    banco_id: string | null;
    cuenta_ahorro: string | null;
}

export interface ContratoPayload {
    fecha_inicio_contrato: string;
    fecha_fin_contrato: string | null;
}

export function useRemuneraciones() {
    const rows = ref<RemuneracionRow[]>([]);
    const regimenes = ref<RegimenPensionario[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchAll = async (): Promise<void> => {
        loading.value = true;
        try {
            const [rem, reg] = await Promise.all([
                axios.get('/planillas/remuneraciones'),
                axios.get('/planillas/regimenes-pensionarios'),
            ]);
            rows.value = rem.data;
            regimenes.value = reg.data;
        } finally {
            loading.value = false;
        }
    };

    const crearRemuneracion = async (payload: RemuneracionPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/remuneraciones', payload);
            await fetchAll();
        } finally {
            saving.value = false;
        }
    };

    const actualizarPerfil = async (employeeId: string, payload: PerfilPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/empleados/${employeeId}/perfil`, payload);
            await fetchAll();
        } finally {
            saving.value = false;
        }
    };

    const actualizarContrato = async (employeeId: string, payload: ContratoPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/empleados/${employeeId}/contrato`, payload);
            await fetchAll();
        } finally {
            saving.value = false;
        }
    };

    return {
        rows,
        regimenes,
        loading,
        saving,
        fetchAll,
        crearRemuneracion,
        actualizarPerfil,
        actualizarContrato,
    };
}
