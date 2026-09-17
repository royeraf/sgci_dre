<template>
    <PlanillaSection
        title="Retenciones y Descuentos"
        description="Descuentos de ley, judiciales y administrativos."
        :icon="Percent"
        empty-description="Aquí se registrarán las retenciones y descuentos que se aplican a la remuneración de cada empleado."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { Percent } from 'lucide-vue-next';
</script>
