<script setup lang="ts">
import { computed } from 'vue';
import { X, Shield, AlertCircle, Loader2 } from 'lucide-vue-next';
import type { Role } from '@/Composables/useRoles';

const props = defineProps<{
    editingRole: Role | null;
    form: any;
    submitting: boolean;
}>();

const emit = defineEmits<{
    (e: 'submit'): void;
}>();

const show = defineModel<boolean>();

const modalTitle = computed(() => props.editingRole ? 'Editar Rol' : 'Nuevo Rol');
const modalMessage = computed(() => props.editingRole ? 'Modifique los datos del rol' : 'Define un nuevo rol para el sistema');

const handleClose = () => { show.value = false; };
const handleSubmit = () => { emit('submit'); };
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="handleClose"></div>
        <div class="relative bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden transform transition-all">
            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <Shield class="w-6 h-6" />
                        {{ modalTitle }}
                    </h3>
                    <p class="text-indigo-100 text-sm mt-1">{{ modalMessage }}</p>
                </div>
                <button @click="handleClose"
                    class="bg-white/10 rounded-md p-2 inline-flex items-center justify-center text-white hover:bg-white/20 focus:outline-none transition-colors">
                    <X class="h-6 w-6" stroke-width="2" />
                </button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                <!-- Nombre -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nombre del Rol <span class="text-red-500">*</span></label>
                    <input v-model="form.nombre" type="text"
                        class="w-full px-4 py-3 rounded-xl border focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-slate-600 outline-none"
                        :class="form.errors.nombre ? 'border-rose-400 bg-rose-50' : 'border-slate-200'"
                        placeholder="Ej. Supervisor de Seguridad" />
                    <p v-if="form.errors.nombre" class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                        <AlertCircle class="w-4 h-4" /> {{ form.errors.nombre }}
                    </p>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Descripción <span class="text-slate-400 font-normal">(Opcional)</span></label>
                    <textarea v-model="form.descripcion" rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 transition-all font-medium text-slate-600 resize-none outline-none"
                        placeholder="Describa brevemente las responsabilidades de este rol..."></textarea>
                </div>

                <!-- Nivel de Acceso -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nivel de Acceso <span class="text-red-500">*</span></label>
                    <div class="flex items-center gap-3">
                        <input v-model.number="form.nivel_acceso" type="range" min="1" max="10"
                            class="flex-1 h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-indigo-600" />
                        <span class="w-8 text-center text-lg font-bold text-indigo-600">{{ form.nivel_acceso }}</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">1 = acceso mínimo · 10 = acceso total</p>
                    <p v-if="form.errors.nivel_acceso" class="mt-1.5 text-xs text-rose-500 font-bold flex items-center gap-1">
                        <AlertCircle class="w-4 h-4" /> {{ form.errors.nivel_acceso }}
                    </p>
                </div>

                <!-- Estado (solo al editar) -->
                <div v-if="editingRole"
                    class="flex items-center justify-between bg-slate-50 p-4 rounded-2xl border border-slate-100">
                    <div class="flex flex-col">
                        <span class="text-sm font-bold text-slate-700">Estado del Rol</span>
                        <span class="text-xs text-slate-500">{{ form.activo ? 'Disponible para asignar' : 'No disponible' }}</span>
                    </div>
                    <button type="button" @click="form.activo = !form.activo"
                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                        :class="form.activo ? 'bg-emerald-500' : 'bg-slate-300'">
                        <span class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                            :class="form.activo ? 'translate-x-5' : 'translate-x-0'" />
                    </button>
                </div>

                <!-- Acciones -->
                <div class="flex gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="handleClose"
                        class="flex-1 px-4 py-3 border-2 border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all active:scale-95">
                        Cancelar
                    </button>
                    <button type="submit" :disabled="submitting"
                        class="flex-1 px-4 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white text-sm font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg shadow-indigo-200 disabled:opacity-50 active:scale-95 flex items-center justify-center gap-2">
                        <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                        <span>{{ editingRole ? 'Actualizar' : 'Guardar Rol' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
