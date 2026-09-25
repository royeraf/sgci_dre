<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start sm:items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="$emit('close')"></div>

            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-6xl w-full z-10 overflow-hidden flex flex-col max-h-[92vh]">

                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Receipt class="w-6 h-6" />
                            Boleta de Pago
                        </h3>
                        <p class="text-teal-50 text-sm mt-1 font-mono">
                            {{ boleta.trabajador.codigo_boleta }} · {{ boleta.periodo.nombre_periodo }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Toolbar -->
                <div class="px-4 sm:px-6 py-3 border-b border-slate-100 flex items-center justify-end gap-3 bg-slate-50 shrink-0">
                    <span class="text-xs text-slate-500 font-medium mr-auto hidden sm:inline">
                        Vista previa · A5 horizontal · el PDF se genera con el mismo contenido
                    </span>
                    <button @click="onPdf"
                        class="cursor-pointer inline-flex items-center px-4 py-2 text-sm font-bold rounded-xl border-2 border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all">
                        <FileText class="w-4 h-4 mr-2" />
                        Descargar PDF
                    </button>
                    <button @click="$emit('close')"
                        class="cursor-pointer inline-flex items-center px-4 py-2 text-sm font-bold rounded-xl bg-gradient-to-r from-teal-600 to-cyan-600 text-white hover:from-teal-700 hover:to-cyan-700 transition-all">
                        Cerrar
                    </button>
                </div>

                <!-- Documento -->
                <div class="overflow-auto p-4 sm:p-6 bg-slate-100">
                    <div v-if="!boleta" class="py-20 text-center">
                        <Loader2 class="w-8 h-8 text-teal-500 animate-spin mx-auto" />
                    </div>

                    <div v-else
                        class="hoja bg-white shadow-lg border border-slate-300 p-5 sm:p-7 text-[10px] text-slate-900 mx-auto w-full max-w-[920px]">

                        <!-- Encabezado -->
                        <div class="flex items-start gap-4 mb-3">
                            <div class="w-16 shrink-0">
                                <img src="/images/logo.png" alt="DRE" class="w-14 h-auto object-contain" />
                            </div>
                            <div class="flex-1 text-center -mt-1">
                                <h2 class="text-lg font-black tracking-[0.15em]">BOLETA DE PAGO</h2>
                                <p class="text-[10px] text-slate-700">Planilla de pago personal CAS</p>
                                <p class="text-xs font-bold text-slate-800 mt-0.5">
                                    {{ boleta.periodo.nombre_mes }} - {{ boleta.periodo.anio }}
                                </p>
                            </div>
                            <div class="w-40 shrink-0 border-2 border-slate-900">
                                <p class="bg-slate-100 border-b-2 border-slate-900 text-[8px] font-bold uppercase tracking-wide text-center py-0.5">
                                    Código de boleta
                                </p>
                                <p class="text-center font-black text-[13px] py-1 tracking-wide">
                                    {{ boleta.trabajador.codigo_boleta }}
                                </p>
                                <p class="border-t border-slate-300 text-center py-0.5 text-[9px] text-slate-600">
                                    DNI: <span class="font-bold text-slate-900">{{ dato(boleta.trabajador.dni) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Empresa + trabajador + relación laboral: misma rejilla
                             unificada que usa la plantilla PDF -->
                        <table class="w-full border-2 border-slate-900 border-collapse">
                            <tbody>
                                <tr>
                                    <th colspan="4" class="sep">Datos de la empresa</th>
                                </tr>
                                <tr>
                                    <td class="label">RUC</td>
                                    <td class="value font-bold">{{ dato(boleta.empresa.ruc) }}</td>
                                    <td class="label">Razón Social</td>
                                    <td class="value font-bold">{{ dato(boleta.empresa.razon_social) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Dirección</td>
                                    <td colspan="3" class="value">{{ dato(boleta.empresa.direccion) }}</td>
                                </tr>

                                <tr>
                                    <th colspan="4" class="sep">Datos del trabajador</th>
                                </tr>
                                <tr>
                                    <td class="label">Apellidos y Nombres</td>
                                    <td colspan="3" class="value font-bold">
                                        {{ dato(boleta.trabajador.apellidos_nombres) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">DNI</td>
                                    <td class="value">{{ dato(boleta.trabajador.dni) }}</td>
                                    <td class="label">Código de Boleta</td>
                                    <td class="value">{{ boleta.trabajador.codigo_boleta }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Fecha de Ingreso</td>
                                    <td class="value">{{ fecha(boleta.trabajador.fecha_ingreso) }}</td>
                                    <td class="label">Fecha Inicio Contrato</td>
                                    <td class="value">{{ fecha(boleta.trabajador.fecha_inicio_contrato) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">{{ indeterminado ? 'Contrato' : 'Fecha Fin Contrato' }}</td>
                                    <td colspan="3" class="value">
                                        <span v-if="indeterminado" class="font-bold">Indeterminado</span>
                                        <template v-else>
                                            {{ fecha(boleta.trabajador.fecha_fin_contrato) }}
                                            <span class="font-bold">(Contrato Fijo)</span>
                                        </template>
                                    </td>
                                </tr>

                                <tr>
                                    <th colspan="4" class="sep">Datos vinculados a la relación laboral</th>
                                </tr>
                                <tr>
                                    <td class="label">Cargo</td>
                                    <td class="value font-bold">{{ dato(boleta.relacion_laboral.cargo) }}</td>
                                    <td class="label">Régimen</td>
                                    <td class="value font-bold">{{ dato(boleta.relacion_laboral.regimen) }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Periodicidad</td>
                                    <td class="value font-bold">{{ boleta.relacion_laboral.periodicidad }}</td>
                                    <td class="label">Sistema de Pensiones</td>
                                    <td class="value font-bold">
                                        {{ dato(boleta.relacion_laboral.sistema_pensiones) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">CUSPP</td>
                                    <td class="value">{{ dato(boleta.relacion_laboral.cuspp) }}</td>
                                    <td class="label">Días Laborados</td>
                                    <td class="value font-bold">
                                        {{ boleta.relacion_laboral.dias_laborados }} / {{ DIAS_MES }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="label">Banco</td>
                                    <td class="value">{{ dato(boleta.relacion_laboral.banco) }}</td>
                                    <td class="label">Cuenta de Ahorro</td>
                                    <td class="value">{{ dato(boleta.relacion_laboral.cuenta_ahorro) }}</td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Conceptos -->
                        <table class="w-full border-2 border-slate-900 border-collapse mt-3">
                            <thead>
                                <tr class="bg-slate-200">
                                    <th
                                        v-for="titulo in columnas" :key="titulo.titulo"
                                        class="text-center uppercase text-[9px] font-bold tracking-wide px-2 py-1 border-r border-slate-900 last:border-r-0">
                                        {{ titulo.titulo }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td v-for="columna in columnas" :key="columna.titulo"
                                        class="align-top p-0 border-r border-slate-900 last:border-r-0">
                                        <div v-if="!columna.items.length" class="text-center text-slate-300 py-2">—</div>
                                        <div v-for="(item, i) in columna.items" :key="i"
                                            class="flex items-baseline justify-between gap-2 px-2 py-1">
                                            <span class="text-slate-700">
                                                {{ item.descripcion }}<span
                                                    v-if="item.porcentaje !== null"
                                                    class="text-slate-400">{{ pct(item.porcentaje) }}</span>
                                            </span>
                                            <span class="font-bold whitespace-nowrap tabular-nums">
                                                {{ money(item.monto) }}
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t-2 border-slate-900 font-bold">
                                    <td v-for="columna in columnas" :key="columna.titulo"
                                        class="px-2 py-1.5 text-right whitespace-nowrap border-r border-slate-900 last:border-r-0">
                                        <span class="text-slate-500 text-[9px] uppercase mr-1">{{ columna.titulo }}</span>
                                        {{ money(columna.total) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>

                        <!-- Neto + fecha de emisión, en una sola línea como el PDF -->
                        <div class="flex items-stretch gap-3 mt-2">
                            <div class="w-1/4 border-2 border-slate-900 bg-slate-100 px-3 py-2 flex items-center">
                                <span class="text-[9px] font-bold uppercase tracking-[0.15em]">Neto a pagar</span>
                            </div>
                            <div class="w-1/5 border-2 border-slate-900 px-3 py-1.5 flex items-center justify-end">
                                <span class="font-black text-base tabular-nums">
                                    S/ {{ money(boleta.totales.neto_pagar) }}
                                </span>
                            </div>
                            <div class="flex-1 flex items-center text-[9px] text-slate-600">
                                Fecha de emisión:
                                <span class="font-bold text-slate-900 ml-1">{{ fechaHora(boleta.fecha_emision) }}</span>
                            </div>
                            <div class="flex-1 flex items-center justify-end text-[9px] text-slate-600">
                                Periodo {{ boleta.periodo.nombre_periodo }}
                            </div>
                        </div>

                        <!-- Firmas -->
                        <div class="flex items-end gap-8 mt-8 mb-3">
                            <div class="flex-1 text-center">
                                <div class="border-t border-slate-900"></div>
                                <p class="text-[9px] font-bold uppercase mt-1">Firma del trabajador</p>
                                <p class="text-[9px] text-slate-500">{{ dato(boleta.trabajador.apellidos_nombres) }}</p>
                            </div>
                            <div class="flex-1 text-center">
                                <div class="border-t border-slate-900"></div>
                                <p class="text-[9px] font-bold uppercase mt-1">Firma del empleador</p>
                                <p class="text-[9px] text-slate-500">{{ dato(boleta.empresa.razon_social) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Receipt, X, Loader2, FileText } from 'lucide-vue-next';
import { DIAS_MES } from '@/Composables/usePlanillasBoletas';

const props = defineProps({
    boleta: { type: Object, required: true },
    detalleId: { type: String, required: true },
});

const emit = defineEmits(['close']);

const columnas = computed(() => [
    {
        titulo: 'Remuneraciones',
        items: props.boleta?.remuneraciones ?? [],
        total: props.boleta?.totales?.remuneraciones ?? 0,
    },
    {
        titulo: 'Retenciones / Descuentos',
        items: props.boleta?.retenciones ?? [],
        total: props.boleta?.totales?.retenciones ?? 0,
    },
    {
        titulo: 'Aportaciones del Empleador',
        items: props.boleta?.aportaciones ?? [],
        total: props.boleta?.totales?.aportaciones ?? 0,
    },
]);

const indeterminado = computed(() => props.boleta?.trabajador?.contrato === 'INDETERMINADO');

const dato = (valor) => (valor === null || valor === undefined || valor === '') ? '—' : valor;

const money = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const pct = (value) => ` (${(value * 100).toFixed(2)}%)`;

/** Formatea 'YYYY-MM-DD' sin pasar por Date, para no cruzar el día por zona horaria. */
const fecha = (value) => {
    if (!value) return '—';
    const [y, m, d] = String(value).split('-');
    return d ? `${d}/${m}/${y}` : value;
};

const fechaHora = (value) => {
    if (!value) return '—';
    const [dia, hora] = String(value).split(' ');
    return `${fecha(dia)} ${(hora ?? '').slice(0, 5)}`;
};

const onPdf = () => {
    window.open(`/planillas/boletas/${props.detalleId}/pdf`, '_blank');
};
</script>

<style scoped>
/* La boleta en PDF usa DejaVu Sans porque es la única sans que trae DomPDF.
   Estos woff2 son un subconjunto de ese mismo archivo (los genera
   `php artisan planillas:fuente-boleta`), de modo que la vista previa se ve
   exactamente igual que el PDF en vez de la sans del sistema. */
@font-face {
    font-family: 'BoletaSans';
    src: url('/fonts/DejaVuSans.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'BoletaSans';
    src: url('/fonts/DejaVuSans-Bold.woff2') format('woff2');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}

.hoja {
    font-family: 'BoletaSans', 'DejaVu Sans', sans-serif;
    font-variant-ligatures: none;
}

.label {
    width: 24%;
    background-color: #eceff3;
    font-weight: 700;
    font-size: 8px;
    text-transform: uppercase;
    letter-spacing: 0.1px;
    padding: 3px 5px;
    border-right: 1px solid #cbd5e1;
    border-bottom: 1px solid #cbd5e1;
}

.sep {
    background-color: #d5dbe3;
    text-align: left;
    text-transform: uppercase;
    font-weight: 700;
    font-size: 8px;
    letter-spacing: 0.4px;
    padding: 3px 5px;
    border-top: 1px solid #0f172a;
    border-bottom: 1px solid #0f172a;
}

.value {
    width: 26%;
    padding: 3px 5px;
    border-bottom: 1px solid #cbd5e1;
}
</style>
