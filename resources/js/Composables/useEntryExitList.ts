import { ref, shallowRef, computed, watch } from 'vue';

export interface EntryExitRecord {
    id: string | number;
    fecha: string;
    personal: string;
    dni: string;
    regimen?: string;
    turno: string;
    hora_salida: string | null;
    hora_retorno: string | null;
    motivo: string;
    tipo_motivo?: string;
    reason_nombre?: string;
    papeleta?: string;
    is_pending: boolean;
}

export interface EntryExitFilter {
    search: string;
    turno: string;
    estado: string;
    fecha: string;
}

interface UseEntryExitListOptions {
    entries: () => EntryExitRecord[];
    initialFilters?: Partial<EntryExitFilter>;
}

export function useEntryExitList({ entries, initialFilters = {} }: UseEntryExitListOptions) {
    const localFilters = ref<EntryExitFilter>({
        search: '',
        turno: initialFilters.turno || '',
        estado: initialFilters.estado || '',
        fecha: initialFilters.fecha || '',
    });

    const currentPage = shallowRef(1);
    const perPage = shallowRef(10);

    const filteredEntries = computed<EntryExitRecord[]>(() => {
        let result = [...entries()];

        if (localFilters.value.search) {
            const searchLower = localFilters.value.search.toLowerCase();
            result = result.filter(entry =>
                (entry.personal && entry.personal.toLowerCase().includes(searchLower)) ||
                (entry.dni && entry.dni.toLowerCase().includes(searchLower)) ||
                (entry.motivo && entry.motivo.toLowerCase().includes(searchLower))
            );
        }

        if (localFilters.value.turno) {
            result = result.filter(entry => entry.turno === localFilters.value.turno);
        }

        if (localFilters.value.estado === 'pendiente') {
            result = result.filter(entry => entry.is_pending);
        } else if (localFilters.value.estado === 'completado') {
            result = result.filter(entry => !entry.is_pending);
        }

        if (localFilters.value.fecha) {
            result = result.filter(entry => entry.fecha === localFilters.value.fecha);
        }

        return result;
    });

    const paginatedEntries = computed<EntryExitRecord[]>(() => {
        const start = (currentPage.value - 1) * perPage.value;
        const end = start + perPage.value;
        return filteredEntries.value.slice(start, end);
    });

    const totalPages = computed(() => {
        return Math.ceil(filteredEntries.value.length / perPage.value) || 1;
    });

    // Reset page when filters change
    watch(localFilters, () => {
        currentPage.value = 1;
    }, { deep: true });

    const clearFilters = (): void => {
        localFilters.value = {
            search: '',
            turno: '',
            estado: '',
            fecha: '',
        };
    };

    return {
        localFilters,
        currentPage,
        perPage,
        filteredEntries,
        paginatedEntries,
        totalPages,
        clearFilters,
    };
}
