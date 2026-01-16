<template>
    <MainLayout>
        <div class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="md:flex md:items-center md:justify-between mb-8">
                    <div class="flex-1 min-w-0">
                        <h2
                            class="text-3xl font-bold leading-7 text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600 sm:text-4xl">
                            Control Vehicular y Gestión
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">
                            Registro y seguimiento de comisiones, inventario, gastos, actas y servicios.
                        </p>
                    </div>
                    <div class="mt-4 flex md:mt-0 md:ml-4 flex-wrap gap-2">
                        <button v-if="activeTab === 'commissions'" @click="openCommissionModal()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Nueva Comisión
                        </button>
                        <button v-if="activeTab === 'inventory'" @click="openVehicleModal()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Registrar Vehículo
                        </button>
                        <button v-if="activeTab === 'maintenance'" @click="openMaintenanceModal()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Registrar Gasto
                        </button>
                        <button v-if="activeTab === 'handover'" @click="openHandoverModal()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-amber-600 hover:bg-amber-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Nueva Acta
                        </button>
                        <button v-if="activeTab === 'service'" @click="openServiceReqModal()"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-pink-600 hover:bg-pink-700">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Nuevo Requerimiento
                        </button>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="mb-6 border-b border-gray-200 overflow-x-auto">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" @click.prevent="activeTab = 'commissions'"
                            :class="[activeTab === 'commissions' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Comisiones
                        </a>
                        <a href="#" @click.prevent="activeTab = 'inventory'"
                            :class="[activeTab === 'inventory' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Inventario
                        </a>
                        <a href="#" @click.prevent="activeTab = 'maintenance'"
                            :class="[activeTab === 'maintenance' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Gastos
                        </a>
                        <a href="#" @click.prevent="activeTab = 'handover'"
                            :class="[activeTab === 'handover' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Actas de Entrega
                        </a>
                        <a href="#" @click.prevent="activeTab = 'service'"
                            :class="[activeTab === 'service' ? 'border-pink-500 text-pink-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm']">
                            Requerimientos
                        </a>
                    </nav>
                </div>

                <!-- TAB: COMMISSIONS -->
                <div v-show="activeTab === 'commissions'">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
                        <input type="text" v-model="searchCommission"
                            class="block w-full pl-3 sm:text-sm border-slate-300 rounded-md py-2 border max-w-lg"
                            placeholder="Buscar por dependencia, chofer o placa...">
                    </div>
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-slate-200">
                        <div v-if="loadingCommissions" class="px-6 py-24 text-center">
                            <div
                                class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4">
                            </div>
                            <p class="text-lg font-medium text-slate-600">Cargando comisiones...</p>
                        </div>
                        <div v-else-if="filteredCommissions.length === 0" class="text-center py-20">
                            <p class="text-slate-500">No hay comisiones registradas.</p>
                        </div>
                        <ul v-else class="divide-y divide-slate-200">
                            <li v-for="commission in filteredCommissions" :key="commission.id"
                                class="hover:bg-slate-50 transition-colors duration-150 px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-blue-600">{{ commission.dependencia }}</span>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="getStatusClass(commission.estado)">{{ commission.estado }}</span>
                                </div>
                                <div class="mt-2 sm:flex sm:justify-between">
                                    <div class="sm:flex">
                                        <p class="flex items-center text-sm text-slate-500 mr-6">{{ commission.lugar }}
                                        </p>
                                        <p class="flex items-center text-sm text-slate-500">{{ commission.placa }} - {{
                                            commission.chofer }}</p>
                                    </div>
                                    <div class="mt-2 flex items-center text-sm text-slate-500 sm:mt-0">
                                        {{ commission.dia }} {{ commission.hora }}
                                        <button @click="openCommissionModal(commission)"
                                            class="ml-4 text-blue-600 hover:text-blue-800 font-medium">Gestionar</button>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- TAB: INVENTORY -->
                <div v-show="activeTab === 'inventory'">
                    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 mb-6">
                        <input type="text" v-model="searchInventory"
                            class="block w-full pl-3 sm:text-sm border-slate-300 rounded-md py-2 border max-w-lg"
                            placeholder="Buscar vehículo...">
                    </div>
                    <div v-if="loadingInventory"
                        class="bg-white rounded-lg shadow border border-slate-200 px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando inventario...</p>
                    </div>
                    <div v-else-if="filteredInventory.length === 0"
                        class="text-center py-20 bg-white rounded-lg shadow border border-slate-200">
                        <p class="text-slate-500">No hay vehículos en el inventario.</p>
                    </div>
                    <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="vehicle in filteredInventory" :key="vehicle.id"
                            class="bg-white overflow-hidden shadow rounded-2xl border border-slate-200 hover:shadow-lg transition-all duration-300 p-5">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-bold text-indigo-700 bg-indigo-100 px-2 py-1 rounded">{{
                                    vehicle.tipo }}</span>
                                <span class="text-xs px-2 py-1 rounded"
                                    :class="vehicle.estado === 'Operativo' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">{{
                                        vehicle.estado }}</span>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">{{ vehicle.marca }} {{ vehicle.modelo }}</h3>
                            <p class="text-sm text-slate-500">{{ vehicle.placa }}</p>
                            <p class="text-xs text-slate-400 mt-1">{{ vehicle.color }} | {{ vehicle.anio }}</p>
                            <button @click="openVehicleModal(vehicle)"
                                class="mt-4 w-full text-indigo-600 border border-indigo-200 rounded py-1 hover:bg-indigo-50 text-sm">Editar</button>
                        </div>
                    </div>
                </div>

                <!-- TAB: MAINTENANCE -->
                <div v-show="activeTab === 'maintenance'">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-slate-200">
                        <div v-if="loadingMaintenance" class="px-6 py-24 text-center">
                            <div
                                class="animate-spin h-12 w-12 border-4 border-emerald-600 border-t-transparent rounded-full mx-auto mb-4">
                            </div>
                            <p class="text-lg font-medium text-slate-600">Cargando gastos de mantenimiento...</p>
                        </div>
                        <div v-else-if="maintenances.length === 0" class="text-center py-20">
                            <p class="text-slate-500">No hay gastos de mantenimiento registrados.</p>
                        </div>
                        <table v-else class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vehículo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Detalle
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Costo
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="expense in maintenances" :key="expense.id">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ expense.fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{
                                        expense.vehicle_name }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ expense.detalle }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-bold">S/ {{
                                        expense.costo }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB: HANDOVER -->
                <div v-show="activeTab === 'handover'">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-slate-200">
                        <div v-if="loadingHandovers" class="px-6 py-24 text-center">
                            <div
                                class="animate-spin h-12 w-12 border-4 border-amber-600 border-t-transparent rounded-full mx-auto mb-4">
                            </div>
                            <p class="text-lg font-medium text-slate-600">Cargando actas de entrega...</p>
                        </div>
                        <div v-else-if="handovers.length === 0" class="text-center py-20">
                            <p class="text-slate-500">No hay actas registradas.</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Placa</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Entidad</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Kilometraje</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Recepciona</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="handover in handovers" :key="handover.id" class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ handover.fecha
                                        }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{
                                            handover.placa }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ handover.entidad }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{
                                            handover.kilometraje }} km</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{
                                            handover.recepciona }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- TAB: SERVICE REQUIREMENTS -->
                <div v-show="activeTab === 'service'">
                    <div class="bg-white shadow overflow-hidden sm:rounded-lg border border-slate-200">
                        <div v-if="loadingServiceReqs" class="px-6 py-24 text-center">
                            <div
                                class="animate-spin h-12 w-12 border-4 border-pink-600 border-t-transparent rounded-full mx-auto mb-4">
                            </div>
                            <p class="text-lg font-medium text-slate-600">Cargando requerimientos...</p>
                        </div>
                        <div v-else-if="serviceReqs.length === 0" class="text-center py-20">
                            <p class="text-slate-500">No hay requerimientos registrados.</p>
                        </div>
                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Fecha</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Vehículo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Conductor</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Motivo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="req in serviceReqs" :key="req.id" class="hover:bg-slate-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ req.created_at
                                        }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{
                                            req.vehicle_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ req.conductor
                                        }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ req.motivo }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <VehicleModal v-if="showVehicleModal" :vehicle="selectedVehicle" :vehicles="inventory"
                @close="showVehicleModal = false" @saved="onVehicleSaved" />
            <CommissionModal v-if="showCommissionModal" :commission="selectedCommission" :vehicles="inventory"
                @close="showCommissionModal = false" @saved="onCommissionSaved" />
            <MaintenanceModal v-if="showMaintenanceModal" :vehicles="inventory" @close="showMaintenanceModal = false"
                @saved="onMaintenanceSaved" />
            <HandoverModal v-if="showHandoverModal" @close="showHandoverModal = false" @saved="onHandoverSaved" />
            <ServiceReqModal v-if="showServiceReqModal" :vehicles="inventory" @close="showServiceReqModal = false"
                @saved="onServiceReqSaved" />
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import VehicleModal from '@/Components/Vehicles/VehicleModal.vue';
import CommissionModal from '@/Components/Vehicles/CommissionModal.vue';
import MaintenanceModal from '@/Components/Vehicles/MaintenanceModal.vue';
import HandoverModal from '@/Components/Vehicles/HandoverModal.vue';
import ServiceReqModal from '@/Components/Vehicles/ServiceReqModal.vue';
import axios from 'axios';

