<template>
    <div class="space-y-4">
        <div class="flex justify-end">
            <button @click="showRegimenes = true"
                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 shadow-lg transition-all duration-300 hover:-translate-y-0.5">
                <Landmark class="w-4 h-4 mr-2" />
                Regímenes de pensión
            </button>
        </div>

        <ConceptosManager tipo="DESCUENTO" />

        <RegimenesManagerModal v-if="showRegimenes" @close="showRegimenes = false" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Landmark } from 'lucide-vue-next';
import ConceptosManager from '@/Components/Planillas/Conceptos/ConceptosManager.vue';
import RegimenesManagerModal from '@/Components/Planillas/Remuneraciones/RegimenesManagerModal.vue';

const showRegimenes = ref(false);
</script>
