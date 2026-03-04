<script setup lang="ts">
import { computed } from 'vue';
import { X, Settings, Loader2, AlertCircle } from 'lucide-vue-next';
import type { EntryExitReason, ReasonTipo } from '@/Composables/useEntryExitReasons';

interface ReasonForm {
    nombre: string;
    descripcion: string;
    tipo: ReasonTipo;
    is_active: boolean;
    processing: boolean;
    errors: Partial<Record<keyof ReasonForm, string>>;
}

const props = defineProps<{
    editingReason: EntryExitReason | null;
    form: ReasonForm;
}>();

const emit = defineEmits<{ (e: 'submit'): void }>();
const show = defineModel<boolean>();

const modalTitle = computed(() => props.editingReason ? 'Editar Motivo' : 'Nuevo Motivo');
const modalMessage = computed(() => props.editingReason ? 'Modifique los datos del motivo' : 'Define un nuevo motivo de salida');
const statusMessage = computed(() => props.form.is_active ? 'Visible en el registro' : 'Oculto en el registro');

const tipoLabels: Record<ReasonTipo, string> = {
    comision: 'Comisión de Servicios',
    permiso: 'Permiso Personal',
    ambos: 'Ambos tipos',
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="show = false"></div>
        <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <Settings class="w-6 h-6" />
                        {{ modalTitle }}
                    </h3>
                    <p class="text-emerald-100 text-sm mt-1">{{ modalMessage }}</p>
                </div>
                <button @click="show = false"
                    class="bg-white/10 rounded-md p-2 text-white hover:bg-white/20 transition-colors">
                    <X class="h-6 w-6" />
                </button>
            </div>

            <form @submit.prevent="emit('submit')" class="p-6 space-y-5">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nombre del Motivo</label>
                    <input v-model="form.nombre" type="text"
                        class="w-full px-4 py-3 rounded-xl border focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-medium text-slate-700 outline-none"
                        :class="form.errors.nombre ? 'border-rose-400 bg-rose-50' : 'border-slate-200'"
                        placeholder="Ej. Diligencias personales" />
                    <p v-if="form.errors.nombre" class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                        <AlertCircle class="w-4 h-4" /> {{ form.errors.nombre }}
                    </p>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Descripción (Opcional)</label>
                    <textarea v-model="form.descripcion" rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition-all font-medium text-slate-600 resize-none outline-none"
                        placeholder="Breve descripción sobre este motivo..."></textarea>
                </div>

                <!-- Tipo -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Aplica para</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label v-for="(label, val) in tipoLabels" :key="val"
                            class="flex flex-col items-center p-3 border-2 rounded-xl cursor-pointer transition-all text-center"
                            :class="form.tipo === val
                                ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                : 'border-slate-200 text-slate-600 hover:border-slate-300'">
                            <input type="radio" v-model="form.tipo" :value="val" class="sr-only" />
                            <span class="text-xs font-bold leading-tight">{{ label }}</span>
                        </label>
                    </div>
                    <p v-if="form.errors.tipo" class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                        <AlertCircle class="w-4 h-4" /> {{ form.errors.tipo }}
                    </p>
                </div>

                <!-- Estado (solo en edición) -->
                <div v-if="editingReason"
                    class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div>
                        <span class="text-sm font-bold text-slate-700">Estado del Motivo</span>
                        <p class="text-xs text-slate-500">{{ statusMessage }}</p>
                    </div>
                    <button type="button" @click="form.is_active = !form.is_active"
                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200"
                        :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200"
                            :class="form.is_active ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>

                <!-- Acciones -->
                <div class="flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="show = false"
                        class="flex-1 px-4 py-3 border-2 border-slate-200 text-sm font-bold rounded-xl text-slate-600 hover:bg-slate-50 transition-all">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="form.processing"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white text-sm font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all shadow-lg shadow-emerald-200 disabled:opacity-50 flex items-center justify-center gap-2">
                        <Loader2 v-if="form.processing" class="w-5 h-5 animate-spin" />
                        {{ editingReason ? 'Actualizar' : 'Guardar Motivo' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
