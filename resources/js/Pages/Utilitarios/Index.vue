<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 class="text-3xl font-bold bg-gradient-to-r from-amber-600 to-orange-600 bg-clip-text text-transparent">
                        Utilitarios
                    </h1>
                    <p class="mt-2 text-slate-600">Inscripción y asistencia a cursos, seminarios y capacitaciones</p>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button v-if="canViewTab('eventos')"
                        @click="activeTab = 'eventos'"
                        :class="[
                            activeTab === 'eventos'
                                ? 'text-amber-600 active-tab'
                                : 'text-slate-500 hover:text-slate-700',
                            'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                        ]"
                    >
                        <ClipboardList class="w-5 h-5" />
                        Eventos y Capacitaciones
                    </button>
                    <button v-if="canViewTab('reportes')"
                        @click="activeTab = 'reportes'"
                        :class="[
                            activeTab === 'reportes'
                                ? 'text-orange-600 active-tab'
                                : 'text-slate-500 hover:text-slate-700',
                            'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                        ]"
                    >
                        <FileText class="w-5 h-5" />
                        Reportes
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content with Transition -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">

            <!-- Eventos Tab Content -->
            <div v-if="activeTab === 'eventos'">
                <BaseTableCard
                    title="Eventos y Capacitaciones"
                    description="Cree eventos y comparta el enlace de inscripción con los participantes"
                    search-placeholder="Buscar por título..."
                    v-model:searchValue="search"
                >
                    <template #actions>
                        <button @click="filtersVisible = !filtersVisible"
                            class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                            <SlidersHorizontal class="w-4 h-4" />
                            Filtros
                            <ChevronDown class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': filtersVisible }" />
                        </button>

                        <button @click="createNewEvento"
                            class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-amber-500/30 text-white bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 transition-all duration-300 hover:-translate-y-0.5">
                            <Plus class="w-4 h-4 mr-2" />
                            Nuevo Evento
                        </button>
                    </template>

                    <div class="filters-collapse bg-slate-50 border-b border-slate-100" :class="{ 'filters-collapse--open': filtersVisible }">
                        <div class="p-4 sm:p-5">
                            <EventoFiltros :filters="localFilters" :result-count="filteredEventos.length"
                                @update:filters="localFilters = $event" @clear="clearFilters" />
                        </div>
                    </div>

                    <EventoTable :eventos="filteredEventos" :loading="isLoading" v-model:currentPage="currentPage"
                        v-model:perPage="perPage" @edit="editEvento" @delete="handleDeleteEvento"
                        @view-detalle="verDetalle" @view-inscritos="verInscritos" @view-examenes="verExamenes"
                        @change-estado="cambiarEstado" />
                </BaseTableCard>
            </div>

                    <!-- Reportes Tab Content -->
                    <div v-else-if="activeTab === 'reportes'">
                        <UtilitariosReports :eventos="eventos" />
                    </div>
                </div>
            </Transition>

            <EventoModal v-if="showEventoModal" :evento="selectedEvento" :is-editing="isEditing"
                :submitting="isSubmitting" @close="closeEventoModal" @submit="saveEvento" />

            <EventoDetalleModal v-if="showDetalleModal" :evento="detalleEvento"
                @close="showDetalleModal = false" @copy-link="copiarEnlace" @copy-asistencia-link="copiarEnlaceAsistencia"
                @toggle-asistencia-habilitada="toggleAsistenciaHabilitada" @copy-examen-link="copiarEnlaceExamen" />
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
import { ref, onMounted, onUnmounted, computed, watch, nextTick } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { Plus, SlidersHorizontal, ChevronDown, FileText, ClipboardList } from 'lucide-vue-next';

