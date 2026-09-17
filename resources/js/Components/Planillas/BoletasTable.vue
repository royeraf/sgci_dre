<template>
    <PlanillaSection
        title="Boletas de Pago"
        description="Emisión y consulta de boletas de pago por periodo."
        :icon="Receipt"
        empty-description="Aquí se generarán y descargarán las boletas de pago de cada empleado, con el detalle de haberes, retenciones y aportaciones."
    />
</template>

<script setup>
import PlanillaSection from '@/Components/Planillas/PlanillaSection.vue';
import { Receipt } from 'lucide-vue-next';
</script>
