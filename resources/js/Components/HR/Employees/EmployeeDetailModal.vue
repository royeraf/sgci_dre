<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 z-10">
                <!-- Header with Avatar -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <div
                            class="h-14 w-14 rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                            {{ employee ? getInitials(employee.nombres + ' ' + employee.apellidos) : '?' }}
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">
                                {{ employee?.nombres }} {{ employee?.apellidos }}
                            </h3>
                            <p class="text-sm text-slate-500">{{ employee?.cargo || 'Sin cargo asignado' }}</p>
                        </div>
                    </div>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Employee Details -->
                <div v-if="employee" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">DNI</p>
                            <p class="text-lg font-semibold text-slate-900 font-mono">{{ employee.dni }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Estado</p>
                            <span class="px-3 py-1 text-sm font-bold rounded-lg" :class="statusClass">
                                {{ employee.estado }}
                            </span>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-4">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Área</p>
                        <p class="text-base font-medium text-slate-900">{{ employee.area || '-' }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Tipo de Contrato
                            </p>
                            <span class="px-2 py-1 text-xs font-bold rounded-lg" :class="contractClass">
                                {{ employee.tipo_contrato || '-' }}
                            </span>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Ingreso</p>
                            <p class="text-base font-medium text-slate-900">{{ formatDate(employee.fecha_ingreso) || '-'
                                }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Teléfono</p>
                            <p class="text-base font-medium text-slate-900">{{ employee.telefono || '-' }}</p>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4">
                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Correo</p>
                            <p class="text-sm font-medium text-slate-900 break-all">{{ employee.correo || '-' }}</p>
                        </div>
                    </div>

                    <div v-if="employee.direccion" class="bg-slate-50 rounded-xl p-4">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección</p>
                        <p class="text-base font-medium text-slate-900">{{ employee.direccion }}</p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="mt-6 pt-4 border-t border-slate-200">
                    <button @click="$emit('close')"
                        class="w-full px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

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
    return new Date(date).toLocaleDateString('es-PE');
};

const statusClass = computed(() => ({
    'bg-green-100 text-green-700': props.employee?.estado === 'ACTIVO',
    'bg-orange-100 text-orange-700': props.employee?.estado === 'VACACIONES',
    'bg-yellow-100 text-yellow-700': props.employee?.estado === 'LICENCIA',
    'bg-red-100 text-red-700': props.employee?.estado === 'INACTIVO'
}));

const contractClass = computed(() => ({
    'bg-blue-100 text-blue-700': props.employee?.tipo_contrato === 'Nombrado',
    'bg-yellow-100 text-yellow-700': props.employee?.tipo_contrato === 'CAS',
    'bg-purple-100 text-purple-700': props.employee?.tipo_contrato === 'Locador',
    'bg-pink-100 text-pink-700': props.employee?.tipo_contrato === 'Practicante',
    'bg-gray-100 text-gray-700': !props.employee?.tipo_contrato
}));
</script>
