<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">
                        Recursos Humanos
                    </h1>
                    <p class="mt-2 text-slate-600">Gestión integral del personal DRE Huánuco</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-if="activeTab === 'personal'" @click="createNewEmployee"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 transition-all">
                        <UserPlus class="w-5 h-5 mr-2" />
                        Nuevo Empleado
                    </button>
                    <button v-if="activeTab === 'areas'" @click="createNewArea"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Área
                    </button>
                    <button v-if="activeTab === 'areas'" @click="createNewOffice"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-700 hover:to-cyan-700 transition-all">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Oficina
                    </button>
                    <button v-if="activeTab === 'cargos'" @click="createNewPosition"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all">
                        <Plus class="w-5 h-5 mr-2" />
                        Nuevo Cargo
                    </button>
                    <button v-if="activeTab === 'vacaciones'" @click="showVacationModal = true"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 transition-all">
                        <CalendarPlus class="w-5 h-5 mr-2" />
                        Registrar Vacaciones
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8 overflow-x-auto pb-1 scrollbar-hide">
                    <button @click="activeTab = 'personal'"
                        :class="[activeTab === 'personal' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-colors']">
                        <Users class="w-5 h-5" />
                        Personal
                    </button>
                    <button @click="activeTab = 'vacaciones'"
                        :class="[activeTab === 'vacaciones' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-colors']">
                        <Calendar class="w-5 h-5" />
                        Vacaciones
                    </button>
                    <button @click="activeTab = 'areas'"
                        :class="[activeTab === 'areas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-colors']">
                        <Building2 class="w-5 h-5" />
                        Áreas / Oficinas
                    </button>
                    <button @click="activeTab = 'cargos'"
                        :class="[activeTab === 'cargos' ? 'border-purple-500 text-purple-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-colors']">
                        <Briefcase class="w-5 h-5" />
                        Cargos / Puestos
                    </button>
                </nav>
            </div>

            <!-- Personal Tab -->
            <div v-if="activeTab === 'personal'">
                <EmployeeFilters :filters="localFilters" :result-count="filteredEmployees.length" :areas="areas"
                    :positions="positions" @update:filters="localFilters = $event" @clear="clearFilters" />

                <EmployeeTable :employees="filteredEmployees" :loading="isLoading" @view="viewEmployee"
                    @edit="editEmployee" />
            </div>

            <!-- Vacaciones Tab -->
            <div v-if="activeTab === 'vacaciones'" class="space-y-6">
                <VacationTable :vacations="vacations" @viewDetail="viewVacationDetail" @edit="editVacation"
                    @delete="handleDeleteVacation" />
            </div>

            <!-- Áreas Tab -->
            <div v-if="activeTab === 'areas'" class="space-y-8">
                <!-- Áreas Section -->
                <div>
                    <div class="flex items-center gap-2 mb-4">
                        <Building2 class="w-5 h-5 text-blue-600" />
                        <h3 class="text-xl font-bold text-slate-800">Direcciones y Áreas Principales</h3>
                    </div>
                    <AreaTable :areas="areas" @edit="editArea" @delete="handleDeleteArea" />
                </div>

                <!-- Oficinas Section -->
                <div>
                    <div class="flex items-center gap-2 mb-4 pt-6 border-t border-slate-200">
                        <Building2 class="w-5 h-5 text-indigo-600" />
                        <h3 class="text-xl font-bold text-slate-800">Oficinas y Unidades Orgánicas</h3>
                        <span class="ml-2 px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                            {{ offices.length }} oficinas
                        </span>
                    </div>
                    <OfficeTable :offices="offices" @edit="editOffice" @delete="handleDeleteOffice" />
                </div>
            </div>

            <!-- Cargos Tab -->
            <div v-if="activeTab === 'cargos'" class="space-y-6">
                <PositionTable :positions="positions" @edit="editPosition" @delete="handleDeletePosition" />
            </div>

            <!-- Modals -->

            <!-- Employee Modal -->
            <EmployeeModal v-if="showEmployeeModal" :employee="selectedEmployee" :is-editing="isEditing"
                :submitting="isSubmitting" :areas="areas" :positions="positions" @close="closeEmployeeModal"
                @submit="saveEmployee" />

            <!-- Employee Detail Modal -->
            <EmployeeDetailModal v-if="showViewEmployeeModal" :employee="selectedEmployee"
                @close="closeViewEmployeeModal" />

            <!-- Vacation Modal -->
            <VacationModal v-if="showVacationModal" :employees="employees" :submitting="isSubmitting"
                :vacation="selectedVacation" :is-editing="isEditingVacation" @close="closeVacationModal"
                @submit="saveVacation" />

            <!-- Vacation Detail Modal -->
            <VacationDetailModal v-if="showVacationDetailModal" :vacation="selectedVacationDetail"
                @close="closeVacationDetailModal" />

            <!-- Area Modal -->
            <AreaModal v-if="showAreaModal" :area="selectedArea" :is-editing="isEditingArea" :submitting="isSubmitting"
                @close="showAreaModal = false" @submit="saveArea" />

            <!-- Office Modal NEW -->
            <OfficeModal v-if="showOfficeModal" :office="selectedOffice" :is-editing="isEditingOffice"
                :submitting="isSubmitting" :areas="areas" @close="showOfficeModal = false" @submit="saveOffice" />

            <!-- Position Modal -->
            <PositionModal v-if="showPositionModal" :position="selectedPosition" :is-editing="isEditingPosition"
                :submitting="isSubmitting" @close="showPositionModal = false" @submit="savePosition" />
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
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import {
    Users, UserPlus, Calendar, Plus, Building2, CalendarPlus, Briefcase
} from 'lucide-vue-next';

