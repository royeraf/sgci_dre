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
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button v-if="canViewTab('personal')" @click="activeTab = 'personal'"
                        :class="[activeTab === 'personal' ? 'text-emerald-600 active-tab' : 'text-slate-500 hover:text-slate-700', 'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300']">
                        <Users class="w-5 h-5" />
                        Personal
                    </button>
                    <button v-if="canViewTab('vacaciones')" @click="activeTab = 'vacaciones'"
                        :class="[activeTab === 'vacaciones' ? 'text-orange-600 active-tab' : 'text-slate-500 hover:text-slate-700', 'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300']">
                        <Calendar class="w-5 h-5" />
                        Vacaciones
                    </button>
                    <button v-if="canViewTab('directions')" @click="activeTab = 'directions'"
                        :class="[activeTab === 'directions' ? 'text-blue-600 active-tab' : 'text-slate-500 hover:text-slate-700', 'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300']">
                        <Building2 class="w-5 h-5" />
                        Direcciones / Oficinas
                    </button>
                    <button v-if="canViewTab('cargos')" @click="activeTab = 'cargos'"
                        :class="[activeTab === 'cargos' ? 'text-purple-600 active-tab' : 'text-slate-500 hover:text-slate-700', 'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300']">
                        <Briefcase class="w-5 h-5" />
                        Cargos / Puestos
                    </button>
                    <button v-if="canViewTab('tipos_contrato')" @click="activeTab = 'tipos_contrato'"
                        :class="[activeTab === 'tipos_contrato' ? 'text-slate-800 active-tab' : 'text-slate-500 hover:text-slate-700', 'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300']">
                        <FileText class="w-5 h-5" />
                        Tipos de Contrato
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Personal Tab -->
            <div v-if="activeTab === 'personal'">
                <BaseTableCard
                    title="Listado de Personal"
                    description="Lista completa de empleados registrados en el sistema"
                >
                    <template #actions>
                        <button
                            @click="filtersVisible = !filtersVisible"
                            class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm"
                        >
                            <SlidersHorizontal class="w-4 h-4" />
                            Filtros
                            <ChevronDown
                                class="w-4 h-4 transition-transform duration-300"
                                :class="{ 'rotate-180': filtersVisible }"
                            />
                        </button>

                        <button @click="createNewEmployee"
                            class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-emerald-500/30 text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 transition-all duration-300 hover:-translate-y-0.5">
                            <UserPlus class="w-4 h-4 mr-2" />
                            Nuevo Empleado
                        </button>
                    </template>

                    <!-- Filters toggle + collapsible panel -->
                    <div
                        class="filters-collapse bg-slate-50 border-b border-slate-100"
                        :class="{ 'filters-collapse--open': filtersVisible }"
                    >
                        <div class="p-4 sm:p-5">
                            <EmployeeFilters :filters="localFilters" :result-count="filteredEmployees.length"
                                :directions="directions" :positions="positions" :contract-types="contractTypes"
                                @update:filters="localFilters = $event" @clear="clearFilters" />
                        </div>
                    </div>

                    <EmployeeTable :employees="filteredEmployees" :loading="isLoading" v-model:currentPage="currentPage"
                        v-model:perPage="perPage" @view="viewEmployee" @edit="editEmployee" />
                </BaseTableCard>
            </div>

            <!-- Vacaciones Tab -->
            <div v-if="activeTab === 'vacaciones'">
                <BaseTableCard
                    title="Listado de Vacaciones"
                    description="Control y registro de vacaciones del personal"
                >
                    <template #actions>
                        <button @click="showVacationModal = true"
                            class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-orange-500/30 text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 transition-all duration-300 hover:-translate-y-0.5">
                            <CalendarPlus class="w-4 h-4 mr-2" />
                            Registrar Vacaciones
                        </button>
                    </template>

                    <VacationTable :vacations="vacations" @viewDetail="viewVacationDetail" @edit="editVacation"
                        @delete="handleDeleteVacation" />
                </BaseTableCard>
            </div>

            <!-- Direcciones Tab -->
            <div v-if="activeTab === 'directions'">
                <BaseTableCard
                    title="Direcciones y Oficinas"
                    description="Gestión estructural y áreas principales de la DRE"
                    searchPlaceholder="Buscar dirección u oficina..."
                    :searchValue="directionSearch"
                    @update:searchValue="directionSearch = $event"
                >
                    <template #subtabs>
                        <div class="flex items-center gap-1 bg-blue-50 rounded-full p-1.5 w-fit shrink-0">
                            <button
                                v-for="tab in directionTabs" :key="tab.key"
                                @click="activeDirectionTab = tab.key"
                                :class="[
                                    activeDirectionTab === tab.key
                                        ? 'bg-white text-blue-900 shadow-md'
                                        : 'text-blue-600 hover:text-blue-800',
                                    'cursor-pointer px-4 py-2 rounded-full text-xs font-semibold transition-all duration-200 flex items-center gap-2 outline-none'
                                ]">
                                {{ tab.label }}
                                <span :class="[
                                    activeDirectionTab === tab.key
                                        ? 'bg-blue-500 text-white'
                                        : 'bg-blue-200 text-blue-700',
                                    'text-[10px] font-bold w-5 h-5 rounded-full flex items-center justify-center transition-colors'
                                ]">{{ tab.count }}</span>
                            </button>
                        </div>
                    </template>

                    <template #actions>
                        <button v-if="activeDirectionTab === 'direcciones'" @click="createNewDirection"
                            class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-blue-500/30 text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 hover:-translate-y-0.5">
                            <Plus class="w-4 h-4 mr-2" />
                            Nueva Dirección
                        </button>
                        <button v-if="activeDirectionTab === 'oficinas'" @click="createNewOffice"
                            class="cursor-pointer inline-flex items-center px-4 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-sky-500/30 text-white bg-gradient-to-r from-sky-600 to-cyan-600 hover:from-sky-700 hover:to-cyan-700 transition-all duration-300 hover:-translate-y-0.5">
                            <Plus class="w-4 h-4 mr-2" />
                            Nueva Oficina
                        </button>
                    </template>

                    <!-- Render Table based on active tab -->
                    <Transition name="fade-slide" mode="out-in">
                        <div :key="activeDirectionTab">
                            <div v-if="activeDirectionTab === 'direcciones'">
                                <DirectionTable v-if="filteredDirectionsManagement.length > 0" :directions="filteredDirectionsManagement" @edit="editDirection"
                                    @delete="handleDeleteDirection" @viewOffices="viewDirectionOffices" />
                                <div v-else class="py-20 text-center bg-white">
                                    <Search class="w-12 h-12 text-slate-300 mx-auto mb-4" />
                                    <p class="text-slate-500 font-medium">No se encontraron resultados para "{{ directionSearch }}"</p>
                                    <button @click="directionSearch = ''"
                                        class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar búsqueda</button>
                                </div>
                            </div>
                            <div v-if="activeDirectionTab === 'oficinas'">
                                <OfficeTable v-if="filteredOfficesManagement.length > 0" :offices="filteredOfficesManagement" @edit="editOffice" @delete="handleDeleteOffice" />
                                <div v-else class="py-20 text-center bg-white">
                                    <Search class="w-12 h-12 text-slate-300 mx-auto mb-4" />
                                    <p class="text-slate-500 font-medium">No se encontraron resultados para "{{ directionSearch }}"</p>
                                    <button @click="directionSearch = ''"
                                        class="mt-4 text-sm font-bold text-blue-600 hover:underline">Limpiar búsqueda</button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </BaseTableCard>
            </div>

            <!-- Cargos Tab -->
            <div v-if="activeTab === 'cargos'">
                <BaseTableCard
                    title="Listado de Cargos"
                    description="Gestión de puestos y funciones laborales"
                >
                    <template #actions>
                        <button @click="createNewPosition"
                            class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-purple-500/30 text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all duration-300 hover:-translate-y-0.5">
                            <Plus class="w-4 h-4 mr-2" />
                            Nuevo Cargo
                        </button>
                    </template>

                    <PositionTable :positions="positions" @edit="editPosition" @delete="handleDeletePosition" />
                </BaseTableCard>
            </div>

            <!-- Tipos de Contrato Tab -->
            <div v-if="activeTab === 'tipos_contrato'">
                <BaseTableCard
                    title="Tipos de Contrato"
                    description="Clasificación de regímenes laborales (CAS, Nombrado, etc.)"
                >
                    <template #actions>
                        <button @click="createNewContractType"
                            class="cursor-pointer inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-gray-500/30 text-white bg-gradient-to-r from-gray-600 to-gray-800 hover:from-gray-700 hover:to-gray-900 transition-all duration-300 hover:-translate-y-0.5">
                            <Plus class="w-4 h-4 mr-2" />
                            Nuevo Tipo
                        </button>
                    </template>

                    <ContractTypeTable :types="contractTypes" @edit="editContractType" @delete="handleDeleteContractType" />
                </BaseTableCard>
            </div>

            <!-- Modals -->

            <!-- Employee Modal -->
            <EmployeeModal v-if="showEmployeeModal" :employee="selectedEmployee" :is-editing="isEditing"
                :submitting="isSubmitting" :directions="directions" :offices="offices" :positions="positions"
                :contract-types="contractTypes" @close="closeEmployeeModal" @submit="saveEmployee" />

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

            <!-- Direction Modal -->
            <DirectionModal v-if="showDirectionModal" :direction="selectedDirection" :is-editing="isEditingDirection"
                :submitting="isSubmitting" :offices="offices" @close="showDirectionModal = false"
                @submit="saveDirection" />

            <!-- Office Modal NEW -->
            <OfficeModal v-if="showOfficeModal" :office="selectedOffice" :is-editing="isEditingOffice"
                :submitting="isSubmitting" @close="showOfficeModal = false" @submit="saveOffice" />

            <!-- Position Modal -->
            <PositionModal v-if="showPositionModal" :position="selectedPosition" :is-editing="isEditingPosition"
                :submitting="isSubmitting" @close="showPositionModal = false" @submit="savePosition" />

            <!-- Contract Type Modal -->
            <ContractTypeModal v-if="showContractTypeModal" :contract-type="selectedContractType"
                :is-editing="isEditingContractType" :submitting="isSubmitting" @close="showContractTypeModal = false"
                @submit="saveContractType" />

            <!-- Direction Offices View Modal -->
            <DirectionOfficesModal v-if="showDirectionOfficesModal" :direction="selectedDirectionForOffices"
                :offices="selectedDirectionForOffices?.offices || []" @close="showDirectionOfficesModal = false" />
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
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { useTabPermission } from '@/composables/useTabPermission';
import axios from 'axios';
import {
    Users, UserPlus, Calendar, Plus, Building2, CalendarPlus, Briefcase, FileText, Search, SlidersHorizontal, ChevronDown
} from 'lucide-vue-next';

