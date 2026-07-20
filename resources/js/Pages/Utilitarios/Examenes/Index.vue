<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <Link href="/utilitarios" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-amber-600 mb-2">
                        <ArrowLeft class="w-4 h-4" /> Volver a Utilitarios
                    </Link>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                        {{ evento.titulo }}
                    </h1>
                    <p class="mt-2 text-slate-600">{{ examenesList.length }} examen(es)</p>
                </div>
            </div>

            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <div class="p-5 sm:p-6 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Exámenes de alternativas</h2>
                        <p class="text-sm text-slate-500 font-medium mt-1">Programe exámenes con duración y comparta el enlace con los inscritos</p>
                    </div>
                    <button @click="crearExamen"
                        class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-purple-500/30 text-white bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-700 hover:to-fuchsia-700 transition-all duration-300 hover:-translate-y-0.5">
                        <Plus class="w-4 h-4 mr-2" />
                        Nuevo Examen
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase">Examen</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Preguntas</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Duración</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Intentos</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Estado</th>
                                <th class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase">Enlace</th>
                                <th class="px-6 py-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="examen in examenesList" :key="examen.id" class="hover:bg-purple-50 transition-colors">
                                <td class="px-6 py-4 max-w-xs">
                                    <p class="font-semibold text-slate-900 truncate">{{ examen.titulo }}</p>
                                    <p class="text-xs text-slate-500">{{ formatFecha(examen.fecha_inicio) }}<span v-if="examen.fecha_fin !== examen.fecha_inicio"> al {{ formatFecha(examen.fecha_fin) }}</span></p>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-slate-700">{{ examen.preguntas.length }}</td>
                                <td class="px-6 py-4 text-center text-sm text-slate-700">{{ examen.duracion_minutos }} min</td>
                                <td class="px-6 py-4 text-center text-sm text-slate-700">{{ examen.intentos_permitidos }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <span class="px-3 py-1 text-xs font-bold rounded-lg" :class="estadoClass(examen.estado)">
                                            {{ estadoLabel(examen.estado) }}
                                        </span>
                                        <button v-if="examen.estado === 'borrador'" @click="cambiarEstado(examen, 'publicado')"
                                            title="Publicar examen" class="text-green-600 hover:text-green-800 p-1.5 rounded-lg hover:bg-green-50 transition-colors">
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button v-if="examen.estado === 'publicado'" @click="cambiarEstado(examen, 'cerrado')"
                                            title="Cerrar examen" class="text-orange-600 hover:text-orange-800 p-1.5 rounded-lg hover:bg-orange-50 transition-colors">
                                            <Lock class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button type="button" @click="toggleHabilitado(examen)"
                                        class="cursor-pointer relative inline-flex h-6 w-11 flex-shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                        :class="examen.examen_habilitado ? 'bg-emerald-500' : 'bg-slate-300'"
                                        :title="examen.examen_habilitado ? 'Activo' : 'Inactivo'">
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                            :class="examen.examen_habilitado ? 'translate-x-5' : 'translate-x-0'" />
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button @click="copiarEnlace(examen)" title="Copiar enlace del examen"
                                        class="text-amber-600 hover:text-amber-800 p-2 rounded-lg hover:bg-amber-50 transition-colors">
                                        <Copy class="w-5 h-5" />
                                    </button>
                                    <button @click="verResultados(examen)" title="Ver resultados"
                                        class="text-emerald-600 hover:text-emerald-800 p-2 rounded-lg hover:bg-emerald-50 transition-colors">
                                        <BarChart3 class="w-5 h-5" />
                                    </button>
                                    <button @click="editarExamen(examen)" title="Editar examen"
                                        class="text-blue-600 hover:text-blue-800 p-2 rounded-lg hover:bg-blue-50 transition-colors">
                                        <Pencil class="w-5 h-5" />
                                    </button>
                                    <button @click="eliminarExamen(examen)" title="Eliminar examen"
                                        class="text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors">
                                        <Trash2 class="w-5 h-5" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="examenesList.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center">
                                        <ClipboardX class="w-16 h-16 text-slate-300 mb-4" />
                                        <p>No hay exámenes registrados para este evento.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <ExamenModal v-if="showModal" :examen="selectedExamen" :is-editing="isEditing" :submitting="isSubmitting"
                @close="closeModal" @submit="guardarExamen" />
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';

export default {
    layout: MainLayout
}
</script>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ArrowLeft, Plus, Pencil, Trash2, Copy, BarChart3, CheckCircle, Lock, ClipboardX } from 'lucide-vue-next';
import ExamenModal from '@/Components/Utilitarios/Examenes/ExamenModal.vue';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    examenes: {
        type: Array,
        default: () => []
    }
});

