<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-orange-500 to-amber-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CalendarRange class="w-6 h-6" />
                            Detalle de Vacaciones
                        </h3>
                        <p class="text-orange-50 text-sm mt-1">Información completa del registro vacacional</p>
                    </div>
                    <button @click="$emit('close')" class="text-orange-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Empleado Info -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Empleado</h4>
                        <p class="text-lg font-bold text-slate-900">
                            {{ vacation?.empleado ? `${vacation.empleado.nombres} ${vacation.empleado.apellidos}` : 'Sin asignar' }}
                        </p>
                        <p v-if="vacation?.empleado?.dni" class="text-sm text-slate-600">DNI: {{ vacation.empleado.dni }}</p>
                    </div>

                    <!-- Estado y Período -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Estado</h4>
                            <span class="inline-block px-3 py-1.5 text-sm font-bold rounded-lg" :class="statusClass(vacation?.estado)">
                                {{ vacation?.estado || 'N/A' }}
                            </span>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Período</h4>
                            <p class="text-lg font-bold text-slate-900">{{ vacation?.periodo || 'N/A' }}</p>
                        </div>
                    </div>

                    <!-- Fechas -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Fecha Inicio</h4>
                            <p class="text-base font-semibold text-slate-900">{{ formatDate(vacation?.fecha_inicio) }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Fecha Fin</h4>
                            <p class="text-base font-semibold text-slate-900">{{ formatDate(vacation?.fecha_fin) }}</p>
                        </div>
                        <div class="bg-orange-50 rounded-xl p-4 border border-orange-200">
                            <h4 class="text-xs font-bold text-orange-600 uppercase mb-2">Días Tomados</h4>
                            <p class="text-2xl font-bold text-orange-600">{{ vacation?.dias_tomados || 0 }}</p>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div v-if="vacation?.observaciones" class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Observaciones</h4>
                        <p class="text-sm text-slate-700">{{ vacation.observaciones }}</p>
                    </div>
                    <div v-else class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h4 class="text-xs font-bold text-slate-500 uppercase mb-2">Observaciones</h4>
                        <p class="text-sm text-slate-400 italic">Sin observaciones registradas</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button @click="$emit('close')"
                        class="px-6 py-2.5 bg-gradient-to-r from-orange-600 to-amber-600 text-white font-bold rounded-xl hover:from-orange-700 hover:to-amber-700 transition-all shadow-lg shadow-orange-600/20">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { X, CalendarRange } from 'lucide-vue-next';

defineProps({
    vacation: {
        type: Object,
        default: null
    }
});

defineEmits(['close']);

const formatDate = (date) => {
    if (!date) return '-';
    // Forzar interpretación local para evitar problemas de zona horaria
    const [year, month, day] = date.split('-');
    const localDate = new Date(year, month - 1, day);
    return localDate.toLocaleDateString('es-PE', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const statusClass = (estado) => ({
    'bg-yellow-100 text-yellow-700': estado === 'PROGRAMADO',
    'bg-green-100 text-green-700': estado === 'COMPLETADO',
    'bg-blue-100 text-blue-700': estado === 'EN CURSO',
    'bg-red-100 text-red-700': estado === 'CANCELADO'
});
</script>
