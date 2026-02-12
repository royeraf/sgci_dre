<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-indigo-50/50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-widest">
                            Oficina / Unidad</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-widest">
                            Dirección Perteneciente</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-indigo-700 uppercase tracking-widest">
                            Info. Adicional</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-indigo-700 uppercase tracking-widest">
                            Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="office in offices" :key="office.id"
                        class="hover:bg-indigo-50/40 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 min-w-[48px] flex items-center justify-center bg-indigo-100 rounded-xl text-indigo-600 relative">
                                    <Building class="w-6 h-6" />
                                    <div v-if="office.codigo"
                                        class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-indigo-500 text-white text-[9px] font-black rounded-lg border-2 border-white whitespace-nowrap shadow-md">
                                        {{ office.codigo }}
                                    </div>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 leading-tight">{{ office.nombre }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2">
                                <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                                <span class="text-sm font-bold text-slate-700">
                                    {{ office.direction?.nombre || 'Sin Dirección' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div v-if="office.ubicacion"
                                    class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <MapPin class="w-3.5 h-3.5 text-slate-400" />
                                    {{ office.ubicacion }}
                                </div>
                                <div v-if="office.telefono_interno"
                                    class="flex items-center gap-1.5 text-xs text-slate-500 font-medium">
                                    <Phone class="w-3.5 h-3.5 text-slate-400" />
                                    {{ office.telefono_interno }}
                                </div>
                                <div v-if="!office.ubicacion && !office.telefono_interno"
                                    class="text-xs text-slate-400 italic">
                                    Sin información extra
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-black rounded-lg shadow-sm tracking-widest"
                                :class="office.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                <span class="w-1.5 h-1.5 rounded-full"
                                    :class="office.activo ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                                {{ office.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="$emit('edit', office)" title="Editar oficina"
                                    class="text-indigo-600 hover:text-indigo-800 p-2.5 rounded-xl hover:bg-indigo-100 transition-all active:scale-90">
                                    <Pencil class="w-5 h-5" />
                                </button>
                                <button @click="$emit('delete', office.id)" title="Eliminar oficina"
                                    class="text-red-500 hover:text-red-700 p-2.5 rounded-xl hover:bg-red-50 transition-all active:scale-90">
                                    <Trash2 class="w-5 h-5" />
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
import { Building, Pencil, Trash2, MapPin, Phone } from 'lucide-vue-next';

defineProps({
    offices: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete']);
</script>
