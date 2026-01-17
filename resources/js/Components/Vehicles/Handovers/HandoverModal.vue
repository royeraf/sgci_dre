<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nueva Acta de Entrega y Recepción
                        </h3>
                        <p class="text-amber-100 text-sm mt-1">Complete el formulario del acta vehicular</p>
                    </div>
                    <button @click="$emit('close')" class="text-amber-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="save" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <!-- Datos Generales -->
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                        <h4 class="font-bold text-amber-800 mb-4">Datos Generales del Vehículo</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Fecha <span class="text-red-500">*</span></label>
                                <input type="date" v-model="form.fecha" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Entidad</label>
                                <input type="text" v-model="form.entidad" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Placa <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.placa" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Kilometraje <span class="text-red-500">*</span></label>
                                <input type="text" v-model="form.kilometraje" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                                <input type="text" v-model="form.color" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                            </div>
                        </div>
                    </div>

                    <!-- Estado de Sistemas -->
                    <div>
                        <h4 class="font-bold text-slate-800 mb-3">Estado de Sistemas del Vehículo</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 max-h-48 overflow-y-auto border border-slate-200 rounded-xl p-4 bg-slate-50">
                            <div v-for="(group, idx) in systemGroups" :key="idx" class="bg-white rounded-lg p-3 border">
                                <div class="font-semibold text-amber-700 text-sm mb-2">{{ idx + 1 }}. {{ group.name }}</div>
                                <div class="space-y-2">
                                    <div v-for="item in group.items" :key="item" class="flex items-center justify-between">
                                        <span class="text-xs text-slate-600">{{ item }}</span>
                                        <select v-model="form.sistemas[item]" class="text-xs px-2 py-1 border rounded-lg w-20">
                                            <option value="">-</option>
                                            <option value="Bueno">Bueno</option>
                                            <option value="Regular">Regular</option>
                                            <option value="Malo">Malo</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Responsables -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Jefe de Abastecimiento</label>
                            <input type="text" v-model="form.abastecimiento" class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Recepciona <span class="text-red-500">*</span></label>
                            <input type="text" v-model="form.recepciona" required class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-amber-500">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')" class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving" class="px-6 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold rounded-xl hover:from-amber-600 hover:to-orange-600 disabled:opacity-50">
                            {{ saving ? 'Guardando...' : 'Guardar Acta' }}
                        </button>
                    </div>
                </form>
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
    { name: 'MOTOR', items: ['CILINDRO', 'CULATA', 'CARBURADOR'] },
    { name: 'FRENOS', items: ['BOMBA DE FRENOS', 'ZAPATA Y TAMBORES'] },
    { name: 'REFRIGERACIÓN', items: ['RADIADOR', 'VENTILADOR'] },
    { name: 'ELÉCTRICO', items: ['BATERIA', 'ALTERNADOR', 'FAROS'] },
    { name: 'TRANSMISIÓN', items: ['CAJA DE CAMBIO', 'DIFERENCIAL'] },
    { name: 'SUSPENSIÓN', items: ['AMORTIGUADORES', 'LLANTAS'] }
];

const initSistemas = () => {
    const sistemas = {};
    systemGroups.forEach(g => g.items.forEach(i => sistemas[i] = ''));
    return sistemas;
};

const form = reactive({
    fecha: new Date().toISOString().split('T')[0], entidad: '', placa: '', color: '',
    kilometraje: '', abastecimiento: '', recepciona: '', sistemas: initSistemas()
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
