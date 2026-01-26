<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Nombre</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Descripción</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="type in types" :key="type.id" class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-900">{{ type.nombre }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            {{ type.descripcion || '-' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2 py-1 text-[10px] font-black rounded-lg shadow-sm"
                                :class="type.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                {{ type.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button @click="$emit('edit', type)" title="Editar"
                                class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                <Pencil class="w-5 h-5" />
                            </button>
                            <button @click="$emit('delete', type.id)" title="Eliminar"
                                class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors ml-1">
                                <Trash2 class="w-5 h-5" />
                            </button>
                        </td>
                    </tr>
                    <tr v-if="types.length === 0">
                        <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <FileText class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay tipos de contrato registrados.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { FileText, Pencil, Trash2 } from 'lucide-vue-next';

defineProps({
    types: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);
</script>
