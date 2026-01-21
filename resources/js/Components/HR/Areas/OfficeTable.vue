<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-indigo-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-800 uppercase">Oficina</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-800 uppercase">Área Perteneciente
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-800 uppercase">Ubicación</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-800 uppercase">Teléfono Int.</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-indigo-800 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="office in offices" :key="office.id" class="hover:bg-indigo-50/30 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ office.nombre }}</p>
                            <p v-if="office.codigo" class="text-xs text-slate-500 font-mono">{{ office.codigo }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ office.area?.nombre || 'Sin Área' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ office.ubicacion || '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 font-mono">
                            {{ office.telefono_interno || '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-[10px] font-black rounded-lg shadow-sm"
                                :class="office.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                {{ office.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button @click="$emit('edit', office)" title="Editar oficina"
                                    class="text-indigo-600 hover:text-indigo-800 p-2 rounded-lg hover:bg-indigo-50 transition-colors">
                                    <Pencil class="w-4 h-4" />
                                </button>
                                <button @click="$emit('delete', office.id)" title="Eliminar oficina"
                                    class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="offices.length === 0">
                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <Building class="w-12 h-12 text-slate-300 mb-4" />
                                <p>No hay oficinas registradas.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Building, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    offices: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);
</script>
