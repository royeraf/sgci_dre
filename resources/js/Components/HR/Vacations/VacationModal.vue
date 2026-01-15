<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900">Registrar Vacaciones</h3>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Empleado *</label>
                        <select v-model="form.empleado_id" required
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500">
                            <option value="">Seleccionar...</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.dni }} - {{ emp.nombres }} {{ emp.apellidos }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Inicio *</label>
                            <input v-model="form.fecha_inicio" type="date" required
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Fin *</label>
                            <input v-model="form.fecha_fin" type="date" required
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="2"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="$emit('close')"
                            class="flex-1 px-4 py-3 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="flex-1 px-4 py-3 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 disabled:opacity-50">
                            {{ submitting ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { X } from 'lucide-vue-next';

defineProps({
    employees: {
        type: Array,
        default: () => []
    },
    submitting: {
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

const handleSubmit = () => {
    emit('submit', form.value);
};
</script>
