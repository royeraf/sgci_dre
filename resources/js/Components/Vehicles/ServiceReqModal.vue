<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
            <div
                class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Requerimientos de Servicios del
                        Vehículo</h3>
                    <form @submit.prevent="save">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Nombre del conductor/a *</label>
                                <input type="text" v-model="form.conductor" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Vehículo *</label>
                                <select v-model="form.vehicle_id" required
                                    class="mt-1 w-full border rounded-md p-2 bg-white">
                                    <option value="">Seleccionar vehículo</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca
                                        }} {{ v.modelo }}</option>
                                </select>
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Estado del
                                    vehículo</label><input type="text" v-model="form.estado_vehiculo"
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Estado del motor</label><input
                                    type="text" v-model="form.estado_motor" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Encendido y sistema
                                    eléctrico</label><input type="text" v-model="form.encendido_electrico"
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Transmisión</label><input
                                    type="text" v-model="form.transmision" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Pintura y
                                    carrocería</label><input type="text" v-model="form.pintura_carroceria"
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Estado de llantas</label><input
                                    type="text" v-model="form.llantas" class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Herramientas</label><input
                                    type="text" v-model="form.herramientas" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Implementos de
                                    aseo</label><input type="text" v-model="form.implementos_aseo"
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div class="col-span-2"><label class="block text-sm font-medium text-gray-700">Comisiones
                                    realizadas</label><textarea v-model="form.comisiones_realizadas" rows="2"
                                    class="mt-1 w-full border rounded-md p-2"></textarea></div>
                            <div class="col-span-2"><label class="block text-sm font-medium text-gray-700">Motivo de la
                                    solicitud *</label><textarea v-model="form.motivo" rows="3" required
                                    class="mt-1 w-full border rounded-md p-2"></textarea></div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-pink-600 text-white rounded-md hover:bg-pink-700 disabled:opacity-50">{{
                                    saving ? 'Guardando...' : 'Registrar Solicitud' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const props = defineProps({ vehicles: Array });
const emit = defineEmits(['close', 'saved']);
const saving = ref(false);

const form = reactive({
    conductor: '', vehicle_id: '', estado_vehiculo: '', estado_motor: '', encendido_electrico: '',
    transmision: '', pintura_carroceria: '', llantas: '', herramientas: '', implementos_aseo: '',
    comisiones_realizadas: '', motivo: ''
});

const save = async () => {
    saving.value = true;
    try {
        await axios.post('/vehicles/service-requirements', form);
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};
</script>
