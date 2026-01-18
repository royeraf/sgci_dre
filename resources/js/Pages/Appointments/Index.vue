<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent tracking-tight">
                        Gestión de Citas
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Administra las reservas de citas realizadas por usuarios externos.
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Left Group: Navigation -->
                    <div class="flex items-center gap-2">
                        <a href="/dashboard"
                            class="inline-flex items-center justify-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Volver
                        </a>
                        <a href="/citas/portal" target="_blank"
                            class="inline-flex items-center justify-center px-4 py-2.5 border border-blue-200 text-sm font-bold rounded-xl text-blue-600 bg-blue-50 hover:bg-blue-100 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                            </svg>
                            Portal Público
                            <svg class="w-3 h-3 ml-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>

                    <!-- Right Group: Status & Action -->
                    <div class="flex items-center gap-2.5">
                        <!-- Live Indicator -->
                        <div class="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 rounded-xl">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-xs font-bold uppercase tracking-wider text-green-700">En vivo</span>
                        </div>

                        <!-- Refresh Button -->
                        <button @click="fetchCitas(true)"
                            class="inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-pink-600/20 text-white bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 focus:outline-none focus:ring-4 focus:ring-pink-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                            <RefreshCw class="h-4 w-4 mr-2" :class="{ 'animate-spin': loading }" />
                            Actualizar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading && citas.length === 0"
                class="bg-white rounded-2xl shadow-xl border border-slate-200 px-6 py-24 text-center">
                <div class="flex flex-col items-center justify-center">
                    <LoaderCircle class="animate-spin h-12 w-12 text-pink-600 mb-4" />
                    <p class="text-lg font-bold text-slate-600">Cargando citas...</p>
                </div>
            </div>

            <!-- Content with Tabs -->
            <div v-else>
                <!-- Tabs Navigation -->
                <div class="border-b border-slate-200 mb-8">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'pending'"
                            :class="[activeTab === 'pending'
                                ? 'border-pink-600 text-pink-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                            <Clock class="w-5 h-5" />
                            Pendientes
                            <span v-if="pendingCitas.length > 0"
                                class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-black leading-none text-white bg-gradient-to-r from-pink-500 to-rose-500 rounded-full shadow-sm ml-1">
                                {{ pendingCitas.length }}
                            </span>
                        </button>
                        <button @click="activeTab = 'completed'"
                            :class="[activeTab === 'completed'
                                ? 'border-rose-600 text-rose-600'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                            <CheckCircle class="w-5 h-5" />
                            Finalizadas
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div v-show="activeTab === 'pending'">
                    <PendingAppointmentList :citas="pendingCitas" @attend="(id) => updateStatus(id, 'ATENDIDO')"
                        @cancel="(id) => updateStatus(id, 'CANCELADO')" />
                </div>

                <div v-show="activeTab === 'completed'">
                    <CompletedAppointmentList :citas="completedCitas" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup>
import PendingAppointmentList from '@/Components/Appointments/Pending/AppointmentList.vue';
import CompletedAppointmentList from '@/Components/Appointments/Completed/AppointmentList.vue';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import { RefreshCw, LoaderCircle, Clock, CheckCircle } from 'lucide-vue-next';

const citas = ref([]);
const loading = ref(false);
const activeTab = ref('pending');
let pollingInterval = null;

// Computed properties for filtering
const pendingCitas = computed(() =>
    citas.value
        .filter(c => c.estado === 'PENDIENTE')
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
);

const completedCitas = computed(() =>
    citas.value
        .filter(c => c.estado !== 'PENDIENTE')
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at))
);

// Fetch citas from API
const fetchCitas = async (showLoading = true) => {
    if (showLoading) {
        loading.value = true;
    }
    try {
        const response = await axios.get('/citas/list');
        const newCitas = response.data;

        const oldPendingCount = citas.value.filter(c => c.estado === 'PENDIENTE').length;
        const newPendingCount = newCitas.filter(c => c.estado === 'PENDIENTE').length;

        if (!showLoading && newPendingCount > oldPendingCount) {
            Swal.fire({
                icon: 'info',
                title: 'Nueva cita registrada',
                text: `Se ha registrado ${newPendingCount - oldPendingCount} nueva(s) cita(s) pendiente(s)`,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        }

        citas.value = newCitas;
    } catch (error) {
        console.error("Error fetching citas", error);
        if (error.response?.status === 401) {
            stopPolling();
            Swal.fire({
                icon: 'warning',
                title: 'Sesión expirada',
                text: 'Por favor refresque la página o inicie sesión nuevamente'
            });
        }
    } finally {
        if (showLoading) {
            loading.value = false;
        }
    }
};

// Polling functions
const startPolling = () => {
    pollingInterval = setInterval(() => {
        fetchCitas(false);
    }, 10000);
};

const stopPolling = () => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
};

// Update status handler
const updateStatus = async (id, status) => {
    const actionText = status === 'ATENDIDO' ? 'atender' : 'cancelar';
    const confirmButtonColor = status === 'ATENDIDO' ? '#16a34a' : '#dc2626';

    const { value: observacion, isConfirmed } = await Swal.fire({
        title: `¿Confirmar ${status}?`,
        text: `Escriba una breve observación para ${actionText} esta cita:`,
        input: 'textarea',
        inputPlaceholder: 'Ingrese el motivo u observación aquí...',
        inputAttributes: {
            'aria-label': 'Ingrese su observación'
        },
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Volver',
        confirmButtonColor: confirmButtonColor,
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return status === 'CANCELADO'
                    ? 'Es obligatorio indicar un motivo para la cancelación'
                    : 'Es obligatorio indicar una observación para la atención';
            }
        }
    });

    if (!isConfirmed) return;

    try {
        await axios.put(`/citas/${id}/status`, {
            status,
            observacion: observacion || ''
        });
        await fetchCitas();
        Swal.fire({
            icon: 'success',
            title: 'Estado Actualizado',
            text: `La cita ha sido marcada como ${status.toLowerCase()}`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No se pudo actualizar el estado de la cita'
        });
    }
};

onMounted(() => {
    fetchCitas();
    startPolling();
});

onUnmounted(() => {
    stopPolling();
});
</script>
