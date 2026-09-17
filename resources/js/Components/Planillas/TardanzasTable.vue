<template>
    <PlanillaSection
        title="Tardanzas"
        description="Cálculo de tardanzas a partir de las marcas de asistencia."
        :icon="Clock"
        empty-description="Aquí se calcularán las tardanzas del personal reutilizando las marcas de entrada y salida registradas en el módulo de Asistencia."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { Clock } from 'lucide-vue-next';
</script>
