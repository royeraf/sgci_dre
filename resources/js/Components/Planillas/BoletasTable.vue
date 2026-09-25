<template>
    <div class="space-y-6">
        <BaseTableCard
            title="Boletas de Pago"
            description="Emisión y consulta de boletas de pago por periodo."
        >
            <template #icon>
                <Receipt class="w-6 h-6 text-cyan-600" />
            </template>

            <template #actions>
                <button @click="abrirConfiguracion"
                    class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl border-2 border-slate-200 bg-white text-slate-700 hover:bg-slate-50 transition-all duration-200">
                    <Building2 class="w-4 h-4 mr-2" />
                    Datos de la empresa
                </button>
                <button @click="onDescargarZip" :disabled="!periodoId || boletas.length === 0 || descargando"
                    class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-cyan-500/30 text-white bg-gradient-to-r from-cyan-600 to-teal-600 hover:from-cyan-700 hover:to-teal-700 transition-all duration-300 hover:-translate-y-0.5 disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:translate-y-0">
                    <Loader2 v-if="descargando" class="w-4 h-4 mr-2 animate-spin" />
                    <FileArchive v-else class="w-4 h-4 mr-2" />
                    Descargar todas
                </button>
            </template>

            <template #filters>
                <div class="w-full max-w-xs">
                    <label class="block text-[11px] font-bold uppercase tracking-widest text-slate-400 mb-1.5">
                        Periodo de planilla
                    </label>
                    <select v-model="periodoId" @change="onPeriodoChange"
                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-sm text-slate-900 focus:ring-4 focus:ring-cyan-500/20 focus:border-cyan-500 transition-all duration-200 outline-none">
                        <option value="" disabled>Seleccione un periodo</option>
                        <option v-for="p in periodos" :key="p.id" :value="p.id">
                            {{ p.nombre_periodo }} ({{ p.estado }})
                            {{ p.boletas > 0 ? ` — ${p.boletas} boletas` : ' — sin planilla' }}
                        </option>
                    </select>
                </div>
            </template>

            <div v-if="periodo" class="px-4 sm:px-5 py-3 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center gap-x-6 gap-y-1 text-xs text-slate-500">
                <span>
                    <span class="font-bold text-slate-700">{{ periodo.total_empleados }}</span> trabajadores
                </span>
                <span>
                    <span class="font-bold text-slate-700">{{ boletas.length }}</span> boletas emitidas
                </span>
                <span>
                    Neto total: <span class="font-bold text-slate-700">S/ {{ money(periodo.total_neto) }}</span>
                </span>
                <span class="ml-auto text-[11px] font-bold uppercase tracking-widest text-slate-400">
                    {{ periodo.estado }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-500">
                        <tr>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Código</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">DNI</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Trabajador</th>
                            <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Cargo</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Días</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Remuneraciones</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Retenciones</th>
                            <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Neto</th>
                            <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-if="loading">
                            <td colspan="9" class="py-16 text-center">
                                <Loader2 class="w-7 h-7 text-cyan-500 animate-spin mx-auto" />
                            </td>
                        </tr>

                        <tr v-else-if="!periodoId">
                            <td colspan="9" class="py-16 text-center text-slate-500 font-medium">
                                Seleccione un periodo para ver sus boletas de pago.
                            </td>
                        </tr>

                        <tr v-else-if="boletas.length === 0">
                            <td colspan="9" class="py-16 text-center text-slate-500 font-medium">
                                Este periodo no tiene planilla generada. Genérela desde la pestaña Planillas.
                            </td>
                        </tr>

                        <template v-else>
                            <tr v-for="fila in boletas" :key="fila.detalle_id" class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-mono text-xs font-bold text-cyan-700 whitespace-nowrap">
                                    {{ fila.codigo_boleta }}
                                </td>
                                <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ fila.dni || '—' }}</td>
                                <td class="px-5 py-3 font-semibold text-slate-800">
                                    {{ fila.apellidos_nombres || '—' }}
                                </td>
                                <td class="px-5 py-3 text-slate-500 text-xs">{{ fila.cargo || '—' }}</td>
                                <td class="px-5 py-3 text-center text-slate-600 tabular-nums">
                                    {{ fila.dias_laborados }}
                                </td>
                                <td class="px-5 py-3 text-right text-emerald-700 font-semibold tabular-nums">
                                    {{ money(fila.total_ingresos) }}
                                </td>
                                <td class="px-5 py-3 text-right text-rose-700 font-semibold tabular-nums">
                                    {{ money(fila.total_descuentos) }}
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900 tabular-nums">
                                    S/ {{ money(fila.neto_pagar) }}
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-center gap-2">
                                        <button @click="onVer(fila)" title="Ver boleta"
                                            class="cursor-pointer p-2 rounded-lg bg-cyan-50 text-cyan-600 hover:bg-cyan-100 transition-all">
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button @click="onPdf(fila)" title="Descargar PDF"
                                            class="cursor-pointer p-2 rounded-lg bg-teal-50 text-teal-600 hover:bg-teal-100 transition-all">
                                            <FileText class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </BaseTableCard>

        <BoletaPreviewModal v-if="boleta && boletaSeleccionado"
            :boleta="boleta"
            :detalle-id="boletaSeleccionado.detalle_id"
            @close="cerrarPreview" />

        <ConfiguracionEmpresaModal v-if="showConfigModal"
            :empresa="empresa"
            :saving="saving"
            @close="showConfigModal = false"
            @submit="onGuardarConfiguracion" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Receipt, Building2, FileArchive, FileText, Eye, Loader2 } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import BoletaPreviewModal from '@/Components/Planillas/Boletas/BoletaPreviewModal.vue';
