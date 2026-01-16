<template>
    <div class="fixed z-50 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="$emit('close')"></div>
            <div
                class="inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:max-w-3xl sm:w-full max-h-[90vh] overflow-y-auto">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Nueva Acta de Entrega y Recepción
                    </h3>
                    <form @submit.prevent="save">
                        <div class="grid grid-cols-3 gap-4 mb-4 bg-gray-50 p-4 rounded-lg">
                            <div><label class="block text-sm font-medium text-gray-700">Fecha *</label><input
                                    type="date" v-model="form.fecha" required class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div class="col-span-2"><label
                                    class="block text-sm font-medium text-gray-700">Entidad</label><input type="text"
                                    v-model="form.entidad" class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Denominación</label><input
                                    type="text" v-model="form.denominacion" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Placa *</label><input
                                    type="text" v-model="form.placa" required class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Color</label><input type="text"
                                    v-model="form.color" class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Kilometraje *</label><input
                                    type="text" v-model="form.kilometraje" required
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Carrocería</label><input
                                    type="text" v-model="form.carroceria" class="mt-1 w-full border rounded-md p-2">
                            </div>
                            <div><label class="block text-sm font-medium text-gray-700">Nº Motor</label><input
                                    type="text" v-model="form.n_motor" class="mt-1 w-full border rounded-md p-2"></div>
                        </div>
                        <p class="text-sm text-gray-500 mb-2 italic">Marque el estado (Bueno, Regular, Malo) para cada
                            sistema:</p>
                        <div class="space-y-3 mb-4 max-h-60 overflow-y-auto border rounded-lg p-3">
                            <div v-for="(group, idx) in systemGroups" :key="idx" class="border-b pb-2">
                                <div class="font-semibold text-gray-700 text-sm uppercase mb-1">{{ idx + 1 }}. {{
                                    group.name }}</div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div v-for="item in group.items" :key="item" class="flex items-center gap-2">
                                        <span class="text-xs text-gray-600 w-32">{{ item }}</span>
                                        <select v-model="form.sistemas[item]" class="text-xs p-1 border rounded flex-1">
                                            <option value="">-</option>
                                            <option value="Bueno">Bueno</option>
                                            <option value="Regular">Regular</option>
                                            <option value="Malo">Malo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4 bg-gray-50 p-4 rounded-lg">
                            <div><label class="block text-sm font-medium text-gray-700">Jefe de
                                    Abastecimiento</label><input type="text" v-model="form.abastecimiento"
                                    class="mt-1 w-full border rounded-md p-2"></div>
                            <div><label class="block text-sm font-medium text-gray-700">Recepciona *</label><input
                                    type="text" v-model="form.recepciona" required
                                    class="mt-1 w-full border rounded-md p-2"></div>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="$emit('close')"
                                class="px-4 py-2 border rounded-md hover:bg-gray-50">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 disabled:opacity-50">{{
                                    saving ? 'Guardando...' : 'Guardar Acta' }}</button>
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

const emit = defineEmits(['close', 'saved']);
const saving = ref(false);

const systemGroups = [
    { name: 'MOTOR', items: ['CILINDRO', 'CULATA', 'CARBURADOR', 'CARTER', 'DISTRIBUIDOR'] },
    { name: 'FRENOS', items: ['BOMBA DE FRENOS', 'ZAPATA Y TAMBORES', 'DISCOS Y PASTILLAS'] },
    { name: 'REFRIGERACIÓN', items: ['RADIADOR', 'VENTILADOR', 'BOMBA DE AGUA'] },
    { name: 'ELÉCTRICO', items: ['MOTOR DE ARRANQUE', 'BATERIA', 'ALTERNADOR', 'FAROS'] },
    { name: 'TRANSMISIÓN', items: ['CAJA DE CAMBIO', 'BOMBA DE EMBRAGUE', 'DIFERENCIAL'] },
    { name: 'SUSPENSIÓN', items: ['AMORTIGUADORES', 'MUELLES', 'LLANTAS'] },
    { name: 'ACCESORIOS', items: ['AIRE ACONDICIONADO', 'ESPEJOS', 'RADIO', 'EXTINTOR'] }
];

const initSistemas = () => {
    const sistemas = {};
    systemGroups.forEach(g => g.items.forEach(i => sistemas[i] = ''));
    return sistemas;
};

const form = reactive({
    fecha: new Date().toISOString().split('T')[0], entidad: '', denominacion: '', placa: '', color: '',
    kilometraje: '', carroceria: '', n_motor: '', abastecimiento: '', recepciona: '', sistemas: initSistemas()
});

const save = async () => {
    saving.value = true;
    try {
        await axios.post('/vehicles/handovers', form);
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        saving.value = false;
    }
};
</script>
