<template>
    <div class="p-4 sm:p-6 lg:p-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
                <div>
                    <h1
                        class="text-3xl font-extrabold bg-gradient-to-r from-slate-700 to-gray-700 bg-clip-text text-transparent tracking-tight">
                        Gestión de Patrimonio
                    </h1>
                    <p class="mt-1 text-slate-500 font-medium">
                        Administración y control de bienes patrimoniales
                    </p>
                </div>
                <div class="flex gap-3">
                    <a v-if="!isEmployeeOnly" href="/assets/catalogs"
                        class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-slate-200 text-sm font-bold rounded-xl text-slate-600 bg-white hover:bg-slate-50 transition-all shadow-sm">
                        <Settings class="w-5 h-5 mr-2" />
                        Catálogos
                    </a>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="border-b border-slate-200 mb-8 relative">
                <nav ref="tabsRef" class="-mb-px flex">
                    <button v-if="isEmployeeOnly && myEmployee" @click="activeTab = 'mis_bienes'" :class="[
                        activeTab === 'mis_bienes' ? 'text-emerald-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <UserCheck class="w-5 h-5" />
                        Mis Bienes
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('list')" @click="activeTab = 'list'" :class="[
                        activeTab === 'list' ? 'text-slate-700 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Box class="w-5 h-5" />
                        Inventario de Bienes
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('movements')" @click="activeTab = 'movements'" :class="[
                        activeTab === 'movements' ? 'text-blue-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <ArrowRightLeft class="w-5 h-5" />
                        Movimientos
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('barcodes')" @click="activeTab = 'barcodes'" :class="[
                        activeTab === 'barcodes' ? 'text-slate-700 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <Barcode class="w-5 h-5" />
                        Códigos de Barra
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('reports')" @click="activeTab = 'reports'" :class="[
                        activeTab === 'reports' ? 'text-green-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <FileDown class="w-5 h-5" />
                        Reportes
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('patrimonio')" @click="activeTab = 'patrimonio'" :class="[
                        activeTab === 'patrimonio' ? 'text-indigo-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                        'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                    ]">
                        <DatabaseIcon class="w-5 h-5" />
                        Patrimonio SIGA
                    </button>
                    <button v-if="!isEmployeeOnly && canViewTab('inventarios')" @click="activeTab = 'inventarios'"
                        :class="[
                            activeTab === 'inventarios' ? 'text-purple-600 active-tab' : 'text-slate-500 hover:text-slate-700',
                            'cursor-pointer whitespace-nowrap py-4 px-5 font-bold text-sm flex items-center gap-2 transition-colors duration-300'
                        ]">
                        <ClipboardList class="w-5 h-5" />
                        Inventarios
                    </button>
                </nav>
                <!-- Gliding Indicator -->
                <div class="absolute bottom-0 h-0.5 transition-all duration-300 ease-out" :style="indicatorStyle"></div>
            </div>

            <!-- Tab Content with Transition -->
            <Transition name="fade-slide" mode="out-in">
                <div :key="activeTab">

                    <!-- Stats Overview -->
                    <div v-if="!isEmployeeOnly && activeTab === 'list'"
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                            <div
                                class="absolute right-0 top-0 w-24 h-24 bg-slate-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                            </div>
                            <div class="relative">
                                <div
                                    class="w-12 h-12 bg-slate-100 rounded-xl flex items-center justify-center text-slate-700 mb-4 group-hover:scale-110 transition-transform">
                                    <Box class="w-6 h-6" />
                                </div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Total Bienes
                                </p>
                                <h3 class="text-3xl font-black text-slate-900 mt-1">{{ stats.total }}</h3>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                            <div
                                class="absolute right-0 top-0 w-24 h-24 bg-emerald-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                            </div>
                            <div class="relative">
                                <div
                                    class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-slate-700 mb-4 group-hover:scale-110 transition-transform">
                                    <CheckCircle class="w-6 h-6" />
                                </div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Buenos</p>
                                <h3 class="text-3xl font-black text-slate-700 mt-1">{{ stats.buenos }}</h3>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                            <div
                                class="absolute right-0 top-0 w-24 h-24 bg-yellow-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                            </div>
                            <div class="relative">
                                <div
                                    class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center text-yellow-600 mb-4 group-hover:scale-110 transition-transform">
                                    <AlertTriangle class="w-6 h-6" />
                                </div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Regulares</p>
                                <h3 class="text-3xl font-black text-yellow-600 mt-1">{{ stats.regulares }}</h3>
                            </div>
                        </div>

                        <div
                            class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                            <div
                                class="absolute right-0 top-0 w-24 h-24 bg-red-50 rounded-bl-[100px] -mr-4 -mt-4 transition-transform group-hover:scale-110">
                            </div>
                            <div class="relative">
                                <div
                                    class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center text-red-600 mb-4 group-hover:scale-110 transition-transform">
                                    <XCircle class="w-6 h-6" />
                                </div>
                                <p class="text-xs font-bold text-slate-500 uppercase tracking-widest mb-1">Malos</p>
                                <h3 class="text-3xl font-black text-red-600 mt-1">{{ stats.malos }}</h3>
                            </div>
                        </div>
                    </div>

                    <!-- List Content -->
                    <div v-if="!isEmployeeOnly && activeTab === 'list'" class="space-y-6">
                        <BaseTableCard title="Inventario de Bienes"
                            description="Gestión y control de bienes patrimoniales">
                            <template #icon>
                                <Box class="w-5 h-5 text-slate-600" />
                            </template>

                            <template #actions>
                                <button @click="showBarcodeScanner = true"
                                    class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-emerald-200 text-sm font-bold rounded-xl text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all shadow-sm">
                                    <ScanBarcode class="w-4 h-4 mr-1.5" />
                                    Escanear
                                </button>
                                <button @click="filtersVisible = !filtersVisible"
                                    class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                                    <SlidersHorizontal class="w-4 h-4" />
                                    Filtros
                                    <ChevronDown class="w-4 h-4 transition-transform duration-300"
                                        :class="{ 'rotate-180': filtersVisible }" />
                                </button>
                                <button @click="showCreateModal = true"
                                    class="cursor-pointer inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-slate-600/20 text-white bg-gradient-to-r from-slate-700 to-gray-700 hover:from-slate-800 hover:to-gray-800 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Nuevo Bien
                                </button>
                            </template>

                            <!-- Filtros colapsables -->
                            <div class="filters-collapse bg-slate-50 border-b border-slate-100"
                                :class="{ 'filters-collapse--open': filtersVisible }">
                                <div class="p-4 sm:p-5">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <div class="lg:col-span-2 relative">
                                            <Search
                                                class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                            <input v-model="listSearch" type="text"
                                                placeholder="Buscar por código, denominación, serie..."
                                                class="w-full pl-9 pr-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all text-sm outline-none" />
                                        </div>
                                        <select v-model="listEstadoId"
                                            class="cursor-pointer border-2 border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white outline-none transition-all">
                                            <option value="">Todos los estados</option>
                                            <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}
                                            </option>
                                        </select>
                                        <select v-model="listCategoriaId"
                                            class="cursor-pointer border-2 border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white outline-none transition-all">
                                            <option value="">Todas las categorías</option>
                                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{
                                                cat.nombre }}</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-200">
                                        <span class="text-sm text-slate-500"><span
                                                class="font-semibold text-slate-700">{{ listTotal }}</span>
                                            resultados</span>
                                        <button @click="clearListFilters"
                                            class="text-sm font-semibold text-slate-500 hover:text-indigo-600 transition-colors flex items-center gap-1">
                                            <X class="w-4 h-4" /> Limpiar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Loading -->
                            <div v-if="listLoading" class="p-12 text-center">
                                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                                <p class="text-sm text-slate-400 mt-2">Cargando bienes...</p>
                            </div>

                            <!-- Empty -->
                            <template v-else-if="listAssets.length === 0">
                                <div class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center">
                                        <div class="bg-slate-100 rounded-full p-4 mb-4">
                                            <PackageOpen class="h-12 w-12 text-slate-400" />
                                        </div>
                                        <h3 class="text-lg font-bold text-slate-900 mb-1">No se encontraron bienes</h3>
                                        <p class="text-sm text-slate-500">Intenta con otro término de búsqueda o
                                            registra un nuevo bien.</p>
                                    </div>
                                </div>
                            </template>

                            <!-- Table -->
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                Código</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">
                                                Denominación</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                                Marca</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                                Serie</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">
                                                Estado</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                                Responsable</th>
                                            <th scope="col"
                                                class="px-6 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                                Ubicación</th>
                                            <th scope="col" class="relative px-6 py-4">
                                                <span class="sr-only">Acciones</span>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-slate-100">
                                        <tr v-for="asset in listAssets" :key="asset.id"
                                            class="hover:bg-blue-50 transition-colors duration-200">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-slate-900 font-mono">{{
                                                    asset.codigo_patrimonio }}</div>
                                                <div class="text-xs text-slate-500 font-mono">{{ asset.codigo_interno }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-slate-700 font-medium line-clamp-2 max-w-xs">{{
                                                    asset.denominacion }}</div>
                                                <span v-if="asset.patrimonio_asset?.sincronizado"
                                                    class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 border border-indigo-200">
                                                    <DatabaseIcon class="w-2.5 h-2.5" />
                                                    SIGA
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden md:table-cell">
                                                <div class="text-sm text-slate-600">{{ asset.brand?.nombre || '—' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                                <div class="text-sm text-slate-500 font-mono">{{ asset.numero_serie ||
                                                    '—' }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center hidden md:table-cell">
                                                <span v-if="asset.latest_movement?.state"
                                                    class="px-3 py-1.5 inline-flex text-xs leading-5 font-bold rounded-lg shadow-sm"
                                                    :class="getStateClass(asset.latest_movement.state.nombre)">
                                                    {{ asset.latest_movement.state.nombre }}
                                                </span>
                                                <span v-else class="text-xs text-slate-400 italic">—</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                                <div v-if="asset.latest_movement?.responsible?.employee?.person"
                                                    class="flex items-center">
                                                    <div
                                                        class="h-8 w-8 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shadow-md mr-3">
                                                        {{ (asset.latest_movement.responsible.employee.person.nombres ||
                                                            '?').charAt(0) }}
                                                    </div>
                                                    <div class="text-sm font-semibold text-slate-900">
                                                        {{ asset.latest_movement.responsible.employee.person.nombres }}
                                                        {{
                                                            asset.latest_movement.responsible.employee.person.apellido_paterno
                                                        }}
                                                    </div>
                                                </div>
                                                <span v-else class="text-sm text-slate-400">—</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap hidden lg:table-cell">
                                                <div class="text-sm text-slate-600">{{
                                                    asset.latest_movement?.office?.nombre ||
                                                    '—' }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center gap-1 justify-end">
                                                    <button @click="openEditModal(asset)"
                                                        class="text-slate-400 hover:text-blue-600 transition-colors duration-200 p-2 hover:bg-blue-50 rounded-lg"
                                                        title="Editar">
                                                        <Pencil class="h-5 w-5" />
                                                    </button>
                                                    <button @click="confirmDelete(asset)"
                                                        class="text-slate-400 hover:text-red-600 transition-colors duration-200 p-2 hover:bg-red-50 rounded-lg"
                                                        title="Eliminar">
                                                        <Trash2 class="h-5 w-5" />
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <ClientPagination :total-items="listTotal" :current-page="listCurrentPage"
                                :per-page="listPerPage" :per-page-options="[10, 25, 50, 100]"
                                @update:current-page="fetchListAssets($event)"
                                @update:per-page="listPerPage = $event" />
                        </BaseTableCard>
                    </div>

                    <!-- Delete confirmation handled by SweetAlert2 -->

                    <!-- Barcodes Tab -->
                    <BarcodeGenerator v-if="!isEmployeeOnly && activeTab === 'barcodes'" />

                    <!-- Movements Tab -->
                    <div v-if="!isEmployeeOnly && activeTab === 'movements'">
                        <MovementsList ref="movementsListRef" :states="states" :offices="offices"
                            :employees="employees" :movement-types="movementTypes" :filters-visible="movFiltersVisible">
                            <template #actions>
                                <button @click="showBarcodeScanner = true"
                                    class="cursor-pointer inline-flex items-center px-4 py-2.5 border border-emerald-200 text-sm font-bold rounded-xl text-emerald-700 bg-emerald-50 hover:bg-emerald-100 transition-all shadow-sm">
                                    <ScanBarcode class="w-4 h-4 mr-1.5" />
                                    Escanear
                                </button>
                                <button @click="movFiltersVisible = !movFiltersVisible"
                                    class="cursor-pointer inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-bold text-slate-600 hover:bg-slate-50 transition-all duration-200 shadow-sm">
                                    <SlidersHorizontal class="w-4 h-4" />
                                    Filtros
                                    <ChevronDown class="w-4 h-4 transition-transform duration-300" :class="{ 'rotate-180': movFiltersVisible }" />
                                </button>
                                <button @click="openNewMovement"
                                    class="cursor-pointer inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-blue-600/20 text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 transition-all duration-200">
                                    <ArrowRightLeft class="w-4 h-4 mr-1.5" />
                                    Nuevo Movimiento
                                </button>
                            </template>
                        </MovementsList>
                    </div>

                    <!-- Reports Tab -->
                    <div v-if="!isEmployeeOnly && activeTab === 'reports'">
                        <ReportsTab :employees="employees" />
                    </div>

                    <!-- Patrimonio SIGA Tab -->
                    <PatrimonioSigaTab v-if="!isEmployeeOnly && activeTab === 'patrimonio'" />

                    <!-- Inventarios Tab -->
                    <div v-if="!isEmployeeOnly && activeTab === 'inventarios'">
                        <InventariosTab ref="inventariosTabRef" :states="states" :offices="offices" :employees="employees">
                            <template #actions>
                                <button @click="openNewInventario"
                                    class="cursor-pointer inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-sm shadow-purple-600/20 text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 transition-all duration-200">
                                    <Plus class="w-4 h-4 mr-1.5" />
                                    Nuevo Inventario
                                </button>
                            </template>
                        </InventariosTab>
                    </div>

                    <!-- Mis Bienes Tab -->
                    <div v-if="activeTab === 'mis_bienes'" class="space-y-6">
                        <!-- Header -->
                        <div
                            class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl p-5 flex items-center gap-4">
                            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                                <UserCheck class="w-6 h-6 text-emerald-700" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-emerald-900">Bienes asignados a {{
                                    myEmployee?.nombre_completo }}</h2>
                                <p class="text-sm text-emerald-700">{{ misBienesTotal }} bien{{ misBienesTotal !== 1 ?
                                    'es' : '' }}
                                    registrado{{ misBienesTotal !== 1 ? 's' : '' }} a tu nombre</p>
                            </div>
                        </div>

                        <BaseTableCard title="Mis Bienes Asignados"
                            search-placeholder="Buscar por código, denominación..." :search-value="misBienesSearch"
                            @update:search-value="misBienesSearch = $event">
                            <template #icon>
                                <UserCheck class="w-5 h-5 text-emerald-600" />
                            </template>

                            <!-- Loading -->
                            <div v-if="misBienesLoading" class="p-12 text-center">
                                <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                                <p class="text-sm text-slate-400 mt-2">Cargando bienes...</p>
                            </div>

                            <!-- Empty -->
                            <div v-else-if="misBienes.length === 0" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="bg-slate-100 rounded-full p-4 mb-4">
                                        <PackageOpen class="h-12 w-12 text-slate-400" />
                                    </div>
                                    <h3 class="text-lg font-bold text-slate-900 mb-1">Sin bienes asignados</h3>
                                    <p class="text-sm text-slate-500">No se encontraron bienes registrados a tu nombre.
                                    </p>
                                </div>
                            </div>

                            <!-- Data -->
                            <div v-else class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-100">
                                    <thead class="bg-slate-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                                Código
                                            </th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                                Denominación</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell">
                                                Categoría</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">
                                                Estado</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden lg:table-cell">
                                                Ubicación</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <tr v-for="bien in misBienes" :key="bien.id"
                                            class="hover:bg-emerald-50/30 transition-colors">
                                            <td class="px-4 py-3">
                                                <span
                                                    class="font-mono text-xs font-semibold text-slate-700 bg-slate-100 px-2 py-1 rounded">
                                                    {{ bien.codigo_patrimonio || bien.codigo_interno || '—' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="text-sm font-semibold text-slate-800">{{ bien.denominacion }}
                                                </p>
                                                <p v-if="bien.numero_serie" class="text-xs text-slate-400 mt-0.5">S/N:
                                                    {{ bien.numero_serie
                                                    }}</p>
                                            </td>
                                            <td class="px-4 py-3 hidden sm:table-cell">
                                                <span class="text-sm text-slate-600">{{ bien.category?.nombre || '—'
                                                    }}</span>
                                            </td>
                                            <td class="px-4 py-3 hidden md:table-cell">
                                                <span v-if="bien.latest_movement?.state" :class="[
                                                    'inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold',
                                                    getStateClass(bien.latest_movement.state.nombre)
                                                ]">{{ bien.latest_movement.state.nombre }}</span>
                                                <span v-else class="text-slate-400 text-sm">—</span>
                                            </td>
                                            <td class="px-4 py-3 hidden lg:table-cell">
                                                <span class="text-sm text-slate-600">{{
                                                    bien.latest_movement?.office?.nombre || '—'
                                                    }}</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <ClientPagination :total-items="misBienesTotal" :current-page="misBienesPage"
                                :per-page="misBienesPerPage" :per-page-options="[10, 15, 25, 50]"
                                @update:current-page="fetchMisBienes($event)"
                                @update:per-page="misBienesPerPage = $event" />
                        </BaseTableCard>
                    </div>

                </div>
            </Transition>

            <!-- Modals (outside Transition) -->
            <AssetModal v-if="showCreateModal" :categories="categories" :brands="brands" :colors="colors"
                :states="states" :origins="origins" :areas="areas" :offices="offices" :employees="employees"
                @close="showCreateModal = false" @success="handleAssetCreated" />
            <AssetModal v-if="showEditModal" :asset="editingAsset" :categories="categories" :brands="brands"
                :colors="colors" :states="states" :origins="origins" :areas="areas" :offices="offices"
                :employees="employees" @close="showEditModal = false" @success="handleAssetUpdated" />
            <BarcodeScannerModal v-if="showBarcodeScanner" @close="showBarcodeScanner = false"
                @found="handleBarcodeFound" />
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
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    Plus,
    SlidersHorizontal,
    ChevronDown,
    Search,
    FileDown,
    Box,
    CheckCircle,
    ArrowRightLeft,
    AlertTriangle,
    XCircle,
    Settings,
    Barcode,
    ScanBarcode,
    Pencil,
    Trash2,
    Loader2,
    PackageOpen,
    X,
    Database as DatabaseIcon,
    ClipboardList,
    UserCheck,
} from 'lucide-vue-next';
import BaseTableCard from '@/Components/Common/BaseTableCard.vue';
import ClientPagination from '@/Components/Common/ClientPagination.vue';
import axios from 'axios';
import Swal from 'sweetalert2';
import AssetModal from '@/Components/Assets/AssetModal.vue';
import BarcodeGenerator from '@/Components/Assets/BarcodeGenerator.vue';
import MovementsList from '@/Components/Assets/MovementsList.vue';
import BarcodeScannerModal from '@/Components/Assets/BarcodeScannerModal.vue';
import ReportsTab from '@/Components/Assets/ReportsTab.vue';
import PatrimonioSigaTab from '@/Components/Assets/PatrimonioSigaTab.vue';
import InventariosTab from '@/Components/Assets/InventariosTab.vue';
import { useTabPermission } from '@/composables/useTabPermission';

const props = defineProps({
    myEmployee: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    colors: { type: Array, default: () => [] },
    states: { type: Array, default: () => [] },
    origins: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
    offices: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    movementTypes: { type: Array, default: () => [] },
});

const page = usePage();
const userRole = computed(() => page.props.auth?.user?.rol_id);
const isEmployeeOnly = computed(() => userRole.value === 'ROL012');

const { canViewTab, firstAllowedTab } = useTabPermission('patrimonio', ['list', 'movements', 'barcodes', 'reports', 'patrimonio', 'inventarios']);
const activeTab = ref(props.myEmployee && isEmployeeOnly.value ? 'mis_bienes' : firstAllowedTab.value);
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingAsset = ref(null);
const movementsListRef = ref(null);
const inventariosTabRef = ref(null);
const showBarcodeScanner = ref(false);

// Filters visibility (colapsable)
const FILTERS_KEY = 'assets_filters_open';
const filtersVisible = ref(localStorage.getItem(FILTERS_KEY) === 'true');
watch(filtersVisible, (val) => localStorage.setItem(FILTERS_KEY, String(val)));

const MOV_FILTERS_KEY = 'assets_mov_filters_open';
const movFiltersVisible = ref(localStorage.getItem(MOV_FILTERS_KEY) !== 'false');
watch(movFiltersVisible, (val) => localStorage.setItem(MOV_FILTERS_KEY, String(val)));

// Gliding tab indicator
const tabsRef = ref(null);
const indicatorStyle = ref({ left: '0px', width: '0px', backgroundColor: '' });

const getIndicatorColor = (tab) => {
    const colors = {
        mis_bienes: '#059669',
        list: '#475569',
        movements: '#2563eb',
        barcodes: '#475569',
        reports: '#16a34a',
        patrimonio: '#4f46e5',
        inventarios: '#9333ea',
    };
    return colors[tab] || '#475569';
};

const updateIndicator = () => {
    if (!tabsRef.value) return;
    const activeBtn = tabsRef.value.querySelector('.active-tab');
    if (activeBtn) {
        indicatorStyle.value = {
            left: `${activeBtn.offsetLeft}px`,
            width: `${activeBtn.offsetWidth}px`,
            backgroundColor: getIndicatorColor(activeTab.value),
        };
    }
};

const openNewMovement = () => {
    if (movementsListRef.value) {
        movementsListRef.value.openModal();
    }
};

const openNewInventario = () => {
    if (inventariosTabRef.value) {
        inventariosTabRef.value.openCreateModal();
    }
};


watch(activeTab, () => nextTick(updateIndicator));

const handleBarcodeFound = (code) => {
    showBarcodeScanner.value = false;
    if (activeTab.value === 'list') {
        listSearch.value = code;
    } else if (activeTab.value === 'movements') {
        if (movementsListRef.value) {
            movementsListRef.value.setSearch(code);
        }
    }
};

// Stats
const stats = ref({ total: 0, buenos: 0, regulares: 0, malos: 0 });

const fetchStats = async () => {
    try {
        const response = await axios.get('/assets/summary');
        stats.value = response.data;
    } catch (error) {
        console.error('Error fetching stats:', error);
    }
};

// List data
const listAssets = ref([]);
const listLoading = ref(false);
const listSearch = ref('');
const listEstadoId = ref('');
const listCategoriaId = ref('');
const listCurrentPage = ref(1);
const listLastPage = ref(1);
const listTotal = ref(0);
const listPerPage = ref(15);

const fetchListAssets = async (page = 1) => {
    listLoading.value = true;
    try {
        const response = await axios.get('/assets/list', {
            params: {
                search: listSearch.value || undefined,
                estado_id: listEstadoId.value || undefined,
                categoria_id: listCategoriaId.value || undefined,
                per_page: listPerPage.value,
                page,
            },
        });
        listAssets.value = response.data.data;
        listCurrentPage.value = response.data.current_page;
        listLastPage.value = response.data.last_page;
        listTotal.value = response.data.total;
    } catch (error) {
        console.error('Error fetching assets:', error);
    } finally {
        listLoading.value = false;
    }
};

let searchTimeout = null;
watch(listSearch, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchListAssets(1), 400);
});

