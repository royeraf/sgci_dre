<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                            </svg>
                            {{ isEditing ? 'Editar Vehículo' : 'Registrar Vehículo' }}
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">{{ isEditing ? 'Modifique los datos del vehículo' : 'Complete los datos del vehículo' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="save" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.tipo"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option>Automóvil</option>
                                <option>Camioneta</option>
                                <option>Minivan</option>
                                <option>Bus</option>
                                <option>Motocicleta</option>
                            </select>
                        </div>

                        <!-- Placa -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Placa <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.placa" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: ABC-123">
                        </div>

                        <!-- Marca -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Marca <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.marca" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Toyota">
                        </div>

                        <!-- Modelo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Modelo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.modelo" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Hilux">
                        </div>

                        <!-- Año -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Año</label>
                            <input type="text" v-model="form.anio"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 2022">
                        </div>

                        <!-- Color -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                            <input type="text" v-model="form.color"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Blanco">
                        </div>

                        <!-- Estado -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado</label>
                            <select v-model="form.estado"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option>Operativo</option>
                                <option>En Mantenimiento</option>
                                <option>Inoperativo</option>
                            </select>
                        </div>

                        <!-- Combustible -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Combustible</label>
                            <select v-model="form.combustible"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                <option>Gasolina</option>
                                <option>Diesel</option>
                                <option>GLP</option>
                            </select>
                        </div>

                        <!-- Kilometraje -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Kilometraje</label>
                            <input type="text" v-model="form.kilometraje"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 50000">
                        </div>

                        <!-- Nº Motor -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Nº Motor</label>
                            <input type="text" v-model="form.motor"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Fecha SOAT -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha SOAT</label>
                            <input type="date" v-model="form.fecha_soat"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Fecha Revisión -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Revisión Técnica</label>
                            <input type="date" v-model="form.fecha_revision"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="3"
                            placeholder="Ingrese observaciones adicionales..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-violet-700 transition-all disabled:opacity-50">
                            <svg v-if="saving" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ saving ? 'Guardando...' : 'Guardar Vehículo' }}
                        </button>
                    </div>
                </form>
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
