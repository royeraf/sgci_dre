<script setup lang="ts">
import { computed } from 'vue';
import {
    X,
    AlertCircle,
    Settings,
    Loader2
} from 'lucide-vue-next';
import { VisitReason } from '@/Composables/useVisitReasons';

const props = defineProps<{
    editingReason: VisitReason | null;
    form: any; // Using any for Inertia form object for now
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'submit'): void;
}>();

const show = defineModel<boolean>();

const modalTitle = computed(() => props.editingReason ? 'Editar Motivo' : 'Nuevo Motivo');
const modalMessage = computed(() => props.editingReason ? 'Modifique los datos del motivo' : 'Defina un nuevo motivo para las visitas');
const statusMessage = computed(() => props.form.is_active ? 'Visible en el registro' : 'Oculto en el registro');

const handleClose = () => {
    show.value = false;
    emit('close');
};

const handleSubmit = () => {
    emit('submit');
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="handleClose"></div>
        <div
            class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all animate-in fade-in zoom-in duration-200">
            <!-- Header -->
            <div class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <Settings class="w-6 h-6" />
                        {{ modalTitle }}
                    </h3>
                    <p class="text-purple-100 text-sm mt-1">{{ modalMessage }}</p>
                </div>
                <button @click="handleClose"
                    class="bg-white/10 rounded-md p-2 inline-flex items-center justify-center text-white hover:text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white/50 transition-colors">
                    <X class="h-6 w-6" stroke-width="2" />
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nombre del Motivo</label>
                    <input v-model="form.nombre" type="text"
                        class="w-full px-4 py-3 rounded-xl border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-medium text-slate-600 outline-none"
                        placeholder="Ej. Trámites de Títulos"
                        :class="{ 'border-rose-400 bg-rose-50': form.errors.nombre }" />
                    <p v-if="form.errors.nombre" class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                        <AlertCircle class="w-4 h-4" /> {{ form.errors.nombre }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Descripción (Opcional)</label>
                    <textarea v-model="form.descripcion" rows="3"
                        class="w-full px-4 py-3 rounded-xl border-slate-200 focus:border-purple-500 focus:ring-2 focus:ring-purple-500/20 transition-all font-medium text-slate-600 resize-none outline-none"
                        placeholder="Agregue una breve descripción sobre este motivo..."></textarea>
                </div>

                <div v-if="editingReason"
                    class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700">Estado del Motivo</span>
                        <span class="text-xs text-slate-500">{{ statusMessage }}</span>
                    </div>
                    <button type="button" @click="form.is_active = !form.is_active"
                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span
                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                            :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>

                <div class="flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="handleClose"
                        class="flex-1 px-4 py-3 border-2 border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-sm font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-lg shadow-purple-200 disabled:opacity-50 active:scale-95 flex items-center justify-center gap-2">
                        <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                        <span>{{ editingReason ? 'Actualizar' : 'Guardar Motivo' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
