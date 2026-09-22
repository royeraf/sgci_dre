import { ref } from 'vue';
import axios from 'axios';
import type { PlanillaConcepto, TipoConcepto } from '@/Composables/useRemuneraciones';

export type { PlanillaConcepto, TipoConcepto };

export interface ConceptoPayload {
    codigo: string;
    nombre: string;
    tipo: TipoConcepto;
    categoria: string | null;
    afecto_renta5: boolean;
    afecto_essalud: boolean;
    afecto_onp: boolean;
    afecto_afp: boolean;
    es_porcentaje: boolean;
    valor: number | null;
    orden: number;
    activo: boolean;
}

export function usePlanillaConceptos() {
    const conceptos = ref<PlanillaConcepto[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchConceptos = async (): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/conceptos');
            conceptos.value = data;
        } finally {
            loading.value = false;
        }
    };

    const crearConcepto = async (payload: ConceptoPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/conceptos', payload);
            await fetchConceptos();
        } finally {
            saving.value = false;
        }
    };

    const actualizarConcepto = async (id: string, payload: ConceptoPayload): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/conceptos/${id}`, payload);
            await fetchConceptos();
        } finally {
            saving.value = false;
        }
    };

    const eliminarConcepto = async (id: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/conceptos/${id}`);
            await fetchConceptos();
        } finally {
            saving.value = false;
        }
    };

    return {
        conceptos,
        loading,
        saving,
        fetchConceptos,
        crearConcepto,
        actualizarConcepto,
        eliminarConcepto,
    };
}
