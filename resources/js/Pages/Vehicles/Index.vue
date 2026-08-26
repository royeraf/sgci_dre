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
                        class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <ArrowLeft class="w-4 h-4 mr-2" />
                        Volver
                    </a>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex overflow-x-auto">
                    <button v-if="canViewTab('commissions')" @click="activeTab = 'commissions'" :class="[
                        activeTab === 'commissions' ? 'text-blue-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <MapPin class="w-5 h-5" />
                        Autorización Salida de Vehículos
                    </button>
                    <button v-if="canViewTab('inventory')" @click="activeTab = 'inventory'" :class="[
                        activeTab === 'inventory' ? 'text-indigo-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Car class="w-5 h-5" />
                        Inventario
                    </button>
                    <button v-if="canViewTab('maintenance')" @click="activeTab = 'maintenance'" :class="[
                        activeTab === 'maintenance' ? 'text-emerald-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Wrench class="w-5 h-5" />
                        Gastos
                    </button>
                    <button v-if="canViewTab('handover')" @click="activeTab = 'handover'" :class="[
                        activeTab === 'handover' ? 'text-amber-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <FileText class="w-5 h-5" />
                        Actas de Entrega
                    </button>
                    <button v-if="canViewTab('service')" @click="activeTab = 'service'" :class="[
                        activeTab === 'service' ? 'text-pink-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Settings class="w-5 h-5" />
                        Requerimientos
                    </button>
                    <button v-if="canViewTab('drivers')" @click="activeTab = 'drivers'" :class="[
                        activeTab === 'drivers' ? 'text-cyan-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <IdCard class="w-5 h-5" />
                        Conductores
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content with Transition -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">
                    <!-- TAB: COMMISSIONS -->
                    <div v-if="activeTab === 'commissions'">
                        <BaseTableCard title="Autorizaciones de Salida"
                            description="Registro de salidas de vehículos autorizadas">
                            <template #actions>
                                <button @click="commissionsFiltersVisible = !commissionsFiltersVisible"
                                    class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                                    <SlidersHorizontal class="w-4 h-4" />
                                    Filtros
                                    <ChevronDown class="w-4 h-4 transition-transform duration-300"
                                        :class="{ 'rotate-180': commissionsFiltersVisible }" />
                                </button>
                                <button @click="openCommissionModal()"
                                    class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-blue-600/20 text-white bg-blue-600 hover:bg-blue-700 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Nueva Autorización de Salida
                                </button>
                            </template>

                            <!-- Filters toggle + collapsible panel -->
                            <div class="filters-collapse bg-slate-50 border-b border-slate-100"
                                :class="{ 'filters-collapse--open': commissionsFiltersVisible }">
                                <div class="p-4 sm:p-5">
                                    <div class="relative max-w-md">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                                        <input type="text" v-model="searchCommission"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 text-sm outline-none cursor-pointer"
                                            placeholder="Buscar por solicitante, conductor o placa...">
                                    </div>
                                </div>
                            </div>

                            <div v-if="loadingCommissions" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-blue-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando autorizaciones...</p>
                            </div>
                            <div v-else-if="filteredCommissions.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <MapPin class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay autorizaciones de salida
                                        registradas</h3>
                                    <p class="text-sm text-slate-500">Haz clic en "Nueva Autorización de Salida" para
                                        agregar una.</p>
                                </div>
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
                                                class="hover:bg-blue-50 transition-colors duration-200">
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
                                                        {{ getStatusLabel(commission.estado) }}
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
                                                        <button v-if="commission.can_authorize" @click="openSigningModal('autorizar', commission)"
                                                            :disabled="approvalProcessing"
                                                            class="cursor-pointer text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap disabled:opacity-50">
                                                            <CheckCircle class="w-3.5 h-3.5" />
                                                            Autorizar
                                                        </button>
                                                        <button v-if="commission.can_authorize" @click="openRejectModal(commission)"
                                                            :disabled="approvalProcessing"
                                                            class="cursor-pointer text-red-600 hover:text-red-900 bg-red-50 hover:bg-red-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap disabled:opacity-50">
                                                            <XCircle class="w-3.5 h-3.5" />
                                                            Rechazar
                                                        </button>
                                                        <button v-if="commission.can_confirm" @click="openSigningModal('confirmar', commission)"
                                                            :disabled="approvalProcessing"
                                                            class="cursor-pointer text-cyan-600 hover:text-cyan-900 bg-cyan-50 hover:bg-cyan-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap disabled:opacity-50">
                                                            <CheckCircle class="w-3.5 h-3.5" />
                                                            Confirmar
                                                        </button>
                                                        <button v-if="commission.estado === 'CONFIRMADA'" @click="openControlModal(commission)"
                                                            class="cursor-pointer text-blue-600 hover:text-blue-900 bg-blue-50 hover:bg-blue-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap">
                                                            <Truck class="w-3.5 h-3.5" />
                                                            Registrar Salida
                                                        </button>
                                                        <button v-if="commission.estado === 'EN_COMISION'" @click="openControlModal(commission)"
                                                            class="cursor-pointer text-emerald-600 hover:text-emerald-900 bg-emerald-50 hover:bg-emerald-100 px-2 py-1 rounded-xl font-bold transition-all flex items-center gap-1 text-xs whitespace-nowrap">
                                                            <Truck class="w-3.5 h-3.5" />
                                                            Registrar Retorno
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
                        </BaseTableCard>
                    </div>

                    <!-- TAB: INVENTORY -->
                    <div v-else-if="activeTab === 'inventory'">
                        <BaseTableCard title="Inventario de Vehículos"
                            description="Vehículos registrados en la institución">
                            <template #actions>
                                <button @click="inventoryFiltersVisible = !inventoryFiltersVisible"
                                    class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                                    <SlidersHorizontal class="w-4 h-4" />
                                    Filtros
                                    <ChevronDown class="w-4 h-4 transition-transform duration-300"
                                        :class="{ 'rotate-180': inventoryFiltersVisible }" />
                                </button>
                                <button @click="openVehicleModal()"
                                    class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-indigo-600/20 text-white bg-indigo-600 hover:bg-indigo-700 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Registrar Vehículo
                                </button>
                            </template>

                            <!-- Filters toggle + collapsible panel -->
                            <div class="filters-collapse bg-slate-50 border-b border-slate-100"
                                :class="{ 'filters-collapse--open': inventoryFiltersVisible }">
                                <div class="p-4 sm:p-5">
                                    <div class="relative max-w-md">
                                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                                        <input type="text" v-model="searchInventory"
                                            class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 text-sm outline-none cursor-pointer"
                                            placeholder="Buscar vehículo por placa, marca o modelo...">
                                    </div>
                                </div>
                            </div>

                            <div v-if="loadingInventory" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-indigo-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando inventario...</p>
                            </div>
                            <div v-else-if="filteredInventory.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <Car class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay vehículos en el
                                        inventario</h3>
                                    <p class="text-sm text-slate-500">Haz clic en "Registrar Vehículo" para agregar
                                        uno.</p>
                                </div>
                            </div>
                            <div v-else class="p-4 sm:p-5 space-y-6">
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
                                            class="cursor-pointer mt-4 w-full py-2.5 text-indigo-600 border-2 border-indigo-200 rounded-xl hover:bg-indigo-50 font-bold text-sm transition-all">
                                            Editar Vehículo
                                        </button>
                                    </div>
                                </div>
                                <ClientPagination
                                    :total-items="filteredInventory.length"
                                    v-model:current-page="inventoryPage"
                                    v-model:per-page="inventoryPerPage"
                                />
                            </div>
                        </BaseTableCard>
                    </div>

                    <!-- TAB: MAINTENANCE -->
                    <div v-else-if="activeTab === 'maintenance'">
                        <BaseTableCard title="Gastos de Mantenimiento"
                            description="Historial de gastos por vehículo">
                            <template #actions>
                                <button @click="openMaintenanceModal()"
                                    class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-emerald-600/20 text-white bg-emerald-600 hover:bg-emerald-700 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Registrar Gasto
                                </button>
                            </template>

                            <div v-if="loadingMaintenance" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-emerald-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando gastos de mantenimiento...</p>
                            </div>
                            <div v-else-if="maintenances.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <Wrench class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay gastos de mantenimiento
                                        registrados</h3>
                                    <p class="text-sm text-slate-500">Haz clic en "Registrar Gasto" para agregar uno.
                                    </p>
                                </div>
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="w-full divide-y divide-slate-200 table-fixed">
                                        <colgroup>
                                            <col class="w-28" />       <!-- Fecha -->
                                            <col class="w-48" />       <!-- Vehículo -->
                                            <col />                    <!-- Detalle -->
                                            <col class="w-32" />       <!-- Costo -->
                                        </colgroup>
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Fecha
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Vehículo
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Detalle
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Costo
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            <tr v-for="expense in paginatedMaintenances" :key="expense.id"
                                                class="hover:bg-emerald-50 transition-colors duration-200">
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600">{{ formatDate(expense.fecha) }}
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-slate-800">{{
                                                    expense.vehicle_name }}</td>
                                                <td class="px-3 py-3 text-xs text-slate-600 truncate">{{ expense.detalle }}</td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-emerald-600">S/ {{
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
                        </BaseTableCard>
                    </div>

                    <!-- TAB: HANDOVER -->
                    <div v-else-if="activeTab === 'handover'">
                        <BaseTableCard title="Actas de Entrega"
                            description="Entregas y recepciones de vehículos">
                            <template #actions>
                                <button @click="openHandoverModal()"
                                    class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-amber-600/20 text-white bg-amber-600 hover:bg-amber-700 transition-all duration-200">
                                    <FilePlus class="w-4 h-4 mr-1.5" />
                                    Nueva Acta
                                </button>
                            </template>

                            <div v-if="loadingHandovers" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-amber-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando actas de entrega...</p>
                            </div>
                            <div v-else-if="handovers.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <FileText class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay actas registradas</h3>
                                    <p class="text-sm text-slate-500">Haz clic en "Nueva Acta" para agregar una.</p>
                                </div>
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="w-full divide-y divide-slate-200 table-fixed">
                                        <colgroup>
                                            <col class="w-28" />       <!-- Fecha -->
                                            <col class="w-28" />       <!-- Placa -->
                                            <col />                    <!-- Entidad -->
                                            <col class="w-32" />       <!-- Kilometraje -->
                                            <col class="w-40" />       <!-- Recepciona -->
                                        </colgroup>
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Fecha
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Placa
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Entidad
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Kilometraje
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Recepciona
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            <tr v-for="handover in paginatedHandovers" :key="handover.id"
                                                class="hover:bg-amber-50 transition-colors duration-200">
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600">{{ formatDate(handover.fecha) }}
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-amber-600">{{
                                                    handover.placa }}</td>
                                                <td class="px-3 py-3 text-xs text-slate-600 truncate">{{ handover.entidad }}</td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600">{{
                                                    handover.kilometraje }} km</td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600 truncate">{{
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
                        </BaseTableCard>
                    </div>

                    <!-- TAB: SERVICE REQUIREMENTS -->
                    <div v-else-if="activeTab === 'service'">
                        <BaseTableCard title="Requerimientos de Servicio"
                            description="Solicitudes de servicio registradas">
                            <template #actions>
                                <button @click="openServiceReqModal()"
                                    class="cursor-pointer inline-flex items-center justify-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-pink-600/20 text-white bg-pink-600 hover:bg-pink-700 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Nuevo Requerimiento
                                </button>
                            </template>

                            <div v-if="loadingServiceReqs" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-pink-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando requerimientos...</p>
                            </div>
                            <div v-else-if="serviceReqs.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <Settings class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay requerimientos
                                        registrados</h3>
                                    <p class="text-sm text-slate-500">Haz clic en "Nuevo Requerimiento" para agregar
                                        uno.</p>
                                </div>
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="w-full divide-y divide-slate-200 table-fixed">
                                        <colgroup>
                                            <col class="w-28" />       <!-- Fecha -->
                                            <col class="w-48" />       <!-- Vehículo -->
                                            <col class="w-48" />       <!-- Conductor -->
                                            <col />                    <!-- Motivo -->
                                        </colgroup>
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Fecha
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Vehículo
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Conductor
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Motivo
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            <tr v-for="req in paginatedServiceReqs" :key="req.id"
                                                class="hover:bg-pink-50 transition-colors duration-200">
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600">{{ formatDate(req.created_at) }}
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs font-bold text-pink-600">{{
                                                    req.vehicle_name }}</td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs text-slate-600">{{ req.conductor }}
                                                </td>
                                                <td class="px-3 py-3 text-xs text-slate-600 truncate">{{ req.motivo }}</td>
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
                        </BaseTableCard>
                    </div>

                    <!-- TAB: DRIVERS -->
                    <div v-else-if="activeTab === 'drivers'">
                        <BaseTableCard title="Conductores"
                            description="Licencias de conducir del personal con cargo o encargatura CHOFER II">
                            <div v-if="loadingDrivers" class="px-6 py-24 text-center">
                                <div
                                    class="animate-spin h-12 w-12 border-4 border-cyan-600 border-t-transparent rounded-full mx-auto mb-4">
                                </div>
                                <p class="text-lg font-medium text-slate-600">Cargando conductores...</p>
                            </div>
                            <div v-else-if="driversList.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <IdCard class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">No hay conductores registrados</h3>
                                    <p class="text-sm text-slate-500">No hay personal activo con cargo o encargatura
                                        CHOFER II.</p>
                                </div>
                            </div>
                            <div v-else>
                                <div class="overflow-x-auto">
                                    <table class="w-full divide-y divide-slate-200 table-fixed">
                                        <colgroup>
                                            <col class="w-56" />       <!-- Conductor -->
                                            <col class="w-52" />       <!-- Cargo / Encargatura -->
                                            <col class="w-36" />       <!-- Nº Licencia -->
                                            <col class="w-24" />       <!-- Categoría -->
                                            <col class="w-32" />       <!-- Revalidación -->
                                            <col class="w-28" />       <!-- Acciones -->
                                        </colgroup>
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Conductor
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Cargo / Encargatura
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Nº Licencia
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Categoría
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Revalidación
                                                </th>
                                                <th scope="col"
                                                    class="px-3 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                    Acciones
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-slate-100">
                                            <tr v-for="driver in driversList" :key="driver.id"
                                                class="hover:bg-cyan-50 transition-colors duration-200">
                                                <td class="px-3 py-3">
                                                    <div class="text-xs font-bold text-slate-900 truncate">{{ driver.nombre_completo }}</div>
                                                    <div class="text-[10px] text-slate-400 font-mono">{{ driver.dni }}</div>
                                                </td>
                                                <td class="px-3 py-3">
                                                    <div class="text-xs text-slate-700 truncate">{{ driver.cargo || '-' }}</div>
                                                    <div v-if="driver.encargatura" class="text-[10px] text-amber-600 truncate">
                                                        Encargatura: {{ driver.encargatura }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-3 text-xs text-slate-700 font-mono">
                                                    {{ driver.licencia_numero || 'Sin registrar' }}
                                                </td>
                                                <td class="px-3 py-3 text-xs text-slate-700">
                                                    {{ driver.licencia_categoria || '-' }}
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap">
                                                    <span v-if="driver.licencia_vencimiento" class="px-2.5 py-0.5 text-[10px] font-bold rounded-full inline-block"
                                                        :class="driver.licencia_vencida ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'">
                                                        {{ formatDate(driver.licencia_vencimiento) }}
                                                        <span v-if="driver.licencia_vencida"> · Vencida</span>
                                                    </span>
                                                    <span v-else class="text-xs text-slate-400">-</span>
                                                </td>
                                                <td class="px-3 py-3 whitespace-nowrap text-xs font-medium">
                                                    <button @click="openDriverLicenseModal(driver)"
                                                        class="cursor-pointer text-cyan-600 hover:text-cyan-900 bg-cyan-50 hover:bg-cyan-100 px-2 py-1 rounded-xl font-bold transition-all inline-flex items-center gap-1 text-xs whitespace-nowrap">
                                                        <Pencil class="w-3.5 h-3.5" />
                                                        Editar
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </BaseTableCard>
                    </div>
                </div>
            </Transition>

            <!-- Modals -->
            <VehicleModal v-if="showVehicleModal" :vehicle="selectedVehicle" :vehicles="inventory"
                @close="showVehicleModal = false" @saved="onVehicleSaved" />
            <CommissionModal v-if="showCommissionModal" :commission="selectedCommission" :vehicles="inventory"
                :drivers="driversList" :employees="employeesList" @close="showCommissionModal = false"
                @saved="onCommissionSaved" />
            <CommissionDetailModal v-if="showCommissionDetailModal" :commission="selectedCommission"
                @close="showCommissionDetailModal = false" />
            <MaintenanceModal v-if="showMaintenanceModal" :vehicles="inventory" @close="showMaintenanceModal = false"
                @saved="onMaintenanceSaved" />
            <HandoverModal v-if="showHandoverModal" @close="showHandoverModal = false" @saved="onHandoverSaved" />
            <ServiceReqModal v-if="showServiceReqModal" :vehicles="inventory" @close="showServiceReqModal = false"
                @saved="onServiceReqSaved" />
            <DriverLicenseModal v-if="showDriverLicenseModal" :driver="selectedDriver"
                @close="showDriverLicenseModal = false" @saved="onDriverLicenseSaved" />

            <CommissionSigningModal v-if="showSigningModal" ref="signingModalRef" :mode="signingMode"
                :commission="signingTargetCommission" :processing="approvalProcessing"
                @close="showSigningModal = false" @submit="handleSigningSubmit" />

            <CommissionControlModal v-if="showControlModal" :commission="controlTargetCommission"
                @close="showControlModal = false" @saved="onControlSaved" />

            <!-- Reject commission modal -->
            <Teleport to="body">
                <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRejectModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Rechazar Autorización de Salida</h3>
                        <p class="text-sm text-slate-600 mb-4">
                            Nº <strong class="text-blue-600">{{ String(rejectTargetCommission?.numero).padStart(3, '0') }}-{{ rejectTargetCommission?.anio }}</strong>
                            de <strong>{{ rejectTargetCommission?.solicitante }}</strong>
                        </p>
                        <div class="mb-4">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo del rechazo *</label>
                            <textarea v-model="rejectComentario" rows="3"
                                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                                :class="{ 'border-red-300': rejectError }"
                                placeholder="Indique el motivo del rechazo..."></textarea>
                            <p v-if="rejectError" class="mt-1 text-xs text-red-500">{{ rejectError }}</p>
                        </div>
                        <div class="flex justify-end gap-2">
                            <button @click="showRejectModal = false"
                                class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                                Cancelar
                            </button>
                            <button @click="handleReject" :disabled="approvalProcessing"
                                class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                                <Loader2 v-if="approvalProcessing" class="h-4 w-4 animate-spin" />
                                Confirmar Rechazo
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>
        </div>
    </div>
</template>

<script>
import MainLayout from '@/Layouts/MainLayout.vue';
export default { layout: MainLayout }
</script>

<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue';
import { useTabPermission } from '@/composables/useTabPermission';
import { ArrowLeft, Plus, FilePlus, MapPin, Car, Wrench, FileText, Settings, Search, Printer, Pencil, Calendar, Clock, Eye, SlidersHorizontal, ChevronDown, CheckCircle, XCircle, Loader2, IdCard, Truck } from 'lucide-vue-next';
import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import VehicleModal from '@/Components/Vehicles/Inventory/VehicleModal.vue';
import CommissionModal from '@/Components/Vehicles/Commissions/CommissionModal.vue';
import CommissionDetailModal from '@/Components/Vehicles/Commissions/CommissionDetailModal.vue';
import CommissionSigningModal from '@/Components/Vehicles/Commissions/CommissionSigningModal.vue';
import CommissionControlModal from '@/Components/Vehicles/Commissions/CommissionControlModal.vue';
import MaintenanceModal from '@/Components/Vehicles/Maintenance/MaintenanceModal.vue';
import HandoverModal from '@/Components/Vehicles/Handovers/HandoverModal.vue';
import ServiceReqModal from '@/Components/Vehicles/ServiceRequirements/ServiceReqModal.vue';
import DriverLicenseModal from '@/Components/Vehicles/Drivers/DriverLicenseModal.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import { useCommissionApproval } from '@/Composables/useCommissionApproval';
import { usePdfViewer } from '@/composables/usePdfViewer';
import axios from 'axios';

const props = defineProps({
    drivers: { type: Array, default: () => [] },
    canAuthorize: { type: Boolean, default: false },
    currentEmployeeId: { type: String, default: null },
});

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

const { canViewTab, firstAllowedTab } = useTabPermission('vehiculos', ['commissions', 'inventory', 'maintenance', 'handover', 'service', 'drivers']);
const activeTab = ref(firstAllowedTab.value);

// Tab indicator logic
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    switch (tab) {
        case 'commissions': return '#2563eb'; // blue-600
        case 'inventory': return '#4f46e5'; // indigo-600
        case 'maintenance': return '#059669'; // emerald-600
        case 'handover': return '#d97706'; // amber-600
        case 'service': return '#db2777'; // pink-600
        case 'drivers': return '#0891b2'; // cyan-600
        default: return '#2563eb';
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

// Filters visibility (persisted)
const COMMISSIONS_FILTERS_KEY = 'vehicles_commissions_filters_open';
const INVENTORY_FILTERS_KEY = 'vehicles_inventory_filters_open';
const commissionsFiltersVisible = ref(localStorage.getItem(COMMISSIONS_FILTERS_KEY) === 'true');
const inventoryFiltersVisible = ref(localStorage.getItem(INVENTORY_FILTERS_KEY) === 'true');
watch(commissionsFiltersVisible, (val) => localStorage.setItem(COMMISSIONS_FILTERS_KEY, String(val)));
watch(inventoryFiltersVisible, (val) => localStorage.setItem(INVENTORY_FILTERS_KEY, String(val)));

// Data
const inventory = ref([]);
const commissions = ref([]);
const maintenances = ref([]);
const handovers = ref([]);
const serviceReqs = ref([]);
// Seeded from the initial Inertia props so the conductor select in
// CommissionModal has data immediately; refreshed via fetchDrivers() below.
const driversList = ref([...props.drivers]);
// Empleados activos, para el selector múltiple de pasajeros en CommissionModal.
const employeesList = ref([]);

// Loading states
const loadingInventory = ref(false);
const loadingCommissions = ref(false);
const loadingMaintenance = ref(false);
const loadingHandovers = ref(false);
const loadingServiceReqs = ref(false);
const loadingDrivers = ref(false);
const loadingEmployees = ref(false);

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
const showDriverLicenseModal = ref(false);

const selectedVehicle = ref(null);
const selectedCommission = ref(null);
const selectedDriver = ref(null);

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
    nextTick(updateIndicator);
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

const fetchDrivers = async () => {
    loadingDrivers.value = true;
    try {
        const res = await axios.get('/vehicles/drivers');
        driversList.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingDrivers.value = false; }
};

const fetchEmployees = async () => {
    loadingEmployees.value = true;
    try {
        const res = await axios.get('/vehicles/employees');
        employeesList.value = res.data;
    } catch (e) { console.error(e); }
    finally { loadingEmployees.value = false; }
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
    const numero = String(commission.numero).padStart(3, '0');
    openPdf({
        src: `/vehicles/commissions/${commission.id}/pdf`,
        filename: `autorizacion_salida_${numero}-${commission.anio}.pdf`,
    });
};

const openMaintenanceModal = () => { showMaintenanceModal.value = true; };
const openHandoverModal = () => { showHandoverModal.value = true; };
const openServiceReqModal = () => { showServiceReqModal.value = true; };
const openDriverLicenseModal = (driver) => {
    selectedDriver.value = driver;
    showDriverLicenseModal.value = true;
};

// Callbacks
const onVehicleSaved = () => { fetchInventory(); showVehicleModal.value = false; };
const onCommissionSaved = () => { fetchCommissions(); showCommissionModal.value = false; };
const onMaintenanceSaved = () => { fetchMaintenances(); showMaintenanceModal.value = false; };
const onHandoverSaved = () => { fetchHandovers(); showHandoverModal.value = false; };
const onServiceReqSaved = () => { fetchServiceReqs(); showServiceReqModal.value = false; };
const onDriverLicenseSaved = () => { fetchDrivers(); showDriverLicenseModal.value = false; };

// Helpers
const getStatusClass = (estado) => {
    switch (estado) {
        case 'AUTORIZADA': return 'bg-indigo-100 text-indigo-800';
        case 'CONFIRMADA': return 'bg-cyan-100 text-cyan-800';
        case 'EN_COMISION': return 'bg-blue-100 text-blue-800';
        case 'COMPLETADA': return 'bg-green-100 text-green-800';
        case 'RECHAZADA': return 'bg-red-100 text-red-800';
        case 'CANCELADA': return 'bg-gray-100 text-gray-800';
        default: return 'bg-yellow-100 text-yellow-800';
    }
};

const getStatusLabel = (estado) => {
    switch (estado) {
        case 'PENDIENTE': return 'Pendiente';
        case 'AUTORIZADA': return 'Autorizada';
        case 'CONFIRMADA': return 'Confirmada';
        case 'EN_COMISION': return 'En Comisión';
        case 'COMPLETADA': return 'Completada';
        case 'RECHAZADA': return 'Rechazada';
        case 'CANCELADA': return 'Cancelada';
        default: return estado;
    }
};

// Control de salida/retorno (Components/Vehicles/Commissions/CommissionControlModal.vue).
const showControlModal = ref(false);
const controlTargetCommission = ref(null);

const openControlModal = (commission) => {
    controlTargetCommission.value = commission;
    showControlModal.value = true;
};

const onControlSaved = () => {
    showControlModal.value = false;
    fetchCommissions();
};

// Autorización de salida vehicular
const { processing: approvalProcessing, autorizar, rechazar, confirmar } = useCommissionApproval();
const { openPdf } = usePdfViewer();
const showRejectModal = ref(false);
const rejectTargetCommission = ref(null);
const rejectComentario = ref('');
const rejectError = ref('');

// Modal único "Autorizar / Confirmar" (Components/Vehicles/Commissions/CommissionSigningModal.vue).
const showSigningModal = ref(false);
const signingMode = ref('autorizar');
const signingTargetCommission = ref(null);
const signingModalRef = ref(null);

const openSigningModal = (mode, commission) => {
    signingMode.value = mode;
    signingTargetCommission.value = commission;
    showSigningModal.value = true;
};

const handleSigningSubmit = async (values) => {
    try {
        if (signingMode.value === 'autorizar') {
            await autorizar(signingTargetCommission.value.id, values.comentario, values.signing_pin);
        } else {
            await confirmar(signingTargetCommission.value.id, values.signing_pin);
        }
        showSigningModal.value = false;
        await fetchCommissions();
    } catch (e) {
        const errors = e.response?.data?.errors;
        if (errors?.signing_pin) {
            signingModalRef.value?.setFieldError('signing_pin', Array.isArray(errors.signing_pin) ? errors.signing_pin[0] : errors.signing_pin);
            return;
        }
        const message = errors ? Object.values(errors).flat().join('\n') : (e.response?.data?.message || 'No se pudo firmar la acción.');
        alert(message);
    }
};

const openRejectModal = (commission) => {
    rejectTargetCommission.value = commission;
    rejectComentario.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const handleReject = async () => {
    if (!rejectComentario.value.trim()) {
        rejectError.value = 'Debe indicar el motivo del rechazo.';
        return;
    }
    try {
        await rechazar(rejectTargetCommission.value.id, rejectComentario.value.trim());
        showRejectModal.value = false;
        await fetchCommissions();
    } catch (e) {
        rejectError.value = e.response?.data?.message || 'Error al rechazar la salida vehicular';
    }
};

onMounted(() => {
    fetchInventory();
    fetchCommissions();
    fetchMaintenances();
    fetchHandovers();
    fetchServiceReqs();
    fetchDrivers();
    fetchEmployees();
    nextTick(updateIndicator);
});
</script>

<style scoped>
.fade-slide-enter-active,
.fade-slide-leave-active {
    transition: all 0.3s ease;
}

.fade-slide-enter-from {
    opacity: 0;
    transform: translateX(10px);
}

.fade-slide-leave-to {
    opacity: 0;
    transform: translateX(-10px);
}

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
