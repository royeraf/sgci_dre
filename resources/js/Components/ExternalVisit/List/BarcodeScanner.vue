<script setup lang="ts">
import { ref, computed, onMounted, nextTick } from 'vue';
import { ScanBarcode, Loader2, X, Hash, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';
import { Visit } from '@/Types/visitor';

const emit = defineEmits<{
    (e: 'visitFound', visit: Visit): void;
}>();

const scannerInput = ref<HTMLInputElement | null>(null);
const dni = ref('');
const isSearching = ref(false);
const statusMessage = ref('');
const statusType = ref<'success' | 'error' | ''>('');

// Auto-focus on mount
onMounted(() => {
    nextTick(() => {
        scannerInput.value?.focus();
    });
});

// Computed classes based on status
const statusClasses = computed(() => {
    if (statusType.value === 'success') return 'border-emerald-500 bg-emerald-50 text-emerald-900 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500';
    if (statusType.value === 'error') return 'border-red-500 bg-red-50 text-red-900 focus:ring-4 focus:ring-red-500/20 focus:border-red-500';
    return 'border-slate-200 bg-white text-slate-900 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500';
});

const statusMessageClasses = computed(() => {
    if (statusType.value === 'success') return 'bg-emerald-100 text-emerald-800 border-emerald-200';
    if (statusType.value === 'error') return 'bg-red-100 text-red-800 border-red-200';
    return '';
});

const statusIcon = computed(() => {
    return statusType.value === 'success' ? CheckCircle2 : AlertCircle;
});

// Only allow numbers
const onlyNumbers = (event: Event) => {
    const target = event.target as HTMLInputElement;
    dni.value = target.value.replace(/[^0-9]/g, '');
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
    } catch (error: any) {
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

<template>
    <div class="bg-emerald-50 rounded-xl border border-emerald-200 p-3 w-full flex flex-col sm:flex-row sm:items-center gap-3">
        <!-- Icon and Text -->
        <div class="flex items-center gap-3 shrink-0">
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 p-2 rounded-lg shadow-sm">
                <ScanBarcode class="w-5 h-5 text-white" />
            </div>
            <div class="hidden sm:block">
                <h3 class="text-sm font-bold text-slate-800 leading-tight">Escáner de Salida</h3>
                <p class="text-[11px] text-slate-600 uppercase tracking-wider font-semibold mt-0.5">DNI del Visitante</p>
            </div>
        </div>

        <!-- Scanner Input & Status Message -->
        <div class="flex-1 relative min-w-0">
            <input ref="scannerInput" type="text" v-model="dni" @input="onlyNumbers" @keyup.enter="searchVisit"
                inputmode="numeric" pattern="[0-9]*" maxlength="8" placeholder="Escriba o escanee el DNI..."
                class="w-full px-3 py-2.5 pl-10 pr-10 border-2 rounded-xl transition-all text-sm outline-none placeholder:text-slate-400 cursor-text"
                :class="statusClasses" :disabled="isSearching" />
            
            <div class="absolute left-3 top-1/2 -translate-y-1/2">
                <Loader2 v-if="isSearching" class="w-4 h-4 text-emerald-600 animate-spin" />
                <Hash v-else class="w-4 h-4 text-slate-400" />
            </div>

            <!-- Clear Button -->
            <button v-if="dni && !isSearching && !statusMessage" @click="clearSearch" type="button"
                class="cursor-pointer absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors">
                <X class="w-4 h-4" />
            </button>

            <!-- Status Messages (Floating inside input) -->
            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 translate-x-2"
                enter-to-class="opacity-100 translate-x-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-x-0"
                leave-to-class="opacity-0 translate-x-2"
            >
                <div v-if="statusMessage" class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold shadow-sm border"
                    :class="statusMessageClasses">
                    <component :is="statusIcon" class="w-3.5 h-3.5 shrink-0" />
                    <span class="whitespace-nowrap">{{ statusMessage }}</span>
                </div>
            </Transition>
        </div>
    </div>
</template>
