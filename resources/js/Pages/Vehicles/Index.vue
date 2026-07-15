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
                        Registro y seguimiento de autorizaciones de salida, inventario, gastos, actas y servicios
                    </p>
                </div>
                <div class="flex gap-3">
                    <a href="/dashboard"
                        class="inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </a>
                    <!-- Dynamic action button based on active tab -->
                    <button v-if="activeTab === 'commissions'" @click="openCommissionModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-blue-600/20 text-white bg-gradient-to-r from-blue-600 to-sky-500 hover:from-blue-700 hover:to-sky-600 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nueva Autorización de Salida
                    </button>
                    <button v-if="activeTab === 'inventory'" @click="openVehicleModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-indigo-600/20 text-white bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 focus:outline-none focus:ring-4 focus:ring-indigo-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Registrar Vehículo
                    </button>
                    <button v-if="activeTab === 'maintenance'" @click="openMaintenanceModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-emerald-600/20 text-white bg-gradient-to-r from-emerald-600 to-teal-500 hover:from-emerald-700 hover:to-teal-600 focus:outline-none focus:ring-4 focus:ring-emerald-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Registrar Gasto
                    </button>
                    <button v-if="activeTab === 'handover'" @click="openHandoverModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-amber-600/20 text-white bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 focus:outline-none focus:ring-4 focus:ring-amber-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <FilePlus class="w-5 h-5 mr-2" />
                        Nueva Acta
                    </button>
                    <button v-if="activeTab === 'service'" @click="openServiceReqModal()"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-lg shadow-pink-600/20 text-white bg-gradient-to-r from-pink-600 to-rose-500 hover:from-pink-700 hover:to-rose-600 focus:outline-none focus:ring-4 focus:ring-pink-300 transition-all duration-300 transform hover:scale-105 active:scale-95">
                        <Plus class="w-5 h-5 mr-2" />
                        Nuevo Requerimiento
                    </button>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8">
                <nav class="-mb-px flex space-x-8 overflow-x-auto">
                    <button v-if="canViewTab('commissions')" @click="activeTab = 'commissions'"
                        :class="[activeTab === 'commissions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <MapPin class="w-5 h-5" />
                        Autorización Salida de Vehículos
                    </button>
                    <button v-if="canViewTab('inventory')" @click="activeTab = 'inventory'"
                        :class="[activeTab === 'inventory' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <Car class="w-5 h-5" />
                        Inventario
                    </button>
                    <button v-if="canViewTab('maintenance')" @click="activeTab = 'maintenance'"
                        :class="[activeTab === 'maintenance' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <Wrench class="w-5 h-5" />
                        Gastos
                    </button>
                    <button v-if="canViewTab('handover')" @click="activeTab = 'handover'"
                        :class="[activeTab === 'handover' ? 'border-amber-600 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <FileText class="w-5 h-5" />
                        Actas de Entrega
                    </button>
                    <button v-if="canViewTab('service')" @click="activeTab = 'service'"
                        :class="[activeTab === 'service' ? 'border-pink-600 text-pink-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm flex items-center gap-2 transition-all duration-200']">
                        <Settings class="w-5 h-5" />
                        Requerimientos
                    </button>
                </nav>
            </div>

            <!-- TAB: COMMISSIONS -->
            <div v-if="activeTab === 'commissions'" class="space-y-6">
                <!-- Search Filter -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="relative max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                        <input type="text" v-model="searchCommission"
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Buscar por solicitante, conductor o placa...">
                    </div>
                </div>

                <!-- Commission Cards -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div v-if="loadingCommissions" class="px-6 py-24 text-center">
                        <div
                            class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4">
                        </div>
                        <p class="text-lg font-medium text-slate-600">Cargando autorizaciones...</p>
                    </div>
                    <div v-else-if="filteredCommissions.length === 0" class="text-center py-20">
                        <MapPin class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                        <p class="text-slate-500 font-medium">No hay autorizaciones de salida registradas</p>
                        <p class="text-slate-400 text-sm mt-1">Haz clic en "Nueva Autorización de Salida" para agregar una</p>
                    </div>
                    <div v-else>
                        <div class="overflow-x-auto">
                            <table class="w-full divide-y divide-slate-200 table-fixed">
                                <colgroup>
                                    <col class="w-32" />       <!-- Nº Autorización -->
                                    <col class="w-48" />       <!-- Solicitante -->
                                    <col class="w-28" />       <!-- Fecha / Hora -->
                                    <col class="w-44" />       <!-- Destino -->
                                    <col class="w-48" />       <!-- Vehículo / Conductor -->
                                    <col class="w-28" />       <!-- Estado -->
                                    <col class="w-36" />       <!-- Acciones -->
                                </colgroup>
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Nº Autorización
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Solicitante
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Fecha / Hora
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Destino
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Vehículo / Conductor
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Estado
                                        </th>
                                        <th scope="col"
                                            class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr v-for="commission in paginatedCommissions" :key="commission.id"
                                        class="hover:bg-slate-50 transition-colors duration-200">
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="text-xs font-bold text-blue-600">
                                                Nº {{ String(commission.numero).padStart(3, '0') }}-{{ commission.anio }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="text-xs font-bold text-slate-900 truncate" :title="commission.solicitante">
                                                {{ commission.solicitante }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-semibold text-slate-700 flex items-center gap-1">
                                                    <Calendar class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                    {{ formatDate(commission.dia) }}
                                                </span>
                                                <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                                                    <Clock class="w-3 h-3 text-slate-400 shrink-0" />
                                                    {{ commission.hora }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="text-xs font-semibold text-slate-700 truncate flex items-center gap-1" :title="commission.lugar">
                                                <MapPin class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                {{ commission.lugar }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-bold text-slate-800 flex items-center gap-1">
                                                    <Car class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                                    {{ commission.placa }}
                                                </span>
                                                <span class="text-[10px] text-slate-500 font-medium truncate" :title="commission.conductor">
                                                    {{ commission.conductor }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 text-[10px] font-bold rounded-full inline-block"
                                                :class="getStatusClass(commission.estado)">
                                                {{ commission.estado }}
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 whitespace-nowrap text-xs font-medium">
                                            <div class="flex flex-col gap-1.5 items-start">
                                                <button @click="openCommissionDetailModal(commission)"
                                                    class="cursor-pointer text-violet-600 hover:text-violet-900 bg-violet-50 hover:bg-violet-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap">
                                                    <Eye class="w-3.5 h-3.5" />
                                                    Detalles
                                                </button>
                                                <button @click="printCommission(commission)"
                                                    class="cursor-pointer text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap">
                                                    <Printer class="w-3.5 h-3.5" />
                                                    PDF
                                                </button>
                                                <button @click="openCommissionModal(commission)"
                                                    class="cursor-pointer text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap">
                                                    <Pencil class="w-3.5 h-3.5" />
                                                    Gestionar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <ClientPagination
                            :total-items="filteredCommissions.length"
                            v-model:current-page="commissionsPage"
                            v-model:per-page="commissionsPerPage"
                        />
                    </div>
                </div>
            </div>

            <!-- TAB: INVENTORY -->
            <div v-if="activeTab === 'inventory'" class="space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4">
                    <div class="relative max-w-md">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
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
                    <Car class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                    <p class="text-slate-500 font-medium">No hay vehículos en el inventario</p>
                </div>
                <div v-else class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="vehicle in paginatedInventory" :key="vehicle.id"
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
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <ClientPagination
                            :total-items="filteredInventory.length"
                            v-model:current-page="inventoryPage"
                            v-model:per-page="inventoryPerPage"
                        />
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
                        <Wrench class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                        <p class="text-slate-500 font-medium">No hay gastos de mantenimiento registrados</p>
                    </div>
                    <div v-else>
                        <div class="overflow-x-auto">
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
                                    <tr v-for="expense in paginatedMaintenances" :key="expense.id"
                                        class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ formatDate(expense.fecha) }}
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
                        <ClientPagination
                            :total-items="maintenances.length"
                            v-model:current-page="maintenancePage"
                            v-model:per-page="maintenancePerPage"
                        />
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
                        <FileText class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                        <p class="text-slate-500 font-medium">No hay actas registradas</p>
                    </div>
                    <div v-else>
                        <div class="overflow-x-auto">
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
                                    <tr v-for="handover in paginatedHandovers" :key="handover.id"
                                        class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ formatDate(handover.fecha) }}
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
                        <ClientPagination
                            :total-items="handovers.length"
                            v-model:current-page="handoverPage"
                            v-model:per-page="handoverPerPage"
                        />
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
                        <Settings class="w-16 h-16 mx-auto text-slate-300 mb-4" />
                        <p class="text-slate-500 font-medium">No hay requerimientos registrados</p>
                    </div>
                    <div v-else>
                        <div class="overflow-x-auto">
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
                                    <tr v-for="req in paginatedServiceReqs" :key="req.id"
                                        class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ formatDate(req.created_at) }}
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
                        <ClientPagination
                            :total-items="serviceReqs.length"
                            v-model:current-page="servicePage"
                            v-model:per-page="servicePerPage"
                        />
                    </div>
                </div>
            </div>

            <!-- Modals -->
            <VehicleModal v-if="showVehicleModal" :vehicle="selectedVehicle" :vehicles="inventory"
                @close="showVehicleModal = false" @saved="onVehicleSaved" />
            <CommissionModal v-if="showCommissionModal" :commission="selectedCommission" :vehicles="inventory"
                :employees="props.employees" :drivers="props.drivers" @close="showCommissionModal = false"
                @saved="onCommissionSaved" />
            <CommissionDetailModal v-if="showCommissionDetailModal" :commission="selectedCommission"
                @close="showCommissionDetailModal = false" />
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
import { ref, computed, onMounted, watch } from 'vue';
import { useTabPermission } from '@/composables/useTabPermission';
import { ArrowLeft, Plus, FilePlus, MapPin, Car, Wrench, FileText, Settings, Search, Printer, Pencil, Calendar, Clock, Eye } from 'lucide-vue-next';
import VehicleModal from '@/Components/Vehicles/Inventory/VehicleModal.vue';
import CommissionModal from '@/Components/Vehicles/Commissions/CommissionModal.vue';
import CommissionDetailModal from '@/Components/Vehicles/Commissions/CommissionDetailModal.vue';
import MaintenanceModal from '@/Components/Vehicles/Maintenance/MaintenanceModal.vue';
import HandoverModal from '@/Components/Vehicles/Handovers/HandoverModal.vue';
import ServiceReqModal from '@/Components/Vehicles/ServiceRequirements/ServiceReqModal.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import axios from 'axios';

