<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent tracking-tight">
                        Control Vehicular y Gestión
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Registro y seguimiento de comisiones, inventario, gastos, actas y servicios
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver
                    </a>
                    <!-- Dynamic action button based on active tab -->
                    <button v-if="activeTab === 'commissions'" @click="openCommissionModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-blue-600/20 text-white bg-gradient-to-r from-blue-600 to-sky-500 hover:from-blue-700 hover:to-sky-600 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nueva Comisión
                    </button>
                    <button v-if="activeTab === 'inventory'" @click="openVehicleModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/20 text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Registrar Vehículo
                    </button>
                    <button v-if="activeTab === 'maintenance'" @click="openMaintenanceModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/20 text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 focus:outline-none focus:ring-4 focus:ring-emerald-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Registrar Gasto
                    </button>
                    <button v-if="activeTab === 'handover'" @click="openHandoverModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-amber-600/20 text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-4 focus:ring-amber-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Nueva Acta
                    </button>
                    <button v-if="activeTab === 'service'" @click="openServiceReqModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-pink-600/20 text-white bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-700 hover:to-rose-600 focus:outline-none focus:ring-4 focus:ring-pink-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Requerimiento
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button @click="activeTab = 'commissions'"
                        :class="[activeTab === 'commissions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        Comisiones
                    </button>
                    <button @click="activeTab = 'inventory'"
                        :class="[activeTab === 'inventory' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                        </svg>
                        Inventario
                    </button>
                    <button @click="activeTab = 'maintenance'"
                        :class="[activeTab === 'maintenance' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Gastos
                    </button>
                    <button @click="activeTab = 'handover'"
                        :class="[activeTab === 'handover' ? 'border-amber-600 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Actas de Entrega
                    </button>
                    <button @click="activeTab = 'service'"
                        :class="[activeTab === 'service' ? 'border-pink-600 text-pink-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        Requerimientos
                    </button>
                </nav>
            </div>

            <!-- TAB: COMMISSIONS -->
            <div v-if="activeTab === 'commissions'" class="space-y-6">
                <!-- Search Filter -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="relative max-w-md">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" v-model="searchCommission"
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Buscar por dependencia, chofer o placa...">
                    </div>
                </div>

                <!-- Commission Cards -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="loadingCommissions" class="px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando comisiones...</p>
                    </div>
                    <div v-else-if="filteredCommissions.length === 0" class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        <p class="text-slate-500 font-medium">No hay comisiones registradas</p>
                        <p class="text-slate-400 text-sm mt-1">Haz clic en "Nueva Comisión" para agregar una</p>
                    </div>
                    <ul v-else class="divide-y divide-slate-100">
                        <li v-for="commission in filteredCommissions" :key="commission.id"
                            class="hover:bg-slate-50 transition-colors duration-200 px-6 py-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-bold text-blue-600">{{ commission.dependencia }}</span>
                                <span class="px-3 py-1 text-xs font-bold rounded-full"
                                    :class="getStatusClass(commission.estado)">{{ commission.estado }}</span>
                            </div>
                            <div class="mt-2 flex flex-col sm:flex-row sm:justify-between gap-2">
                                <div class="flex flex-wrap gap-4 text-sm text-slate-500">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        {{ commission.lugar }}
                                    </span>
                                    <span>{{ commission.placa }} - {{ commission.chofer }}</span>
                                </div>
                                <div class="flex items-center gap-4 text-sm">
                                    <span class="text-slate-400">{{ commission.dia }} {{ commission.hora }}</span>
                                    <button @click="openCommissionModal(commission)"
                                        class="text-blue-600 hover:text-blue-800 font-bold transition-colors">Gestionar</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- TAB: INVENTORY -->
            <div v-if="activeTab === 'inventory'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="relative max-w-md">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" v-model="searchInventory"
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Buscar vehículo por placa, marca o modelo...">
                    </div>
                </div>
                <div v-if="loadingInventory"
                    class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-24 text-center">
                    <div
                        class="animate-spin h-12 w-12 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4">
                    </div>
                    <p class="text-lg font-medium text-slate-600">Cargando inventario...</p>
                </div>
                <div v-else-if="filteredInventory.length === 0"
                    class="text-center py-20 bg-white rounded-2xl shadow-sm border border-slate-200">
                    <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <p class="text-slate-500 font-medium">No hay vehículos en el inventario</p>
                </div>
                <div v-else class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="vehicle in filteredInventory" :key="vehicle.id"
                        class="bg-white overflow-hidden shadow-sm hover:shadow-xl rounded-2xl border border-slate-200 transition-all duration-300 p-6 group">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-bold text-indigo-700 bg-indigo-100 px-3 py-1 rounded-lg">{{
                                vehicle.tipo }}</span>
                            <span class="text-xs font-bold px-3 py-1 rounded-lg"
                                :class="vehicle.estado === 'Operativo' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'">{{
                                vehicle.estado }}</span>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">{{
                            vehicle.marca }} {{ vehicle.modelo }}</h3>
                        <p class="text-lg font-bold text-slate-600 mt-1">{{ vehicle.placa }}</p>
                        <p class="text-sm text-slate-400 mt-2">{{ vehicle.color }} | {{ vehicle.anio }}</p>
                        <button @click="openVehicleModal(vehicle)"
                            class="mt-4 w-full py-2.5 text-indigo-600 border-2 border-indigo-200 rounded-xl hover:bg-indigo-50 font-bold text-sm transition-all">
                            Editar Vehículo
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB: MAINTENANCE -->
            <div v-if="activeTab === 'maintenance'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="loadingMaintenance" class="px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-emerald-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando gastos de mantenimiento...</p>
                    </div>
                    <div v-else-if="maintenances.length === 0" class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <p class="text-slate-500 font-medium">No hay gastos de mantenimiento registrados</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-emerald-50 to-teal-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-emerald-700 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-emerald-700 uppercase tracking-wider">
                                        Vehículo</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-emerald-700 uppercase tracking-wider">
                                        Detalle</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-emerald-700 uppercase tracking-wider">
                                        Costo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="expense in maintenances" :key="expense.id"
                                    class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ expense.fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">{{
                                        expense.vehicle_name }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ expense.detalle }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-emerald-600">S/ {{
                                        expense.costo }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: HANDOVER -->
            <div v-if="activeTab === 'handover'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="loadingHandovers" class="px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-amber-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando actas de entrega...</p>
                    </div>
                    <div v-else-if="handovers.length === 0" class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-slate-500 font-medium">No hay actas registradas</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-amber-50 to-orange-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Placa</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Entidad</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Kilometraje</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-amber-700 uppercase tracking-wider">
                                        Recepciona</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="handover in handovers" :key="handover.id"
                                    class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ handover.fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-amber-600">{{
                                        handover.placa }}</td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ handover.entidad }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{
                                        handover.kilometraje }} km</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{
                                        handover.recepciona }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB: SERVICE REQUIREMENTS -->
            <div v-if="activeTab === 'service'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="loadingServiceReqs" class="px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-pink-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando requerimientos...</p>
                    </div>
                    <div v-else-if="serviceReqs.length === 0" class="text-center py-20">
                        <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        </svg>
                        <p class="text-slate-500 font-medium">No hay requerimientos registrados</p>
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-pink-50 to-rose-50">
                                <tr>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-pink-700 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-pink-700 uppercase tracking-wider">
                                        Vehículo</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-pink-700 uppercase tracking-wider">
                                        Conductor</th>
                                    <th
                                        class="px-6 py-4 text-left text-xs font-bold text-pink-700 uppercase tracking-wider">
                                        Motivo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                <tr v-for="req in serviceReqs" :key="req.id"
                                    class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ req.created_at }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-pink-600">{{
                                        req.vehicle_name }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ req.conductor }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-600">{{ req.motivo }}</td>
                                </tr>
                            </tbody>
                        </table>
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
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup>
import { ref, computed, onMounted } from 'vue';
import VehicleModal from '@/Components/Vehicles/Inventory/VehicleModal.vue';
import CommissionModal from '@/Components/Vehicles/Commissions/CommissionModal.vue';
import MaintenanceModal from '@/Components/Vehicles/Maintenance/MaintenanceModal.vue';
import HandoverModal from '@/Components/Vehicles/Handovers/HandoverModal.vue';
import ServiceReqModal from '@/Components/Vehicles/ServiceRequirements/ServiceReqModal.vue';
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
