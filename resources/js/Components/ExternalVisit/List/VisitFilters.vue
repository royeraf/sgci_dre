<template>
    <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <input type="text" :value="filters.search"
                        @input="$emit('update:filters', { ...filters, search: $event.target.value })"
                        placeholder="Buscar por nombre, DNI o motivo..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm" />
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                <select :value="filters.estado"
                    @change="$emit('update:filters', { ...filters, estado: $event.target.value })"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm bg-white">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente salida</option>
                    <option value="completado">Completado</option>
                </select>
            </div>

            <!-- Date Filter -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha</label>
                <input type="date" :value="filters.fecha"
                    @input="$emit('update:filters', { ...filters, fecha: $event.target.value })"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 text-sm" />
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">
                Resultados
            </p>
            <div class="flex gap-2">
                <button @click="$emit('clear')"
                    class="text-sm font-semibold text-slate-500 hover:text-purple-600 transition-colors duration-200 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Search, X, Filter } from 'lucide-vue-next';

defineProps({
    filters: {
        type: Object,
        required: true
    }
});

defineEmits(['update:filters', 'clear', 'apply']);
</script>