const activeTab = ref('commissions');

// Data
const inventory = ref([]);
const commissions = ref([]);
const maintenances = ref([]);
const handovers = ref([]);
const serviceReqs = ref([]);

// Loading states
const loadingInventory = ref(false);
const loadingCommissions = ref(false);
const loadingMaintenance = ref(false);
const loadingHandovers = ref(false);
const loadingServiceReqs = ref(false);

// Search
const searchCommission = ref('');
const searchInventory = ref('');

// Modals
const showVehicleModal = ref(false);
const showCommissionModal = ref(false);
const showMaintenanceModal = ref(false);
const showHandoverModal = ref(false);
const showServiceReqModal = ref(false);

const selectedVehicle = ref(null);
const selectedCommission = ref(null);

// Computed
const filteredCommissions = computed(() => {
    if (!searchCommission.value) return commissions.value;
    const q = searchCommission.value.toLowerCase();
    return commissions.value.filter(c =>
        c.dependencia?.toLowerCase().includes(q) ||
        c.chofer?.toLowerCase().includes(q) ||
        c.placa?.toLowerCase().includes(q)
    );
});

const filteredInventory = computed(() => {
    if (!searchInventory.value) return inventory.value;
    const q = searchInventory.value.toLowerCase();
    return inventory.value.filter(v =>
        v.placa?.toLowerCase().includes(q) ||
        v.marca?.toLowerCase().includes(q) ||
        v.modelo?.toLowerCase().includes(q)
    );
});