const props = defineProps({ employees: { type: Array, default: () => [] }, drivers: { type: Array, default: () => [] } });

const formatDate = (dateString) => {
    if (!dateString) return '';
    const datePart = dateString.includes(' ') 
        ? dateString.split(' ')[0] 
        : (dateString.includes('T') ? dateString.split('T')[0] : dateString);
    if (datePart.includes('-')) {
        return datePart.split('-').reverse().join('/');
    }
    return dateString;
};

const { canViewTab, firstAllowedTab } = useTabPermission('vehiculos', ['commissions', 'inventory', 'maintenance', 'handover', 'service']);
const activeTab = ref(firstAllowedTab.value);

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
const showCommissionDetailModal = ref(false);
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
        c.solicitante?.toLowerCase().includes(q) ||
        c.conductor?.toLowerCase().includes(q) ||
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

// Pagination refs
const commissionsPage = ref(1);
const commissionsPerPage = ref(10);
const inventoryPage = ref(1);
const inventoryPerPage = ref(10);
const maintenancePage = ref(1);
const maintenancePerPage = ref(10);
const handoverPage = ref(1);
const handoverPerPage = ref(10);
const servicePage = ref(1);
const servicePerPage = ref(10);

// Reset pages on search/data changes
watch([searchCommission, commissions], () => { commissionsPage.value = 1; });
watch([searchInventory, inventory], () => { inventoryPage.value = 1; });
watch(maintenances, () => { maintenancePage.value = 1; });
watch(handovers, () => { handoverPage.value = 1; });
watch(serviceReqs, () => { servicePage.value = 1; });
watch(activeTab, () => {
    commissionsPage.value = 1;
    inventoryPage.value = 1;
    maintenancePage.value = 1;
    handoverPage.value = 1;
    servicePage.value = 1;
});

