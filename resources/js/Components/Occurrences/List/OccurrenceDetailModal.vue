<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 flex justify-between items-center" :class="getHeaderClass(occurrence?.tipo)">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Eye class="w-6 h-6" />
                            Detalle de Ocurrencia
                        </h3>
                        <p class="text-white/80 text-sm mt-1">{{ occurrence?.tipo }} - {{ occurrence?.fecha }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-white/80 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 space-y-6">
                    <!-- Info Cards -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <Calendar class="w-6 h-6 text-blue-600 mx-auto mb-2" />
                            <p class="text-xs text-slate-500 font-medium">Fecha</p>
                            <p class="text-sm font-bold text-slate-900">{{ occurrence?.fecha }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <Clock class="w-6 h-6 text-green-600 mx-auto mb-2" />
                            <p class="text-xs text-slate-500 font-medium">Hora</p>
                            <p class="text-sm font-bold text-slate-900">{{ occurrence?.hora }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <Sun class="w-6 h-6 text-yellow-600 mx-auto mb-2" />
                            <p class="text-xs text-slate-500 font-medium">Turno</p>
                            <p class="text-sm font-bold text-slate-900">{{ occurrence?.turno }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <AlertTriangle class="w-6 h-6 text-red-600 mx-auto mb-2" />
                            <p class="text-xs text-slate-500 font-medium">Tipo</p>
                            <p class="text-sm font-bold text-slate-900">{{ occurrence?.tipo }}</p>
                        </div>
                    </div>

                    <!-- Responsable -->
                    <div
                        class="flex items-center gap-4 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-100">
                        <div
                            class="h-14 w-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                            {{ (occurrence?.vigilante || '?').charAt(0) }}
                        </div>
                        <div>
                            <p class="text-xs text-blue-600 font-semibold uppercase tracking-wider">Registrado por</p>
                            <p class="text-lg font-bold text-slate-900">{{ occurrence?.vigilante }}</p>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <h4 class="text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <FileText class="w-4 h-4" />
                            Descripción
                        </h4>
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">
                                {{ occurrence?.descripcion || 'Sin descripción' }}
                            </p>
                        </div>
                    </div>

                    <!-- Acciones (si existen) -->
                    <div v-if="occurrence?.acciones">
                        <h4 class="text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            <CheckCircle class="w-4 h-4" />
                            Acciones Tomadas
                        </h4>
                        <div class="bg-green-50 rounded-xl p-4 border border-green-200">
                            <p class="text-sm text-green-800 leading-relaxed whitespace-pre-wrap">
                                {{ occurrence?.acciones }}
                            </p>
                        </div>
                    </div>

                    <!-- Estado -->
                    <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <p class="text-xs text-slate-500 font-medium">Estado actual</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-3 py-1 text-xs font-bold rounded-lg"
                                    :class="getStatusClass(occurrence?.estado)">
                                    {{ occurrence?.estado }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 flex justify-end">
                    <button @click="$emit('close')"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {
    Eye,
    X,
    Calendar,
    Clock,
    Sun,
    AlertTriangle,
    FileText,
    CheckCircle
} from 'lucide-vue-next';

defineProps({
    occurrence: {
        type: Object,
        default: null
    }
});

defineEmits(['close']);

const getHeaderClass = (tipo) => {
    const classes = {
        'Emergencia': 'bg-gradient-to-r from-red-600 to-red-700',
        'Robo': 'bg-gradient-to-r from-red-600 to-red-700',
        'Incidente': 'bg-gradient-to-r from-yellow-600 to-yellow-700',
        'Rutina': 'bg-gradient-to-r from-green-600 to-green-700',
        'Aviso': 'bg-gradient-to-r from-blue-600 to-indigo-600',
        'Otros': 'bg-gradient-to-r from-gray-600 to-gray-700'
    };
    return classes[tipo] || classes['Otros'];
};

const getStatusClass = (estado) => {
    const classes = {
        'Pendiente': 'bg-yellow-100 text-yellow-800',
        'Aprobado': 'bg-green-100 text-green-800',
        'Cerrado': 'bg-gray-100 text-gray-800'
    };
    return classes[estado] || classes['Pendiente'];
};
</script>
