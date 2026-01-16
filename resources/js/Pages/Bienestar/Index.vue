<template>
    <MainLayout>
        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">
                        Bienestar Social
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Gestión y control de licencias del personal institucional
                    </p>
                </div>
                <div class="flex gap-3">
                    <button @click="showRegisterModal = true"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-purple-200 text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Registrar Licencia
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <BienestarStats :summary="summary" />

            <!-- Tabs Section -->
            <div class="bg-white shadow-sm border border-slate-200 rounded-2xl overflow-hidden">
                <div class="border-b border-slate-200">
                    <nav class="flex -mb-px">
                        <button @click="activeTab = 'licenses'"
                            :class="[activeTab === 'licenses'
                                ? 'border-purple-600 text-purple-700 bg-purple-50/50'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'flex-1 py-4 px-1 text-center border-b-2 font-bold text-sm transition-all duration-200']">
                            <div class="flex items-center justify-center gap-2">
                                <BookOpen class="w-4 h-4" />
                                Historial de Licencias
                            </div>
                        </button>
                        <button @click="activeTab = 'balances'"
                            :class="[activeTab === 'balances'
                                ? 'border-indigo-600 text-indigo-700 bg-indigo-50/50'
                                : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300',
                                'flex-1 py-4 px-1 text-center border-b-2 font-bold text-sm transition-all duration-200']">
                            <div class="flex items-center justify-center gap-2">
                                <CalendarDays class="w-4 h-4" />
                                Saldos y Cuotas
                            </div>
                        </button>
                    </nav>
                </div>

                <!-- Historial de Licencias Tab -->
                <div v-show="activeTab === 'licenses'" class="p-0">
                    <LicenseHistoryTable :licenses="licenses" :loading="loading" />
                </div>

                <!-- Saldos Tab -->
                <div v-show="activeTab === 'balances'" class="p-0">
                    <BalanceTable :employeeBalances="employeeBalances" />
                </div>
            </div>
        </div>

        <!-- Register License Modal -->
        <LicenseModal :show="showRegisterModal" :employees="employeeBalances" @close="showRegisterModal = false"
            @success="fetchData" />
    </MainLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';
import { Plus, BookOpen, CalendarDays } from 'lucide-vue-next';

import BienestarStats from '@/Components/Bienestar/BienestarStats.vue';
import LicenseHistoryTable from '@/Components/Bienestar/LicenseHistoryTable.vue';
import BalanceTable from '@/Components/Bienestar/BalanceTable.vue';
import LicenseModal from '@/Components/Bienestar/LicenseModal.vue';

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

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
