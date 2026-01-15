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
                <div class="flex gap-2">
                    <button @click="showEmployeeModal = true"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 transition-all">
                        <UserPlus class="w-5 h-5 mr-2" />
                        Nuevo Empleado
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <SummaryCards :summary="summary" />

            <!-- Tabs -->
            <div class="border-b border-gray-200 mb-6">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'personal'"
                        :class="[activeTab === 'personal' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2']">
                        <Users class="w-5 h-5" />
                        Directorio de Personal
                    </button>
                    <button @click="activeTab = 'vacaciones'"
                        :class="[activeTab === 'vacaciones' ? 'border-orange-500 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center gap-2']">
                        <Calendar class="w-5 h-5" />
                        Vacaciones
                    </button>
                </nav>
            </div>

            <!-- Personal Tab -->
            <div v-if="activeTab === 'personal'">
                <EmployeeTable :employees="filteredEmployees" :loading="isLoading" v-model:search-query="searchQuery"
                    @view="viewEmployee" @edit="editEmployee" />
            </div>

            <!-- Vacaciones Tab -->
            <div v-if="activeTab === 'vacaciones'" class="space-y-6">
                <div class="flex justify-end">
                    <button @click="showVacationModal = true"
                        class="inline-flex items-center px-4 py-2 bg-orange-600 text-white font-bold rounded-xl hover:bg-orange-700 transition-colors">
                        <Plus class="w-5 h-5 mr-2" />
                        Registrar Vacaciones
                    </button>
                </div>
                <VacationTable :vacations="vacations" />
            </div>

            <!-- Employee Modal -->
            <EmployeeModal v-if="showEmployeeModal" :employee="selectedEmployee" :is-editing="isEditing"
                :submitting="isSubmitting" @close="closeEmployeeModal" @submit="saveEmployee" />

            <!-- Employee Detail Modal -->
            <EmployeeDetailModal v-if="showViewEmployeeModal" :employee="selectedEmployee"
                @close="closeViewEmployeeModal" />

            <!-- Vacation Modal -->
            <VacationModal v-if="showVacationModal" :employees="employees" :submitting="isSubmitting"
                @close="showVacationModal = false" @submit="saveVacation" />
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
import { Users, UserPlus, Calendar, Plus } from 'lucide-vue-next';

// Components
import SummaryCards from '@/Components/HR/SummaryCards.vue';
import EmployeeTable from '@/Components/HR/Employees/EmployeeTable.vue';
import EmployeeModal from '@/Components/HR/Employees/EmployeeModal.vue';
import EmployeeDetailModal from '@/Components/HR/Employees/EmployeeDetailModal.vue';
import VacationTable from '@/Components/HR/Vacations/VacationTable.vue';
import VacationModal from '@/Components/HR/Vacations/VacationModal.vue';

const activeTab = ref('personal');
const isLoading = ref(false);
const isSubmitting = ref(false);
const searchQuery = ref('');

const employees = ref([]);
const vacations = ref([]);
const summary = ref({});

const showEmployeeModal = ref(false);
const showVacationModal = ref(false);
const showViewEmployeeModal = ref(false);
const selectedEmployee = ref(null);
const isEditing = ref(false);

const filteredEmployees = computed(() => {
    if (!searchQuery.value) return employees.value;
    const q = searchQuery.value.toLowerCase();
    return employees.value.filter(e =>
        (e.nombres + ' ' + e.apellidos).toLowerCase().includes(q) ||
        String(e.dni).includes(q) ||
        (e.area || '').toLowerCase().includes(q)
    );
});

const fetchData = async () => {
    isLoading.value = true;
    try {
        const [empRes, vacRes, sumRes] = await Promise.all([
            axios.get('/hr/employees'),
            axios.get('/hr/vacations'),
            axios.get('/hr/summary')
        ]);
        employees.value = empRes.data;
        vacations.value = vacRes.data;
        summary.value = sumRes.data;
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

const saveVacation = async (formData) => {
    isSubmitting.value = true;
    try {
        await axios.post('/hr/vacations', formData);
        window.Swal?.fire?.({ icon: 'success', title: 'Vacaciones Registradas', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        showVacationModal.value = false;
        fetchData();
    } catch (error) {
        window.Swal?.fire?.({ icon: 'error', title: 'Error', text: error.response?.data?.message || 'No se pudo registrar' });
    } finally {
        isSubmitting.value = false;
    }
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
