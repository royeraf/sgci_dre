import { ref } from 'vue';
import axios from 'axios';

export interface PlanillaRegimen {
    id: string;
    nombre: string;
    tipo: 'AFP' | 'ONP';
    aporte_obligatorio: number;
    prima_seguro: number;
    comision_fija: number | null;
    comision_mixta: number | null;
    comision_flujo: number | null;
    activo: boolean;
}

export interface RegimenPayload {
    nombre: string;
    tipo: 'AFP' | 'ONP';
    aporte_obligatorio: number;
    prima_seguro: number;
    comision_fija: number | null;
    activo: boolean;
}

// Estado compartido: el catálogo se edita desde varios lugares
// (perfil de pensión, pestaña de descuentos) y todos deben verse al día.
const regimenes = ref<PlanillaRegimen[]>([]);
const loading = ref(false);
const saving = ref(false);

export function usePlanillaRegimenes() {
    const fetchRegimenes = async (todos = true): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/regimenes-pensionarios', {
                params: todos ? { todos: 1 } : {},
            });
            regimenes.value = data;
        } finally {
            loading.value = false;
        }
    };

    const crearRegimen = async (payload: RegimenPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/regimenes-pensionarios', payload);
            await fetchRegimenes();
        } finally {
            saving.value = false;
        }
    };

    const actualizarRegimen = async (id: string, payload: RegimenPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/regimenes-pensionarios/${id}`, payload);
            await fetchRegimenes();
        } finally {
            saving.value = false;
        }
    };

    const eliminarRegimen = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/regimenes-pensionarios/${id}`);
            await fetchRegimenes();
        } finally {
            saving.value = false;
        }
    };

    return {
        regimenes,
        loading,
        saving,
        fetchRegimenes,
        crearRegimen,
        actualizarRegimen,
        eliminarRegimen,
    };
}