// Components
import SummaryCards from '@/Components/HR/SummaryCards.vue';
import EmployeeTable from '@/Components/HR/Employees/EmployeeTable.vue';
import EmployeeModal from '@/Components/HR/Employees/EmployeeModal.vue';
import EmployeeDetailModal from '@/Components/HR/Employees/EmployeeDetailModal.vue';
import VacationTable from '@/Components/HR/Vacations/VacationTable.vue';
import VacationModal from '@/Components/HR/Vacations/VacationModal.vue';
import VacationDetailModal from '@/Components/HR/Vacations/VacationDetailModal.vue';
import AreaTable from '@/Components/HR/Areas/AreaTable.vue';
import AreaModal from '@/Components/HR/Areas/AreaModal.vue';
import OfficeTable from '@/Components/HR/Areas/OfficeTable.vue';
import OfficeModal from '@/Components/HR/Areas/OfficeModal.vue';
import PositionTable from '@/Components/HR/Positions/PositionTable.vue';
import PositionModal from '@/Components/HR/Positions/PositionModal.vue';
import EmployeeFilters from '@/Components/HR/Employees/EmployeeFilters.vue';

const activeTab = ref('personal');
const isLoading = ref(false);
const isSubmitting = ref(false);

const localFilters = ref({
    search: '',
    area: '',
    position: ''
});

const employees = ref([]);
const vacations = ref([]);
const areas = ref([]);
const offices = ref([]); // Nuevo estado
const positions = ref([]);
const summary = ref({});

const showEmployeeModal = ref(false);
const showVacationModal = ref(false);
const showVacationDetailModal = ref(false);
const selectedVacation = ref(null);
const selectedVacationDetail = ref(null);
const isEditingVacation = ref(false);
const showViewEmployeeModal = ref(false);
const showAreaModal = ref(false);
const showOfficeModal = ref(false); // Nuevo Modal
const showPositionModal = ref(false);

const selectedEmployee = ref(null);
const isEditing = ref(false);

const selectedArea = ref(null);
const isEditingArea = ref(false);

const selectedOffice = ref(null); // Nueva selección
const isEditingOffice = ref(false);

const selectedPosition = ref(null);
const isEditingPosition = ref(false);

const filteredEmployees = computed(() => {
    let result = employees.value;

    if (localFilters.value.search) {
        const q = localFilters.value.search.toLowerCase();
        result = result.filter(e =>
            (e.nombres + ' ' + e.apellidos).toLowerCase().includes(q) ||
            String(e.dni).includes(q) ||
            (e.cargo || '').toLowerCase().includes(q)
        );
    }

    if (localFilters.value.area) {
        result = result.filter(e => e.area?.__name === localFilters.value.area || e.area_nombre === localFilters.value.area);
    }

    if (localFilters.value.position) {
        // ... logica existente
        result = result.filter(e => e.cargo === localFilters.value.position);
    }

    return result;
});

const clearFilters = () => {
    localFilters.value = {
        search: '',
        area: '',
        position: ''
    };
};

const fetchData = async () => {
    isLoading.value = true;
    try {
        const [empRes, vacRes, sumRes, areaRes, posRes, officeRes] = await Promise.all([
            axios.get('/hr/employees'),
            axios.get('/hr/vacations'),
            axios.get('/hr/summary'),
            axios.get('/hr/areas'),
            axios.get('/hr/positions'),
            axios.get('/hr/offices') // Nuevo endpoint
        ]);
        employees.value = empRes.data;
        vacations.value = vacRes.data;
        summary.value = sumRes.data;
        areas.value = areaRes.data;
        positions.value = posRes.data;
        offices.value = officeRes.data;
    } catch (error) {
        console.error('Error fetching HR data:', error);
        window.Swal?.fire?.({
            icon: 'error',
            title: 'Error',
            text: 'No se pudieron cargar los datos de RRHH'
        });
    } finally {
        isLoading.value = false;
    }
};

