<template>
    <BaseTableCard
        :title="meta.title"
        :description="meta.description"
        searchPlaceholder="Buscar concepto..."
        :searchValue="search"
        @update:searchValue="search = $event"
    >
        <template #icon>
            <component :is="meta.icon" class="w-6 h-6" :class="meta.iconClass" />
        </template>

        <template #actions>
            <button @click="openCreate"
                class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white transition-all duration-300 hover:-translate-y-0.5"
                :class="meta.buttonClass">
                <Plus class="w-4 h-4 mr-2" />
                Nuevo Concepto
            </button>
        </template>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-500">
                    <tr>
                        <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">#</th>
                        <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Código</th>
                        <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Concepto</th>
                        <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Categoría</th>
                        <th class="text-right font-bold uppercase text-[11px] tracking-widest px-5 py-3">Valor</th>
                        <th class="text-left font-bold uppercase text-[11px] tracking-widest px-5 py-3">Afecto a</th>
                        <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Estado</th>
                        <th class="text-center font-bold uppercase text-[11px] tracking-widest px-5 py-3">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="loading">
                        <td colspan="8" class="py-16 text-center">
                            <Loader2 class="w-7 h-7 text-teal-500 animate-spin mx-auto" />
                        </td>
                    </tr>
                    <tr v-else-if="filtered.length === 0">
                        <td colspan="8" class="py-16 text-center text-slate-500 font-medium">
                            No hay conceptos registrados en esta sección.
                        </td>
                    </tr>
                    <template v-else>
                        <tr v-for="(concepto, index) in filtered" :key="concepto.id" class="hover:bg-slate-50/70">
                            <td class="px-5 py-3 text-slate-400 font-bold">{{ index + 1 }}</td>
                            <td class="px-5 py-3 font-mono text-xs text-slate-500">{{ concepto.codigo }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-800">{{ concepto.nombre }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ concepto.categoria || '—' }}</td>
                            <td class="px-5 py-3 text-right font-bold text-slate-700">{{ formatValor(concepto) }}</td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="tag in afectoTags(concepto)" :key="tag"
                                        class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500">
                                        {{ tag }}
                                    </span>
                                    <span v-if="afectoTags(concepto).length === 0" class="text-xs text-slate-300">—</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                    :class="concepto.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                    {{ concepto.activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button @click="openAsignaciones(concepto)" title="Asignaciones"
                                        class="relative cursor-pointer p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all">
                                        <Link2 class="w-4 h-4" />
                                        <span v-if="concepto.asignaciones_count > 0"
                                            class="absolute -top-1.5 -right-1.5 text-[9px] font-bold bg-indigo-600 text-white rounded-full min-w-[16px] h-4 flex items-center justify-center px-1">
                                            {{ concepto.asignaciones_count }}
                                        </span>
                                    </button>
                                    <button @click="openEdit(concepto)" title="Editar"
                                        class="cursor-pointer p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="confirmDelete(concepto)" title="Eliminar"
                                        class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </BaseTableCard>

    <ConceptoModal v-if="showModal" :concepto="selected" :tipo="tipo" :saving="saving"
        @close="closeModal" @submit="submit" />

    <AsignacionesModal v-if="showAsignacionesModal && selectedConcepto" :concepto="selectedConcepto"
        @close="closeAsignaciones" @changed="onAsignacionChanged" />
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Plus, Pencil, Trash2, Loader2, Coins, Percent, PiggyBank, Link2 } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import ConceptoModal from '@/Components/Planillas/Conceptos/ConceptoModal.vue';
import AsignacionesModal from '@/Components/Planillas/Conceptos/AsignacionesModal.vue';
import { usePlanillaConceptos } from '@/Composables/usePlanillaConceptos';

const props = defineProps({
    tipo: { type: String, required: true },
});

const META = {
    INGRESO: {
        title: 'Remuneraciones (Ingresos)',
        description: 'Conceptos de ingreso que conforman la remuneración del personal',
        icon: Coins,
        iconClass: 'text-emerald-600',
        buttonClass: 'bg-gradient-to-r from-emerald-600 to-teal-600 shadow-emerald-500/30 hover:from-emerald-700 hover:to-teal-700',
    },
    DESCUENTO: {
        title: 'Retenciones y Descuentos',
        description: 'Descuentos de ley, AFP/ONP, renta, tardanzas y otros',
        icon: Percent,
        iconClass: 'text-rose-600',
        buttonClass: 'bg-gradient-to-r from-rose-600 to-red-600 shadow-rose-500/30 hover:from-rose-700 hover:to-red-700',
    },
    APORTACION: {
        title: 'Aportaciones del Empleador',
        description: 'Aportes que asume el empleador (Essalud y otros)',
        icon: PiggyBank,
        iconClass: 'text-indigo-600',
        buttonClass: 'bg-gradient-to-r from-indigo-600 to-blue-600 shadow-indigo-500/30 hover:from-indigo-700 hover:to-blue-700',
    },
};

const meta = computed(() => META[props.tipo] || META.INGRESO);

const { conceptos, loading, saving, fetchConceptos, crearConcepto, actualizarConcepto, eliminarConcepto } = usePlanillaConceptos();

const search = ref('');
const showModal = ref(false);
const selected = ref(null);
const showAsignacionesModal = ref(false);
const selectedConcepto = ref(null);

const normalize = (text) => (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');

const filtered = computed(() => {
    const term = normalize(search.value).trim();
    return conceptos.value
        .filter((c) => c.tipo === props.tipo)
        .filter((c) => !term
            || normalize(c.nombre).includes(term)
            || normalize(c.codigo).includes(term)
            || normalize(c.categoria).includes(term)
        )
        .sort((a, b) => a.orden - b.orden);
});

const formatValor = (concepto) => {
    if (concepto.valor === null || concepto.valor === undefined) return 'Según asignación';
    if (concepto.es_porcentaje) return `${(Number(concepto.valor) * 100).toFixed(2)}%`;
    return `S/ ${Number(concepto.valor).toFixed(2)}`;
};

const afectoTags = (concepto) => {
    const tags = [];
    if (concepto.afecto_essalud) tags.push('EsSalud');
    if (concepto.afecto_onp) tags.push('ONP');
    if (concepto.afecto_afp) tags.push('AFP');
    if (concepto.afecto_renta5) tags.push('Renta 5ta');
    return tags;
};

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

const openCreate = () => {
    selected.value = null;
    showModal.value = true;
};

const openEdit = (concepto) => {
    selected.value = concepto;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selected.value = null;
};

const openAsignaciones = (concepto) => {
    selectedConcepto.value = concepto;
    showAsignacionesModal.value = true;
};

const closeAsignaciones = () => {
    showAsignacionesModal.value = false;
    selectedConcepto.value = null;
};

const onAsignacionChanged = () => {
    fetchConceptos();
};

const submit = async (payload) => {
    try {
        if (selected.value) {
            await actualizarConcepto(selected.value.id, payload);
            notify('success', 'Concepto actualizado correctamente');
        } else {
            await crearConcepto(payload);
            notify('success', 'Concepto registrado correctamente');
        }
        closeModal();
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo guardar el concepto');
    }
};

const confirmDelete = async (concepto) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar concepto?',
        html: `<p><strong>${concepto.nombre}</strong></p><p class="text-sm text-gray-500 mt-1">Esta acción no se puede deshacer.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarConcepto(concepto.id);
        notify('success', 'Concepto eliminado correctamente');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar el concepto');
    }
};

onMounted(fetchConceptos);
</script>
