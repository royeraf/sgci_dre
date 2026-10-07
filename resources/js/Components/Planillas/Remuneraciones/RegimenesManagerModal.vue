<template>
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Landmark class="w-6 h-6" />
                            Regímenes de Pensión
                        </h3>
                        <p class="text-indigo-50 text-sm mt-1">Fondo y seguro por régimen · las comisiones AFP se administan en «Parámetros SBS»</p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Alta / edición -->
                    <form @submit.prevent="onSubmit" class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 space-y-4">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                            {{ editingId ? 'Editar régimen' : 'Nuevo régimen' }}
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Nombre <span class="text-red-500">*</span></label>
                                <input v-model="nombre" type="text" placeholder="Ej. AFP Integra"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none"
                                    :class="error ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Tipo <span class="text-red-500">*</span></label>
                                <select v-model="tipo"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none">
                                    <option value="AFP">AFP</option>
                                    <option value="ONP">ONP</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    {{ tipo === 'ONP' ? 'Aporte obligatorio (%)' : 'Fondo (%)' }} <span class="text-red-500">*</span>
                                </label>
                                <input v-model.number="aporte" type="number" step="0.01" min="0" max="100"
                                    placeholder="Ej. 10"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none"
                                    :class="error ? 'border-red-400' : 'border-slate-200'" />
                            </div>

                            <div v-if="tipo === 'AFP'">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Prima de seguro (%)</label>
                                <input v-model.number="prima" type="number" step="0.01" min="0" max="100"
                                    placeholder="Ej. 1.37"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                            </div>

                            <div class="flex items-end gap-2">
                                <label class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-600 pb-2.5">
                                    <input type="checkbox" v-model="activo" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500" />
                                    Régimen activo
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 font-bold">
                            <button v-if="editingId" type="button" @click="reset"
                                class="cursor-pointer px-5 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-100">Cancelar</button>
                            <button type="submit" :disabled="saving"
                                class="cursor-pointer px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                                <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                                {{ editingId ? 'Guardar cambios' : 'Agregar' }}
                            </button>
                        </div>
                    </form>

                    <!-- Listado -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-indigo-500 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="regimenes.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            No hay regímenes registrados.
                        </p>
                        <table v-else class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500">
                                <tr>
                                    <th class="text-left font-bold uppercase text-[11px] tracking-widest px-4 py-3">Régimen</th>
                                    <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Tipo</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Fondo / Aporte</th>
                                    <th class="text-right font-bold uppercase text-[11px] tracking-widest px-4 py-3">Seguro</th>
                                    <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Estado</th>
                                    <th class="text-center font-bold uppercase text-[11px] tracking-widest px-4 py-3">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                <tr v-for="reg in regimenes" :key="reg.id" class="hover:bg-slate-50/70"
                                    :class="editingId === reg.id ? 'bg-indigo-50/60' : ''">
                                    <td class="px-4 py-3 font-semibold text-slate-800">{{ reg.nombre }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                            :class="reg.tipo === 'ONP' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700'">
                                            {{ reg.tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ pct(reg.aporte_obligatorio) }}%</td>
                                    <td class="px-4 py-3 text-right tabular-nums">{{ reg.tipo === 'ONP' ? '—' : pct(reg.prima_seguro) + '%' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                            :class="reg.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                            {{ reg.activo ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="toggleActivo(reg)" :title="reg.activo ? 'Desactivar' : 'Activar'"
                                                class="cursor-pointer p-2 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 transition-all">
                                                <Power class="w-4 h-4" />
                                            </button>
                                            <button @click="startEdit(reg)" title="Editar"
                                                class="cursor-pointer p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                                <Pencil class="w-4 h-4" />
                                            </button>
                                            <button @click="remove(reg)" title="Eliminar"
                                                class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Landmark, X, Pencil, Trash2, Power, Loader2 } from 'lucide-vue-next';
import { usePlanillaRegimenes } from '@/Composables/usePlanillaRegimenes';

const emit = defineEmits(['close', 'changed']);

const { regimenes, loading, saving, fetchRegimenes, crearRegimen, actualizarRegimen, eliminarRegimen } =
    usePlanillaRegimenes();

const nombre = ref('');
const tipo = ref('AFP');
const aporte = ref(10);
const prima = ref(1.37);
const activo = ref(true);
const editingId = ref(null);
const error = ref('');

// El backend guarda decimales (0.0155); el formulario trabaja en porcentaje (1.55).
const pct = (value) => {
    const n = Math.round(Number(value || 0) * 10000) / 100;
    return Number.isInteger(n) ? n : n.toFixed(2).replace(/\.00$/, '');
};

const dePct = (value) => Number(value || 0) / 100;

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

const payload = () => ({
    nombre: nombre.value.trim(),
    tipo: tipo.value,
    aporte_obligatorio: dePct(aporte.value),
    prima_seguro: tipo.value === 'ONP' ? 0 : dePct(prima.value),
    activo: activo.value,
});

const onSubmit = async () => {
    error.value = '';

    if (!nombre.value.trim()) {
        error.value = 'Ingrese el nombre del régimen';
        return;
    }
    if (!(Number(aporte.value) >= 0)) {
        error.value = 'Ingrese el aporte obligatorio (en %)';
        return;
    }

    try {
        if (editingId.value) {
            await actualizarRegimen(editingId.value, payload());
            notify('success', 'Régimen actualizado correctamente');
        } else {
            await crearRegimen(payload());
            notify('success', 'Régimen registrado correctamente');
        }
        reset();
        emitAfter();
    } catch (err) {
        const message = err.response?.data?.message
            || err.response?.data?.errors?.nombre?.[0]
            || 'No se pudo guardar el régimen';
        error.value = message;
        notify('error', message);
    }
};

const emitAfter = () => emit('changed');

const startEdit = (reg) => {
    editingId.value = reg.id;
    nombre.value = reg.nombre;
    tipo.value = reg.tipo;
    aporte.value = pct(reg.aporte_obligatorio);
    prima.value = pct(reg.prima_seguro);
    activo.value = !!reg.activo;
    error.value = '';
};

const reset = () => {
    editingId.value = null;
    nombre.value = '';
    tipo.value = 'AFP';
    aporte.value = 10;
    prima.value = 1.37;
    activo.value = true;
    error.value = '';
};

const toggleActivo = async (reg) => {
    try {
        await actualizarRegimen(reg.id, {
            nombre: reg.nombre,
            tipo: reg.tipo,
            aporte_obligatorio: Number(reg.aporte_obligatorio),
            prima_seguro: Number(reg.prima_seguro),
            activo: !reg.activo,
        });
        notify('success', `Régimen ${reg.activo ? 'desactivado' : 'activado'}`);
        emitAfter();
    } catch (err) {
        notify('error', err.response?.data?.message || 'No se pudo actualizar el régimen');
    }
};

const remove = async (reg) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar régimen?',
        html: `<p><strong>${reg.nombre}</strong></p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarRegimen(reg.id);
        notify('success', 'Régimen eliminado correctamente');
        if (editingId.value === reg.id) reset();
        emitAfter();
    } catch (err) {
        notify('error', err.response?.data?.message || 'No se pudo eliminar el régimen');
    }
};

onMounted(() => fetchRegimenes(true));
</script>