watch([listEstadoId, listCategoriaId], () => fetchListAssets(1));
watch(listPerPage, () => fetchListAssets(1));

const clearListFilters = () => {
    listSearch.value = '';
    listEstadoId.value = '';
    listCategoriaId.value = '';
    fetchListAssets(1);
};

// State badge classes (estilo Ocurrencias)
const getStateClass = (nombre) => {
    const classes = {
        'BUENO': 'bg-gradient-to-r from-green-500 to-green-600 text-white',
        'REGULAR': 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
        'MALO': 'bg-gradient-to-r from-red-500 to-red-600 text-white',
    };
    return classes[nombre] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white';
};

// Edit
const openEditModal = (asset) => {
    editingAsset.value = asset;
    showEditModal.value = true;
};

const handleAssetUpdated = () => {
    showEditModal.value = false;
    editingAsset.value = null;
    fetchListAssets(listCurrentPage.value);
    fetchStats();
    Swal.fire({
        icon: 'success',
        title: 'Bien actualizado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
    });
};

// Delete con SweetAlert2
const confirmDelete = async (asset) => {
    const result = await Swal.fire({
        title: '¿Eliminar este bien?',
        html: `<p class="text-sm text-gray-500">Se eliminará permanentemente el bien:</p>
               <p class="font-mono font-bold mt-2">${asset.codigo_patrimonio} — ${asset.denominacion}</p>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(`/assets/${asset.id}`);
            fetchListAssets(listCurrentPage.value);
            fetchStats();
            Swal.fire({
                icon: 'success',
                title: 'Bien eliminado',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
            });
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo eliminar el bien. Puede tener movimientos asociados.',
            });
        }
    }
};

// Create
const handleAssetCreated = () => {
    showCreateModal.value = false;
    fetchListAssets(1);
    fetchStats();
    Swal.fire({
        icon: 'success',
        title: 'Bien registrado',
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 2500,
    });
};

// ===== MIS BIENES =====
const misBienes = ref([]);
const misBienesLoading = ref(false);
const misBienesSearch = ref('');
const misBienesPage = ref(1);
const misBienesLastPage = ref(1);
const misBienesTotal = ref(0);
const misBienesPerPage = ref(15);

const fetchMisBienes = async (page = 1) => {
    misBienesLoading.value = true;
    try {
        const response = await axios.get('/assets/mis-bienes', {
            params: {
                search: misBienesSearch.value || undefined,
                per_page: misBienesPerPage.value,
                page,
            },
        });
        misBienes.value = response.data.data;
        misBienesPage.value = response.data.current_page;
        misBienesLastPage.value = response.data.last_page;
        misBienesTotal.value = response.data.total;
    } catch {
        misBienes.value = [];
    } finally {
        misBienesLoading.value = false;
    }
};

let misBienesTimeout = null;
watch(misBienesSearch, () => {
    clearTimeout(misBienesTimeout);
    misBienesTimeout = setTimeout(() => fetchMisBienes(1), 400);
});
watch(misBienesPerPage, () => fetchMisBienes(1));

onMounted(() => {
    if (props.myEmployee) fetchMisBienes(1);
    if (!isEmployeeOnly.value) {
        fetchStats();
        fetchListAssets(1);
    }
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
