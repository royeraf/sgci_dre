<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-pink-600 to-rose-500 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Requerimientos de Servicios del Vehículo
                        </h3>
                        <p class="text-pink-100 text-sm mt-1">Registre la solicitud de servicio</p>
                    </div>
                    <button @click="$emit('close')" class="text-pink-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="save" class="p-6 space-y-6 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Conductor -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre del conductor/a <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.conductor" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                                placeholder="Nombre completo del conductor">
                        </div>

                        <!-- Vehículo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Vehículo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.vehicle_id" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 bg-white">
                                <option value="">Seleccionar vehículo</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }} {{ v.modelo }}</option>
                            </select>
                        </div>

                        <!-- Estado del vehículo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del vehículo</label>
                            <input type="text" v-model="form.estado_vehiculo"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Estado del motor -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del motor</label>
                            <input type="text" v-model="form.estado_motor"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Sistema eléctrico -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Encendido y sistema eléctrico</label>
                            <input type="text" v-model="form.encendido_electrico"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Transmisión -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Transmisión</label>
                            <input type="text" v-model="form.transmision"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Pintura y carrocería -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pintura y carrocería</label>
                            <input type="text" v-model="form.pintura_carroceria"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Llantas -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado de llantas</label>
                            <input type="text" v-model="form.llantas"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500">
                        </div>

                        <!-- Comisiones realizadas -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Comisiones realizadas</label>
                            <textarea v-model="form.comisiones_realizadas" rows="2"
                                placeholder="Describa las comisiones realizadas..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none"></textarea>
                        </div>

                        <!-- Motivo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Motivo de la solicitud <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="form.motivo" rows="3" required
                                placeholder="Describa el motivo por el cual solicita el servicio..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-gradient-to-r from-pink-600 to-rose-500 text-white font-bold rounded-xl hover:from-pink-700 hover:to-rose-600 transition-all disabled:opacity-50">
                            {{ saving ? 'Guardando...' : 'Registrar Solicitud' }}
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

const props = defineProps({ vehicles: Array });
const emit = defineEmits(['close', 'saved']);
const saving = ref(false);

const form = reactive({
    conductor: '', vehicle_id: '', estado_vehiculo: '', estado_motor: '', encendido_electrico: '',
    transmision: '', pintura_carroceria: '', llantas: '', comisiones_realizadas: '', motivo: ''
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
