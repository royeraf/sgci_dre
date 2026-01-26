<template>
    <div class="bg-emerald-50 rounded-lg border border-emerald-200 p-4">
        <div class="flex items-center gap-3">
            <div class="bg-emerald-500 p-2 rounded-lg">
                <ScanBarcode class="w-5 h-5 text-white" />
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-bold text-slate-800">Escáner de Códigos de Barras</h3>
                <p class="text-xs text-slate-600">Escanee el DNI para registrar la salida</p>
            </div>
        </div>

        <div class="mt-3 space-y-2">
            <!-- Scanner Input -->
            <div class="relative">
                <input
                    ref="scannerInput"
                    type="text"
                    v-model="dni"
                    @input="onlyNumbers"
                    @keyup.enter="searchVisit"
                    inputmode="numeric"
                    pattern="[0-9]*"
                    maxlength="8"
                    placeholder="Escanee o escriba el DNI..."
                    class="w-full px-3 py-2 pl-10 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all text-sm outline-none"
                    :class="statusClasses"
                    :disabled="isSearching"
                />
                <div class="absolute left-3 top-1/2 -translate-y-1/2">
                    <Loader2 v-if="isSearching" class="w-4 h-4 text-emerald-600 animate-spin" />
                    <Hash v-else class="w-4 h-4 text-slate-400" />
                </div>
                <button
                    v-if="dni && !isSearching"
                    @click="clearSearch"
                    type="button"
                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Status Messages -->
            <div v-if="statusMessage" class="flex items-center gap-2 p-2 rounded-lg text-xs" :class="statusMessageClasses">
                <component :is="statusIcon" class="w-4 h-4 shrink-0" />
                <span class="font-medium">{{ statusMessage }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { ScanBarcode, Loader2, X, Hash, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const emit = defineEmits(['visitFound']);

const scannerInput = ref(null);
const dni = ref('');
const isSearching = ref(false);
const statusMessage = ref('');
const statusType = ref(''); // 'success', 'error'

// Auto-focus on mount
onMounted(() => {
    nextTick(() => {
        scannerInput.value?.focus();
    });
});

// Computed classes based on status
const statusClasses = computed(() => {
    if (statusType.value === 'success') return 'border-2 border-green-400 bg-green-50';
    if (statusType.value === 'error') return 'border-2 border-red-400 bg-red-50';
    return 'border border-slate-300 bg-white';
});

const statusMessageClasses = computed(() => {
    if (statusType.value === 'success') return 'bg-green-100 text-green-800';
    if (statusType.value === 'error') return 'bg-red-100 text-red-800';
    return '';
});

const statusIcon = computed(() => {
    return statusType.value === 'success' ? CheckCircle2 : AlertCircle;
});

// Search visit by DNI
const searchVisit = async () => {
    if (!dni.value || dni.value.length !== 8) {
        statusMessage.value = 'DNI debe tener 8 dígitos';
        statusType.value = 'error';
        return;
    }

    isSearching.value = true;
    statusMessage.value = '';
    statusType.value = '';

    try {
        const response = await axios.get('/visitors/api/find-pending', {
            params: { dni: dni.value }
        });

        if (response.data.success) {
            statusMessage.value = 'Visita encontrada, abriendo...';
            statusType.value = 'success';

            setTimeout(() => {
                emit('visitFound', response.data.visit);
                clearSearch();
            }, 500);
        }
    } catch (error) {
        statusMessage.value = error.response?.status === 404
            ? 'No hay visita pendiente para este DNI'
            : 'Error al buscar';
        statusType.value = 'error';

        setTimeout(() => {
            if (statusType.value === 'error') {
                clearSearch();
            }
        }, 2500);
    } finally {
        isSearching.value = false;
    }
};

// Only allow numbers
const onlyNumbers = (event) => {
    dni.value = event.target.value.replace(/[^0-9]/g, '');
};

// Clear search
const clearSearch = () => {
    dni.value = '';
    statusMessage.value = '';
    statusType.value = '';
    nextTick(() => {
        scannerInput.value?.focus();
    });
};

// Focus method exposed to parent
const focusInput = () => {
    nextTick(() => {
        scannerInput.value?.focus();
    });
};

// Expose methods to parent
defineExpose({
    focusInput
});
</script>
