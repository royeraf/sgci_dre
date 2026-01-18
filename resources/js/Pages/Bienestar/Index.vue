<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent tracking-tight">
                        Bienestar Social
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Gestión y control de licencias del personal institucional
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </a>
                    <button @click="showRegisterModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-purple-600/20 text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Registrar Licencia
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <BienestarStats :summary="summary" />

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'licenses'"
                        :class="[activeTab === 'licenses' ? 'border-purple-600 text-purple-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <BookOpen class="w-5 h-5" />
                        Historial de Licencias
                    </button>
                    <button @click="activeTab = 'balances'"
                        :class="[activeTab === 'balances' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <CalendarDays class="w-5 h-5" />
                        Saldos y Cuotas
                    </button>
                </nav>
            </div>

            <!-- Historial de Licencias Tab -->
            <div v-if="activeTab === 'licenses'" class="space-y-6">
                <LicenseHistoryTable :licenses="licenses" :loading="loading" />
            </div>

            <!-- Saldos Tab -->
            <div v-if="activeTab === 'balances'" class="space-y-6">
                <BalanceTable :employeeBalances="employeeBalances" />
            </div>
        </div>

        <!-- Register License Modal -->
        <LicenseModal :show="showRegisterModal" :employees="employeeBalances" @close="showRegisterModal = false"
            @success="fetchData" />
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { Plus, BookOpen, CalendarDays, ArrowLeft } from 'lucide-vue-next';

import BienestarStats from '@/Components/Bienestar/Stats/BienestarStats.vue';
import LicenseHistoryTable from '@/Components/Bienestar/Licenses/LicenseHistoryTable.vue';
import BalanceTable from '@/Components/Bienestar/Balances/BalanceTable.vue';
import LicenseModal from '@/Components/Bienestar/Licenses/LicenseModal.vue';

const activeTab = ref('licenses');
const loading = ref(false);
const showRegisterModal = ref(false);

const licenses = ref([]);
const employeeBalances = ref([]);
const summary = ref({});

const fetchData = async () => {
    loading.value = true;
    try {
        const [licRes, balRes, sumRes] = await Promise.all([
            axios.get('/bienestar/api/licenses'),
            axios.get('/bienestar/api/employees'),
            axios.get('/bienestar/api/summary')
        ]);
        licenses.value = licRes.data;
        employeeBalances.value = balRes.data;
        summary.value = sumRes.data;
    } catch (error) {
        console.error('Error fetching data:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchData();
});
</script>
