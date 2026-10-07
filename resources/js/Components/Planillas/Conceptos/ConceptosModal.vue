<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full z-10 overflow-hidden">
                <button @click="$emit('close')" title="Cerrar"
                    class="cursor-pointer absolute top-5 right-5 z-20 p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-all">
                    <X class="w-6 h-6" />
                </button>

                <ConceptosManager :tipo="tipo" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { X } from 'lucide-vue-next';
import ConceptosManager from '@/Components/Planillas/Conceptos/ConceptosManager.vue';

defineProps({
    tipo: { type: String, required: true },
});

defineEmits(['close']);
</script>
