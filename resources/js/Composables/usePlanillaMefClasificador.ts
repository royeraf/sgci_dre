import { ref } from 'vue';
import axios from 'axios';

export const MEF_CODIGO_REGEX = /^\d+(\.\d+)*$/;

export interface MefClasificadorGasto {
    id: string;
    anio: number;
    codigo: string;
    codigo_alias: string | null;
    codigo_padre: string | null;
    nivel: number | null;
    descripcion: string;
    es_terminal: boolean;
    activo: boolean;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    fuente: string | null;
    version_catalogo: string | null;
}

export interface MefClasificadorPayload {
    anio: number;
    codigo: string;
    codigo_alias?: string | null;
    codigo_padre?: string | null;
    nivel?: number | null;
    descripcion: string;
    es_terminal?: boolean;
    activo?: boolean;
    fecha_inicio?: string | null;
    fecha_fin?: string | null;
    fuente?: string | null;
    version_catalogo?: string | null;
}

export interface MefRegla {
    id: string;
    anio: number;
    regimen: string | null;
    modalidad: string | null;
    concepto_codigo: string;
    clasificador_id: string;
    codigo: string | null;
    clasificador_descripcion: string | null;
    prioridad: number;
    activo: boolean;
    fuente: string | null;
}

export interface MefReglaPayload {
    anio: number;
    regimen?: string | null;
    modalidad?: string | null;
    concepto_codigo: string;
    clasificador_id: string;
    prioridad?: number;
    activo?: boolean;
    fuente?: string | null;
}

export interface MefHistorial {
    id: string;
    clasificador_id: string;
    campo: string;
    valor_anterior: string | null;
    valor_nuevo: string | null;
    fecha_cambio: string | null;
    fuente: string | null;
    usuario_id: number | null;
}

export interface MefFiltrosCatalogo {
    anio?: number;
    codigo?: string;
    search?: string;
    nivel?: number;
    estado?: 'activo' | 'inactivo';
    terminal?: boolean;
}

export function usePlanillaMefClasificador() {
    const clasificadores = ref<MefClasificadorGasto[]>([]);
    const reglas = ref<MefRegla[]>([]);
    const historial = ref<MefHistorial[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchClasificadores = async (filtros: MefFiltrosCatalogo = {}): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/mef/clasificador-gasto', { params: filtros });
            clasificadores.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchReglas = async (anio: number): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/mef/reglas', { params: { anio } });
            reglas.value = data;
        } finally {
            loading.value = false;
        }
    };

    const fetchHistorial = async (clasificadorId: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get(`/planillas/mef/clasificador-gasto/${clasificadorId}/historial`);
            historial.value = data;
        } finally {
            loading.value = false;
        }
    };

    const crearClasificador = async (payload: MefClasificadorPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/mef/clasificador-gasto', payload);
        } finally {
            saving.value = false;
        }
    };

    const actualizarClasificador = async (id: string, payload: MefClasificadorPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/mef/clasificador-gasto/${id}`, payload);
        } finally {
            saving.value = false;
        }
    };

    const eliminarClasificador = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/mef/clasificador-gasto/${id}`);
        } finally {
            saving.value = false;
        }
    };

    const crearRegla = async (payload: MefReglaPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/mef/reglas', payload);
        } finally {
            saving.value = false;
        }
    };

    const actualizarRegla = async (id: string, payload: MefReglaPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/mef/reglas/${id}`, payload);
        } finally {
            saving.value = false;
        }
    };

    const eliminarRegla = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/mef/reglas/${id}`);
        } finally {
            saving.value = false;
        }
    };

    return {
        clasificadores,
        reglas,
        historial,
        loading,
        saving,
        fetchClasificadores,
        fetchReglas,
        fetchHistorial,
        crearClasificador,
        actualizarClasificador,
        eliminarClasificador,
        crearRegla,
        actualizarRegla,
        eliminarRegla,
    };
}
