<template>
    <div class="space-y-6">
        <!-- Stats Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 mb-3 group-hover:scale-110 transition-transform">
                        <ArrowRightLeft class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total</p>
                    <h3 class="text-2xl font-black text-slate-900">{{ movStats.total }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-3 group-hover:scale-110 transition-transform">
                        <UserPlus class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Asignaciones</p>
                    <h3 class="text-2xl font-black text-blue-600">{{ movStats.asignaciones }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-3 group-hover:scale-110 transition-transform">
                        <RotateCcw class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Devoluciones</p>
                    <h3 class="text-2xl font-black text-amber-600">{{ movStats.devoluciones }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-purple-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center text-purple-600 mb-3 group-hover:scale-110 transition-transform">
                        <Repeat class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Traslados</p>
                    <h3 class="text-2xl font-black text-purple-600">{{ movStats.traslados }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-red-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 mb-3 group-hover:scale-110 transition-transform">
                        <XCircle class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Bajas</p>
                    <h3 class="text-2xl font-black text-red-600">{{ movStats.bajas }}</h3>
                </div>
            </div>

            <div
                class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                <div
                    class="absolute right-0 top-0 w-16 h-16 bg-emerald-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110">
                </div>
                <div class="relative">
                    <div
                        class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 mb-3 group-hover:scale-110 transition-transform">
                        <Calendar class="w-5 h-5" />
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Último mes</p>
                    <h3 class="text-2xl font-black text-emerald-600">{{ movStats.ultimo_mes }}</h3>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <!-- Search -->
                <div class="lg:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar</label>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                        <input v-model="search" type="text" placeholder="Código, denominación, responsable..."
                            class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm" />
                    </div>
                </div>

                <!-- Tipo Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tipo</label>
                    <select v-model="filterTipo"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos</option>
                        <option value="ASIGNACION">Asignación</option>
                        <option value="DEVOLUCION">Devolución</option>
                        <option value="TRASLADO">Traslado</option>
                        <option value="BAJA">Baja</option>
                    </select>
                </div>

                <!-- Estado Filter -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                    <select v-model="filterEstadoId"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm bg-white">
                        <option value="">Todos</option>
                        <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                    </select>
                </div>

                <!-- Fecha Desde -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Desde</label>
                    <input v-model="filterFechaDesde" type="date"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm" />
                </div>

                <!-- Fecha Hasta -->
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Hasta</label>
                    <input v-model="filterFechaHasta" type="date"
                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-sm" />
                </div>
            </div>

            <!-- Filter Actions -->
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                <p class="text-sm text-slate-500">
                    <span class="font-semibold text-slate-700">{{ total }}</span>
                    movimientos encontrados
                </p>
                <button @click="clearFilters"
                    class="text-sm font-semibold text-slate-500 hover:text-blue-600 transition-colors duration-200 flex items-center gap-1">
                    <X class="w-4 h-4" />
                    Limpiar filtros
                </button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
            <!-- Loading -->
            <div v-if="loading" class="p-12 text-center">
                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                <p class="text-sm text-slate-400 mt-2">Cargando movimientos...</p>
            </div>

            <!-- Empty -->
            <template v-else-if="movements.length === 0">
                <div class="px-6 py-16 text-center">
                    <div class="flex flex-col items-center">
                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                            <ArrowRightLeft class="h-12 w-12 text-slate-400" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-1">No se encontraron movimientos</h3>
                        <p class="text-sm text-slate-500 mb-4">Intenta con otros filtros o registra un nuevo movimiento.
                        </p>
                        <button @click="showNewMovementModal = true"
                            class="inline-flex items-center px-5 py-2.5 text-sm font-bold rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-300 shadow-lg shadow-blue-600/20">
                            <Plus class="w-5 h-5 mr-2" />
                            Registrar Movimiento
                        </button>
                    </div>
                </div>
            </template>

            <!-- Table Content -->
            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Fecha
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Bien
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                Estado
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                Responsable
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                Ubicación
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden xl:table-cell">
                                Registrado por
                            </th>
                            <th scope="col"
                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden xl:table-cell">
                                Obs.
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        <tr v-for="mov in movements" :key="mov.id"
                            class="hover:bg-blue-50/50 transition-colors duration-200">
                            <!-- Fecha -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-slate-900">
                                    {{ formatDate(mov.fecha) }}
                                </div>
                                <div class="text-xs text-slate-400">
                                    {{ formatTime(mov.created_at) }}
                                </div>
                            </td>

                            <!-- Tipo -->
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="px-3 py-1.5 inline-flex items-center gap-1.5 text-xs leading-5 font-bold rounded-lg shadow-sm"
                                    :class="getTypeClass(mov.tipo)">
                                    <component :is="getTypeIcon(mov.tipo)" class="w-3.5 h-3.5" />
                                    {{ getTypeLabel(mov.tipo) }}
                                </span>
                            </td>

                            <!-- Bien -->
                            <td class="px-6 py-4">
                                <div class="text-sm font-semibold text-slate-900 font-mono">
                                    {{ mov.asset?.codigo_patrimonio || '—' }}
                                    <span v-if="mov.asset?.codigo_interno"
                                        class="text-xs text-slate-400 ml-1 font-normal">
                                        {{ mov.asset.codigo_interno }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 line-clamp-1 max-w-[200px]"
                                    :title="mov.asset?.denominacion">
                                    {{ mov.asset?.denominacion || '—' }}
                                </div>
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                <span v-if="mov.state"
                                    class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                    :class="getStateClass(mov.state.nombre)">
                                    {{ mov.state.nombre }}
                                </span>
                                <span v-else class="text-xs text-slate-400 italic">—</span>
                            </td>

                            <!-- Responsable -->
                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div v-if="mov.responsible?.employee?.person" class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3 flex-shrink-0">
                                        {{ (mov.responsible.employee.person.nombres || '?').charAt(0) }}
                                    </div>
                                    <div class="text-sm font-semibold text-slate-900 truncate max-w-[150px]">
                                        {{ mov.responsible.employee.person.nombres }}
                                        {{ mov.responsible.employee.person.apellido_paterno }}
                                    </div>
                                </div>
                                <div v-else-if="mov.responsible?.nombre_original" class="flex items-center">
                                    <div
                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-slate-400 to-slate-500 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3 flex-shrink-0">
                                        {{ mov.responsible.nombre_original.charAt(0) }}
                                    </div>
                                    <div class="text-sm text-slate-700 truncate max-w-[150px]">
                                        {{ mov.responsible.nombre_original }}
                                    </div>
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Ubicación -->
                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                <div v-if="mov.office" class="text-sm text-slate-600">
                                    {{ mov.office.nombre }}
                                    <div v-if="mov.office.direction" class="text-xs text-slate-400">
                                        {{ mov.office.direction.nombre }}
                                    </div>
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Registrado por -->
                            <td class="px-6 py-4 whitespace-nowrap hidden xl:table-cell">
                                <div v-if="mov.inventariador" class="text-sm text-slate-600">
                                    {{ mov.inventariador.name }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>

                            <!-- Observaciones -->
                            <td class="px-6 py-4 hidden xl:table-cell">
                                <div v-if="mov.observaciones" class="text-sm text-slate-500 line-clamp-2 max-w-[180px]"
                                    :title="mov.observaciones">
                                    {{ mov.observaciones }}
                                </div>
                                <span v-else class="text-sm text-slate-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <span>Mostrar</span>
                        <select v-model="perPage"
                            class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500 bg-white">
                            <option :value="10">10</option>
                            <option :value="15">15</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>por página</span>
                    </div>
                    <div class="text-sm text-slate-600">
                        Página {{ currentPage }} de {{ lastPage }}
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="fetchMovements(1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronsLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchMovements(currentPage - 1)" :disabled="currentPage === 1"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronLeft class="w-4 h-4" />
                        </button>
                        <button @click="fetchMovements(currentPage + 1)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronRight class="w-4 h-4" />
                        </button>
                        <button @click="fetchMovements(lastPage)" :disabled="currentPage === lastPage"
                            class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-colors">
                            <ChevronsRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Movement Modal -->
        <div v-if="showNewMovementModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-8">
                <div class="fixed inset-0 bg-black/50 transition-opacity" @click="closeModal"></div>

                <div
                    class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden flex flex-col max-h-[90vh]">
                    <!-- Modal Header -->
                    <div
                        class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center shrink-0">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <ArrowRightLeft class="w-6 h-6" />
                                Registrar Movimiento
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">Seleccione un bien y registre el movimiento</p>
                        </div>
                        <button @click="closeModal" class="text-blue-100 hover:text-white transition-colors p-1">
                            <X class="w-6 h-6" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="overflow-y-auto p-6">
                        <form @submit.prevent="submitMovement" class="space-y-6">

                            <!-- Sección 1: Seleccionar Bien -->
                            <div>
                                <h4
                                    class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-8 h-[1px] bg-slate-200"></span> Seleccionar Bien
                                </h4>
                                <div class="relative" ref="assetDropdownRef">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Bien Patrimonial <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <Search
                                            class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                                        <input type="text" v-model="assetSearch" @focus="showAssetDropdown = true"
                                            placeholder="Buscar por código patrimonial, denominación..."
                                            class="w-full pl-10 pr-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors outline-none"
                                            :class="modalErrors.asset_id ? 'border-red-400' : 'border-slate-200'" />
                                    </div>
                                    <p v-if="modalErrors.asset_id" class="mt-1 text-sm text-red-600">{{
                                        modalErrors.asset_id }}
                                    </p>

                                    <!-- Asset search results -->
                                    <div v-if="showAssetDropdown && assetSearchResults.length > 0"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                        <div v-if="searchingAssets" class="p-4 text-center">
                                            <Loader2 class="w-5 h-5 mx-auto text-slate-400 animate-spin" />
                                        </div>
                                        <button type="button" v-for="asset in assetSearchResults" :key="asset.id"
                                            @click="selectAsset(asset)"
                                            class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors flex items-center justify-between group border-b border-slate-50 last:border-b-0">
                                            <div>
                                                <p
                                                    class="font-mono font-bold text-slate-700 group-hover:text-blue-700 text-sm">
                                                    {{ asset.codigo_patrimonio }}
                                                    <span class="text-slate-400 font-normal">{{ asset.codigo_interno
                                                    }}</span>
                                                </p>
                                                <p class="text-xs text-slate-500 line-clamp-1">{{ asset.denominacion }}
                                                </p>
                                            </div>
                                            <div v-if="asset.latest_movement?.state" class="ml-2 flex-shrink-0">
                                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md"
                                                    :class="getStateClass(asset.latest_movement.state.nombre)">
                                                    {{ asset.latest_movement.state.nombre }}
                                                </span>
                                            </div>
                                        </button>
                                    </div>

                                    <div v-if="showAssetDropdown && assetSearch.length >= 2 && assetSearchResults.length === 0 && !searchingAssets"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 text-center text-sm text-slate-500">
                                        No se encontraron bienes con ese criterio
                                    </div>
                                </div>

                                <!-- Selected asset preview -->
                                <div v-if="selectedAsset"
                                    class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-center justify-between">
                                    <div>
                                        <p class="font-mono font-bold text-blue-800 text-sm">
                                            {{ selectedAsset.codigo_patrimonio }}{{ selectedAsset.codigo_interno }}
                                        </p>
                                        <p class="text-sm text-blue-700">{{ selectedAsset.denominacion }}</p>
                                        <p v-if="selectedAsset.brand" class="text-xs text-blue-500">
                                            {{ selectedAsset.brand.nombre }}
                                            {{ selectedAsset.modelo ? `— ${selectedAsset.modelo}` : '' }}
                                        </p>
                                    </div>
                                    <button type="button" @click="clearSelectedAsset"
                                        class="text-blue-400 hover:text-blue-600 transition-colors p-1">
                                        <X class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Sección 2: Datos del Movimiento -->
                            <div class="bg-slate-50 p-5 rounded-xl border border-slate-200">
                                <h4
                                    class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span> Datos del Movimiento
                                </h4>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <!-- Tipo -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">
                                            Tipo de Movimiento <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="movForm.tipo"
                                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors outline-none"
                                            :class="modalErrors.tipo ? 'border-red-400' : 'border-slate-200'">
                                            <option value="">Seleccione tipo</option>
                                            <option value="ASIGNACION">Asignación</option>
                                            <option value="DEVOLUCION">Devolución</option>
                                            <option value="TRASLADO">Traslado</option>
                                            <option value="BAJA">Baja</option>
                                        </select>
                                        <p v-if="modalErrors.tipo" class="mt-1 text-sm text-red-600">{{ modalErrors.tipo
                                        }}</p>
                                    </div>

                                    <!-- Fecha -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">
                                            Fecha <span class="text-red-500">*</span>
                                        </label>
                                        <input type="date" v-model="movForm.fecha"
                                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors outline-none"
                                            :class="modalErrors.fecha ? 'border-red-400' : 'border-slate-200'" />
                                        <p v-if="modalErrors.fecha" class="mt-1 text-sm text-red-600">{{
                                            modalErrors.fecha }}
                                        </p>
                                    </div>

                                    <!-- Estado -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">
                                            Estado del Bien <span class="text-red-500">*</span>
                                        </label>
                                        <select v-model="movForm.estado_id"
                                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors outline-none"
                                            :class="modalErrors.estado_id ? 'border-red-400' : 'border-slate-200'">
                                            <option value="">Seleccione estado</option>
                                            <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}
                                            </option>
                                        </select>
                                        <p v-if="modalErrors.estado_id" class="mt-1 text-sm text-red-600">{{
                                            modalErrors.estado_id }}</p>
                                    </div>

                                    <!-- Oficina -->
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Ubicación /
                                            Oficina</label>
                                        <select v-model="movForm.oficina_id"
                                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white transition-colors outline-none">
                                            <option value="">Sin especificar</option>
                                            <option v-for="office in offices" :key="office.id" :value="office.id">
                                                {{ office.nombre }} {{ office.direction ? `(${office.direction.nombre})`
                                                : '' }}
                                            </option>
                                        </select>
                                    </div>

                                    <!-- Responsable -->
                                    <div class="md:col-span-2 relative" ref="empDropdownRef">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Responsable</label>
                                        <div class="relative">
                                            <input type="text" v-model="empSearchQuery" @focus="showEmpDropdown = true"
                                                @input="onEmpSearchInput"
                                                placeholder="Buscar personal por nombre o DNI..."
                                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors outline-none pr-10" />
                                            <ChevronDown
                                                class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        </div>
                                        <!-- Employee dropdown -->
                                        <div v-if="showEmpDropdown && filteredEmployees.length > 0"
                                            class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                            <button type="button" v-for="emp in filteredEmployees" :key="emp.id"
                                                @click="selectEmployee(emp)"
                                                class="w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors flex items-center justify-between group">
                                                <div>
                                                    <p
                                                        class="font-medium text-slate-700 group-hover:text-slate-900 text-sm">
                                                        {{ emp.nombre_completo }}</p>
                                                    <p class="text-xs text-slate-400">{{ emp.dni }}</p>
                                                </div>
                                                <Check v-if="movForm.responsable_id && selectedEmployee?.id === emp.id"
                                                    class="w-4 h-4 text-blue-600" />
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Observaciones -->
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                                        <textarea v-model="movForm.observaciones" rows="2"
                                            placeholder="Detalle o motivo del movimiento (opcional)..."
                                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none transition-colors outline-none"></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                                <button type="button" @click="closeModal"
                                    class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="isSubmitting"
                                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-xl hover:from-blue-700 hover:to-indigo-700 transition-all disabled:opacity-50 shadow-lg shadow-blue-600/20">
                                    <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                                    {{ isSubmitting ? 'Registrando...' : 'Registrar Movimiento' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import {
    ArrowRightLeft,
    UserPlus,
    RotateCcw,
    Repeat,
    XCircle,
    Calendar,
    Search,
    X,
    Plus,
    Loader2,
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
    ChevronDown,
    Check,
} from 'lucide-vue-next';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const props = defineProps({
    states: { type: Array, default: () => [] },
    offices: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

// ===== STATS =====
const movStats = ref({
    total: 0,
    asignaciones: 0,
    devoluciones: 0,
    traslados: 0,
    bajas: 0,
    ultimo_mes: 0,
});

// ===== FILTERS =====
const search = ref('');
const filterTipo = ref('');
const filterEstadoId = ref('');
const filterFechaDesde = ref('');
const filterFechaHasta = ref('');

// ===== PAGINATION =====
const movements = ref([]);
const loading = ref(false);
const currentPage = ref(1);
const lastPage = ref(1);
const total = ref(0);
const perPage = ref(15);

// ===== NEW MOVEMENT MODAL =====
const showNewMovementModal = ref(false);
const isSubmitting = ref(false);
const selectedAsset = ref(null);
const assetSearch = ref('');
const showAssetDropdown = ref(false);
const assetSearchResults = ref([]);
const searchingAssets = ref(false);
const assetDropdownRef = ref(null);

// Employee search for modal
const empSearchQuery = ref('');
const showEmpDropdown = ref(false);
const selectedEmployee = ref(null);
const empDropdownRef = ref(null);

const movForm = ref({
    tipo: '',
    fecha: new Date().toISOString().split('T')[0],
    estado_id: '',
    oficina_id: '',
    responsable_id: '',
    observaciones: '',
});

const modalErrors = ref({});

// ===== FETCH DATA =====
const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/movements/stats');
        movStats.value = response.data;
    } catch (error) {
        console.error('Error fetching movement stats:', error);
    }
};

const fetchMovements = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get('/assets/movements', {
            params: {
                search: search.value || undefined,
                tipo: filterTipo.value || undefined,
                estado_id: filterEstadoId.value || undefined,
                fecha_desde: filterFechaDesde.value || undefined,
                fecha_hasta: filterFechaHasta.value || undefined,
                per_page: perPage.value,
                page,
            },
        });
        movements.value = response.data.data;
        currentPage.value = response.data.current_page;
        lastPage.value = response.data.last_page;
        total.value = response.data.total;
    } catch (error) {
        console.error('Error fetching movements:', error);
    } finally {
        loading.value = false;
    }
};

// ===== ASSET SEARCH (for modal) =====
let assetSearchTimeout = null;
watch(assetSearch, (val) => {
    if (selectedAsset.value) return; // Don't search when an asset is already selected
    clearTimeout(assetSearchTimeout);
    if (val.length < 2) {
        assetSearchResults.value = [];
        return;
    }
    assetSearchTimeout = setTimeout(async () => {
        searchingAssets.value = true;
        try {
            const response = await axios.get('/assets/list', {
                params: { search: val, per_page: 8 },
            });
            assetSearchResults.value = response.data.data;
        } catch (error) {
            console.error('Error searching assets:', error);
        } finally {
            searchingAssets.value = false;
        }
    }, 300);
});

const selectAsset = (asset) => {
    selectedAsset.value = asset;
    assetSearch.value = `${asset.codigo_patrimonio} — ${asset.denominacion}`;
    showAssetDropdown.value = false;
    assetSearchResults.value = [];
    modalErrors.value.asset_id = '';

    // Pre-fill estado from current state
    if (asset.latest_movement?.state?.id) {
        movForm.value.estado_id = asset.latest_movement.state.id;
    }
};

const clearSelectedAsset = () => {
    selectedAsset.value = null;
    assetSearch.value = '';
    assetSearchResults.value = [];
};

// ===== EMPLOYEE SEARCH (for modal) =====
const filteredEmployees = computed(() => {
    if (!empSearchQuery.value || !empSearchQuery.value.trim()) {
        return props.employees.slice(0, 10);
    }
    const s = empSearchQuery.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
    const terms = s.split(' ').filter(t => t.length > 0);
    return props.employees.filter(emp => {
        const name = (emp.nombre_completo || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const dni = emp.dni || '';
        if (dni.includes(empSearchQuery.value.trim())) return true;
        return terms.every(t => name.includes(t));
    }).slice(0, 10);
});

const onEmpSearchInput = () => {
    showEmpDropdown.value = true;
    selectedEmployee.value = null;
    movForm.value.responsable_id = '';
};

const selectEmployee = async (emp) => {
    selectedEmployee.value = emp;
    empSearchQuery.value = emp.nombre_completo;
    showEmpDropdown.value = false;

    // Find or create the AssetResponsible for this employee
    try {
        const response = await axios.post('/assets/responsibles', {
            employee_id: emp.id,
        });
        movForm.value.responsable_id = response.data.id;
    } catch (error) {
        console.error('Error creating responsible:', error);
        // Fallback: try to find existing
        try {
            const resp = await axios.get('/assets/responsibles', {
                params: { employee_id: emp.id }
            });
            if (resp.data.length > 0) {
                movForm.value.responsable_id = resp.data[0].id;
            }
        } catch (err2) {
            console.error('Error finding responsible:', err2);
        }
    }
};

// ===== SUBMIT MOVEMENT =====
const submitMovement = async () => {
    modalErrors.value = {};

    // Validate
    if (!selectedAsset.value) {
        modalErrors.value.asset_id = 'Debe seleccionar un bien';
        return;
    }
    if (!movForm.value.tipo) {
        modalErrors.value.tipo = 'Seleccione el tipo de movimiento';
        return;
    }
    if (!movForm.value.fecha) {
        modalErrors.value.fecha = 'Ingrese la fecha del movimiento';
        return;
    }
    if (!movForm.value.estado_id) {
        modalErrors.value.estado_id = 'Seleccione el estado del bien';
        return;
    }

    isSubmitting.value = true;
    try {
        const payload = {
            tipo: movForm.value.tipo,
            fecha: movForm.value.fecha,
            estado_id: movForm.value.estado_id,
            oficina_id: movForm.value.oficina_id || null,
            responsable_id: movForm.value.responsable_id || null,
            observaciones: movForm.value.observaciones || null,
        };

        await axios.post(`/assets/${selectedAsset.value.id}/movements`, payload);

        closeModal();
        fetchMovements(1);
        fetchStats();

        Swal.fire({
            icon: 'success',
            title: 'Movimiento registrado',
            text: `Movimiento de ${getTypeLabel(payload.tipo).toLowerCase()} registrado correctamente.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });
    } catch (error) {
        console.error('Error creating movement:', error);
        const serverErrors = error.response?.data?.errors;
        if (serverErrors) {
            modalErrors.value = serverErrors;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo registrar el movimiento. Intente nuevamente.',
            });
        }
    } finally {
        isSubmitting.value = false;
    }
};

const closeModal = () => {
    showNewMovementModal.value = false;
    selectedAsset.value = null;
    assetSearch.value = '';
    assetSearchResults.value = [];
    empSearchQuery.value = '';
    selectedEmployee.value = null;
    modalErrors.value = {};
    movForm.value = {
        tipo: '',
        fecha: new Date().toISOString().split('T')[0],
        estado_id: '',
        oficina_id: '',
        responsable_id: '',
        observaciones: '',
    };
};

// ===== WATCHERS =====
let searchTimeout = null;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchMovements(1), 400);
});

watch([filterTipo, filterEstadoId, filterFechaDesde, filterFechaHasta], () => fetchMovements(1));
watch(perPage, () => fetchMovements(1));

const clearFilters = () => {
    search.value = '';
    filterTipo.value = '';
    filterEstadoId.value = '';
    filterFechaDesde.value = '';
    filterFechaHasta.value = '';
    fetchMovements(1);
};

// ===== CLICK OUTSIDE (dropdowns) =====
const handleClickOutside = (event) => {
    if (assetDropdownRef.value && !assetDropdownRef.value.contains(event.target)) {
        showAssetDropdown.value = false;
    }
    if (empDropdownRef.value && !empDropdownRef.value.contains(event.target)) {
        showEmpDropdown.value = false;
    }
};

onMounted(() => {
    fetchStats();
    fetchMovements(1);
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});

// ===== FORMATTERS =====
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    // Si la fecha ya viene en formato ISO (con tiempo), procesarla como tal
    if (dateStr.includes('T')) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric', timeZone: 'UTC' });
    }
    const d = new Date(dateStr + 'T00:00:00');
    return d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const formatTime = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
};

// ===== TYPE HELPERS =====
const getTypeClass = (tipo) => {
    const classes = {
        'ASIGNACION': 'bg-gradient-to-r from-blue-500 to-blue-600 text-white',
        'DEVOLUCION': 'bg-gradient-to-r from-amber-500 to-amber-600 text-white',
        'TRASLADO': 'bg-gradient-to-r from-purple-500 to-purple-600 text-white',
        'BAJA': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[tipo] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

const getTypeIcon = (tipo) => {
    const icons = {
        'ASIGNACION': UserPlus,
        'DEVOLUCION': RotateCcw,
        'TRASLADO': Repeat,
        'BAJA': XCircle,
    };
    return icons[tipo] || ArrowRightLeft;
};

const getTypeLabel = (tipo) => {
    const labels = {
        'ASIGNACION': 'Asignación',
        'DEVOLUCION': 'Devolución',
        'TRASLADO': 'Traslado',
        'BAJA': 'Baja',
    };
    return labels[tipo] || tipo;
};

const getStateClass = (nombre) => {
    const classes = {
        'BUENO': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'REGULAR': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'MALO': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[nombre] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

// ===== EXPOSE =====
const openModal = () => {
    showNewMovementModal.value = true;
};

const setSearch = (code) => {
    search.value = code;
    fetchMovements(1);
};

defineExpose({ openModal, setSearch });
</script>
