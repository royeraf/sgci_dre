<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <ScanBarcode class="w-6 h-6" />
                            Escanear Código de Barras
                        </h3>
                        <p class="text-emerald-100 text-sm mt-1">Escanee o ingrese el código manualmente</p>
                    </div>
                    <button @click="$emit('close')" class="text-emerald-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-5">
                    <!-- Scanner Input -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Código de Barras
                        </label>
                        <div class="relative">
                            <ScanBarcode
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-emerald-500" />
                            <input ref="scanInput" v-model="barcodeValue" type="text"
                                @keydown.enter.prevent="handleScan" placeholder="Escanee o ingrese el código..."
                                autofocus
                                class="w-full pl-10 pr-12 py-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-lg font-mono font-bold text-slate-800 placeholder:text-slate-400 placeholder:font-normal placeholder:text-base outline-none" />
                            <button v-if="barcodeValue" @click="barcodeValue = ''; focusInput()"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors">
                                <X class="w-5 h-5" />
                            </button>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 flex items-center gap-1.5">
                            <Zap class="w-3.5 h-3.5 text-amber-500" />
                            El lector de código de barras ingresará el código automáticamente
                        </p>
                    </div>

                    <!-- Scanning animation -->
                    <div v-if="isSearching" class="flex items-center justify-center py-4">
                        <div class="flex items-center gap-3 text-emerald-600">
                            <Loader2 class="w-5 h-5 animate-spin" />
                            <span class="text-sm font-medium">Buscando bien...</span>
                        </div>
                    </div>

                    <!-- Result -->
                    <div v-if="foundAsset" class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 space-y-3">
                        <div class="flex items-center gap-2 text-emerald-700">
                            <CircleCheckBig class="w-5 h-5" />
                            <span class="text-sm font-bold">Bien encontrado</span>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-emerald-100">
                            <p class="font-mono font-bold text-slate-800">
                                {{ foundAsset.codigo_patrimonio }}{{ foundAsset.codigo_interno }}
                            </p>
                            <p class="text-sm text-slate-600 mt-0.5">{{ foundAsset.denominacion }}</p>
                            <div class="flex items-center gap-3 mt-2 text-xs text-slate-500">
                                <span v-if="foundAsset.brand">{{ foundAsset.brand.nombre }}</span>
                                <span v-if="foundAsset.numero_serie" class="font-mono">S/N: {{ foundAsset.numero_serie
                                    }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Not Found -->
                    <div v-if="notFound" class="bg-red-50 border border-red-200 rounded-xl p-4">
                        <div class="flex items-center gap-2 text-red-700">
                            <CircleX class="w-5 h-5" />
                            <span class="text-sm font-bold">No se encontró ningún bien con ese código</span>
                        </div>
                        <p class="text-xs text-red-600 mt-1">Verifique el código e intente nuevamente</p>
                    </div>

                    <!-- Recent scans -->
                    <div v-if="recentScans.length > 0">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Escaneos recientes
                        </p>
                        <div class="space-y-1.5 max-h-32 overflow-y-auto">
                            <button v-for="(scan, index) in recentScans" :key="index" @click="applyRecentScan(scan)"
                                class="w-full text-left px-3 py-2 bg-slate-50 hover:bg-slate-100 rounded-lg transition-colors flex items-center justify-between group">
                                <div>
                                    <span class="font-mono font-bold text-sm text-slate-700">{{ scan.code }}</span>
                                    <span class="text-xs text-slate-500 ml-2">{{ scan.name }}</span>
                                </div>
                                <Search
                                    class="w-3.5 h-3.5 text-slate-400 opacity-0 group-hover:opacity-100 transition-opacity" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 border-t border-slate-200 flex justify-between items-center bg-slate-50">
                    <button @click="$emit('close')"
                        class="px-5 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm">
                        Cerrar
                    </button>
                    <button @click="applySearch" :disabled="!foundAsset"
                        class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 shadow-lg shadow-emerald-600/20 text-sm">
                        Buscar en la Lista
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue';
import {
    ScanBarcode,
    X,
    Loader2,
    CircleCheckBig,
    CircleX,
    Search,
    Zap,
} from 'lucide-vue-next';
import axios from 'axios';

const emit = defineEmits(['close', 'found']);

const scanInput = ref(null);
const barcodeValue = ref('');
const isSearching = ref(false);
const foundAsset = ref(null);
const notFound = ref(false);
const recentScans = ref([]);

const focusInput = () => {
    nextTick(() => {
        scanInput.value?.focus();
    });
};

const handleScan = async () => {
    const code = barcodeValue.value.trim();
    if (!code) return;

    isSearching.value = true;
    foundAsset.value = null;
    notFound.value = false;

    try {
        const response = await axios.get('/assets/list', {
            params: { search: code, per_page: 1 },
        });

        if (response.data.data.length > 0) {
            foundAsset.value = response.data.data[0];
            notFound.value = false;

            // Add to recent scans
            const scanEntry = {
                code: code,
                name: foundAsset.value.denominacion,
            };
            recentScans.value = [scanEntry, ...recentScans.value.filter(s => s.code !== code)].slice(0, 5);
        } else {
            notFound.value = true;
        }
    } catch (error) {
        console.error('Error searching asset:', error);
        notFound.value = true;
    } finally {
        isSearching.value = false;
    }
};

const applySearch = () => {
    if (foundAsset.value) {
        const code = foundAsset.value.codigo_barras || foundAsset.value.codigo_completo || (foundAsset.value.codigo_patrimonio + foundAsset.value.codigo_interno);
        emit('found', code);
    }
};

const applyRecentScan = (scan) => {
    barcodeValue.value = scan.code;
    handleScan();
};

onMounted(() => {
    focusInput();
});
</script>
