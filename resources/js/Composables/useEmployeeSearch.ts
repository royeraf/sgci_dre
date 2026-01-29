import { ref, computed } from 'vue';

export interface Employee {
    id: string | number;
    nombre_completo: string;
    dni: string;
}

export function useEmployeeSearch(employees: Employee[]) {
    const searchQuery = ref('');
    const showDropdown = ref(false);

    const normalizeText = (text: string) => {
        if (!text) return '';
        return text
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    };

    const filteredEmployees = computed(() => {
        if (!searchQuery.value || !searchQuery.value.trim()) return employees.slice(0, 10);

        const search = normalizeText(searchQuery.value);
        const searchTerms = search.split(' ').filter(term => term.length > 0);

        return employees.filter(emp => {
            const normalizedName = normalizeText(emp.nombre_completo);
            const dni = emp.dni || '';

            if (dni.includes(searchQuery.value.trim())) {
                return true;
            }

            return searchTerms.every(term => normalizedName.includes(term));
        }).slice(0, 10);
    });

    return {
        searchQuery,
        showDropdown,
        filteredEmployees
    };
}
