<template>
    <MainLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- Header -->
            <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Gestión de Citas</h1>
                    <p class="mt-2 text-sm text-slate-700">Administra las reservas de citas realizadas por usuarios
                        externos.</p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center gap-3">
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <div class="flex items-center gap-1.5">
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-xs font-medium">Actualización automática</span>
                        </div>
                    </div>
                    <button @click="fetchCitas(true)"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 shadow-lg shadow-pink-500/30">
                        <RefreshCw class="h-5 w-5 mr-2" />
                        Actualizar
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div v-if="loading && citas.length === 0"
                class="bg-white rounded-xl shadow-md border border-gray-200 px-6 py-24 text-center">
                <div class="flex flex-col items-center justify-center">
                    <LoaderCircle class="animate-spin h-12 w-12 text-pink-600 mb-4" />
                    <p class="text-lg font-medium text-slate-600">Cargando citas...</p>
                </div>
            </div>

            <!-- Content with Tabs -->
            <div v-else>
                <!-- Tabs Navigation -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8">
                        <button @click="activeTab = 'pending'"
                            :class="[activeTab === 'pending'
                                ? 'border-pink-500 text-pink-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors']">
                            <Clock class="w-5 h-5" />
                            Pendientes
                            <span v-if="pendingCitas.length > 0"
                                class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-gradient-to-r from-pink-500 to-rose-500 rounded-full">
                                {{ pendingCitas.length }}
                            </span>
                        </button>
                        <button @click="activeTab = 'completed'"
                            :class="[activeTab === 'completed'
                                ? 'border-rose-500 text-rose-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300',
                                'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors']">
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
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
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