// Components
import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import SummaryCards from '@/Components/HR/SummaryCards.vue';
import EmployeeTable from '@/Components/HR/Employees/EmployeeTable.vue';
import EmployeeModal from '@/Components/HR/Employees/EmployeeModal.vue';
import EmployeeDetailModal from '@/Components/HR/Employees/EmployeeDetailModal.vue';
import VacationTable from '@/Components/HR/Vacations/VacationTable.vue';
import VacationModal from '@/Components/HR/Vacations/VacationModal.vue';
import VacationDetailModal from '@/Components/HR/Vacations/VacationDetailModal.vue';
import DirectionTable from '@/Components/HR/Directions/DirectionTable.vue';
import DirectionModal from '@/Components/HR/Directions/DirectionModal.vue';
import OfficeTable from '@/Components/HR/Directions/OfficeTable.vue';
import OfficeModal from '@/Components/HR/Directions/OfficeModal.vue';
import PositionTable from '@/Components/HR/Positions/PositionTable.vue';
import PositionModal from '@/Components/HR/Positions/PositionModal.vue';
import ContractTypeTable from '@/Components/HR/ContractTypes/ContractTypeTable.vue';
import ContractTypeModal from '@/Components/HR/ContractTypes/ContractTypeModal.vue';
import EmployeeFilters from '@/Components/HR/Employees/EmployeeFilters.vue';
import DirectionOfficesModal from '@/Components/HR/Directions/DirectionOfficesModal.vue';

