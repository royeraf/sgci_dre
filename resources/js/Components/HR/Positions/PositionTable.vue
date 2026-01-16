<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Nombre del Cargo</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="pos in positions" :key="pos.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ pos.nombre }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ pos.descripcion || '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-[10px] font-black rounded-lg shadow-sm"
                                :class="pos.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                {{ pos.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="$emit('edit', pos)" title="Editar cargo"
                                class="text-purple-600 hover:text-purple-800 p-2 rounded-lg hover:bg-purple-50 transition-colors">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button @click="$emit('delete', pos.id)" title="Eliminar cargo"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors ml-1">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="positions.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <Briefcase class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay cargos registrados.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Briefcase, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    positions: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);
</script>