// Fetch functions
const fetchInventory = async () => {
    loadingInventory.value = true;
    try {
        const res = await axios.get('/vehicles/inventory');
        inventory.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingInventory.value = false; }
};

const fetchCommissions = async () => {
    loadingCommissions.value = true;
    try {
        const res = await axios.get('/vehicles/commissions');
        commissions.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingCommissions.value = false; }
};

const fetchMaintenances = async () => {
    loadingMaintenance.value = true;
    try {
        const res = await axios.get('/vehicles/maintenance');
        maintenances.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingMaintenance.value = false; }
};

const fetchHandovers = async () => {
    loadingHandovers.value = true;
    try {
        const res = await axios.get('/vehicles/handovers');
        handovers.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingHandovers.value = false; }
};

const fetchServiceReqs = async () => {
    loadingServiceReqs.value = true;
    try {
        const res = await axios.get('/vehicles/service-requirements');
        serviceReqs.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingServiceReqs.value = false; }
};

// Modal functions
const openVehicleModal = (vehicle = null) => {
    selectedVehicle.value = vehicle;
    showVehicleModal.value = true;
};

const openCommissionModal = (commission = null) => {
    selectedCommission.value = commission;
    showCommissionModal.value = true;
};

const openMaintenanceModal = () => { showMaintenanceModal.value = true; };
const openHandoverModal = () => { showHandoverModal.value = true; };
const openServiceReqModal = () => { showServiceReqModal.value = true; };

// Callbacks
const onVehicleSaved = () => { fetchInventory(); showVehicleModal.value = false; };
const onCommissionSaved = () => { fetchCommissions(); showCommissionModal.value = false; };
const onMaintenanceSaved = () => { fetchMaintenances(); showMaintenanceModal.value = false; };
const onHandoverSaved = () => { fetchHandovers(); showHandoverModal.value = false; };
const onServiceReqSaved = () => { fetchServiceReqs(); showServiceReqModal.value = false; };

// Helpers
const getStatusClass = (estado) => {
    switch (estado) {
        case 'EN_COMISION': return 'bg-blue-100 text-blue-800';
        case 'COMPLETADA': return 'bg-green-100 text-green-800';
        case 'CANCELADA': return 'bg-gray-100 text-gray-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};

onMounted(() => {
    fetchInventory();
    fetchCommissions();
    fetchMaintenances();
    fetchHandovers();
    fetchServiceReqs();
});
</script>
