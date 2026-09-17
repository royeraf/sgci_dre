<template>
    <PlanillaSection
        title="Planillas"
        description="Generación y control de la planilla mensual del personal."
        :icon="Wallet"
        empty-description="Aquí se generarán las planillas por periodo, reutilizando los datos de los empleados registrados en Recursos Humanos."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { Wallet } from 'lucide-vue-next';
</script>
