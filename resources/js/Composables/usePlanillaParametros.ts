import { ref } from 'vue';
import axios from 'axios';

export interface PlanillaParametro {
    id: string;
    anio: number;
    uit: number;
    pct_tope_essalud: number;
    rmv: number | null;
    tasa_essalud: number;
    tope_essalud: number;
    activo: boolean;
}

export function usePlanillaParametros() {
    const parametros = ref<PlanillaParametro[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchParametros = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/parametros');
            parametros.value = data.map((p) => ({
                ...p,
                uit: Number(p.uit),
                pct_tope_essalud: Number(p.pct_tope_essalud) * 100,
                rmv: p.rmv === null ? null : Number(p.rmv),
                tasa_essalud: Number(p.tasa_essalud) * 100,
                tope_essalud: Number(p.tope_essalud),
            }));
        } finally {
            loading.value = false;
        }
    };

    const actualizarParametro = async (
        id: string,
        payload: { uit: number; pct_tope_essalud: number; rmv: number | null; tasa_essalud: number; activo: boolean },
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/parametros/${id}`, payload);
            await fetchParametros();
        } finally {
            saving.value = false;
        }
    };

    return {
        parametros,
        loading,
        saving,
        fetchParametros,
        actualizarParametro,
    };
}
