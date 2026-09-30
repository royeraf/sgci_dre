import { ref } from 'vue';
import axios from 'axios';

export interface ResumenIngreso {
    esp_gasto: string;
    nombre: string;
    monto: number;
}

export interface ResumenDescuento {
    nombre: string;
    c13_11: number;
    c13_12: number;
    total: number;
}

export interface ResumenAbono {
    c13_11: number;
    c13_12: number;
    teleahorro: number;
    aporte_essalud: number;
    total_liquido: number;
}

export interface PlanillaResumen {
    periodo: {
        id: string;
        nombre_periodo: string;
        anio: number;
        mes: number;
        estado: string;
        total_empleados: number;
        total_neto: number;
    };
    empleados: number;
    ingresos: ResumenIngreso[];
    total_planillas: number;
    descuentos: ResumenDescuento[];
    total_descuentos: number;
    abono: ResumenAbono;
}

export interface ResumenPlanillaIngreso {
    esp_gasto: string;
    nombre: string;
    monto: number;
}

export interface ResumenPlanillaDescuento {
    nombre: string;
    monto: number;
}

export interface ResumenPlanillaBloque {
    generica: string;
    ingresos: ResumenPlanillaIngreso[];
    essalud: number;
    total_ingresos: number;
    descuentos: ResumenPlanillaDescuento[];
    total_descuentos: number;
    neto: number;
}

export interface ResumenPlanillaTotales {
    liquido: number;
    descuento: number;
    aporte: number;
    planilla: number;
}

export interface PlanillaResumenPlanilla {
    periodo: PlanillaResumen['periodo'];
    bloques: Record<string, ResumenPlanillaBloque>;
    totales: ResumenPlanillaTotales;
}

export function usePlanillaResumen() {
    const resumen = ref<PlanillaResumen | null>(null);
    const loading = ref(false);
    const resumenPlanilla = ref<PlanillaResumenPlanilla | null>(null);
    const loadingPlanilla = ref(false);
    const exportando = ref<'xlsx' | 'pdf' | null>(null);

    const fetchResumen = async (periodoId?: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/resumen', {
                params: periodoId ? { periodo_id: periodoId } : {},
            });
            resumen.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchResumenPlanilla = async (periodoId?: string): Promise<void> => {
        loadingPlanilla.value = true;
        try {
            const { data } = await axios.get('/planillas/resumen-planilla', {
                params: periodoId ? { periodo_id: periodoId } : {},
            });
            resumenPlanilla.value = data;
        } finally {
            loadingPlanilla.value = false;
        }
    };

    const exportarResumenPlanilla = async (periodoId: string, format: 'xlsx' | 'pdf'): Promise<void> => {
        exportando.value = format;
        try {
            const { data, headers } = await axios.get(`/planillas/resumen-planilla/export/${format}`, {
                params: { periodo_id: periodoId },
                responseType: 'blob',
            });

            const nombre = /filename="?([^"]+)"?/.exec(headers['content-disposition'] || '')?.[1]
                || `resumen_planilla.${format}`;

            const url = URL.createObjectURL(data);
            const link = document.createElement('a');
            link.href = url;
            link.download = nombre;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        } finally {
            exportando.value = null;
        }
    };

    return {
        resumen,
        loading,
        resumenPlanilla,
        loadingPlanilla,
        exportando,
        fetchResumen,
        fetchResumenPlanilla,
        exportarResumenPlanilla,
    };
}
