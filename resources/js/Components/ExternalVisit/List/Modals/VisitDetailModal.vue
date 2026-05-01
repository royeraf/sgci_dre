<script setup lang="ts">
import { LogIn, LogOut, FileText, Users, Calendar, MapPin, UserCheck, Info, X } from 'lucide-vue-next';
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
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                        <!-- Header -->
                        <div class="bg-gradient-to-r from-violet-600 to-fuchsia-600 px-6 py-5 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="bg-white/20 rounded-xl p-2">
                                    <Info class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white leading-tight">Detalles de Visita</h2>
                                    <p class="text-violet-200 text-xs">Información completa del registro</p>
                                </div>
                            </div>
                            <button @click="emit('close')"
                                class="cursor-pointer bg-white/10 hover:bg-white/25 text-white rounded-xl p-2 transition-colors duration-150">
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Visitante Hero -->
                        <div class="px-6 pt-5 pb-3 flex items-center gap-4 border-b border-slate-100">
                            <div class="h-14 w-14 shrink-0 rounded-2xl bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-xl font-extrabold text-white shadow-lg">
                                {{ visit.nombres?.charAt(0) || '?' }}
                            </div>
                            <div>
                                <p class="text-base font-bold text-slate-900">{{ visit.nombres }}</p>
                                <p class="text-sm text-slate-500 font-medium">DNI: {{ visit.dni || 'N/A' }}</p>
                                <span
                                    :class="visit.is_pending
                                        ? 'bg-amber-50 text-amber-700 border border-amber-200'
                                        : 'bg-green-50 text-green-700 border border-green-200'"
                                    class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full mt-1">
                                    {{ visit.is_pending ? 'En curso' : 'Completada' }}
                                </span>
                            </div>
                        </div>

                        <!-- Detalles Grid -->
                        <div class="px-6 py-4 grid grid-cols-2 gap-4">
                            <!-- Fecha -->
                            <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-violet-100 rounded-lg p-1.5 shrink-0">
                                    <Calendar class="w-4 h-4 text-violet-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Fecha</p>
                                    <p class="text-sm font-bold text-slate-800">{{ formatDate(visit.fecha) || 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Hora Ingreso -->
                            <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-purple-100 rounded-lg p-1.5 shrink-0">
                                    <LogIn class="w-4 h-4 text-purple-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Hora de ingreso</p>
                                    <p class="text-sm font-bold text-slate-800">{{ visit.hora_ingreso || '--:--' }}</p>
                                </div>
                            </div>

                            <!-- Hora Salida -->
                            <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-red-100 rounded-lg p-1.5 shrink-0">
                                    <LogOut class="w-4 h-4 text-red-500" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Hora de salida</p>
                                    <p class="text-sm font-bold text-slate-800">{{ visit.hora_salida || '--:--' }}</p>
                                </div>
                            </div>

                            <!-- Destino -->
                            <div class="flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-blue-100 rounded-lg p-1.5 shrink-0">
                                    <MapPin class="w-4 h-4 text-blue-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Destino</p>
                                    <p class="text-sm font-bold text-slate-800 break-words">{{ visit.destino || 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- A quien visita -->
                            <div class="col-span-2 flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-fuchsia-100 rounded-lg p-1.5 shrink-0">
                                    <UserCheck class="w-4 h-4 text-fuchsia-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">A quien visita</p>
                                    <p class="text-sm font-bold text-slate-800 break-words">{{ visit.a_quien_visita_nombre || 'N/A' }}</p>
                                </div>
                            </div>

                            <!-- Motivo -->
                            <div class="col-span-2 flex items-start gap-3 bg-violet-50 rounded-xl p-3 border border-violet-100">
                                <div class="bg-violet-100 rounded-lg p-1.5 shrink-0">
                                    <FileText class="w-4 h-4 text-violet-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Motivo</p>
                                    <p class="text-sm font-bold text-slate-800 break-words">{{ visit.motivo_nombre || 'No especificado' }}</p>
                                    <p v-if="visit.motivo" class="text-xs text-slate-500 mt-0.5 break-words">{{ visit.motivo }}</p>
                                </div>
                            </div>

                            <!-- Registrado por -->
                            <div class="col-span-2 flex items-start gap-3 bg-slate-50 rounded-xl p-3">
                                <div class="bg-slate-200 rounded-lg p-1.5 shrink-0">
                                    <Users class="w-4 h-4 text-slate-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-slate-500 font-medium">Registrado por</p>
                                    <p class="text-sm font-bold text-slate-800 break-words">{{ visit.registrado_por || 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 pb-5 flex justify-end gap-3">
                            <a :href="`/visitors/${visit.id}/ticket`" target="_blank"
                                class="cursor-pointer flex items-center gap-2 px-4 py-2 rounded-xl bg-blue-50 text-blue-700 hover:bg-blue-100 text-sm font-semibold transition-colors duration-150">
                                <FileText class="w-4 h-4" />
                                Ver ticket
                            </a>
                            <button @click="emit('close')"
                                class="cursor-pointer px-4 py-2 rounded-xl bg-slate-100 text-slate-700 hover:bg-slate-200 text-sm font-semibold transition-colors duration-150">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