const { canViewTab, firstAllowedTab } = useTabPermission('recursos_humanos', ['personal', 'vacaciones', 'directions', 'cargos', 'tipos_contrato']);
const activeTab = ref(firstAllowedTab.value);
const activeDirectionTab = ref('direcciones');
const isLoading = ref(false);
const isSubmitting = ref(false);

const directionSearch = ref('');
const localFilters = ref({
    search: '',
    direction: '',
    position: '',
    contractType: ''
});

const currentPage = ref(1);
const perPage = ref(10);

const FILTERS_STORAGE_KEY = 'hr_filters_open';
const filtersVisible = ref(
    localStorage.getItem(FILTERS_STORAGE_KEY) === 'true'
);
watch(filtersVisible, (val) => localStorage.setItem(FILTERS_STORAGE_KEY, String(val)));

watch(localFilters, () => {
    currentPage.value = 1;
}, { deep: true });

const employees = ref([]);
const vacations = ref([]);
const directions = ref([]);
const offices = ref([]);
const positions = ref([]);
const contractTypes = ref([]);
const summary = ref({});

const directionTabs = computed(() => [
    { key: 'direcciones', label: 'Direcciones', count: directions.value.length },
    { key: 'oficinas', label: 'Oficinas', count: offices.value.length }
]);

