<template>
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-5xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Network class="w-6 h-6" />
                            Clasificador Económico de Gastos MEF
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">Catálogo jerárquico y reglas por concepto de planilla</p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="px-6 pt-4 flex gap-2 border-b border-slate-100">
                    <button v-for="opcion in tabs" :key="opcion.id" type="button" @click="tab = opcion.id"
                        class="cursor-pointer flex items-center gap-2 px-4 py-2.5 text-sm font-bold rounded-t-xl border-b-2 transition-all"
                        :class="tab === opcion.id
                            ? 'border-indigo-600 text-indigo-700 bg-indigo-50/60'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50'">
                        <component :is="opcion.icono" class="w-4 h-4" />
                        {{ opcion.label }}
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[70vh] overflow-y-auto">
                    <!-- CATÁLOGO -->
                    <template v-if="tab === 'catalogo'">
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Ejercicio</label>
                                <input v-model.number="filtros.anio" type="number" min="2000" max="2100"
                                    @change="recargar"
                                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                            </div>
                            <div class="col-span-2">
                                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Buscar</label>
                                <div class="relative">
                                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                    <input v-model="filtros.search" type="text" placeholder="Código o descripción..."
                                        @input="recargar"
                                        class="w-full pl-9 pr-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Estado</label>
                                <select v-model="filtros.estado" @change="recargar"
                                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none">
                                    <option value="">Todos</option>
                                    <option value="activo">Activos</option>
                                    <option value="inactivo">Inactivos</option>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-2xl border-2 border-indigo-100 bg-indigo-50/40 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-indigo-500">Nuevo clasificador</p>
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                                <input v-model="nuevo.codigo" type="text" placeholder="Código (2.1.1.13.1.1)"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                <input v-model="nuevo.codigo_padre" type="text" placeholder="Código padre"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                <input v-model="nuevo.codigo_alias" type="text" placeholder="Alias (opcional)"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                <input v-model.number="nuevo.nivel" type="number" min="1" max="10" placeholder="Nivel"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end">
                                <input v-model="nuevo.descripcion" type="text" placeholder="Descripción *"
                                    class="sm:col-span-2 px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 cursor-pointer">
                                        <input v-model="nuevo.es_terminal" type="checkbox" class="w-4 h-4 rounded accent-indigo-600" />
                                        Terminal
                                    </label>
                                    <button @click="agregar" :disabled="saving"
                                        class="cursor-pointer ml-auto inline-flex items-center px-4 py-2 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 disabled:opacity-50 transition-all">
                                        <Plus class="w-4 h-4 mr-1" />
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <div v-if="loading" class="py-12 text-center">
                                <Loader2 class="w-6 h-6 text-indigo-500 animate-spin mx-auto" />
                            </div>
                            <p v-else-if="clasificadores.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                                No hay clasificadores para el ejercicio {{ filtros.anio }}.
                            </p>
                            <ul v-else class="divide-y divide-slate-100">
                                <li v-for="c in clasificadores" :key="c.id" class="px-4 py-3 hover:bg-slate-50/70">
                                    <div v-if="editandoId === c.id" class="space-y-2">
                                        <div class="grid grid-cols-1 sm:grid-cols-4 gap-2">
                                            <input v-model="edit.codigo_padre" type="text" placeholder="Código padre"
                                                class="px-3 py-2 border-2 border-indigo-200 rounded-xl text-sm font-mono text-slate-900 outline-none focus:border-indigo-500" />
                                            <input v-model="edit.codigo_alias" type="text" placeholder="Alias"
                                                class="px-3 py-2 border-2 border-indigo-200 rounded-xl text-sm font-mono text-slate-900 outline-none focus:border-indigo-500" />
                                            <input v-model.number="edit.nivel" type="number" min="1" max="10" placeholder="Nivel"
                                                class="px-3 py-2 border-2 border-indigo-200 rounded-xl text-sm text-slate-900 outline-none focus:border-indigo-500" />
                                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-600 px-1">
                                                <input v-model="edit.es_terminal" type="checkbox" class="w-4 h-4 rounded accent-indigo-600" />
                                                Terminal
                                            </label>
                                        </div>
                                        <input v-model="edit.descripcion" type="text" placeholder="Descripción"
                                            class="w-full px-3 py-2 border-2 border-indigo-200 rounded-xl text-sm text-slate-900 outline-none focus:border-indigo-500" />
                                        <div class="flex justify-end gap-2">
                                            <button @click="guardarEdicion(c)" :disabled="saving"
                                                class="cursor-pointer p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                                                <Check class="w-4 h-4" />
                                            </button>
                                            <button @click="cancelarEdicion"
                                                class="cursor-pointer p-2 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
                                                <X class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <div v-else class="flex items-center gap-3">
                                        <div class="flex-1 min-w-0" :style="{ paddingLeft: `${Math.max((c.nivel || 1) - 1, 0) * 14}px` }">
                                            <p class="font-semibold text-slate-800 truncate">
                                                <span class="font-mono text-indigo-600 mr-2">{{ c.codigo }}</span>
                                                {{ c.descripcion }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">
                                                {{ c.codigo_padre ? `Padre ${c.codigo_padre}` : 'Nivel raíz' }}
                                                <template v-if="c.codigo_alias"> · Alias {{ c.codigo_alias }}</template>
                                            </p>
                                        </div>
                                        <span v-if="!c.activo" class="text-xs font-bold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">
                                            Inactivo
                                        </span>
                                        <span v-if="c.es_terminal" class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700">
                                            Terminal
                                        </span>
                                        <button @click="verHistorial(c)" title="Historial"
                                            class="cursor-pointer p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-all">
                                            <History class="w-4 h-4" />
                                        </button>
                                        <button @click="startEdit(c)" title="Editar"
                                            class="cursor-pointer p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                            <Pencil class="w-4 h-4" />
                                        </button>
                                        <button @click="toggleActivo(c)" :title="c.activo ? 'Desactivar' : 'Activar'"
                                            class="cursor-pointer p-2 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 transition-all">
                                            <Power class="w-4 h-4" />
                                        </button>
                                        <button @click="remove(c)" title="Eliminar"
                                            class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </template>

                    <!-- REGLAS -->
                    <template v-else-if="tab === 'reglas'">
                        <div class="rounded-2xl border-2 border-violet-100 bg-violet-50/40 p-4 space-y-3">
                            <p class="text-xs font-bold uppercase tracking-widest text-violet-500">Nueva regla de clasificación</p>
                            <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
                                <input v-model="nuevaRegla.concepto_codigo" type="text" placeholder="Código concepto *"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm font-mono text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 outline-none" />
                                <input v-model="nuevaRegla.regimen" type="text" placeholder="Régimen (CAS)"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 outline-none uppercase" />
                                <select v-model="nuevaRegla.modalidad"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 outline-none">
                                    <option value="">Cualquier modalidad</option>
                                    <option value="INDETERMINADO">Indeterminado</option>
                                    <option value="TRANSITORIO">Transitorio</option>
                                </select>
                                <select v-model="nuevaRegla.clasificador_id"
                                    class="px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 outline-none">
                                    <option value="">Clasificador terminal * ({{ terminales.length }})</option>
                                    <option v-for="t in terminales" :key="t.id" :value="t.id">
                                        {{ t.codigo }} — {{ t.descripcion }}
                                    </option>
                                </select>
                                <button @click="agregarRegla" :disabled="saving"
                                    class="cursor-pointer inline-flex items-center justify-center px-4 py-2 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 disabled:opacity-50 transition-all">
                                    <Plus class="w-4 h-4 mr-1" />
                                    Crear regla
                                </button>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <div v-if="loading" class="py-12 text-center">
                                <Loader2 class="w-6 h-6 text-violet-500 animate-spin mx-auto" />
                            </div>
                            <p v-else-if="reglas.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                                No hay reglas para el ejercicio {{ filtros.anio }}. Sin regla, los conceptos quedan en PENDIENTE.
                            </p>
                            <ul v-else class="divide-y divide-slate-100">
                                <li v-for="r in reglas" :key="r.id" class="px-4 py-3 hover:bg-slate-50/70 flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">
                                            <span class="font-mono text-violet-600 mr-2">{{ r.concepto_codigo }}</span>
                                            → <span class="font-mono text-indigo-600 mr-1">{{ r.codigo || '—' }}</span>
                                            <span class="text-slate-500 font-normal">{{ r.clasificador_descripcion }}</span>
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            {{ r.regimen || 'Cualquier régimen' }}
                                            · {{ r.modalidad || 'Cualquier modalidad' }}
                                            · Prioridad {{ r.prioridad }}
                                        </p>
                                    </div>
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                        :class="r.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                        {{ r.activo ? 'Activa' : 'Inactiva' }}
                                    </span>
                                    <button @click="toggleRegla(r)" :title="r.activo ? 'Desactivar' : 'Activar'"
                                        class="cursor-pointer p-2 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 transition-all">
                                        <Power class="w-4 h-4" />
                                    </button>
                                    <button @click="removeRegla(r)" title="Eliminar"
                                        class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </template>

                    <!-- HISTORIAL -->
                    <template v-else>
                        <div class="flex flex-col sm:flex-row gap-3 items-end">
                            <div class="flex-1 w-full">
                                <label class="block text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Clasificador</label>
                                <select v-model="historialId" @change="cargarHistorial"
                                    class="w-full px-3 py-2 border-2 border-slate-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none">
                                    <option value="">Seleccione un clasificador...</option>
                                    <option v-for="c in clasificadores" :key="c.id" :value="c.id">
                                        {{ c.codigo }} — {{ c.descripcion }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 overflow-hidden">
                            <div v-if="loading" class="py-12 text-center">
                                <Loader2 class="w-6 h-6 text-amber-500 animate-spin mx-auto" />
                            </div>
                            <p v-else-if="!historialId" class="py-10 text-center text-slate-500 font-medium text-sm">
                                Seleccione un clasificador para ver su historial de cambios.
                            </p>
                            <p v-else-if="historial.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                                Este clasificador no tiene cambios registrados.
                            </p>
                            <ul v-else class="divide-y divide-slate-100">
                                <li v-for="h in historial" :key="h.id" class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-slate-800">
                                                <span class="uppercase text-amber-600">{{ h.campo }}</span>
                                            </p>
                                            <p class="text-xs text-slate-500 mt-0.5 break-words">
                                                <span class="line-through text-slate-400">{{ h.valor_anterior ?? '—' }}</span>
                                                →
                                                <span class="font-semibold text-slate-700">{{ h.valor_nuevo ?? '—' }}</span>
                                            </p>
                                            <p v-if="h.fuente" class="text-[11px] text-slate-400 mt-0.5 truncate">{{ h.fuente }}</p>
                                        </div>
                                        <span class="text-xs text-slate-400 whitespace-nowrap">{{ formatFecha(h.fecha_cambio) }}</span>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
    Network, X, Plus, Pencil, Trash2, Check, Power, Loader2, History, Search,
} from 'lucide-vue-next';
import { usePlanillaMefClasificador, MEF_CODIGO_REGEX } from '@/Composables/usePlanillaMefClasificador';

const emit = defineEmits(['close', 'changed']);

const tabs = [
    { id: 'catalogo', label: 'Catálogo', icono: Network },
    { id: 'reglas', label: 'Reglas', icono: Check },
    { id: 'historial', label: 'Historial', icono: History },
];

const {
    clasificadores, reglas, historial, loading, saving,
    fetchClasificadores, fetchReglas, fetchHistorial,
    crearClasificador, actualizarClasificador, eliminarClasificador,
    crearRegla, actualizarRegla, eliminarRegla,
} = usePlanillaMefClasificador();

const tab = ref('catalogo');
const filtros = ref({ anio: 2026, search: '', estado: '' });
const nuevo = ref({ codigo: '', codigo_padre: '', codigo_alias: '', nivel: null, descripcion: '', es_terminal: true });
const nuevaRegla = ref({ concepto_codigo: '', regimen: 'CAS', modalidad: '', clasificador_id: '' });
const editandoId = ref(null);
const edit = ref({ codigo_padre: '', codigo_alias: '', nivel: null, descripcion: '', es_terminal: false });
const historialId = ref('');

const terminales = computed(() => clasificadores.value.filter((c) => c.es_terminal && c.activo));

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

const params = () => {
    const salida = { anio: Number(filtros.value.anio) || 2026 };
    if (filtros.value.search.trim()) salida.search = filtros.value.search.trim();
    if (filtros.value.estado) salida.estado = filtros.value.estado;
    return salida;
};

const recargar = async () => {
    await fetchClasificadores(params());
    await fetchReglas(Number(filtros.value.anio) || 2026);
};

const mensajeError = (error) =>
    error.response?.data?.message
    || Object.values(error.response?.data?.errors || {})[0]?.[0]
    || 'No se pudo completar la operación';

const agregar = async () => {
    if (!nuevo.value.codigo.trim() || !nuevo.value.descripcion.trim()) {
        notify('warning', 'Ingrese código y descripción');
        return;
    }
    if (!MEF_CODIGO_REGEX.test(nuevo.value.codigo.trim())) {
        notify('warning', 'El código debe tener formato 2.1.1 (dígitos y puntos)');
        return;
    }
    try {
        await crearClasificador({
            anio: Number(filtros.value.anio) || 2026,
            codigo: nuevo.value.codigo.trim(),
            codigo_alias: nuevo.value.codigo_alias.trim() || null,
            codigo_padre: nuevo.value.codigo_padre.trim() || null,
            nivel: nuevo.value.nivel || null,
            descripcion: nuevo.value.descripcion.trim(),
            es_terminal: nuevo.value.es_terminal,
            activo: true,
        });
        nuevo.value = { codigo: '', codigo_padre: '', codigo_alias: '', nivel: null, descripcion: '', es_terminal: true };
        notify('success', 'Clasificador registrado correctamente');
        emit('changed');
        await recargar();
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const startEdit = (clasificador) => {
    editandoId.value = clasificador.id;
    edit.value = {
        codigo_padre: clasificador.codigo_padre || '',
        codigo_alias: clasificador.codigo_alias || '',
        nivel: clasificador.nivel,
        descripcion: clasificador.descripcion,
        es_terminal: clasificador.es_terminal,
    };
};

const cancelarEdicion = () => {
    editandoId.value = null;
};

const guardarEdicion = async (clasificador) => {
    if (!edit.value.descripcion.trim()) {
        notify('warning', 'La descripción es obligatoria');
        return;
    }
    try {
        await actualizarClasificador(clasificador.id, {
            anio: clasificador.anio,
            codigo: clasificador.codigo,
            codigo_alias: edit.value.codigo_alias.trim() || null,
            codigo_padre: edit.value.codigo_padre.trim() || null,
            nivel: edit.value.nivel || null,
            descripcion: edit.value.descripcion.trim(),
            es_terminal: edit.value.es_terminal,
            activo: clasificador.activo,
        });
        cancelarEdicion();
        notify('success', 'Clasificador actualizado correctamente');
        emit('changed');
        await recargar();
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const toggleActivo = async (clasificador) => {
    try {
        await actualizarClasificador(clasificador.id, {
            anio: clasificador.anio,
            codigo: clasificador.codigo,
            codigo_alias: clasificador.codigo_alias,
            codigo_padre: clasificador.codigo_padre,
            nivel: clasificador.nivel,
            descripcion: clasificador.descripcion,
            es_terminal: clasificador.es_terminal,
            activo: !clasificador.activo,
        });
        emit('changed');
        await recargar();
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const remove = async (clasificador) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar clasificador?',
        html: `<p><strong>${clasificador.codigo}</strong> — ${clasificador.descripcion}</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarClasificador(clasificador.id);
        notify('success', 'Clasificador eliminado');
        emit('changed');
        await recargar();
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const agregarRegla = async () => {
    if (!nuevaRegla.value.concepto_codigo.trim() || !nuevaRegla.value.clasificador_id) {
        notify('warning', 'Ingrese el código de concepto y seleccione un clasificador');
        return;
    }
    try {
        await crearRegla({
            anio: Number(filtros.value.anio) || 2026,
            regimen: nuevaRegla.value.regimen.trim() || null,
            modalidad: nuevaRegla.value.modalidad || null,
            concepto_codigo: nuevaRegla.value.concepto_codigo.trim(),
            clasificador_id: nuevaRegla.value.clasificador_id,
        });
        nuevaRegla.value = { concepto_codigo: '', regimen: 'CAS', modalidad: '', clasificador_id: '' };
        notify('success', 'Regla creada correctamente');
        emit('changed');
        await fetchReglas(Number(filtros.value.anio) || 2026);
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const toggleRegla = async (regla) => {
    try {
        await actualizarRegla(regla.id, {
            anio: regla.anio,
            regimen: regla.regimen,
            modalidad: regla.modalidad,
            concepto_codigo: regla.concepto_codigo,
            clasificador_id: regla.clasificador_id,
            prioridad: regla.prioridad,
            activo: !regla.activo,
        });
        emit('changed');
        await fetchReglas(Number(filtros.value.anio) || 2026);
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const removeRegla = async (regla) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar regla?',
        html: `<p><strong>${regla.concepto_codigo}</strong> → ${regla.codigo || ''}</p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarRegla(regla.id);
        notify('success', 'Regla eliminada');
        emit('changed');
        await fetchReglas(Number(filtros.value.anio) || 2026);
    } catch (error) {
        notify('error', mensajeError(error));
    }
};

const cargarHistorial = async () => {
    if (!historialId.value) {
        historial.value = [];
        return;
    }
    await fetchHistorial(historialId.value);
};

const verHistorial = async (clasificador) => {
    historialId.value = clasificador.id;
    tab.value = 'historial';
    await fetchHistorial(clasificador.id);
};

const formatFecha = (valor) => {
    if (!valor) return '—';
    return new Date(valor).toLocaleString('es-PE', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
    });
};

onMounted(recargar);
</script>
