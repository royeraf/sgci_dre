import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export interface Filters {
    search: string;
    estado: string;
    fecha: string;
    per_page: number;
    page?: number;
}

export function useVisitFilters(initialFilters: Partial<Filters>) {
    const localFilters = ref<Filters>({
        search: initialFilters.search || '',
        estado: initialFilters.estado || '',
        fecha: initialFilters.fecha || '',
        per_page: initialFilters.per_page || 10,
    });

    // Debounce helper
    const debounce = (fn: Function, delay: number) => {
        let timeoutId: ReturnType<typeof setTimeout>;
        return (...args: any[]) => {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => fn(...args), delay);
        };
    };

    const applyFilters = () => {
        router.get('/visitors', localFilters.value as any, {
            preserveState: true,
            preserveScroll: true,
            replace: true
        });
    };

    const debouncedApply = debounce(applyFilters, 500);

    watch(localFilters, () => {
        debouncedApply();
    }, { deep: true });

    const updateFilters = (newFilters: Filters) => {
        localFilters.value = { ...newFilters };
    };

    const changePage = (page: number) => {
        router.get('/visitors', { ...localFilters.value, page } as any, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const updatePerPage = (newPerPage: string | number) => {
        localFilters.value.per_page = Number(newPerPage);
        router.get('/visitors', { ...localFilters.value, page: 1 } as any, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        localFilters.value = {
            search: '',
            estado: '',
            fecha: '',
            per_page: 10
        };
        applyFilters();
    };

    return {
        localFilters,
        updateFilters,
        changePage,
        updatePerPage,
        clearFilters,
        applyFilters
    };
}
