<template>
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Landmark class="w-6 h-6" />
                            Gestión de Bancos
                        </h3>
                        <p class="text-sky-50 text-sm mt-1">Catálogo de bancos para la cuenta de abono</p>
                    </div>
                    <button @click="$emit('close')" class="text-sky-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <!-- Alta -->
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input v-model="nuevoNombre" type="text" placeholder="Nombre del banco"
                            class="flex-1 px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none" />
                        <input v-model="nuevoCodigo" type="text" placeholder="Código"
                            class="w-full sm:w-28 px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all outline-none uppercase" />
                        <button @click="agregar" :disabled="saving"
                            class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-700 hover:to-cyan-700 disabled:opacity-50 transition-all">
                            <Plus class="w-4 h-4 mr-1" />
                            Agregar
                        </button>
                    </div>

                    <!-- Listado -->
                    <div class="rounded-2xl border border-slate-200 overflow-hidden">
                        <div v-if="loading" class="py-12 text-center">
                            <Loader2 class="w-6 h-6 text-sky-500 animate-spin mx-auto" />
                        </div>
                        <p v-else-if="bancos.length === 0" class="py-10 text-center text-slate-500 font-medium text-sm">
                            No hay bancos registrados.
                        </p>
                        <ul v-else class="divide-y divide-slate-100">
                            <li v-for="banco in bancos" :key="banco.id" class="px-4 py-3 hover:bg-slate-50/70">
                                <div v-if="editingId === banco.id" class="flex flex-col sm:flex-row gap-2">
                                    <input v-model="editNombre" type="text"
                                        class="flex-1 px-3 py-2 border-2 border-sky-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 outline-none" />
                                    <input v-model="editCodigo" type="text" placeholder="Cód."
                                        class="w-full sm:w-24 px-3 py-2 border-2 border-sky-200 rounded-xl text-sm text-slate-900 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 outline-none uppercase" />
                                    <div class="flex gap-1">
                                        <button @click="saveEdit(banco)" :disabled="saving"
                                            class="cursor-pointer p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                                            <Check class="w-4 h-4" />
                                        </button>
                                        <button @click="cancelEdit"
                                            class="cursor-pointer p-2 rounded-lg bg-slate-100 text-slate-500 hover:bg-slate-200">
                                            <X class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                                <div v-else class="flex items-center gap-3">
                                    <div class="flex-1 min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ banco.nombre }}</p>
                                        <p class="text-xs text-slate-400">{{ banco.codigo || 'Sin código' }}</p>
                                    </div>
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full"
                                        :class="banco.activo ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                        {{ banco.activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                    <button @click="toggleActivo(banco)" :title="banco.activo ? 'Desactivar' : 'Activar'"
                                        class="cursor-pointer p-2 rounded-lg bg-slate-50 text-slate-500 hover:bg-slate-100 transition-all">
                                        <Power class="w-4 h-4" />
                                    </button>
                                    <button @click="startEdit(banco)" title="Editar"
                                        class="cursor-pointer p-2 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="remove(banco)" title="Eliminar"
                                        class="cursor-pointer p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Landmark, X, Plus, Pencil, Trash2, Check, Power, Loader2 } from 'lucide-vue-next';
import { usePlanillaBancos } from '@/Composables/usePlanillaBancos';

const emit = defineEmits(['close', 'changed']);

const { bancos, loading, saving, fetchBancos, crearBanco, actualizarBanco, eliminarBanco } = usePlanillaBancos();

const nuevoNombre = ref('');
const nuevoCodigo = ref('');
const editingId = ref(null);
const editNombre = ref('');
const editCodigo = ref('');

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

const agregar = async () => {
    if (!nuevoNombre.value.trim()) {
        notify('warning', 'Ingrese el nombre del banco');
        return;
    }
    try {
        await crearBanco({
            nombre: nuevoNombre.value.trim(),
            codigo: nuevoCodigo.value.trim() || null,
            activo: true,
        });
        nuevoNombre.value = '';
        nuevoCodigo.value = '';
        notify('success', 'Banco registrado correctamente');
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo registrar el banco');
    }
};

const startEdit = (banco) => {
    editingId.value = banco.id;
    editNombre.value = banco.nombre;
    editCodigo.value = banco.codigo || '';
};

const cancelEdit = () => {
    editingId.value = null;
};

const saveEdit = async (banco) => {
    if (!editNombre.value.trim()) {
        notify('warning', 'Ingrese el nombre del banco');
        return;
    }
    try {
        await actualizarBanco(banco.id, {
            nombre: editNombre.value.trim(),
            codigo: editCodigo.value.trim() || null,
            activo: banco.activo,
        });
        cancelEdit();
        notify('success', 'Banco actualizado correctamente');
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el banco');
    }
};

const toggleActivo = async (banco) => {
    try {
        await actualizarBanco(banco.id, {
            nombre: banco.nombre,
            codigo: banco.codigo,
            activo: !banco.activo,
        });
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo actualizar el banco');
    }
};

const remove = async (banco) => {
    const result = await window.Swal?.fire({
        icon: 'warning',
        title: '¿Eliminar banco?',
        html: `<p><strong>${banco.nombre}</strong></p>`,
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#e11d48',
    });

    if (!result?.isConfirmed) return;

    try {
        await eliminarBanco(banco.id);
        notify('success', 'Banco eliminado correctamente');
        emit('changed');
    } catch (error) {
        notify('error', error.response?.data?.message || 'No se pudo eliminar el banco');
    }
};

onMounted(fetchBancos);
</script>
