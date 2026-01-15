<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <AppointmentCard v-for="cita in citas" :key="cita.id" :cita="cita" />

        <!-- Empty State -->
        <div v-if="citas.length === 0"
            class="col-span-full text-center py-12 bg-white rounded-xl shadow-sm border border-gray-200">
            <CheckCircle class="mx-auto h-12 w-12 text-rose-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay citas finalizadas</h3>
            <p class="mt-1 text-sm text-gray-500">Las citas atendidas o canceladas aparecerán aquí.</p>
        </div>
    </div>
</template>

<script setup>
import AppointmentCard from './AppointmentCard.vue';
import { CheckCircle } from 'lucide-vue-next';

defineProps({
    citas: {
        type: Array,
        required: true
    }
});
</script>
