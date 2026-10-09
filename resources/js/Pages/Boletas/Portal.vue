<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-teal-50">
        <div class="max-w-5xl mx-auto px-3 sm:px-4 lg:px-6 py-8 sm:py-12">
            <!-- Encabezado institucional -->
            <header class="text-center mb-8">
                <img src="/images/logo.png" alt="DRE Huánuco" class="h-14 sm:h-16 w-auto mx-auto mb-3" />
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight">
                    Consulta de Boletas Electrónicas
                </h1>
                <p class="text-slate-500 text-sm mt-2 max-w-xl mx-auto">
                    Ingrese su número de DNI para consultar, descargar y confirmar la revisión de
                    sus boletas de pago. No requiere cuenta ni contraseña.
                </p>
            </header>

            <!-- Identificación por DNI -->
            <div v-if="!empleado" class="max-w-md mx-auto bg-white rounded-2xl shadow-xl border border-slate-100 p-6 sm:p-8">
                <h2 class="text-lg font-bold text-slate-800 mb-1 flex items-center gap-2">
                    <UserRound class="w-5 h-5 text-teal-600" />
                    Identifíquese con su DNI
                </h2>
                <p class="text-sm text-slate-500 mb-5">
                    Solo personal con boletas aprobadas puede consultarlas desde aquí.
                </p>

                <form @submit.prevent="onConsultar" class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            DNI <span class="text-red-500">*</span>
                        </label>
                        <input v-model="dni" type="text" inputmode="numeric" :maxlength="8"
                            placeholder="########" @input="onDniInput" @keypress.enter.prevent="onConsultar"
                            class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 bg-white transition-all duration-200 outline-none font-mono tracking-wider text-lg"
                            :class="error ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="error" class="mt-1.5 text-sm text-red-600">{{ error }}</p>
                    </div>

                    <button type="submit" :disabled="loading || dni.length !== 8"
                        class="w-full py-3.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white font-bold rounded-xl hover:from-teal-700 hover:to-cyan-700 transition-all shadow-lg disabled:opacity-50 flex items-center justify-center gap-2 cursor-pointer">
                        <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
                        <Search v-else class="w-5 h-5" />
                        {{ loading ? 'Consultando…' : 'Consultar mis boletas' }}
                    </button>
                </form>
            </div>

            <!-- Resultado: boletas del trabajador -->
            <template v-else>
                <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Periodo</th>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Empleado</th>
                                <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Estado</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="boletas.length === 0">
                                <td colspan="4" class="py-14 text-center text-slate-500 font-medium">
                                    Aún no tiene boletas publicadas. Vuelva a consultar cuando su
                                    planilla sea aprobada.
                                </td>
                            </tr>
                            <tr v-for="fila in boletas" :key="fila.detalle_id"
                                class="border-t border-slate-100 hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ fila.periodo.nombre_periodo }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ empleado?.apellidos_nombres }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span v-if="fila.revisada_en"
                                        :title="`Recibida el ${fechaHora(fila.revisada_en)}`"
                                        class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 whitespace-nowrap">
                                        <CheckCircle class="w-3.5 h-3.5 mr-1" />
                                        {{ fecha(fila.revisada_en) }}
                                    </span>
                                    <span v-else
                                        class="inline-flex items-center text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                        Pendiente
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button v-if="!fila.revisada_en" @click="onConfirmar(fila)" :disabled="guardando"
                                        title="Confirmar que recibí esta boleta"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm bg-emerald-600 text-white hover:bg-emerald-700 transition-all disabled:opacity-50">
                                        <Loader2 v-if="guardando" class="w-3.5 h-3.5 animate-spin" />
                                        <CheckCircle v-else class="w-3.5 h-3.5" />
                                        Confirmar recibido
                                    </button>
                                    <button v-else @click="onPdf(fila)" title="Descargar PDF"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm border-2 border-cyan-200 bg-white text-cyan-700 hover:bg-cyan-50 transition-all">
                                        <FileText class="w-3.5 h-3.5" />
                                        Descargar
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fuera del card -->
            <div class="mt-4 flex justify-end">
                <button @click="onSalir" :disabled="loading"
                    class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 text-sm font-bold rounded-xl border-2 border-yellow-500 bg-yellow-400 text-slate-900 hover:bg-yellow-500 transition-all disabled:opacity-50">
                    <LogOut class="w-4 h-4" />
                    Hacer otra consulta
                </button>
            </div>
            </template>

            <p class="text-center text-xs text-slate-400 mt-8">
                Dirección Regional de Educación Huánuco · SGCI
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import {
    UserRound, Loader2, Search, LogOut, FileText, CheckCircle,
} from 'lucide-vue-next';
import { useBoletasPortal } from '@/Composables/useBoletasPortal';

const props = defineProps({
    empleado: { type: Object, default: null },
    boletas: { type: Array, default: () => [] },
});

const {
    empleado, boletas, loading, guardando, error,
    consultar, salir, revisar, urlPdf,
} = useBoletasPortal();

const dni = ref('');

onMounted(() => {
    if (props.empleado) {
        empleado.value = props.empleado;
        boletas.value = [...props.boletas];
    }
});

const onDniInput = (event) => {
    dni.value = event.target.value.replace(/\D/g, '');
    error.value = '';
};

const onConsultar = async () => {
    if (dni.value.length !== 8 || loading.value) return;
    await consultar(dni.value);
};

const onSalir = async () => {
    await salir();
    dni.value = '';
};

const onPdf = (fila) => {
    window.open(urlPdf(fila.detalle_id), '_blank');
};

/**
 * El trabajador confirma desde la fila que recibió su boleta; recién entonces
 * se muestra el botón de descarga (v-if/v-else, nunca ambos a la vez).
 */
const onConfirmar = async (fila) => {
    if (guardando.value) return;

    const result = await window.Swal?.fire({
        icon: 'question',
        title: '¿Confirmar recibido?',
        html: `<p>Quedará registrado que recibió la boleta <strong>${fila.codigo_boleta}</strong> (${fila.periodo.nombre_periodo}).</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmé el recibido',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#059669',
    });

    if (!result?.isConfirmed) return;

    const fecha = await revisar(fila);
    if (fecha) {
        window.Swal?.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            icon: 'success',
            title: 'Boleta confirmada como recibida',
        });
    } else {
        window.Swal?.fire({
            icon: 'error',
            title: 'No se pudo confirmar',
            text: error.value || 'Inténtelo nuevamente.',
        });
    }
};

/** 'YYYY-MM-DD [HH:mm:ss]' → 'DD/MM/YYYY' sin Date (evita correr el día por zona horaria). */
const fecha = (value) => {
    if (!value) return '—';
    const [y, m, d] = String(value).split(' ')[0].split('-');
    return d ? `${d}/${m}/${y}` : value;
};

const fechaHora = (value) => {
    if (!value) return '—';
    const [dia, hora] = String(value).split(' ');
    return `${fecha(dia)} ${(hora ?? '').slice(0, 5)}`;
};
</script>
