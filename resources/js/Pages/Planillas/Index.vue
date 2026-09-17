<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-teal-600 to-cyan-600 bg-clip-text text-transparent">
                        Planillas y Remuneraciones
                    </h1>
                    <p class="mt-2 text-slate-600">Administración de planillas, boletas y conceptos del personal</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex overflow-x-auto">
                    <button v-for="tab in visibleTabs" :key="tab.key" @click="activeTab = tab.key"
                        class="cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 border-b-2 transition-colors duration-300"
                        :class="activeTab === tab.key
                            ? 'text-teal-600 border-teal-600'
                            : 'text-slate-500 border-transparent hover:text-slate-700'">
                        <component :is="tab.icon" class="w-5 h-5" />
                        {{ tab.label }}
                    </button>
                </nav>
            </div>

            <!-- Active Section -->
            <Transition name="fade-slide" mode="out-in">
                <component :is="activeComponent" :key="activeTab" />
            </Transition>
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout,
};
</script>

<script setup>
import { ref, computed } from 'vue';
import { useTabPermission } from '@/composables/useTabPermission';
import { PLANILLA_TABS, PLANILLA_TAB_KEYS } from '@/Composables/usePlanillaTabs';

import SummaryCards from '@/Components/Planillas/SummaryCards.vue';
import PlanillasTable from '@/Components/Planillas/PlanillasTable.vue';
import BoletasTable from '@/Components/Planillas/BoletasTable.vue';
import ConceptosTable from '@/Components/Planillas/ConceptosTable.vue';
import DescuentosTable from '@/Components/Planillas/DescuentosTable.vue';
import AportacionesTable from '@/Components/Planillas/AportacionesTable.vue';
import TardanzasTable from '@/Components/Planillas/TardanzasTable.vue';

const { canViewTab, firstAllowedTab } = useTabPermission('planillas', PLANILLA_TAB_KEYS);

const tabComponents = {
    planillas: PlanillasTable,
    boletas: BoletasTable,
    conceptos: ConceptosTable,
    descuentos: DescuentosTable,
    aportaciones: AportacionesTable,
    tardanzas: TardanzasTable,
};

const summary = ref({});
const activeTab = ref(firstAllowedTab.value);

const visibleTabs = computed(() => PLANILLA_TABS.filter((tab) => canViewTab(tab.key)));
const activeComponent = computed(() => tabComponents[activeTab.value] ?? PlanillasTable);
</script>
