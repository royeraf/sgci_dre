<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Nombre del Área</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="area in areas" :key="area.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ area.nombre }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ area.descripcion || '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-[10px] font-black rounded-lg shadow-sm"
                                :class="area.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                {{ area.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="$emit('edit', area)" title="Editar área"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button @click="$emit('delete', area.id)" title="Eliminar área"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors ml-1">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="areas.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <Building2 class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay áreas registradas.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Building2, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    areas: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);
</script>
