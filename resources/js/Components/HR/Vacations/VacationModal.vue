<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            {{ isEditing ? 'Editar Vacaciones' : 'Registrar Vacaciones' }}
                        </h3>
                        <p class="text-orange-50 text-sm mt-1">{{ isEditing ? 'Modifique los datos del período vacacional' : 'Programe el período de descanso del personal' }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-orange-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Empleado -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Empleado <span class="text-red-500">*</span>
                        </label>
                        <select v-model="form.empleado_id" required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-white">
                            <option value="">Seleccione al empleado...</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.dni }} - {{ emp.nombres }} {{ emp.apellidos }}
                            </option>
                        </select>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de Inicio <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.fecha_inicio" type="date" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de Fin <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.fecha_fin" type="date" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500" />
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="3"
                            placeholder="Detalles adicionales sobre el período vacacional..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white rounded-xl hover:from-orange-700 hover:to-amber-700 transition-all shadow-lg shadow-orange-600/20 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Vacaciones' : 'Registrar Vacaciones') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { X, CalendarRange, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    employees: {
        type: Array,
        default: () => []
    },
    submitting: {
        type: Boolean,
        default: false
    },
    vacation: {
        type: Object,
        default: null
    },
    isEditing: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit']);

const form = ref({
    empleado_id: '',
    fecha_inicio: '',
    fecha_fin: '',
    observaciones: ''
});

watch(() => props.vacation, (newVal) => {
    if (newVal && props.isEditing) {
        form.value = {
            empleado_id: newVal.empleado_id || '',
            fecha_inicio: newVal.fecha_inicio || '',
            fecha_fin: newVal.fecha_fin || '',
            observaciones: newVal.observaciones || ''
        };
    } else {
        form.value = {
            empleado_id: '',
            fecha_inicio: '',
            fecha_fin: '',
            observaciones: ''
        };
    }
}, { immediate: true });

const handleSubmit = () => {
    emit('submit', form.value);
};
</script>
