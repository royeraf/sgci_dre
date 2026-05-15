<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-6 sm:py-8 md:py-12 px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">

            <!-- Header -->
            <div class="mb-6 sm:mb-8">
                <h1 class="text-3xl sm:text-4xl font-black text-slate-800 tracking-tight">
                    Listado de Visitas
                </h1>
                <p class="mt-1 text-slate-500 font-medium">Dirección Regional de Educación Huánuco</p>
            </div>

            <!-- Filters -->
            <VisitFilters :filters="localFilters" search-placeholder="Buscar por DNI o nombre..." :hide-estado="true"
                @update:filters="updateFilters" @clear="clearFilters" />

            <!-- Table -->
            <div class="bg-white shadow-lg rounded-2xl border border-slate-200 overflow-hidden">
                <VisitTable :visits="visits" :readonly="true" @page-change="changePage" @update:perPage="updatePerPage" />
            </div>

            <!-- Footer -->
            <p class="text-center text-xs text-slate-400 mt-6 font-medium">
                Información de carácter público · DRE Huánuco
            </p>

        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { PaginatedVisits } from '@/Types/visitor';
import type { Filters } from '@/Composables/useVisitFilters';
import VisitFilters from '@/Components/ExternalVisit/List/VisitFilters.vue';
import VisitTable from '@/Components/ExternalVisit/List/VisitTable.vue';

const props = defineProps<{
    visits: PaginatedVisits;
    filters: Partial<Filters>;
}>();

const localFilters = ref<Filters>({
    search: props.filters.search || '',
    estado: props.filters.estado || '',
    fecha: props.filters.fecha || '',
    per_page: props.filters.per_page || 10,
});

let debounceTimer: ReturnType<typeof setTimeout>;

function applyFilters(page = 1) {
    router.get('/visitas/publico', { ...localFilters.value, page } as any, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

watch(localFilters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => applyFilters(1), 500);
}, { deep: true });

function updateFilters(newFilters: Filters) {
    localFilters.value = { ...newFilters };
}

function clearFilters() {
    localFilters.value = { search: '', estado: '', fecha: '', per_page: 10 };
    applyFilters(1);
}

function changePage(page: number) {
    applyFilters(page);
}

function updatePerPage(perPage: string | number) {
    localFilters.value.per_page = Number(perPage);
    applyFilters(1);
}
</script>