const examenesList = ref([...props.examenes]);

const showModal = ref(false);
const selectedExamen = ref(null);
const isEditing = ref(false);
const isSubmitting = ref(false);

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
};

const ESTADO_LABELS = {
    borrador: 'Borrador',
    publicado: 'Publicado',
    cerrado: 'Cerrado',
};

const estadoLabel = (estado) => ESTADO_LABELS[estado] || estado;

const estadoClass = (estado) => ({
    'bg-slate-100 text-slate-600': estado === 'borrador',
    'bg-green-100 text-green-700': estado === 'publicado',
    'bg-red-100 text-red-700': estado === 'cerrado',
});

const crearExamen = () => {
    isEditing.value = false;
    selectedExamen.value = null;
    showModal.value = true;
};

const editarExamen = (examen) => {
    isEditing.value = true;
    selectedExamen.value = examen;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedExamen.value = null;
    isEditing.value = false;
};

const guardarExamen = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value && selectedExamen.value) {
            await axios.put(`/utilitarios/eventos/${props.evento.id}/examenes/${selectedExamen.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Examen actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        } else {
            await axios.post(`/utilitarios/eventos/${props.evento.id}/examenes`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Examen registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
        closeModal();
        router.reload({ only: ['examenes'] });
    } catch (error) {
        window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo guardar el examen.', 'error');
    } finally {
        isSubmitting.value = false;
    }
};

const eliminarExamen = (examen) => {
    window.Swal?.fire?.({
        title: '¿Eliminar examen?',
        text: `Se eliminará "${examen.titulo}". Esta acción no se puede deshacer.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/utilitarios/eventos/${props.evento.id}/examenes/${examen.id}`);
                examenesList.value = examenesList.value.filter((e) => e.id !== examen.id);
                window.Swal?.fire?.({ icon: 'success', title: 'Examen eliminado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar el examen.', 'error');
            }
        }
    });
};

const cambiarEstado = async (examen, nuevoEstado) => {
    try {
        await axios.patch(`/utilitarios/eventos/${props.evento.id}/examenes/${examen.id}/estado`, { estado: nuevoEstado });
        examen.estado = nuevoEstado;
        window.Swal?.fire?.({ icon: 'success', title: 'Estado actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo actualizar el estado.', 'error');
    }
};

const toggleHabilitado = async (examen) => {
    try {
        const { data } = await axios.patch(`/utilitarios/eventos/${props.evento.id}/examenes/${examen.id}/habilitado`, {
            habilitado: !examen.examen_habilitado,
        });
        examen.examen_habilitado = data.examen_habilitado;
        window.Swal?.fire?.({ icon: 'success', title: data.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo actualizar el enlace del examen.', 'error');
    }
};

const copiarEnlace = async (examen) => {
    try {
        await navigator.clipboard.writeText(examen.enlace_publico);
        window.Swal?.fire?.({ icon: 'success', title: 'Enlace copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo copiar el enlace.', 'error');
    }
};

const verResultados = (examen) => {
    router.visit(`/utilitarios/eventos/${props.evento.id}/examenes/${examen.id}/resultados`);
};
</script>
