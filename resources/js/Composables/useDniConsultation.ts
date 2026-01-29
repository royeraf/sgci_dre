import { ref } from 'vue';
import axios from 'axios';

interface Persona {
    dni: string;
    nombres: string;
    apellido_paterno?: string;
    apellido_materno?: string;
    nombre_completo: string;
}

export function useDniConsultation() {
    const isConsultando = ref(false);
    const consultaMessage = ref('');
    const consultaSuccess = ref(false);

    const consultarDni = async (dni: string) => {
        if (dni.length !== 8) return null;
        
        isConsultando.value = true;
        consultaMessage.value = '';
        consultaSuccess.value = false;

        try {
            const response = await axios.get('/visitors/api/consultar-dni', { params: { dni } });
            if (response.data.success && response.data.data) {
                const persona: Persona = response.data.data;
                consultaMessage.value = `Datos encontrados: ${persona.nombre_completo}`;
                consultaSuccess.value = true;
                return persona;
            } else {
                consultaMessage.value = response.data.message || 'No encontrado';
                consultaSuccess.value = false;
                return null;
            }
        } catch (e) {
            consultaMessage.value = 'Error al consultar';
            consultaSuccess.value = false;
            return null;
        } finally {
            isConsultando.value = false;
        }
    };

    return {
        isConsultando,
        consultaMessage,
        consultaSuccess,
        consultarDni
    };
}