const showEmployeeModal = ref(false);
const showVacationModal = ref(false);
const showVacationDetailModal = ref(false);
const selectedVacation = ref(null);
const selectedVacationDetail = ref(null);
const isEditingVacation = ref(false);
const showViewEmployeeModal = ref(false);
const showDirectionModal = ref(false);
const showOfficeModal = ref(false);
const showPositionModal = ref(false);
const showContractTypeModal = ref(false);
const showDirectionOfficesModal = ref(false);

const selectedEmployee = ref(null);
const isEditing = ref(false);

const selectedDirection = ref(null);
const isEditingDirection = ref(false);

const selectedOffice = ref(null);
const isEditingOffice = ref(false);

const selectedPosition = ref(null);
const isEditingPosition = ref(false);

const selectedContractType = ref(null);
const isEditingContractType = ref(false);

const selectedDirectionForOffices = ref(null);

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

    if (localFilters.value.direction) {
        result = result.filter(e => e.direction?.__name === localFilters.value.direction || e.direction_nombre === localFilters.value.direction);
    }

    if (localFilters.value.position) {
        // ... logica existente
        result = result.filter(e => e.cargo === localFilters.value.position);
    }

    if (localFilters.value.contractType) {
        result = result.filter(e => e.contract_type_id === localFilters.value.contractType);
    }

    return result;
});

const filteredDirectionsManagement = computed(() => {
    if (!directionSearch.value) return directions.value;
    const q = directionSearch.value.toLowerCase();
    return directions.value.filter(d =>
        d.nombre.toLowerCase().includes(q) ||
        (d.descripcion && d.descripcion.toLowerCase().includes(q))
    );
});

