<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <!-- Header with Gradient and Avatar -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-6 border-b border-emerald-500/20">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-16 w-16 flex-shrink-0 aspect-square rounded-full bg-white border-2 border-emerald-400 p-0.5 shadow-xl flex items-center justify-center text-emerald-600 font-bold text-2xl">
                                {{ employee ? getInitials(employee.nombres + ' ' + employee.apellidos) : '?' }}
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-white tracking-tight">
                                    {{ employee?.nombres }} {{ employee?.apellidos }}
                                </h3>
                                <p class="text-emerald-50 text-sm font-medium opacity-90">
                                    {{ employee?.cargo || 'Sin cargo asignado' }}
                                </p>
                            </div>
                        </div>
                        <button @click="$emit('close')"
                            class="text-emerald-100 hover:text-white transition-colors p-1 bg-white/10 rounded-lg backdrop-blur-sm">
                            <X class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <!-- Employee Details -->
                <div v-if="employee" class="p-6 space-y-5 max-h-[70vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <CreditCard class="w-3 h-3" /> DNI
                            </p>
                            <p class="text-lg font-bold text-slate-800 font-mono tracking-tighter">{{ employee.dni }}
                            </p>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Estado</p>
                            <span class="inline-flex px-3 py-1 text-xs font-black rounded-lg shadow-sm"
                                :class="statusClass">
                                {{ employee.estado }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                        <p
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                            <Building2 class="w-3 h-3" /> Dirección / Oficina
                        </p>
                        <p class="text-base font-bold text-slate-800">{{ employee.direction_nombre || 'No especificada'
                            }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <ScrollText class="w-3 h-3" /> Tipo Contrato
                            </p>
                            <span class="inline-flex px-2.5 py-1 text-[10px] font-black rounded-lg shadow-sm"
                                :class="contractClass">
                                {{ employee.tipo_contrato || '-' }}
                            </span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <Calendar class="w-3 h-3" /> Fecha Ingreso
                            </p>
                            <p class="text-base font-bold text-slate-800">{{ formatDate(employee.fecha_ingreso) || '-'
                            }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <Phone class="w-3 h-3" /> Teléfono
                            </p>
                            <p
                                class="text-base font-bold text-slate-800 underline decoration-emerald-200 decoration-2 underline-offset-4">
                                {{ employee.telefono || 'Sin registro' }}</p>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                            <p
                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                                <Mail class="w-3 h-3" /> Correo
                            </p>
                            <p class="text-sm font-bold text-slate-800 truncate" :title="employee.correo">{{
                                employee.correo || 'Sin registro' }}</p>
                        </div>
                    </div>

                    <div v-if="employee.direccion"
                        class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                        <p
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                            <MapPin class="w-3 h-3" /> Dirección
                        </p>
                        <p class="text-sm font-bold text-slate-800 leading-relaxed">{{ employee.direccion }}</p>
                    </div>

                    <div v-if="employee.observaciones"
                        class="bg-slate-50 border border-slate-100 rounded-2xl p-4 transition-all hover:shadow-md">
                        <p
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1.5">
                            <MessageSquareText class="w-3 h-3" /> Observaciones
                        </p>
                        <p class="text-sm text-slate-600 font-medium italic">"{{ employee.observaciones }}"</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="p-6 bg-slate-50 border-t border-slate-200">
                    <button @click="$emit('close')"
                        class="w-full px-6 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-black uppercase tracking-widest text-xs rounded-2xl hover:from-emerald-700 hover:to-teal-700 transition-all shadow-lg shadow-emerald-500/20 active:scale-95">
                        Entendido / Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import {
    X, CreditCard, Building2, Calendar, ScrollText, Phone, Mail, MapPin, MessageSquareText
} from 'lucide-vue-next';

const props = defineProps({
    employee: {
        type: Object,
        default: null
    }
});

defineEmits(['close']);

const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const statusClass = computed(() => ({
    'bg-green-100 text-green-700 ring-1 ring-green-600/20': props.employee?.estado === 'ACTIVO',
    'bg-orange-100 text-orange-700 ring-1 ring-orange-600/20': props.employee?.estado === 'VACACIONES',
    'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-600/20': props.employee?.estado === 'LICENCIA',
    'bg-red-100 text-red-700 ring-1 ring-red-600/20': props.employee?.estado === 'INACTIVO'
}));

const contractClass = computed(() => ({
    'bg-blue-100 text-blue-700 ring-1 ring-blue-600/20': props.employee?.tipo_contrato === 'Nombrado',
    'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-600/20': props.employee?.tipo_contrato === 'CAS',
    'bg-purple-100 text-purple-700 ring-1 ring-purple-600/20': props.employee?.tipo_contrato === 'Locador',
    'bg-pink-100 text-pink-700 ring-1 ring-pink-600/20': props.employee?.tipo_contrato === 'Practicante',
    'bg-gray-100 text-gray-700 ring-1 ring-gray-600/20': !props.employee?.tipo_contrato
}));
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>
