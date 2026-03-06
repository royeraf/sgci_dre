<template>
    <PortalLayout>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link href="/portal/papeletas"
                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-black text-slate-900">
                    Papeleta #{{ papeleta.numero_papeleta }}
                </h1>
                <p class="text-sm text-slate-500">Detalle de la solicitud</p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Status Banner -->
            <div :class="estadoBannerClass" class="rounded-xl p-4 flex items-center gap-3">
                <CheckCircle v-if="papeleta.estado === 'APROBADO'" class="h-6 w-6 flex-shrink-0" />
                <XCircle v-else-if="papeleta.estado === 'DESAPROBADO'" class="h-6 w-6 flex-shrink-0" />
                <Clock v-else class="h-6 w-6 flex-shrink-0" />
                <div>
                    <p class="font-bold text-sm">{{ estadoTexto }}</p>
                    <p v-if="papeleta.fecha_aprobacion" class="text-xs opacity-80">
                        {{ formatDateTime(papeleta.fecha_aprobacion) }}
                        {{ papeleta.aprobador ? '- ' + papeleta.aprobador.full_name : '' }}
                    </p>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white overflow-hidden">
                <div class="divide-y divide-slate-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Motivo de Salida</p>
                            <p class="text-sm font-bold text-slate-800">{{ papeleta.reason?.nombre ?? '-' }}</p>
                        </div>
                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Turno</p>
                            <p class="text-sm font-bold text-slate-800">{{ papeleta.turno }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Fecha</p>
                            <p class="text-sm font-bold text-slate-800">{{ formatDate(papeleta.fecha_salida) }}</p>
                        </div>
                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Hora Salida</p>
                            <p class="text-sm font-bold text-slate-800">{{ papeleta.hora_salida_estimada }}</p>
                        </div>
                        <div class="p-4 sm:p-5">
                            <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Hora Retorno</p>
                            <p class="text-sm font-bold text-slate-800">{{ papeleta.hora_retorno_estimada || '--:--' }}</p>
                        </div>
                    </div>
                    <div class="p-4 sm:p-5">
                        <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Justificacion</p>
                        <p class="text-sm text-slate-700">{{ papeleta.motivo }}</p>
                    </div>

                    <!-- Approval comment -->
                    <div v-if="papeleta.comentario_aprobacion" class="p-4 sm:p-5 bg-slate-50/50">
                        <p class="text-xs font-semibold text-slate-400 uppercase mb-1">Comentario del Jefe</p>
                        <p class="text-sm text-slate-700">{{ papeleta.comentario_aprobacion }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex justify-between items-center">
                <Link href="/portal/papeletas" class="text-sm font-semibold text-slate-500 hover:text-slate-700 transition-colors">
                    Volver al listado
                </Link>
                <a v-if="papeleta.estado === 'APROBADO'" :href="`/portal/papeletas/${papeleta.id}/pdf`" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-500/20 transition-all">
                    <Download class="h-4 w-4" />
                    Descargar PDF
                </a>
            </div>
        </div>
    </PortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ArrowLeft, CheckCircle, XCircle, Clock, Download } from 'lucide-vue-next';

const props = defineProps({
    papeleta: Object,
    employee: Object,
});

const estadoBannerClass = computed(() => {
    const classes = {
        PENDIENTE: 'bg-yellow-50 text-yellow-800 border border-yellow-200',
        APROBADO: 'bg-green-50 text-green-800 border border-green-200',
        DESAPROBADO: 'bg-red-50 text-red-800 border border-red-200',
    };
    return classes[props.papeleta.estado] || '';
});

const estadoTexto = computed(() => {
    const textos = {
        PENDIENTE: 'Solicitud pendiente de aprobacion',
        APROBADO: 'Solicitud aprobada',
        DESAPROBADO: 'Solicitud desaprobada',
    };
    return textos[props.papeleta.estado] || props.papeleta.estado;
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    return new Date(datetime).toLocaleString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
