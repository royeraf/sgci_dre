<template>
    <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search -->
            <div class="lg:col-span-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <input type="text" :value="filters.search"
                        @input="$emit('update:filters', { ...filters, search: $event.target.value })"
                        placeholder="Buscar por nombre, DNI o cargo..."
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-sm" />
                </div>
            </div>

            <!-- Area Filter -->
            <div class="lg:col-span-2">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Área</label>
                <select :value="filters.area"
                    @change="$emit('update:filters', { ...filters, area: $event.target.value })"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-sm bg-white">
                    <option value="">Todas las áreas</option>
                    <option v-for="area in areas" :key="area.id" :value="area.nombre">
                        {{ area.nombre }}
                    </option>
                </select>
            </div>

            <!-- Position Filter -->
            <div class="lg:col-span-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Cargo</label>
                <select :value="filters.position"
                    @change="$emit('update:filters', { ...filters, position: $event.target.value })"
                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all duration-200 text-sm bg-white">
                    <option value="">Todos los cargos</option>
                    <option v-for="pos in positions" :key="pos.id" :value="pos.nombre">
                        {{ pos.nombre }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Filter Actions -->
        <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
            <p class="text-sm text-slate-500">
                <span class="font-semibold text-slate-700">{{ resultCount }}</span>
                empleados encontrados
            </p>
            <button @click="$emit('clear')"
                class="text-sm font-semibold text-slate-500 hover:text-emerald-600 transition-colors duration-200 flex items-center gap-1">
                <X class="w-4 h-4" />
                Limpiar filtros
            </button>
        </div>
    </div>
</template>

<script setup>
import { Search, X } from 'lucide-vue-next';

defineProps({
    filters: {
        type: Object,
        required: true
    },
    resultCount: {
        type: Number,
        default: 0
    },
    areas: {
        type: Array,
        default: () => []
    },
    positions: {
        type: Array,
        default: () => []
    }
});

defineEmits(['update:filters', 'clear']);
</script>
