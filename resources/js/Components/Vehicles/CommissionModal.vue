<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
            <div
                class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">
                        {{ isEditing ? 'Gestionar Comisión' : 'Nueva Comisión' }}
                    </h3>
                    <form @submit.prevent="save">
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Dependencia *</label>
                                <input type="text" v-model="form.dependencia" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Lugar *</label>
                                <input type="text" v-model="form.lugar" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Día *</label>
                                <input type="date" v-model="form.dia" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hora *</label>
                                <input type="time" v-model="form.hora" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Vehículo</label>
                                <select v-model="form.vehicle_id" class="mt-1 w-full border rounded-md p-2 bg-white">
                                    <option value="">Seleccionar vehículo</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca
                                        }} {{ v.modelo }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Chofer *</label>
                                <input type="text" v-model="form.chofer" required
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Usuarios</label>
                                <input type="text" v-model="form.usuarios" class="mt-1 w-full border rounded-md p-2"
                                    placeholder="Personas que viajan">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-700">Motivo</label>
                                <textarea v-model="form.motivo" rows="2"
                                    class="mt-1 w-full border rounded-md p-2"></textarea>
                            </div>
                        </div>
                        <div v-if="isEditing" class="grid grid-cols-2 gap-4 mb-4 p-3 bg-blue-50 rounded-lg">
                            <div>
                                <label class="block text-sm font-medium text-blue-700">Hora Salida</label>
                                <input type="time" v-model="form.hora_salida" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-blue-700">Hora Regreso</label>
                                <input type="time" v-model="form.hora_regreso"
                                    class="mt-1 w-full border rounded-md p-2">
                            </div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
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

const props = defineProps({ commission: Object, vehicles: Array });
const emit = defineEmits(['close', 'saved']);

const isEditing = computed(() => !!props.commission?.id);
const saving = ref(false);

const form = ref({
    dependencia: '', dia: new Date().toISOString().split('T')[0], hora: new Date().toTimeString().slice(0, 5),
    lugar: '', motivo: '', usuarios: '', vehicle_id: '', chofer: '', hora_salida: '', hora_regreso: ''
});

onMounted(() => {
    if (props.commission) {
        Object.assign(form.value, props.commission);
    }
});

const save = async () => {
    saving.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/vehicles/commissions/${props.commission.id}`, form.value);
        } else {
            await axios.post('/vehicles/commissions', form.value);
        }
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};
</script>
