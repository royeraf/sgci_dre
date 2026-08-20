import { ref, reactive } from 'vue';
import axios from 'axios';

export function usePapeletaList() {
    const pendientes = ref([]);
    const historial = ref([]);
    const stats = ref({ total: 0, pendientes: 0, pendientes_rrhh: 0, aprobadas: 0, desaprobadas: 0 });
    const loading = ref(false);

    const filtros = reactive({
        estado: 'TODOS',
        fecha_desde: '',
        fecha_hasta: '',
        direction_id: '',
        office_id: '',
    });

    const fetchPendientes = async () => {
        loading.value = true;
        try {
            const { data } = await axios.get('/papeletas/api/pendientes');
            pendientes.value = data;
        } catch (error) {
            console.error('Error fetching pendientes:', error);
        } finally {
            loading.value = false;
        }
    };

    const fetchHistorial = async () => {
        loading.value = true;
        try {
            const params: Record<string, string> = {};
            if (filtros.estado && filtros.estado !== 'TODOS') params.estado = filtros.estado;
            if (filtros.fecha_desde) params.fecha_desde = filtros.fecha_desde;
            if (filtros.fecha_hasta) params.fecha_hasta = filtros.fecha_hasta;
            if (filtros.direction_id) params.direction_id = filtros.direction_id;
            if (filtros.office_id) params.office_id = filtros.office_id;

            const { data } = await axios.get('/papeletas/api/historial', { params });
            historial.value = data;
        } catch (error) {
            console.error('Error fetching historial:', error);
        } finally {
            loading.value = false;
        }
    };

    const fetchStats = async () => {
        try {
            const { data } = await axios.get('/papeletas/api/stats');
            stats.value = data;
        } catch (error) {
            console.error('Error fetching stats:', error);
        }
    };

    return {
        pendientes,
        historial,
        stats,
        loading,
        filtros,
        fetchPendientes,
        fetchHistorial,
        fetchStats,
    };
}