const paginatedCommissions = computed(() => {
    const start = (commissionsPage.value - 1) * commissionsPerPage.value;
    const end = start + commissionsPerPage.value;
    return filteredCommissions.value.slice(start, end);
});

const paginatedInventory = computed(() => {
    const start = (inventoryPage.value - 1) * inventoryPerPage.value;
    const end = start + inventoryPerPage.value;
    return filteredInventory.value.slice(start, end);
});

const paginatedMaintenances = computed(() => {
    const start = (maintenancePage.value - 1) * maintenancePerPage.value;
    const end = start + maintenancePerPage.value;
    return maintenances.value.slice(start, end);
});

const paginatedHandovers = computed(() => {
    const start = (handoverPage.value - 1) * handoverPerPage.value;
    const end = start + handoverPerPage.value;
    return handovers.value.slice(start, end);
});

const paginatedServiceReqs = computed(() => {
    const start = (servicePage.value - 1) * servicePerPage.value;
    const end = start + servicePerPage.value;
    return serviceReqs.value.slice(start, end);
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

const openCommissionDetailModal = (commission) => {
    selectedCommission.value = commission;
    showCommissionDetailModal.value = true;
};

const printCommission = (commission) => {
    window.open(`/vehicles/commissions/${commission.id}/pdf`, '_blank');
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