const filteredOfficesManagement = computed(() => {
    if (!directionSearch.value) return offices.value;
    const q = directionSearch.value.toLowerCase();
    return offices.value.filter(o =>
        o.nombre.toLowerCase().includes(q) ||
        (o.codigo && o.codigo.toLowerCase().includes(q)) ||
        (o.direction?.nombre && o.direction.nombre.toLowerCase().includes(q))
    );
});

const clearFilters = () => {
    localFilters.value = {
        search: '',
        direction: '',
        position: '',
        contractType: ''
    };
};

const fetchData = async () => {
    isLoading.value = true;
    try {
        const [empRes, vacRes, sumRes, dirRes, posRes, officeRes, contractTypeRes] = await Promise.all([
            axios.get('/hr/employees'),
            axios.get('/hr/vacations'),
            axios.get('/hr/summary'),
            axios.get('/hr/directions'),
            axios.get('/hr/positions'),
            axios.get('/hr/offices'),
            axios.get('/hr/contract-types')
        ]);
        employees.value = empRes.data;
        vacations.value = vacRes.data;
        summary.value = sumRes.data;
        directions.value = dirRes.data;
        positions.value = posRes.data;
        offices.value = officeRes.data;
        contractTypes.value = contractTypeRes.data;
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

const createNewDirection = () => {
    isEditingDirection.value = false;
    selectedDirection.value = null;
    showDirectionModal.value = true;
};

const editDirection = (direction) => {
    isEditingDirection.value = true;
    selectedDirection.value = direction;
    showDirectionModal.value = true;
};

const saveDirection = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingDirection.value && selectedDirection.value) {
            await axios.put(`/hr/directions/${selectedDirection.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Dirección Actualizada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } else {
            await axios.post('/hr/directions', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Dirección Creada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        }
        showDirectionModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar la dirección' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteDirection = (id) => {
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
                await axios.delete(`/hr/directions/${id}`);
                window.Swal?.fire?.('Eliminado', 'La dirección ha sido eliminada.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar la dirección.', 'error');
            }
        }
    });
};

// --- OFFICE METHODS ---

const viewDirectionOffices = (direction) => {
    selectedDirectionForOffices.value = direction;
    showDirectionOfficesModal.value = true;
};

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

// --- CONTRACT TYPE METHODS ---

const createNewContractType = () => {
    isEditingContractType.value = false;
    selectedContractType.value = null;
    showContractTypeModal.value = true;
};

const editContractType = (type) => {
    isEditingContractType.value = true;
    selectedContractType.value = type;
    showContractTypeModal.value = true;
};

const saveContractType = async (formData) => {
    isSubmitting.value = true;
    try {
        if (isEditingContractType.value && selectedContractType.value) {
            await axios.put(`/hr/contract-types/${selectedContractType.value.id}`, formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Tipo Actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        } else {
            await axios.post('/hr/contract-types', formData);
            window.Swal?.fire?.({ icon: 'success', title: 'Tipo Registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        }
        showContractTypeModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo guardar el tipo' });
    } finally {
        isSubmitting.value = false;
    }
};

const handleDeleteContractType = (id) => {
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
                await axios.delete(`/hr/contract-types/${id}`);
                window.Swal?.fire?.('Eliminado', 'El tipo de contrato ha sido eliminado.', 'success');
                fetchData();
            } catch (error) {
                window.Swal?.fire?.('Error', 'No se pudo eliminar el registro.', 'error');
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
    nextTick(updateIndicator);
});

watch(activeTab, () => {
    nextTick(updateIndicator);
});

// Tab indicator logic
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    switch (tab) {
        case 'personal': return '#10b981'; // emerald-500
        case 'vacaciones': return '#f97316'; // orange-500
        case 'directions': return '#3b82f6'; // blue-500
        case 'cargos': return '#a855f7'; // purple-500
        case 'tipos_contrato': return '#1e293b'; // slate-800
        default: return '#10b981';
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
</script>

<style scoped>
/* Filters collapse animation */
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
