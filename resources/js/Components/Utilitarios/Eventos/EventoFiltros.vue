<template>
    <div class="flex flex-col gap-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tipo</label>
                <select :value="filters.tipo" @change="$emit('update:filters', { ...filters, tipo: $event.target.value })"
                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-200 text-sm outline-none cursor-pointer">
                    <option value="">Todos los tipos</option>
                    <option value="curso">Curso</option>
                    <option value="seminario">Seminario</option>
                    <option value="capacitacion">Capacitación</option>
                    <option value="taller">Taller</option>
                    <option value="conferencia">Conferencia</option>
                    <option value="otro">Otro</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Modalidad</label>
                <select :value="filters.modalidad" @change="$emit('update:filters', { ...filters, modalidad: $event.target.value })"
                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-200 text-sm outline-none cursor-pointer">
                    <option value="">Todas las modalidades</option>
                    <option value="presencial">Presencial</option>
                    <option value="virtual">Virtual</option>
                    <option value="hibrida">Híbrida</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                <select :value="filters.estado" @change="$emit('update:filters', { ...filters, estado: $event.target.value })"
                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 transition-all duration-200 text-sm outline-none cursor-pointer">
                    <option value="">Todos los estados</option>
                    <option value="borrador">Borrador</option>
                    <option value="publicado">Publicado</option>
                    <option value="cerrado">Cerrado</option>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">
                <span class="font-semibold text-slate-700">{{ resultCount }}</span>
                eventos encontrados
            </p>
            <button @click="$emit('clear')"
                class="text-sm font-semibold text-slate-500 hover:text-amber-600 transition-colors duration-200 flex items-center gap-1">
                <X class="w-4 h-4" />
                Limpiar filtros
            </button>
        </div>
    </div>
</template>

<script setup>
import { X } from 'lucide-vue-next';

defineProps({
    filters: {
        type: Object,
        required: true
    },
    resultCount: {
        type: Number,
        default: 0
    }
});

defineEmits(['update:filters', 'clear']);
</script>
