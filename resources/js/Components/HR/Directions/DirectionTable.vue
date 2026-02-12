<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                            Dirección / Área</th>
                        <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Info.
                            Adicional</th>
                        <th
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider text-center">
                            Oficinas</th>
                        <th
                            class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider text-center">
                            Estado</th>
                        <th class="px-6 py-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 italic-muted">
                    <tr v-for="direction in directions" :key="direction.id"
                        class="hover:bg-blue-50/30 transition-all duration-200">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-12 h-12 min-w-[48px] flex items-center justify-center bg-blue-100/80 rounded-xl text-blue-600 relative group-hover:bg-blue-200/50 transition-colors">
                                    <Building2 class="w-6 h-6" />
                                    <div v-if="direction.codigo"
                                        class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-indigo-600 text-white text-[9px] font-black rounded-lg border-2 border-white whitespace-nowrap shadow-md transform rotate-3">
                                        {{ direction.codigo }}
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-black text-slate-900 leading-tight">{{ direction.nombre }}</p>
                                        <span v-if="direction.abreviacion"
                                            class="px-1.5 py-0.5 bg-slate-100 text-slate-500 text-[10px] font-bold rounded uppercase">
                                            {{ direction.abreviacion }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-slate-500 font-medium line-clamp-1 max-w-xs mt-0.5"
                                        :title="direction.descripcion">
                                        {{ direction.descripcion || 'Sin descripción detallada' }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <div v-if="direction.ubicacion"
                                    class="flex items-center gap-1.5 text-[11px] text-slate-600">
                                    <MapPin class="w-3.5 h-3.5 text-indigo-500" />
                                    {{ direction.ubicacion }}
                                </div>
                                <div v-if="direction.telefono_interno"
                                    class="flex items-center gap-1.5 text-[11px] text-slate-600">
                                    <Phone class="w-3.5 h-3.5 text-blue-500" />
                                    {{ direction.telefono_interno }}
                                </div>
                                <div v-if="!direction.ubicacion && !direction.telefono_interno"
                                    class="text-[11px] text-slate-400 italic">
                                    No especificada
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <button @click="$emit('viewOffices', direction)" title="Ver oficinas de esta dirección"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-700 rounded-full text-xs font-bold border border-slate-200 hover:bg-slate-200 hover:border-slate-300 transition-all active:scale-95 group">
                                <LayoutGrid
                                    class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition-colors" />
                                <span class="group-hover:text-indigo-700 transition-colors">{{ direction.offices_count
                                    || 0 }}</span>
                            </button>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 text-[10px] font-black rounded-lg shadow-sm tracking-widest"
                                :class="direction.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                <span class="w-1.5 h-1.5 rounded-full"
                                    :class="direction.activo ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                                {{ direction.activo ? 'ACTIVO' : 'INACTIVO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button @click="$emit('edit', direction)" title="Editar dirección"
                                    class="text-blue-600 hover:text-blue-800 p-2.5 rounded-xl hover:bg-blue-100 transition-all active:scale-90">
                                    <Pencil class="w-5 h-5" />
                                </button>
                                <button @click="$emit('delete', direction.id)" title="Eliminar dirección"
                                    class="text-red-500 hover:text-red-700 p-2.5 rounded-xl hover:bg-red-50 transition-all active:scale-90">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="directions.length === 0">
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center">
                                <Building2 class="w-16 h-16 text-slate-300 mb-4" />
                                <p>No hay direcciones registradas.</p>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Building2, Pencil, Trash2, LayoutGrid, MapPin, Phone } from 'lucide-vue-next';

defineProps({
    directions: {
        type: Array,
        default: () => []
    }
});

defineEmits(['edit', 'delete', 'viewOffices']);
</script>