// ... (Métodos de empleados y vacaciones existentes se mantienen igual) ...

const createNewEmployee = () => {
    isEditing.value = false;
    selectedEmployee.value = null;
    showEmployeeModal.value = true;
};

const closeEmployeeModal = () => {
    showEmployeeModal.value = false;
    selectedEmployee.value = null;
    isEditing.value = false;
};

const saveEmployee = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value && selectedEmployee.value) {
            await axios.put(`/hr/employees/${selectedEmployee.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Empleado Actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        } else {
            await axios.post('/hr/employees', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Empleado Registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
        closeEmployeeModal();
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar' });
    } finally {
        isSubmitting.value = false;
    }
};

const closeVacationModal = () => {
    showVacationModal.value = false;
    selectedVacation.value = null;
    isEditingVacation.value = false;
};

const saveVacation = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingVacation.value && selectedVacation.value) {
            await axios.put(`/hr/vacations/${selectedVacation.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Vacaciones Actualizadas', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        } else {
            await axios.post('/hr/vacations', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Vacaciones Registradas', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        }
        closeVacationModal();
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo registrar' });
    } finally {
        isSubmitting.value = false;
    }
};

const viewVacationDetail = (vacation) => {
    selectedVacationDetail.value = vacation;
    showVacationDetailModal.value = true;
};

const closeVacationDetailModal = () => {
    showVacationDetailModal.value = false;
    selectedVacationDetail.value = null;
};

const editVacation = (vacation) => {
    isEditingVacation.value = true;
    selectedVacation.value = vacation;
    showVacationModal.value = true;
};

const handleDeleteVacation = (id) => {
    window.Swal?.fire?.({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/hr/vacations/${id}`);
                window.Swal?.fire?.('Eliminado', 'Las vacaciones han sido eliminadas.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudieron eliminar las vacaciones.', 'error');
            }
        }
    });
};

const createNewArea = () => {
    isEditingArea.value = false;
    selectedArea.value = null;
    showAreaModal.value = true;
};

const editArea = (area) => {
    isEditingArea.value = true;
    selectedArea.value = area;
    showAreaModal.value = true;
};

const saveArea = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingArea.value && selectedArea.value) {
            await axios.put(`/hr/areas/${selectedArea.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Área Actualizada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } else {
            await axios.post('/hr/areas', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Área Creada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        }
        showAreaModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar el área' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteArea = (id) => {
    window.Swal?.fire?.({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/hr/areas/${id}`);
                window.Swal?.fire?.('Eliminado', 'El área ha sido eliminada.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar el área.', 'error');
            }
        }
    });
};

// --- OFFICE METHODS ---

const createNewOffice = () => {
    isEditingOffice.value = false;
    selectedOffice.value = null;
    showOfficeModal.value = true;
};

const editOffice = (office) => {
    isEditingOffice.value = true;
    selectedOffice.value = office;
    showOfficeModal.value = true;
};

const saveOffice = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingOffice.value && selectedOffice.value) {
            await axios.put(`/hr/offices/${selectedOffice.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Oficina Actualizada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } else {
            await axios.post('/hr/offices', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Oficina Creada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        }
        showOfficeModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar la oficina' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteOffice = (id) => {
    window.Swal?.fire?.({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/hr/offices/${id}`);
                window.Swal?.fire?.('Eliminado', 'La oficina ha sido eliminada.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar la oficina.', 'error');
            }
        }
    });
};

const createNewPosition = () => {
    isEditingPosition.value = false;
    selectedPosition.value = null;
    showPositionModal.value = true;
};

const editPosition = (pos) => {
    isEditingPosition.value = true;
    selectedPosition.value = pos;
    showPositionModal.value = true;
};

const savePosition = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingPosition.value && selectedPosition.value) {
            await axios.put(`/hr/positions/${selectedPosition.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Cargo Actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } else {
            await axios.post('/hr/positions', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Cargo Creado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        }
        showPositionModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar el cargo' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeletePosition = (id) => {
    window.Swal?.fire?.({
        title: '¿Está seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                await axios.delete(`/hr/positions/${id}`);
                window.Swal?.fire?.('Eliminado', 'El cargo ha sido eliminado.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar el cargo.', 'error');
            }
        }
    });
};

const viewEmployee = (emp) => {
    selectedEmployee.value = emp;
    showViewEmployeeModal.value = true;
};

const editEmployee = (emp) => {
    isEditing.value = true;
    selectedEmployee.value = emp;
    showEmployeeModal.value = true;
};

const closeViewEmployeeModal = () => {
    showViewEmployeeModal.value = false;
    selectedEmployee.value = null;
};

onMounted(() => {
    fetchData();
});
</script>
