import { ref } from 'vue';
import axios from 'axios';

export interface PlanillaBanco {
    id: string;
    nombre: string;
    codigo: string | null;
    activo: boolean;
}

export interface BancoPayload {
    nombre: string;
    codigo: string | null;
    activo: boolean;
}

export function usePlanillaBancos() {
    const bancos = ref<PlanillaBanco[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchBancos = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/bancos');
            bancos.value = data;
        } finally {
            loading.value = false;
        }
    };

    const crearBanco = async (payload: BancoPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/bancos', payload);
            await fetchBancos();
        } finally {
            saving.value = false;
        }
    };

    const actualizarBanco = async (id: string, payload: BancoPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/bancos/${id}`, payload);
            await fetchBancos();
        } finally {
            saving.value = false;
        }
    };

    const eliminarBanco = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/bancos/${id}`);
            await fetchBancos();
        } finally {
            saving.value = false;
        }
    };

    return {
        bancos,
        loading,
        saving,
        fetchBancos,
        crearBanco,
        actualizarBanco,
        eliminarBanco,
    };
}