import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import EventoTable from '@/Components/Utilitarios/Eventos/EventoTable.vue';
import EventoModal from '@/Components/Utilitarios/Eventos/EventoModal.vue';
import EventoDetalleModal from '@/Components/Utilitarios/Eventos/EventoDetalleModal.vue';
import EventoFiltros from '@/Components/Utilitarios/Eventos/EventoFiltros.vue';
import UtilitariosReports from '@/Components/Utilitarios/Reports/UtilitariosReports.vue';
import { useTabPermission } from '@/composables/useTabPermission';

const { canViewTab, firstAllowedTab } = useTabPermission('utilitarios', ['eventos', 'reportes']);
const activeTab = ref(firstAllowedTab.value);

// Tab indicator logic
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    switch (tab) {
        case 'eventos': return '#d97706'; // amber-600
        case 'reportes': return '#ea580c'; // orange-600
        default: return '#d97706';
    }
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const activeBtn = tabsRef.value.querySelector('.active-tab');
    if (activeBtn) {
        indicatorStyle.value = {
            left: `${activeBtn.offsetLeft}px`,
            width: `${activeBtn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value)
        };
    }
};

watch(activeTab, () => {
    nextTick(updateIndicator);
});

const isLoading = ref(false);
const isSubmitting = ref(false);
const eventos = ref([]);
const search = ref('');

const currentPage = ref(1);
const perPage = ref(10);

const filtersVisible = ref(false);
const localFilters = ref({
    tipo: '',
    modalidad: '',
    estado: '',
});

watch([localFilters, search], () => {
    currentPage.value = 1;
}, { deep: true });

const clearFilters = () => {
    localFilters.value = { tipo: '', modalidad: '', estado: '' };
};

const normalizeText = (text) => {
    if (!text) return '';
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
};

const filteredEventos = computed(() => {
    return eventos.value.filter((evento) => {
        if (search.value && !normalizeText(evento.titulo).includes(normalizeText(search.value))) return false;
        if (localFilters.value.tipo && evento.tipo !== localFilters.value.tipo) return false;
        if (localFilters.value.modalidad && evento.modalidad !== localFilters.value.modalidad) return false;
        if (localFilters.value.estado && evento.estado !== localFilters.value.estado) return false;
        return true;
    });
});

const fetchEventos = async () => {
    isLoading.value = true;
    try {
        const { data } = await axios.get('/utilitarios/eventos');
        eventos.value = data;
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los eventos' });
    } finally {
        isLoading.value = false;
    }
};

const showEventoModal = ref(false);
const selectedEvento = ref(null);
const isEditing = ref(false);

const showDetalleModal = ref(false);
const detalleEvento = ref(null);

const verDetalle = (evento) => {
    detalleEvento.value = evento;
    showDetalleModal.value = true;
};

const createNewEvento = () => {
    isEditing.value = false;
    selectedEvento.value = null;
    showEventoModal.value = true;
};

const editEvento = (evento) => {
    isEditing.value = true;
    selectedEvento.value = evento;
    showEventoModal.value = true;
};

const closeEventoModal = () => {
    showEventoModal.value = false;
    selectedEvento.value = null;
    isEditing.value = false;
};

const buildEventoFormData = (payload) => {
    const data = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
        if (key === 'expositores') {
            data.append('expositores', JSON.stringify(value));
        } else if (key === 'imagen_fondo') {
            if (value instanceof File) data.append('imagen_fondo', value);
        } else if (value !== null && value !== undefined) {
            data.append(key, value);
        }
    });
    return data;
};

const saveEvento = async (formData) => {
    isSubmitting.value = true;
    try {
        const data = buildEventoFormData(formData);
        if (isEditing.value && selectedEvento.value) {
            data.append('_method', 'PUT');
            await axios.post(`/utilitarios/eventos/${selectedEvento.value.id}`, data);
            window.Swal?.fire?.({ icon: 'success', title: 'Evento Actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        } else {
            await axios.post('/utilitarios/eventos', data);
            window.Swal?.fire?.({ icon: 'success', title: 'Evento Registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
        closeEventoModal();
        fetchEventos();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar el evento' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteEvento = (evento) => {
    window.Swal?.fire?.({
        title: '¿Eliminar este evento?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/utilitarios/eventos/${evento.id}`);
                window.Swal?.fire?.('Eliminado', 'El evento ha sido eliminado.', 'success');
                fetchEventos();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar el evento.', 'error');
            }
        }
    });
};

