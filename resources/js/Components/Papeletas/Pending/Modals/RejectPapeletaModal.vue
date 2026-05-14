<script setup>
import { ref } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { usePapeletaApproval } from '@/Composables/usePapeletaApproval';

const props = defineProps({
    papeleta: { type: Object, required: true },
});

const emit = defineEmits(['close', 'rejected']);

const comentario = ref('');
const error = ref('');
const { processing, desaprobar } = usePapeletaApproval();

const handleConfirm = async () => {
    if (!comentario.value.trim()) {
        error.value = 'Debe indicar el motivo del rechazo';
        return;
    }
    error.value = '';
    try {
        await desaprobar(props.papeleta.id, comentario.value);
        window.Swal?.fire({ icon: 'success', title: 'Papeleta desaprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        emit('rejected');
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
                <h3 class="text-lg font-bold text-slate-900 mb-4">Desaprobar Papeleta</h3>
                <p class="text-sm text-slate-600 mb-4">
                    Papeleta <strong class="text-blue-600">{{ papeleta.numero_papeleta }}</strong> de
                    <strong>{{ papeleta.employee?.person?.apellidos }}, {{ papeleta.employee?.person?.nombres }}</strong>
                </p>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo del rechazo *</label>
                    <textarea v-model="comentario" rows="3"
                        class="w-full rounded-xl border px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                        :class="error ? 'border-red-300' : 'border-slate-200'"
                        placeholder="Indique el motivo del rechazo..."></textarea>
                    <p v-if="error" class="mt-1 text-xs text-red-500">{{ error }}</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button @click="$emit('close')"
                        class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                        Cancelar
                    </button>
                    <button @click="handleConfirm" :disabled="processing"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                        <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                        Confirmar Rechazo
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
