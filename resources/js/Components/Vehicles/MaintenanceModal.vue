<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
            <div
                class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Registrar Gasto de Mantenimiento</h3>
                    <form @submit.prevent="save">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Vehículo *</label>
                                <select v-model="form.vehicle_id" required
                                    class="mt-1 w-full border rounded-md p-2 bg-white">
                                    <option value="">Seleccionar vehículo</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca
                                        }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha *</label>
                                <input type="date" v-model="form.fecha" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Costo (S/) *</label>
                                <input type="number" step="0.01" v-model="form.costo" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Factura</label>
                                <input type="text" v-model="form.factura" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Proveedor</label>
                                <input type="text" v-model="form.proveedor" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Detalle *</label>
                                <textarea v-model="form.detalle" rows="3" required
                                    class="mt-1 w-full border rounded-md p-2"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Responsable</label>
                                <input type="text" v-model="form.responsable" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Vigilante</label>
                                <input type="text" v-model="form.vigilante" class="mt-1 w-full border rounded-md p-2">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 disabled:opacity-50">
                                {{ saving ? 'Guardando...' : 'Guardar' }}
                            </button>
                        </div>
                    </form>
                </div>
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
