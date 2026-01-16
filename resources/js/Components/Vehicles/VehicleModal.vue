<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
            <div
                class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">
                        {{ isEditing ? 'Editar Vehículo' : 'Registrar Vehículo' }}
                    </h3>
                    <form @submit.prevent="save">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                                <select v-model="form.tipo" class="mt-1 w-full border rounded-md p-2">
                                    <option>Automóvil</option>
                                    <option>Camioneta</option>
                                    <option>Minivan</option>
                                    <option>Bus</option>
                                    <option>Motocicleta</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Placa *</label>
                                <input type="text" v-model="form.placa" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Marca *</label>
                                <input type="text" v-model="form.marca" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Modelo *</label>
                                <input type="text" v-model="form.modelo" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Año</label>
                                <input type="text" v-model="form.anio" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Color</label>
                                <input type="text" v-model="form.color" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Estado</label>
                                <select v-model="form.estado" class="mt-1 w-full border rounded-md p-2">
                                    <option>Operativo</option>
                                    <option>En Mantenimiento</option>
                                    <option>Inoperativo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Combustible</label>
                                <select v-model="form.combustible" class="mt-1 w-full border rounded-md p-2">
                                    <option>Gasolina</option>
                                    <option>Diesel</option>
                                    <option>GLP</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Kilometraje</label>
                                <input type="text" v-model="form.kilometraje" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Nº Motor</label>
                                <input type="text" v-model="form.motor" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha SOAT</label>
                                <input type="date" v-model="form.fecha_soat" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Fecha Revisión</label>
                                <input type="date" v-model="form.fecha_revision"
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Observaciones</label>
                            <textarea v-model="form.observaciones" rows="2"
                                class="mt-1 w-full border rounded-md p-2"></textarea>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
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
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ vehicle: Object });
const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.vehicle?.id);
const saving = ref(false);

const form = ref({
    tipo: 'Automóvil', marca: '', modelo: '', placa: '', anio: '', motor: '', color: '',
    estado: 'Operativo', kilometraje: '', combustible: 'Gasolina', fecha_soat: '', fecha_revision: '', observaciones: ''
});

onMounted(() => {
    if (props.vehicle) {
        Object.assign(form.value, props.vehicle);
    }
});

const save = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/vehicles/inventory/${props.vehicle.id}`, form.value);
        } else {
            await axios.post('/vehicles/inventory', form.value);
        }
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};
</script>