import ConfiguracionEmpresaModal from '@/Components/Planillas/Boletas/ConfiguracionEmpresaModal.vue';
import { usePlanillasBoletas } from '@/Composables/usePlanillasBoletas';

const {
    periodos,
    periodoId,
    periodo,
    boletas,
    boleta,
    empresa,
    loading,
    saving,
    fetchPeriodos,
    fetchBoletas,
    fetchBoleta,
    fetchConfiguracion,
    updateConfiguracion,
    urlPdf,
    urlZip,
} = usePlanillasBoletas();

const boletaSeleccionado = ref(null);
const showConfigModal = ref(false);
const descargando = ref(false);

const notify = (icon, title) => {
    window.Swal?.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon,
        title,
    });
};

const money = (value) => Number(value || 0).toLocaleString('es-PE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const cargarPeriodoInicial = async () => {
    if (periodoId.value) return;
    const conBoletas = periodos.value.find((p) => p.boletas > 0) ?? periodos.value[0];
    if (conBoletas) {
        periodoId.value = conBoletas.id;
        await onPeriodoChange();
    }
};

const onPeriodoChange = async () => {
    boletaSeleccionado.value = null;
    boleta.value = null;

    if (!periodoId.value) {
        boletas.value = [];
        periodo.value = null;
        return;
    }

    try {
        await fetchBoletas(periodoId.value);
    } catch (error) {
        boletas.value = [];
        periodo.value = null;
        notify('error', error.response?.data?.message || 'No se pudieron cargar las boletas del periodo');
    }
};

const onVer = async (fila) => {
    boletaSeleccionado.value = fila;
    try {
        await fetchBoleta(fila.detalle_id);
    } catch (error) {
        boletaSeleccionado.value = null;
        notify('error', error.response?.data?.message || 'No se pudo cargar la boleta');
    }
};

const cerrarPreview = () => {
    boleta.value = null;
    boletaSeleccionado.value = null;
};

const onPdf = (fila) => {
    window.open(urlPdf(fila.detalle_id), '_blank');
};

const onDescargarZip = async () => {
    if (!periodoId.value) return;

    descargando.value = true;
    try {
        const { data, headers } = await axios.get(urlZip(periodoId.value), { responseType: 'blob' });

        const nombre = /filename="?([^"]+)"?/.exec(headers['content-disposition'] || '')?.[1]
            || `boletas_${periodo.value?.nombre_periodo || ''}.zip`;

        const url = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = url;
        link.download = nombre;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    } catch (error) {
        notify('error', 'No se pudo generar el archivo ZIP');
    } finally {
        descargando.value = false;
    }
};

const abrirConfiguracion = async () => {
    try {
        await fetchConfiguracion();
        showConfigModal.value = true;
    } catch (error) {
        notify('error', 'No se pudieron cargar los datos de la empresa');
    }
};

const onGuardarConfiguracion = async (payload) => {
    try {
        await updateConfiguracion(payload);
        showConfigModal.value = false;
        notify('success', 'Datos de la empresa actualizados');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudieron guardar los datos de la empresa');
    }
};

onMounted(async () => {
    try {
        await fetchPeriodos();
        await cargarPeriodoInicial();
    } catch (error) {
        notify('error', 'No se pudieron cargar los periodos de planilla');
    }
});
</script>
