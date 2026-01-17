<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-sky-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ isEditing ? 'Gestionar Comisión' : 'Nueva Comisión' }}
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">{{ isEditing ? 'Actualice los datos de la comisión' : 'Complete los datos de la comisión' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="save" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Dependencia -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Dependencia <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.dependencia" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: Dirección Regional de Educación">
                        </div>

                        <!-- Lugar -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Lugar de Destino <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.lugar" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Ej: Lima - Ministerio de Educación">
                        </div>

                        <!-- Día -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="form.dia" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Hora -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora Programada <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="form.hora" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Vehículo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Vehículo</label>
                            <select v-model="form.vehicle_id"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                <option value="">Seleccionar vehículo</option>
                                <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }} {{ v.modelo }}</option>
                            </select>
                        </div>

                        <!-- Chofer -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Chofer <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.chofer" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nombre del conductor">
                        </div>

                        <!-- Usuarios -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Pasajeros</label>
                            <input type="text" v-model="form.usuarios"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Nombres de los pasajeros separados por coma">
                        </div>

                        <!-- Motivo -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Motivo de la Comisión</label>
                            <textarea v-model="form.motivo" rows="3"
                                placeholder="Describa el motivo de la comisión..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Sección de Control (solo en edición) -->
                    <div v-if="isEditing" class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-4">
                        <h4 class="font-bold text-blue-800 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Control de Tiempos
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Salida</label>
                                <input type="time" v-model="form.hora_salida"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Regreso</label>
                                <input type="time" v-model="form.hora_regreso"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-sky-500 text-white font-bold rounded-xl hover:from-blue-700 hover:to-sky-600 transition-all disabled:opacity-50">
                            <svg v-if="saving" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ saving ? 'Guardando...' : 'Guardar Comisión' }}
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
