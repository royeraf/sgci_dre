import { ref } from 'vue';
import axios from 'axios';

export interface Nota {
    id: string;
    texto: string;
    autor: string | null;
    fecha: string;
    hora: string;
    editado: boolean;
}

export function usePlanillaNotas() {
    const notas = ref<Nota[]>([]);
    const loading = ref(false);
    const saving = ref(false);

    const fetchNotas = async (employeeId: string): Promise<void> => {
        loading.value = true;
        try {
            const { data } = await axios.get('/planillas/notas', {
                params: { employee_id: employeeId },
            });
            notas.value = data.notas;
        } finally {
            loading.value = false;
        }
    };

    const crearNota = async (payload: { employee_id: string; texto: string }): Promise<void> => {
        saving.value = true;
        try {
            await axios.post('/planillas/notas', payload);
            await fetchNotas(payload.employee_id);
        } finally {
            saving.value = false;
        }
    };

    const actualizarNota = async (
        id: string,
        payload: { texto: string },
        employeeId: string
    ): Promise<void> => {
        saving.value = true;
        try {
            await axios.put(`/planillas/notas/${id}`, payload);
            await fetchNotas(employeeId);
        } finally {
            saving.value = false;
        }
    };

    const eliminarNota = async (id: string, employeeId: string): Promise<void> => {
        saving.value = true;
        try {
            await axios.delete(`/planillas/notas/${id}`);
            await fetchNotas(employeeId);
        } finally {
            saving.value = false;
        }
    };

    return {
        notas,
        loading,
        saving,
        fetchNotas,
        crearNota,
        actualizarNota,
        eliminarNota,
    };
}
