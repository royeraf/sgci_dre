<script setup lang="ts">
import { FileText, X } from 'lucide-vue-next';
import { Visit } from '@/Types/visitor';

defineProps<{
    visit: Visit;
}>();

const emit = defineEmits<{
    close: [];
}>();

const formatDate = (dateString?: string) => {
    if (!dateString) return '';
    return dateString.split('-').reverse().join('/');
};
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="emit('close')"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                    appear
                >
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden max-h-[90vh] flex flex-col">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-violet-600 to-fuchsia-600 px-5 py-4 flex items-center justify-between flex-shrink-0">
                            <h2 class="text-base font-bold text-white">Detalles de Visita</h2>
                            <button @click="emit('close')"
                                class="cursor-pointer bg-white/10 hover:bg-white/25 text-white rounded-lg p-1.5 transition-colors duration-150">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Nombre y estado -->
                        <div class="px-5 pt-4 pb-3 border-b border-slate-100 flex-shrink-0">
                            <p class="text-sm font-bold text-slate-900">{{ visit.nombres }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-xs text-slate-500 font-medium">DNI: {{ visit.dni || 'N/A' }}</span>
                                <span
                                    :class="visit.is_pending
                                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-green-50 text-green-700 border border-green-200'"
                                    class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full">
                                    {{ visit.is_pending ? 'En curso' : 'Completada' }}
                                </span>
                            </div>
                        </div>

                        <!-- Detalles -->
                        <div class="px-5 py-4 overflow-y-auto space-y-3 text-sm">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-3">
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Fecha</p>
                                    <p class="font-semibold text-slate-800">{{ formatDate(visit.fecha) || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Destino</p>
                                    <p class="font-semibold text-slate-800">{{ visit.destino || 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Hora ingreso</p>
                                    <p class="font-semibold text-slate-800">{{ visit.hora_ingreso || '--:--' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Hora salida</p>
                                    <p class="font-semibold text-slate-800">{{ visit.hora_salida || '--:--' }}</p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 font-medium">A quien visita</p>
                                <p class="font-semibold text-slate-800">{{ visit.a_quien_visita_nombre || 'N/A' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 font-medium">Motivo</p>
                                <p class="font-semibold text-slate-800">{{ visit.motivo_nombre || 'No especificado' }}</p>
                                <p v-if="visit.motivo" class="text-xs text-slate-500 mt-0.5">{{ visit.motivo }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-400 font-medium">Registrado por</p>
                                <p class="font-semibold text-slate-800">{{ visit.registrado_por || 'N/A' }}</p>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-5 pb-4 pt-3 flex justify-end gap-3 flex-shrink-0 border-t border-slate-100">
                            <a :href="`/visitors/${visit.id}/ticket`" target="_blank"
                                class="cursor-pointer flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 text-sm font-semibold transition-colors duration-150">
                                <FileText class="w-3.5 h-3.5" />
                                Ver ticket
                            </a>
                            <button @click="emit('close')"
                                class="cursor-pointer px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-semibold transition-colors duration-150">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
