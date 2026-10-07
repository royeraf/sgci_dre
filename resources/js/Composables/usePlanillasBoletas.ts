import { ref } from 'vue';
import axios from 'axios';

export type EstadoPeriodoBoleta = 'BORRADOR' | 'CALCULADA' | 'APROBADA' | 'PAGADA' | 'CERRADA';
export type TipoContrato = 'FIJO' | 'INDETERMINADO';

export interface BoletaPeriodo {
    id: string;
    anio: number;
    mes: number;
    nombre_periodo: string;
    estado: EstadoPeriodoBoleta;
    total_empleados: number;
    total_neto: number;
    boletas: number;
}

export interface BoletaFila {
    detalle_id: string;
    codigo_boleta: string;
    dni: string | null;
    apellidos: string | null;
    nombres: string | null;
    apellidos_nombres: string | null;
    cargo: string | null;
    dias_laborados: number;
    total_ingresos: number;
    total_descuentos: number;
    neto_pagar: number;
}

export interface BoletaConcepto {
    descripcion: string;
    porcentaje: number | null;
    monto: number;
}

export interface Boleta {
    empresa: {
        ruc: string;
        razon_social: string;
        nombre_abreviado: string | null;
        direccion: string;
    };
    periodo: {
        id: string;
        anio: number;
        mes: number;
        nombre_mes: string;
        nombre_periodo: string;
        estado: EstadoPeriodoBoleta;
    };
    trabajador: {
        codigo_boleta: string;
        dni: string | null;
        apellidos: string | null;
        nombres: string | null;
        apellidos_nombres: string | null;
        fecha_ingreso: string | null;
        fecha_inicio_contrato: string | null;
        fecha_fin_contrato: string | null;
        contrato: TipoContrato;
    };
    relacion_laboral: {
        cargo: string | null;
        regimen: string | null;
        periodicidad: string;
        sistema_pensiones: string | null;
        cuspp: string | null;
        dias_laborados: number;
        cuenta_ahorro: string | null;
        banco: string | null;
    };
    remuneraciones: BoletaConcepto[];
    retenciones: BoletaConcepto[];
    aportaciones: BoletaConcepto[];
    totales: {
        remuneraciones: number;
        retenciones: number;
        aportaciones: number;
        neto_pagar: number;
    };
    fecha_emision: string;
}

export interface EmpresaPayload {
    ruc: string;
    razon_social: string;
    nombre_abreviado: string | null;
    direccion: string;
}

export const DIAS_MES = 30;

export function usePlanillasBoletas() {
    const periodos = ref<BoletaPeriodo[]>([]);
    const periodoId = ref<string>('');
    const periodo = ref<BoletaPeriodo | null>(null);
    const boletas = ref<BoletaFila[]>([]);
    const boleta = ref<Boleta | null>(null);
    const empresa = ref<EmpresaPayload | null>(null);
    const loading = ref(false);
    const saving = ref(false);

    const fetchPeriodos = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/boletas');
            periodos.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchBoletas = async (id: string): Promise<void> => {
        loading.value = true;
        boletas.value = [];
        try {
            const { data } = await axios.get(`/planillas/boletas/periodo/${id}`);
            periodo.value = data.periodo;
            boletas.value = data.boletas;
        } finally {
            loading.value = false;
        }
    };

    const fetchBoleta = async (detalleId: string): Promise<void> => {
        loading.value = true;
        boleta.value = null;
        try {
            const { data } = await axios.get(`/planillas/boletas/${detalleId}`);
            boleta.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchConfiguracion = async (): Promise<void> => {
        const { data } = await axios.get('/planillas/boletas/configuracion');
        empresa.value = data;
    };

    const updateConfiguracion = async (payload: EmpresaPayload): Promise<void> => {
        saving.value = true;
        try {
            const { data } = await axios.put('/planillas/boletas/configuracion', payload);
            empresa.value = data.empresa;
        } finally {
            saving.value = false;
        }
    };

    const urlPdf = (detalleId: string): string => `/planillas/boletas/${detalleId}/pdf`;

    const urlZip = (id: string): string => `/planillas/boletas/periodo/${id}/pdf-zip`;

    return {
        periodos,
        periodoId,
        periodo,
        boletas,
        boleta,
        empresa,
        loading,
        saving,
        fetchPeriodos,
        fetchBoletas,
        fetchBoleta,
        fetchConfiguracion,
        updateConfiguracion,
        urlPdf,
        urlZip,
    };
}
