<template>
    <MainLayout>
        <div class="max-w-5xl mx-auto px-3 sm:px-4 lg:px-6 py-8">
            <header class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-bold text-slate-800 tracking-tight flex items-center gap-3">
                    <span class="bg-teal-600 rounded-2xl p-2.5 shadow-lg shadow-teal-600/30">
                        <Receipt class="w-6 h-6 text-white" />
                    </span>
                    Mis Boletas de Pago
                </h1>
                <p v-if="empleado" class="text-slate-500 text-sm mt-2">
                    {{ empleado.apellidos_nombres }}
                    <span v-if="empleado.cargo" class="text-slate-400">· {{ empleado.cargo }}</span>
                </p>
                <p v-else class="text-slate-500 text-sm mt-2">
                    Consulte, descargue y confirme la revisión de sus boletas de pago.
                </p>
            </header>

            <!-- Sin empleado vinculado -->
            <div v-if="sinVinculo"
                class="bg-white rounded-2xl shadow-xl border border-slate-100 px-6 py-14 text-center">
                <UserX class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                <p class="font-bold text-slate-700 text-lg">Cuenta sin trabajador vinculado</p>
                <p class="text-slate-500 text-sm mt-2 max-w-md mx-auto">
                    Su cuenta no está vinculada a ningún trabajador registrado.
                    Contáctese con la oficina de personal para vincularla.
                </p>
            </div>

            <!-- Listado de boletas -->
            <div v-else class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Periodo</th>
                                <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Código</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Neto S/</th>
                                <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Estado</th>
                                <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="boletas.length === 0">
                                <td colspan="5" class="py-14 text-center text-slate-500 font-medium">
                                    Aún no tiene boletas publicadas. Vuelva a consultar cuando su
                                    planilla sea aprobada.
                                </td>
                            </tr>
                            <tr v-for="fila in boletas" :key="fila.detalle_id"
                                class="border-t border-slate-100 hover:bg-slate-50/70 transition-colors">
                                <td class="px-4 py-3 font-semibold text-slate-800 whitespace-nowrap">
                                    {{ fila.periodo.nombre_periodo }}
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-cyan-700 whitespace-nowrap">
                                    {{ fila.codigo_boleta }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800 tabular-nums whitespace-nowrap">
                                    {{ money(fila.neto_pagar) }}
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
                                    <button @click="onVer(fila)" title="Ver boleta"
                                        class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg shadow-sm border-2 border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all mr-1.5">
                                        <Eye class="w-3.5 h-3.5" />
                                        Ver
                                    </button>
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
        </div>

        <!-- Vista previa -->
        <BoletaPreviewModal v-if="modalAbierto && boletaDetalle"
            :boleta="boletaDetalle"
            :detalle-id="activa?.detalle_id ?? ''"
            :url-pdf="activa ? urlPdf(activa.detalle_id) : null"
            :mostrar-confirmacion="true"
            :revisada="activa?.revisada_en ?? null"
            :guardando="guardando"
            @close="cerrarModal"
            @revisar="onRevisarDesdeModal" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import {
    Receipt, UserX, Loader2, Eye, FileText, CheckCircle,
} from 'lucide-vue-next';
import MainLayout from '@/Layouts/MainLayout.vue';
import BoletaPreviewModal from '@/Components/Planillas/Boletas/BoletaPreviewModal.vue';
import { useMisBoletas } from '@/Composables/useMisBoletas';

const props = defineProps({
    empleado: { type: Object, default: null },
    boletas: { type: Array, default: () => [] },
    sin_vinculo: { type: Boolean, default: false },
});

const sinVinculo = ref(props.sin_vinculo);
const boletas = ref([...props.boletas]);

const { guardando, error, ver, revisar, urlPdf } = useMisBoletas();

const modalAbierto = ref(false);
const boletaDetalle = ref(null);
const activa = ref(null);

const money = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

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

const onVer = async (fila) => {
    try {
        activa.value = fila;
        boletaDetalle.value = await ver(fila.detalle_id);
        modalAbierto.value = true;
    } catch {
        window.Swal?.fire({
            icon: 'error',
            title: 'No se pudo cargar la boleta',
            text: error.value || 'Inténtelo nuevamente.',
        });
    }
};

const cerrarModal = () => {
    modalAbierto.value = false;
    boletaDetalle.value = null;
    activa.value = null;
};

const onPdf = (fila) => {
    window.open(urlPdf(fila.detalle_id), '_blank');
};

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

    const fechaConfirmacion = await revisar(fila);
    if (fechaConfirmacion) {
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

const onRevisarDesdeModal = () => {
    if (activa.value) onConfirmar(activa.value);
};
</script>