const cambiarEstado = (evento, nuevoEstado) => {
    const mensajes = {
        publicado: { title: '¿Publicar este evento?', text: 'El enlace de inscripción quedará disponible para el público.' },
        cerrado: { title: '¿Cerrar inscripciones?', text: 'Ya no se aceptarán nuevas inscripciones para este evento.' },
    };
    const msg = mensajes[nuevoEstado] || { title: '¿Confirmar cambio de estado?', text: '' };

    window.Swal?.fire?.({
        title: msg.title,
        text: msg.text,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.patch(`/utilitarios/eventos/${evento.id}/estado`, { estado: nuevoEstado });
                window.Swal?.fire?.({ icon: 'success', title: 'Estado actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
                fetchEventos();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo actualizar el estado.', 'error');
            }
        }
    });
};

const copiarEnlace = async (evento) => {
    try {
        await navigator.clipboard.writeText(evento.enlace_publico);
        window.Swal?.fire?.({ icon: 'success', title: 'Enlace copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo copiar el enlace.', 'error');
    }
};

const copiarEnlaceAsistencia = async (evento) => {
    try {
        await navigator.clipboard.writeText(evento.enlace_asistencia);
        window.Swal?.fire?.({ icon: 'success', title: 'Enlace de asistencia copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo copiar el enlace.', 'error');
    }
};

const copiarEnlaceExamen = async (examen) => {
    try {
        await navigator.clipboard.writeText(examen.enlace_publico);
        window.Swal?.fire?.({ icon: 'success', title: 'Enlace del examen copiado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo copiar el enlace.', 'error');
    }
};

const toggleAsistenciaHabilitada = async (evento) => {
    try {
        const { data } = await axios.patch(`/utilitarios/eventos/${evento.id}/asistencia-habilitada`, {
            habilitada: !evento.asistencia_habilitada,
        });
        evento.asistencia_habilitada = data.asistencia_habilitada;
        if (detalleEvento.value?.id === evento.id) {
            detalleEvento.value.asistencia_habilitada = data.asistencia_habilitada;
        }
        window.Swal?.fire?.({ icon: 'success', title: data.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
    } catch (error) {
        window.Swal?.fire?.('Error', 'No se pudo actualizar el enlace de asistencia.', 'error');
    }
};

const verInscritos = (evento) => {
    router.visit(`/utilitarios/eventos/${evento.id}/inscritos`);
};

const verExamenes = (evento) => {
    router.visit(`/utilitarios/eventos/${evento.id}/examenes`);
};

let echoChannel = null;
let pollingInterval = null;

const incrementarContador = (nuevaInscripcion) => {
    const evento = eventos.value.find((e) => e.id === nuevaInscripcion.evento_id);
    if (evento) {
        evento.inscritos_count = (evento.inscritos_count || 0) + 1;
    }
};

onMounted(() => {
    fetchEventos();
    nextTick(updateIndicator);

    if (typeof window.Echo !== 'undefined') {
        try {
            echoChannel = window.Echo.private('inscripciones')
                .listen('.new-inscripcion', (nuevaInscripcion) => {
                    incrementarContador(nuevaInscripcion);
                });
            return;
        } catch (error) {
            console.warn('Echo no disponible, usando polling de respaldo');
        }
    }

    pollingInterval = setInterval(fetchEventos, 20000);
});

onUnmounted(() => {
    if (echoChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave('inscripciones');
    }
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

.filters-collapse {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transition: max-height 0.35s ease, opacity 0.3s ease;
}

.filters-collapse--open {
    max-height: 500px;
    opacity: 1;
}
</style>
