<script setup>
import { ref } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { usePapeletaApproval } from '@/Composables/usePapeletaApproval';

const props = defineProps({
    papeleta: { type: Object, required: true },
});

const emit = defineEmits(['close', 'approved']);

const comentario = ref('');
const { processing, aprobar } = usePapeletaApproval();

const handleConfirm = async () => {
    try {
        await aprobar(props.papeleta.id, comentario.value);
        window.Swal?.fire({ icon: 'success', title: 'Papeleta aprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        emit('approved');
    } catch {
        // error handled in composable
    }
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Aprobar Papeleta</h3>
                <p class="text-sm text-slate-600 mb-4">
                    Papeleta <strong class="text-blue-600">{{ papeleta.numero_papeleta }}</strong> de
                    <strong>{{ papeleta.employee?.person?.apellidos }}, {{ papeleta.employee?.person?.nombres }}</strong>
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Comentario (opcional)</label>
                    <textarea v-model="comentario" rows="3"
                        class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                        placeholder="Agregue un comentario..."></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="$emit('close')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                        Cancelar
                    </button>
                    <button @click="handleConfirm" :disabled="processing"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors">
                        <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                        Confirmar Aprobación
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
