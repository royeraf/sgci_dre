<template>
    <div class="space-y-4 sm:space-y-6">
        <!-- Search -->
        <div>
            <label class="block text-sm font-bold text-slate-700 mb-2">Ingrese su DNI para consultar sus citas</label>
            <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <div class="flex-1">
                    <input v-model="dniInput" type="text" placeholder="Ingrese su DNI (8 dígitos)" maxlength="8"
                        @keyup.enter="consultCitas"
                        :class="['block w-full rounded-xl shadow-sm text-base border px-3 sm:px-4 py-2.5 transition-colors outline-none focus:ring-2',
                            dniError ? 'border-red-500 focus:ring-red-500 focus:border-red-500' : 'border-slate-200 focus:ring-blue-500 focus:border-blue-500']">
                    <p v-if="dniError" class="mt-1 text-sm text-red-600">{{ dniError }}</p>
                </div>
                <button @click="consultCitas" :disabled="loading"
                    class="w-full sm:w-auto inline-flex justify-center items-center py-2.5 px-6 border border-transparent shadow-sm text-base font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-blue-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    :aria-label="loading ? 'Buscando citas' : 'Buscar citas'">
                    <LoaderCircle v-if="loading" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" />
                    <Search v-else class="w-4 h-4 sm:w-5 sm:h-5 mr-2" />
                    <span>{{ loading ? 'Buscando...' : 'Buscar' }}</span>
                </button>
            </div>
        </div>

        <!-- Skeleton Loader -->
        <div v-if="loading" class="mt-4 sm:mt-6 space-y-4 sm:space-y-6">
            <div v-for="i in 2" :key="i" class="border rounded-2xl p-4 sm:p-6 bg-white animate-pulse">
                <div class="flex justify-between items-start mb-4">
                    <div class="space-y-2">
                        <div class="h-6 bg-gray-200 rounded w-48"></div>
                        <div class="h-4 bg-gray-100 rounded w-32"></div>
                        <div class="h-3 bg-gray-50 rounded w-24"></div>
                    </div>
                    <div class="h-10 bg-gray-200 rounded-full w-28"></div>
                </div>
                <div class="py-4 border-t border-b border-gray-100 mt-4">
                    <div class="h-3 bg-gray-100 rounded w-20 mb-4"></div>
                    <div class="flex justify-center items-center gap-12 relative h-20">
                        <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
                        <div class="w-20 h-[2px] bg-gray-100"></div>
                        <div class="w-12 h-12 bg-gray-200 rounded-full"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results -->
        <div v-if="results.length > 0 && !loading" class="mt-4 sm:mt-6 space-y-4 sm:space-y-6">
            <AppointmentStatusCard v-for="cita in results" :key="cita.id" :cita="cita"
                @generate-voucher="$emit('generate-voucher', $event)" />
        </div>

        <!-- Empty State -->
        <div v-else-if="searched && !loading" class="text-center text-gray-500 py-8 sm:py-12">
            <SearchX class="mx-auto h-10 w-10 sm:h-12 sm:w-12 text-gray-400 mb-3" />
            <p class="text-sm sm:text-base">No se encontraron citas para el DNI ingresado.</p>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Search, LoaderCircle, SearchX } from 'lucide-vue-next';
import AppointmentStatusCard from './AppointmentStatusCard.vue';

const emit = defineEmits(['generate-voucher']);

const dniInput = ref('');
const dniError = ref('');
const results = ref([]);
const searched = ref(false);
const loading = ref(false);

const validateDni = () => {
    dniError.value = '';
    if (!dniInput.value) {
        dniError.value = 'Ingrese su DNI';
        return false;
    }
    if (!/^\d{8}$/.test(dniInput.value)) {
        dniError.value = 'El DNI debe tener 8 dígitos';
        return false;
    }
    return true;
};

const consultCitas = async () => {
    if (!validateDni()) return;

    loading.value = true;
    try {
        const response = await axios.get(`/citas/status/${dniInput.value}`);
        results.value = response.data;
        searched.value = true;
    } catch (error) {
        console.error(error);
        results.value = [];
    } finally {
        loading.value = false;
    }
};
</script>
