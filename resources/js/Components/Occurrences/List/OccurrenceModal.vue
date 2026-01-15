<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Plus class="w-6 h-6" />
                            Registrar Nueva Ocurrencia
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">Complete los datos del evento</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Fecha -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="form.fecha"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-300': form.errors.fecha }" />
                            <p v-if="form.errors.fecha" class="mt-1 text-sm text-red-600">{{ form.errors.fecha }}</p>
                        </div>

                        <!-- Hora -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="form.hora"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                :class="{ 'border-red-300': form.errors.hora }" />
                            <p v-if="form.errors.hora" class="mt-1 text-sm text-red-600">{{ form.errors.hora }}</p>
                        </div>

                        <!-- Turno -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Turno <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.turno"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                :class="{ 'border-red-300': form.errors.turno }">
                                <option value="">Seleccione turno</option>
                                <option value="Mañana">Mañana</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noche">Noche</option>
                            </select>
                            <p v-if="form.errors.turno" class="mt-1 text-sm text-red-600">{{ form.errors.turno }}</p>
                        </div>

                        <!-- Tipo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.tipo"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                :class="{ 'border-red-300': form.errors.tipo }">
                                <option value="">Seleccione tipo</option>
                                <option value="Rutina">Rutina</option>
                                <option value="Aviso">Aviso</option>
                                <option value="Incidente">Incidente</option>
                                <option value="Emergencia">Emergencia</option>
                                <option value="Robo">Robo</option>
                                <option value="Otros">Otros</option>
                            </select>
                            <p v-if="form.errors.tipo" class="mt-1 text-sm text-red-600">{{ form.errors.tipo }}</p>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Descripción <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="form.descripcion" rows="4"
                            placeholder="Describa detalladamente el evento ocurrido..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"
                            :class="{ 'border-red-300': form.errors.descripcion }"></textarea>
                        <p v-if="form.errors.descripcion" class="mt-1 text-sm text-red-600">{{ form.errors.descripcion
                            }}</p>
                    </div>

                    <!-- Acciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Acciones Tomadas
                        </label>
                        <textarea v-model="form.acciones" rows="3"
                            placeholder="Describa las acciones tomadas ante el evento (opcional)..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="form.processing"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ form.processing ? 'Guardando...' : 'Registrar Ocurrencia' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Plus, X, Loader2 } from 'lucide-vue-next';

const emit = defineEmits(['close']);

// Get current shift based on time
const getCurrentShift = () => {
    const hour = new Date().getHours();
    if (hour >= 6 && hour < 14) return 'Mañana';
    if (hour >= 14 && hour < 22) return 'Tarde';
    return 'Noche';
};

// Get current date and time
const now = new Date();
const currentDate = now.toISOString().split('T')[0];
const currentTime = now.toTimeString().slice(0, 5);

const form = useForm({
    fecha: currentDate,
    hora: currentTime,
    turno: getCurrentShift(),
    tipo: '',
    descripcion: '',
    acciones: '',
});

const submitForm = () => {
    form.post('/occurrences', {
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>
