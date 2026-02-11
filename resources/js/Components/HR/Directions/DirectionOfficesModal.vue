<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-6">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden border border-slate-200">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <LayoutGrid class="w-6 h-6" />
                            Oficinas Registradas
                        </h3>
                        <p class="text-blue-50 text-sm mt-1">{{ direction?.nombre }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6">
                    <div v-if="offices.length > 0" class="space-y-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
                        <div v-for="office in offices" :key="office.id"
                            class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100 hover:border-blue-200 hover:bg-white transition-all group shadow-sm">

                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 flex items-center justify-center bg-blue-100 rounded-lg text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                                    <Building class="w-5 h-5" />
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 leading-tight">{{ office.nombre }}</p>
                                    <div
                                        class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-[11px] text-slate-500 font-medium">
                                        <span v-if="office.codigo"
                                            class="bg-slate-200 text-slate-700 px-1.5 py-0.5 rounded uppercase font-black text-[9px]">
                                            {{ office.codigo }}
                                        </span>
                                        <div v-if="office.telefono_interno" class="flex items-center gap-1">
                                            <Phone class="w-3 h-3 text-slate-400" />
                                            {{ office.telefono_interno }}
                                        </div>
                                        <div v-if="office.ubicacion" class="flex items-center gap-1">
                                            <MapPin class="w-3 h-3 text-slate-400" />
                                            {{ office.ubicacion }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 text-[9px] font-black rounded-md shadow-sm tracking-widest"
                                    :class="office.activo ? 'bg-green-100 text-green-700 ring-1 ring-green-600/20' : 'bg-red-100 text-red-700 ring-1 ring-red-600/20'">
                                    <span class="w-1 h-1 rounded-full"
                                        :class="office.activo ? 'bg-green-500' : 'bg-red-500'"></span>
                                    {{ office.activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-else class="py-12 text-center">
                        <div
                            class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                            <Building class="w-10 h-10 text-slate-200" />
                        </div>
                        <h4 class="text-lg font-bold text-slate-800 mb-1">Sin oficinas asignadas</h4>
                        <p class="text-slate-400 text-sm max-w-[250px] mx-auto italic">
                            No se encontraron dependencias para esta dirección.
                        </p>
                    </div>

                    <div class="flex justify-end mt-6 pt-4 border-t border-slate-100">
                        <button @click="$emit('close')"
                            class="px-6 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-all active:scale-95 text-sm">
                            Cerrar Vista
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { LayoutGrid, X, Building, Phone, MapPin } from 'lucide-vue-next';

defineProps({
    direction: { type: Object, required: true },
    offices: { type: Array, default: () => [] }
});

defineEmits(['close']);
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: #f8fafc;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
