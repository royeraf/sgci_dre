<template>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <AppointmentCard v-for="cita in citas" :key="cita.id" :cita="cita" />

        <!-- Empty State -->
        <div v-if="citas.length === 0"
            class="col-span-full text-center py-20 bg-white rounded-2xl shadow-xl border border-slate-200">
            <div class="flex flex-col items-center">
                <div class="bg-slate-100 rounded-full p-4 mb-4">
                    <CheckCircle class="h-12 w-12 text-slate-400" />
                </div>
                <h3 class="text-lg font-black text-slate-900 mb-1">No hay citas finalizadas</h3>
                <p class="text-sm text-slate-500 font-medium max-w-xs mx-auto">
                    Aquí se mostrará el histórico de citas que ya han sido atendidas o canceladas.
                </p>
            </div>
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
