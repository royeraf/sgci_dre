import { ref } from 'vue';
import axios from 'axios';

export interface ParametroAfp {
    id: string;
    mes: string;
    aporte_obligatorio: number;
    prima_seguro: number;
    remuneracion_maxima_asegurable: number;
}

export interface ComisionAfp {
    id: string;
    mes: string;
    regimen_pensionario_id: string;
    regimen: string | null;
    comision_flujo: number;
    comision_saldo: number;
}

export function usePlanillaParametrosAfp() {
    const parametros = ref<ParametroAfp[]>([]);
    const comisiones = ref<ComisionAfp[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchParametrosAfp = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/parametros-afp');
            parametros.value = (data.parametros || []).map((p) => ({
                ...p,
                aporte_obligatorio: Number(p.aporte_obligatorio) * 100,
                prima_seguro: Number(p.prima_seguro) * 100,
                remuneracion_maxima_asegurable: Number(p.remuneracion_maxima_asegurable),
            }));
            comisiones.value = (data.comisiones || []).map((c) => ({
                ...c,
                comision_flujo: Number(c.comision_flujo) * 100,
                comision_saldo: Number(c.comision_saldo) * 100,
            }));
        } finally {
            loading.value = false;
        }
    };

    const actualizarParametroAfp = async (
        id: string,
        payload: { aporte_obligatorio: number; prima_seguro: number; remuneracion_maxima_asegurable: number },
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/parametros-afp/${id}`, payload);
            await fetchParametrosAfp();
        } finally {
            saving.value = false;
        }
    };

    const actualizarComisionAfp = async (
        id: string,
        payload: { comision_flujo: number; comision_saldo: number },
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/comisiones-afp/${id}`, payload);
            await fetchParametrosAfp();
        } finally {
            saving.value = false;
        }
    };

    return {
        parametros,
        comisiones,
        loading,
        saving,
        fetchParametrosAfp,
        actualizarParametroAfp,
        actualizarComisionAfp,
    };
}
