<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-violet-600 to-indigo-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Detalles de la Autorización de Salida
                        </h3>
                        <p class="text-violet-100 text-sm mt-1">
                            Nº {{ String(commission.numero).padStart(3, '0') }}-{{ commission.anio }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="cursor-pointer text-violet-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                    <!-- Sección 1: Datos de la Comisión -->
                    <div class="space-y-3">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-1.5 text-xs uppercase tracking-wide">
                            <span class="p-1 rounded-lg bg-violet-50 text-violet-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                            Datos de la Comisión
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Solicitante</span>
                                <span class="text-sm font-bold text-slate-800">{{ commission.solicitante || 'N/A' }}</span>
                            </div>
                            
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Lugar de Destino</span>
                                <span class="text-sm font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    {{ commission.lugar }}
                                </span>
                            </div>
                            
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Referencia / Doc.</span>
                                <span class="text-sm font-semibold text-slate-800">{{ commission.referencia || 'Sin referencia' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Fecha Programada</span>
                                <span class="text-sm font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ formatDate(commission.dia) }}
                                </span>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Hora Programada</span>
                                <span class="text-sm font-semibold text-slate-800 flex items-center gap-1 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    {{ commission.hora }}
                                </span>
                            </div>

                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Estado</span>
                                <span class="inline-block px-3 py-1 text-xs font-bold rounded-full mt-1"
                                    :class="getStatusClass(commission.estado)">
                                    {{ commission.estado }}
                                </span>
                            </div>

                            <div class="sm:col-span-2" v-if="commission.motivo">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Motivo detallado del Viaje</span>
                                <p class="text-sm text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 mt-1 whitespace-pre-line leading-relaxed">
                                    {{ commission.motivo }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Vehículo y Personal -->
                    <div class="space-y-3 pt-2">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-1.5 text-xs uppercase tracking-wide">
                            <span class="p-1 rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </span>
                            Vehículo y Personal Asignado
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Vehículo Asignado</span>
                                <span class="text-sm font-bold text-slate-800 flex items-center gap-1 mt-0.5">
                                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                                    </svg>
                                    {{ commission.placa }} <span class="font-normal text-slate-500">· {{ commission.marca }} {{ commission.modelo }}</span>
                                </span>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Conductor</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.conductor || 'N/A' }}</span>
                            </div>

                            <div class="sm:col-span-2" v-if="commission.pasajeros?.length || commission.usuarios">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Pasajeros</span>
                                <p v-if="commission.pasajeros?.length" class="text-sm text-slate-700 mt-0.5 font-medium">
                                    {{ commission.pasajeros.map(p => p.nombre_completo).join(', ') }}
                                </p>
                                <p v-else class="text-sm text-slate-700 mt-0.5 font-medium">{{ commission.usuarios }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Control de Salida y Retorno -->
                    <div class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 space-y-3">
                        <h4 class="font-bold text-blue-800 flex items-center gap-2 border-b border-blue-100/50 pb-1.5 text-xs uppercase tracking-wide">
                            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Control de Salida y Retorno
                        </h4>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">Hora de Salida</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.hora_salida || '--:--' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">Hora de Regreso</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.hora_regreso || '--:--' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">Km de Salida</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.km_salida !== null ? `${commission.km_salida} km` : '--' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">Km de Retorno</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.km_retorno !== null ? `${commission.km_retorno} km` : '--' }}</span>
                            </div>

                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">Combustible</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.combustible || 'No registrado' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-blue-600 uppercase">P/Nº</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.pnro || '--' }}</span>
                            </div>
                            
                            <div class="col-span-2 bg-blue-100/60 border border-blue-200 rounded-xl p-3 flex justify-between items-center">
                                <span class="text-xs font-bold text-blue-800 uppercase">Total Kilómetros Recorridos</span>
                                <span class="text-md font-black text-blue-900 bg-white/90 px-2.5 py-1 rounded-lg border border-blue-200 shadow-sm">
                                    {{ commission.total_km_recorrido !== null ? `${commission.total_km_recorrido} km` : '—' }}
                                </span>
                            </div>

                        </div>
                    </div>

                    <!-- Sección 4: Autorización -->
                    <div class="space-y-3 pt-2">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-1.5 text-xs uppercase tracking-wide">
                            <span class="p-1 rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            Autorización
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Autorizado / Rechazado por</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.autorizado_por_nombre || 'Pendiente' }}</span>
                            </div>
                            <div>
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Fecha de Autorización</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">{{ commission.fecha_autorizacion || '—' }}</span>
                            </div>
                            <div class="sm:col-span-2" v-if="commission.comentario_autorizacion">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">
                                    {{ commission.estado === 'RECHAZADA' ? 'Motivo del Rechazo' : 'Comentario' }}
                                </span>
                                <p class="text-sm text-slate-600 bg-slate-50 p-2.5 rounded-xl border border-slate-100 mt-1 whitespace-pre-line leading-relaxed">
                                    {{ commission.comentario_autorizacion }}
                                </p>
                            </div>
                            <div class="sm:col-span-2">
                                <span class="block text-xs font-semibold text-slate-400 uppercase">Confirmación del Conductor</span>
                                <span class="text-sm font-bold text-slate-800 mt-0.5 block">
                                    {{ commission.fecha_confirmacion_conductor ? `Confirmada el ${commission.fecha_confirmacion_conductor}` : 'Pendiente' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer / Close -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button @click="$emit('close')"
                        class="cursor-pointer px-5 py-2 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 transition-colors text-sm">
                        Cerrar Detalles
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    commission: {
        type: Object,
        required: true
    }
});

defineEmits(['close']);

const formatDate = (dateString) => {
    if (!dateString) return '';
    const datePart = dateString.includes(' ') 
        ? dateString.split(' ')[0] 
        : (dateString.includes('T') ? dateString.split('T')[0] : dateString);
    if (datePart.includes('-')) {
        return datePart.split('-').reverse().join('/');
    }
    return dateString;
};

const getStatusClass = (status) => {
    switch (status) {
        case 'COMPLETADA':
            return 'bg-green-100 text-green-800 border border-green-200';
        case 'EN_COMISION':
            return 'bg-blue-100 text-blue-800 border border-blue-200';
        case 'CONFIRMADA':
            return 'bg-cyan-100 text-cyan-800 border border-cyan-200';
        case 'AUTORIZADA':
            return 'bg-indigo-100 text-indigo-800 border border-indigo-200';
        case 'RECHAZADA':
            return 'bg-red-100 text-red-800 border border-red-200';
        case 'PENDIENTE':
            return 'bg-yellow-100 text-yellow-800 border border-yellow-200';
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
};
</script>
