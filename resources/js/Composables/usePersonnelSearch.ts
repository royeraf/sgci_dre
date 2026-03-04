import { ref, shallowRef, watch, nextTick, onUnmounted, useTemplateRef } from 'vue';

export interface PersonDisplay {
    nombre: string;
    dni: string;
    cargo: string;
    area: string;
    regimen: string;
}

export interface PersonResult {
    id: string;
    nombre: string;
    dni: string;
    cargo?: string;
    area?: string;
    regimen?: string;
}

interface UsePersonnelSearchOptions {
    onPersonSelected?: (person: PersonResult) => void;
    onPersonCleared?: () => void;
}

export function usePersonnelSearch(options: UsePersonnelSearchOptions = {}) {
    const searchQuery = ref('');
    const showDropdown = shallowRef(false);
    const searchResults = ref<PersonResult[]>([]);
    const isSearching = shallowRef(false);
    const scanMessage = shallowRef('');
    const scanStatus = shallowRef<'' | 'success' | 'error'>('');
    const selectedPersonDisplay = ref<PersonDisplay | null>(null);

    const searchInputRef = useTemplateRef<HTMLInputElement>('searchInputRef');
    const dropdownContainerRef = useTemplateRef<HTMLElement>('dropdownContainerRef');

    let searchTimeout: ReturnType<typeof setTimeout> | null = null;

    const performSearch = async (query: string): Promise<void> => {
        if (!query || query.length < 3) {
            searchResults.value = [];
            isSearching.value = false;
            return;
        }

        isSearching.value = true;
        showDropdown.value = true;
        try {
            const response = await fetch(`/entry-exits/api/search-personnel?query=${encodeURIComponent(query)}`);
            if (response.ok) {
                searchResults.value = await response.json();
                showDropdown.value = true;
            }
        } catch (e) {
            console.error('Search error:', e);
        } finally {
            isSearching.value = false;
        }
    };

    const performBarcodeSearch = async (dniValue: string): Promise<void> => {
        isSearching.value = true;
        scanMessage.value = '';
        scanStatus.value = '';
        showDropdown.value = false;

        try {
            const response = await fetch(`/entry-exits/api/search-personnel?query=${encodeURIComponent(dniValue)}`);
            if (response.ok) {
                const results: PersonResult[] = await response.json();
                const exactMatch = results.find(r => r.dni === dniValue);

                if (exactMatch) {
                    selectPerson(exactMatch);
                    scanMessage.value = `Personal encontrado: ${exactMatch.nombre}`;
                    scanStatus.value = 'success';
                    setTimeout(() => { scanMessage.value = ''; scanStatus.value = ''; }, 3000);
                } else if (results.length > 0) {
                    searchResults.value = results;
                    showDropdown.value = true;
                } else {
                    scanMessage.value = 'No se encontró personal con ese DNI';
                    scanStatus.value = 'error';
                    setTimeout(() => { scanMessage.value = ''; scanStatus.value = ''; }, 3000);
                }
            }
        } catch {
            scanMessage.value = 'Error al buscar';
            scanStatus.value = 'error';
        } finally {
            isSearching.value = false;
        }
    };

    // Watch search query with debounce + barcode detection
    watch(searchQuery, (val) => {
        if (searchTimeout) clearTimeout(searchTimeout);

        // Detect barcode scan (exactly 8 digits)
        if (/^\d{8}$/.test(val.trim())) {
            performBarcodeSearch(val.trim());
            return;
        }

        if (val.length >= 3) {
            showDropdown.value = true;
        }

        // Normal debounced text search
        searchTimeout = setTimeout(() => {
            performSearch(val);
        }, 500);
    });

    const selectPerson = (person: PersonResult): void => {
        selectedPersonDisplay.value = {
            nombre: person.nombre,
            dni: person.dni,
            cargo: person.cargo || 'N/A',
            area: person.area || 'N/A',
            regimen: person.regimen || '',
        };
        searchQuery.value = '';
        showDropdown.value = false;
        options.onPersonSelected?.(person);
    };

    const clearSelection = (): void => {
        selectedPersonDisplay.value = null;
        searchQuery.value = '';
        options.onPersonCleared?.();
        nextTick(() => { searchInputRef.value?.focus(); });
    };

    const handleClickOutside = (event: MouseEvent): void => {
        if (dropdownContainerRef.value && !dropdownContainerRef.value.contains(event.target as Node)) {
            showDropdown.value = false;
        }
    };

    const focusSearchInput = (): void => {
        nextTick(() => { searchInputRef.value?.focus(); });
    };

    // Cleanup timeout on unmount
    onUnmounted(() => {
        if (searchTimeout) clearTimeout(searchTimeout);
    });

    return {
        searchQuery,
        showDropdown,
        searchResults,
        isSearching,
        scanMessage,
        scanStatus,
        selectedPersonDisplay,
        searchInputRef,
        dropdownContainerRef,
        selectPerson,
        clearSelection,
        handleClickOutside,
        focusSearchInput,
    };
}
