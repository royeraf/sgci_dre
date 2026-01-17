<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Registrar Gasto de Mantenimiento
                        </h3>
                        <p class="text-emerald-100 text-sm mt-1">Ingrese los detalles del gasto realizado</p>
                    </div>
                    <button @click="$emit('close')" class="text-emerald-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="save" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Vehículo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Vehículo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.vehicle_id" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="">Seleccionar vehículo</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }}</option>
                            </select>
                        </div>

                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="form.fecha" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        </div>

                        <!-- Costo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Costo (S/) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" step="0.01" v-model="form.costo" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="0.00">
                        </div>

                        <!-- Factura -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nº Factura / Boleta</label>
                            <input type="text" v-model="form.factura"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Ej: F001-00012345">
                        </div>

                        <!-- Proveedor -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Proveedor</label>
                            <input type="text" v-model="form.proveedor"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Nombre del proveedor">
                        </div>

                        <!-- Detalle -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Detalle del Gasto <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="form.detalle" rows="3" required
                                placeholder="Describa el mantenimiento o reparación realizada..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"></textarea>
                        </div>

                        <!-- Responsable -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Responsable</label>
                            <input type="text" v-model="form.responsable"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Persona que autoriza">
                        </div>

                        <!-- Vigilante -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Vigilante de Turno</label>
                            <input type="text" v-model="form.vigilante"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Vigilante que registra">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-600 transition-all disabled:opacity-50">
                            <svg v-if="saving" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ saving ? 'Guardando...' : 'Registrar Gasto' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const props = defineProps({ vehicles: Array });
const emit = defineEmits(['close', 'saved']);

const saving = ref(false);
const form = ref({
    vehicle_id: '', fecha: new Date().toISOString().split('T')[0], factura: '',
    proveedor: '', detalle: '', costo: '', responsable: '', vigilante: ''
});

const save = async () => {
    saving.value = true;
    try {
        await axios.post('/vehicles/maintenance', form.value);
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};
</script>
