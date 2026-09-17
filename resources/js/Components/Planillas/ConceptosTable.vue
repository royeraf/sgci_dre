<template>
    <PlanillaSection
        title="Remuneraciones y Conceptos"
        description="Haberes, bonificaciones y asignaciones por empleado."
        :icon="Coins"
        empty-description="Aquí se administrarán las remuneraciones base y los conceptos de haberes aplicables a cada régimen laboral."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { Coins } from 'lucide-vue-next';
</script>
