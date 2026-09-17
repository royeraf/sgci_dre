<template>
    <PlanillaSection
        title="Aportaciones del Empleado"
        description="Aportes previsionales y de salud del personal."
        :icon="PiggyBank"
        empty-description="Aquí se administrarán las aportaciones del empleado (pensiones, salud y otras) con sus porcentajes y bases de cálculo."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { PiggyBank } from 'lucide-vue-next';
</script>
