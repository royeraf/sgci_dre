<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-teal-50 flex items-center justify-center px-4 py-10">
        <div class="w-full max-w-lg">
            <header class="text-center mb-6">
                <img src="/images/logo.png" alt="DRE Huánuco" class="h-12 w-auto mx-auto mb-3" />
                <h1 class="text-xl font-bold text-slate-800 tracking-tight">
                    Verificación de Boleta de Pago
                </h1>
                <p class="text-slate-500 text-sm mt-1">
                    Consulta la autenticidad de una boleta escaneando su código QR.
                </p>
            </header>

            <!-- Boleta válida -->
            <div v-if="boleta" class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-center">
                    <ShieldCheck class="w-10 h-10 text-white mx-auto mb-2" />
                    <p class="text-white font-bold text-lg">Boleta válida</p>
                    <p class="text-emerald-50 text-sm mt-0.5">
                        Documento emitido por la Dirección Regional de Educación Huánuco
                    </p>
                </div>

                <dl class="divide-y divide-slate-100">
                    <div class="flex items-baseline justify-between gap-4 px-6 py-3">
                        <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Código</dt>
                        <dd class="font-mono font-bold text-cyan-700">{{ boleta.codigo_boleta }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 px-6 py-3">
                        <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Periodo</dt>
                        <dd class="font-semibold text-slate-800">{{ boleta.periodo }}</dd>
                    </div>
                    <div class="flex items-baseline justify-between gap-4 px-6 py-3">
                        <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Trabajador</dt>
                        <dd class="font-semibold text-slate-800 text-right">
                            {{ boleta.apellidos_nombres || '—' }}
                            <span v-if="boleta.cargo" class="block text-xs font-normal text-slate-500">
                                {{ boleta.cargo }}
                            </span>
                        </dd>
                    </div>
                    <div v-if="boleta.fecha_emision" class="flex items-baseline justify-between gap-4 px-6 py-3">
                        <dt class="text-xs font-bold uppercase tracking-widest text-slate-400">Emitida</dt>
                        <dd class="text-sm text-slate-600">{{ fecha(boleta.fecha_emision) }}</dd>
                    </div>
                </dl>
            </div>

            <!-- No válida -->
            <div v-else class="bg-white rounded-2xl shadow-xl border border-slate-100 px-6 py-10 text-center">
                <ShieldX class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <p class="font-bold text-slate-700 text-lg">Boleta no válida</p>
                <p class="text-slate-500 text-sm mt-2 max-w-sm mx-auto">
                    No se encontró ninguna boleta con este código. Verifique que escaneó el
                    código QR de un documento vigente o contáctese con su unidad de personal.
                </p>
            </div>

            <p class="text-center mt-6">
                <a href="/boletas"
                    class="text-sm font-semibold text-teal-700 hover:text-teal-900 hover:underline transition-colors">
                    ¿Es trabajador? Consulte sus boletas con su DNI →
                </a>
            </p>

            <p class="text-center text-xs text-slate-400 mt-6">
                Dirección Regional de Educación Huánuco · SGCI
            </p>
        </div>
    </div>
</template>

<script setup>
import { ShieldCheck, ShieldX } from 'lucide-vue-next';

defineProps({
    boleta: { type: Object, default: null },
});

/** 'YYYY-MM-DD [HH:mm:ss]' → 'DD/MM/YYYY' sin Date (evita correr el día por zona horaria). */
const fecha = (value) => {
    if (!value) return '—';
    const [y, m, d] = String(value).split(' ')[0].split('-');
    return d ? `${d}/${m}/${y}` : value;
};
</script>
