<template>
    <div class="space-y-6">

        <!-- ===== VISTA DETALLE ===== -->
        <template v-if="view === 'detail'">
            <!-- Back + Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button @click="view = 'list'"
                        class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:bg-slate-100 transition-colors">
                        <ArrowLeft class="w-5 h-5" />
                    </button>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-xl font-black text-slate-800">{{ selectedInventario.nombre }}</h2>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                                :class="tipoClass(selectedInventario.tipo)">
                                {{ tipoLabel(selectedInventario.tipo) }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-0.5">
                            Año {{ selectedInventario.anio }} ·
                            {{ formatDate(selectedInventario.fecha_inicio) }}
                            <template v-if="selectedInventario.fecha_fin"> — {{ formatDate(selectedInventario.fecha_fin) }}</template>
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs font-bold"
                                :class="estadoClass(selectedInventario.estado)">
                                {{ estadoLabel(selectedInventario.estado) }}
                            </span>
                        </p>
                        <!-- Saliente (solo ROTACION) -->
                        <p v-if="selectedInventario.tipo === 'ROTACION' && selectedInventario.responsable_saliente"
                            class="text-sm text-orange-600 font-semibold mt-1 flex items-center gap-1">
                            <UserMinus class="w-4 h-4" />
                            Saliente: {{ nombreEmpleado(selectedInventario.responsable_saliente) }}
                        </p>
                    </div>
                </div>

                <button v-if="selectedInventario.estado !== 'CERRADO'"
                    @click="openItemModal(null)"
                    class="inline-flex items-center px-5 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all">
                    <Plus class="w-4 h-4 mr-2" />
                    Agregar verificación
                </button>
            </div>

            <!-- Stats de verificaciones -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total verificados</p>
                    <h3 class="text-2xl font-black text-slate-800">{{ itemStats.total }}</h3>
                </div>
                <div v-for="(count, estado) in itemStats.por_estado" :key="estado"
                    class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm text-center">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">{{ estado }}</p>
                    <h3 class="text-2xl font-black" :class="estadoCountClass(estado)">{{ count }}</h3>
                </div>
            </div>

            <!-- Filtros items -->
            <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Buscar bien</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                            <input v-model="itemSearch" type="text"
                                placeholder="Código patrimonial, denominación..."
                                class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                        <select v-model="itemFilterEstado"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Todos</option>
                            <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-700">{{ itemTotal }}</span> bienes verificados
                    </p>
                    <button @click="clearItemFilters"
                        class="text-sm font-semibold text-slate-500 hover:text-purple-600 transition-colors flex items-center gap-1">
                        <X class="w-4 h-4" /> Limpiar
                    </button>
                </div>
            </div>

            <!-- Tabla items -->
            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="itemsLoading" class="p-12 text-center">
                    <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                    <p class="text-sm text-slate-400 mt-2">Cargando verificaciones...</p>
                </div>

                <template v-else-if="items.length === 0">
                    <div class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="bg-slate-100 rounded-full p-4 mb-4">
                                <ClipboardList class="h-12 w-12 text-slate-400" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">Sin verificaciones registradas</h3>
                            <p class="text-sm text-slate-500">Agrega la verificación de los bienes encontrados en el inventario.</p>
                        </div>
                    </div>
                </template>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Bien</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Denominación</th>
                                <th class="px-4 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Estado</th>
                                <th v-if="selectedInventario.tipo === 'ROTACION'"
                                    class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">
                                    Resp. Anterior → Nuevo
                                </th>
                                <th v-else class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Responsable</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden lg:table-cell">Oficina</th>
                                <th class="px-4 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Fecha</th>
                                <th class="px-4 py-4 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="item in items" :key="item.id"
                                class="hover:bg-purple-50 transition-colors duration-200">
                                <td class="px-4 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-slate-800 font-mono">{{ item.asset?.codigo_patrimonio }}</div>
                                    <div class="text-xs text-slate-400 font-mono">{{ item.asset?.codigo_interno }}</div>
                                </td>
                                <td class="px-4 py-4 hidden md:table-cell">
                                    <div class="text-sm text-slate-700 max-w-xs line-clamp-2">{{ item.asset?.denominacion }}</div>
                                    <div v-if="item.asset?.brand" class="text-xs text-slate-400">{{ item.asset.brand.nombre }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-center">
                                    <span v-if="item.estado"
                                        class="px-3 py-1 inline-flex text-xs font-bold rounded-lg"
                                        :class="estadoItemClass(item.estado.nombre)">
                                        {{ item.estado.nombre }}
                                    </span>
                                    <span v-else class="text-xs text-slate-400">—</span>
                                </td>
                                <!-- Rotación: muestra anterior → nuevo -->
                                <td v-if="selectedInventario.tipo === 'ROTACION'"
                                    class="px-4 py-4 hidden lg:table-cell">
                                    <div class="flex items-center gap-1 text-sm">
                                        <span class="text-slate-500 line-through text-xs">
                                            {{ responsableNombre(item.responsable_anterior) || '—' }}
                                        </span>
                                        <ArrowRight class="w-3 h-3 text-slate-400 flex-shrink-0" />
                                        <span class="font-semibold text-slate-800">
                                            {{ responsableNombre(item.responsable) || '—' }}
                                        </span>
                                    </div>
                                </td>
                                <!-- Otros tipos: solo responsable actual -->
                                <td v-else class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                    <div class="text-sm text-slate-600">{{ responsableNombre(item.responsable) || '—' }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden lg:table-cell">
                                    <div class="text-sm text-slate-600">{{ item.oficina?.nombre || '—' }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap hidden md:table-cell">
                                    <div class="text-sm text-slate-500">{{ formatDate(item.fecha_verificacion) }}</div>
                                    <div class="text-xs text-slate-400">{{ item.inventariador?.name }}</div>
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center gap-1 justify-end">
                                        <button v-if="selectedInventario.estado !== 'CERRADO'"
                                            @click="openItemModal(item)"
                                            class="text-slate-400 hover:text-blue-600 transition-colors p-2 hover:bg-blue-50 rounded-lg"
                                            title="Editar">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <button v-if="selectedInventario.estado !== 'CERRADO'"
                                            @click="confirmDeleteItem(item)"
                                            class="text-slate-400 hover:text-red-600 transition-colors p-2 hover:bg-red-50 rounded-lg"
                                            title="Eliminar">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination items -->
                <div v-if="items.length > 0" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <span>Mostrar</span>
                            <select v-model="itemPerPage"
                                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 bg-white">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span>por página</span>
                        </div>
                        <div class="text-sm text-slate-600">Página {{ itemCurrentPage }} de {{ itemLastPage }}</div>
                        <div class="flex items-center gap-1">
                            <button @click="fetchItems(1)" :disabled="itemCurrentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsLeft class="w-4 h-4" />
                            </button>
                            <button @click="fetchItems(itemCurrentPage - 1)" :disabled="itemCurrentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronLeft class="w-4 h-4" />
                            </button>
                            <button @click="fetchItems(itemCurrentPage + 1)" :disabled="itemCurrentPage === itemLastPage"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronRight class="w-4 h-4" />
                            </button>
                            <button @click="fetchItems(itemLastPage)" :disabled="itemCurrentPage === itemLastPage"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== VISTA LISTA ===== -->
        <template v-else>
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-slate-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center justify-center text-slate-600 mb-3 group-hover:scale-110 transition-transform">
                            <ClipboardList class="w-5 h-5" />
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Total</p>
                        <h3 class="text-2xl font-black text-slate-900">{{ stats.total }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-blue-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mb-3 group-hover:scale-110 transition-transform">
                            <Clock class="w-5 h-5" />
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Pendientes</p>
                        <h3 class="text-2xl font-black text-blue-600">{{ stats.pendientes }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-amber-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 mb-3 group-hover:scale-110 transition-transform">
                            <Loader2 class="w-5 h-5" />
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">En Proceso</p>
                        <h3 class="text-2xl font-black text-amber-600">{{ stats.en_proceso }}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-16 h-16 bg-green-50 rounded-bl-[60px] -mr-2 -mt-2 transition-transform group-hover:scale-110"></div>
                    <div class="relative">
                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center text-green-600 mb-3 group-hover:scale-110 transition-transform">
                            <CheckCircle class="w-5 h-5" />
                        </div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-0.5">Cerrados</p>
                        <h3 class="text-2xl font-black text-green-600">{{ stats.cerrados }}</h3>
                    </div>
                </div>
            </div>

            <!-- Filtros + Nuevo -->
            <div class="bg-white shadow-lg rounded-2xl border border-slate-200 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Año</label>
                        <select v-model="filterAnio"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Todos los años</option>
                            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tipo</label>
                        <select v-model="filterTipo"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Todos</option>
                            <option value="ANUAL">Anual</option>
                            <option value="ROTACION">Rotación de Personal</option>
                            <option value="EXTRAORDINARIO">Extraordinario</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                        <select v-model="filterEstado"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Todos</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="EN_PROCESO">En Proceso</option>
                            <option value="CERRADO">Cerrado</option>
                        </select>
                    </div>
                    <div class="flex items-end justify-end">
                        <button @click="openCreateModal"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-purple-300 transition-all">
                            <Plus class="w-4 h-4 mr-2" />
                            Nuevo Inventario
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between mt-4 pt-4 border-t border-slate-100">
                    <p class="text-sm text-slate-500">
                        <span class="font-semibold text-slate-700">{{ total }}</span> inventarios encontrados
                    </p>
                    <button @click="clearFilters"
                        class="text-sm font-semibold text-slate-500 hover:text-purple-600 transition-colors flex items-center gap-1">
                        <X class="w-4 h-4" /> Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Tabla inventarios -->
            <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="loading" class="p-12 text-center">
                    <Loader2 class="w-8 h-8 mx-auto text-slate-400 animate-spin" />
                    <p class="text-sm text-slate-400 mt-2">Cargando inventarios...</p>
                </div>

                <template v-else-if="inventarios.length === 0">
                    <div class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center">
                            <div class="bg-slate-100 rounded-full p-4 mb-4">
                                <ClipboardList class="h-12 w-12 text-slate-400" />
                            </div>
                            <h3 class="text-lg font-bold text-slate-900 mb-1">No hay inventarios registrados</h3>
                            <p class="text-sm text-slate-500">Crea el primer inventario para comenzar.</p>
                        </div>
                    </div>
                </template>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Año</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Nombre</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden sm:table-cell">Tipo</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Inicio</th>
                                <th class="px-5 py-4 text-left text-xs font-bold text-slate-600 uppercase tracking-wider hidden md:table-cell">Fin</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider hidden sm:table-cell">Bienes</th>
                                <th class="px-5 py-4 text-center text-xs font-bold text-slate-600 uppercase tracking-wider">Estado</th>
                                <th class="px-5 py-4 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="inv in inventarios" :key="inv.id"
                                class="hover:bg-purple-50 transition-colors duration-200">
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="text-lg font-black text-slate-800">{{ inv.anio }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-sm font-semibold text-slate-800">{{ inv.nombre }}</div>
                                    <div v-if="inv.tipo === 'ROTACION' && inv.responsable_saliente"
                                        class="text-xs text-orange-600 mt-0.5 flex items-center gap-1">
                                        <UserMinus class="w-3 h-3" />
                                        {{ nombreEmpleado(inv.responsable_saliente) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center hidden sm:table-cell">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-bold rounded-full"
                                        :class="tipoClass(inv.tipo)">
                                        {{ tipoLabel(inv.tipo) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                                    <div class="text-sm text-slate-600">{{ formatDate(inv.fecha_inicio) }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap hidden md:table-cell">
                                    <div class="text-sm text-slate-600">{{ inv.fecha_fin ? formatDate(inv.fecha_fin) : '—' }}</div>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center hidden sm:table-cell">
                                    <span class="text-sm font-bold text-purple-700 bg-purple-50 px-3 py-1 rounded-full">
                                        {{ inv.items_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-center">
                                    <span class="px-3 py-1 inline-flex text-xs font-bold rounded-full"
                                        :class="estadoClass(inv.estado)">
                                        {{ estadoLabel(inv.estado) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-right">
                                    <div class="flex items-center gap-1 justify-end">
                                        <!-- Ver detalle -->
                                        <button @click="openDetail(inv)"
                                            class="text-slate-400 hover:text-purple-600 transition-colors p-2 hover:bg-purple-50 rounded-lg"
                                            title="Ver verificaciones">
                                            <Eye class="h-4 w-4" />
                                        </button>
                                        <!-- Cambiar estado -->
                                        <button v-if="inv.estado === 'PENDIENTE'" @click="changeEstado(inv, 'EN_PROCESO')"
                                            class="text-slate-400 hover:text-amber-600 transition-colors p-2 hover:bg-amber-50 rounded-lg"
                                            title="Iniciar">
                                            <Play class="h-4 w-4" />
                                        </button>
                                        <button v-if="inv.estado === 'EN_PROCESO'" @click="changeEstado(inv, 'CERRADO')"
                                            class="text-slate-400 hover:text-green-600 transition-colors p-2 hover:bg-green-50 rounded-lg"
                                            title="Cerrar inventario">
                                            <CheckCircle class="h-4 w-4" />
                                        </button>
                                        <button v-if="inv.estado === 'CERRADO'" @click="changeEstado(inv, 'EN_PROCESO')"
                                            class="text-slate-400 hover:text-amber-600 transition-colors p-2 hover:bg-amber-50 rounded-lg"
                                            title="Reabrir">
                                            <RotateCcw class="h-4 w-4" />
                                        </button>
                                        <!-- Editar -->
                                        <button @click="openEditModal(inv)"
                                            class="text-slate-400 hover:text-blue-600 transition-colors p-2 hover:bg-blue-50 rounded-lg"
                                            title="Editar">
                                            <Pencil class="h-4 w-4" />
                                        </button>
                                        <!-- Eliminar -->
                                        <button v-if="inv.estado !== 'CERRADO'" @click="confirmDelete(inv)"
                                            class="text-slate-400 hover:text-red-600 transition-colors p-2 hover:bg-red-50 rounded-lg"
                                            title="Eliminar">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="inventarios.length > 0" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <span>Mostrar</span>
                            <select v-model="perPage"
                                class="border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-purple-500 bg-white">
                                <option :value="10">10</option>
                                <option :value="25">25</option>
                                <option :value="50">50</option>
                            </select>
                            <span>por página</span>
                        </div>
                        <div class="text-sm text-slate-600">Página {{ currentPage }} de {{ lastPage }}</div>
                        <div class="flex items-center gap-1">
                            <button @click="fetchInventarios(1)" :disabled="currentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsLeft class="w-4 h-4" />
                            </button>
                            <button @click="fetchInventarios(currentPage - 1)" :disabled="currentPage === 1"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronLeft class="w-4 h-4" />
                            </button>
                            <button @click="fetchInventarios(currentPage + 1)" :disabled="currentPage === lastPage"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronRight class="w-4 h-4" />
                            </button>
                            <button @click="fetchInventarios(lastPage)" :disabled="currentPage === lastPage"
                                class="p-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed">
                                <ChevronsRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- ===== MODAL INVENTARIO (crear/editar) ===== -->
        <div v-if="showInvModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeInvModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <h2 class="text-lg font-bold text-slate-800">
                        {{ editingInvId ? 'Editar Inventario' : 'Nuevo Inventario' }}
                    </h2>
                    <button @click="closeInvModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="saveInventario" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Año *</label>
                            <input v-model.number="invForm.anio" type="number" min="2000" max="2100" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado</label>
                            <select v-if="editingInvId" v-model="invForm.estado"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="EN_PROCESO">En Proceso</option>
                                <option value="CERRADO">Cerrado</option>
                            </select>
                            <div v-else class="px-4 py-2.5 border border-slate-100 rounded-xl bg-slate-50 text-sm text-slate-500">
                                Pendiente (inicial)
                            </div>
                        </div>
                    </div>

                    <!-- Tipo de inventario -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Tipo de inventario *</label>
                        <div class="grid grid-cols-3 gap-2">
                            <label v-for="opt in tipoOpciones" :key="opt.value"
                                class="flex flex-col items-center gap-1 p-3 border-2 rounded-xl cursor-pointer transition-all text-center"
                                :class="invForm.tipo === opt.value
                                    ? 'border-purple-500 bg-purple-50'
                                    : 'border-slate-200 hover:border-slate-300'">
                                <input type="radio" v-model="invForm.tipo" :value="opt.value" class="sr-only" />
                                <component :is="opt.icon" class="w-5 h-5" :class="invForm.tipo === opt.value ? 'text-purple-600' : 'text-slate-400'" />
                                <span class="text-xs font-bold" :class="invForm.tipo === opt.value ? 'text-purple-700' : 'text-slate-500'">{{ opt.label }}</span>
                            </label>
                        </div>
                    </div>

                    <!-- Empleado saliente (solo ROTACION) -->
                    <div v-if="invForm.tipo === 'ROTACION'"
                        class="p-4 border border-orange-200 rounded-xl bg-orange-50 space-y-2">
                        <label class="block text-xs font-bold text-orange-700 uppercase tracking-wider">
                            Empleado saliente / Rotación
                        </label>
                        <select v-model="invForm.responsable_saliente_id"
                            class="w-full px-4 py-2.5 border border-orange-200 rounded-xl focus:ring-2 focus:ring-orange-400 text-sm bg-white transition-all">
                            <option value="">Sin especificar</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.nombre_completo }}
                            </option>
                        </select>
                        <p class="text-xs text-orange-600">Empleado cuyos bienes serán reasignados durante este inventario.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nombre *</label>
                        <input v-model="invForm.nombre" type="text" required maxlength="200"
                            placeholder="Ej: Inventario Anual 2025"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Inicio *</label>
                            <input v-model="invForm.fecha_inicio" type="date" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Fin</label>
                            <input v-model="invForm.fecha_fin" type="date" :min="invForm.fecha_inicio"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Descripción</label>
                        <textarea v-model="invForm.descripcion" rows="3"
                            placeholder="Observaciones sobre este inventario..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeInvModal"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="invSaving"
                            class="inline-flex items-center px-6 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:ring-4 focus:ring-purple-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="invSaving" class="w-4 h-4 mr-2 animate-spin" />
                            {{ invSaving ? 'Guardando...' : (editingInvId ? 'Guardar cambios' : 'Crear inventario') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ===== MODAL VERIFICACIÓN (item) ===== -->
        <div v-if="showItemModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="closeItemModal"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-6 border-b border-slate-100 sticky top-0 bg-white z-10">
                    <h2 class="text-lg font-bold text-slate-800">
                        {{ editingItemId ? 'Editar verificación' : 'Agregar verificación' }}
                    </h2>
                    <button @click="closeItemModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="saveItem" class="p-6 space-y-4">
                    <!-- Búsqueda de bien -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bien patrimonial *</label>

                        <!-- Si ya está seleccionado -->
                        <div v-if="itemForm.selectedAsset" class="flex items-center gap-3 p-3 border border-purple-200 rounded-xl bg-purple-50">
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-bold text-slate-800 font-mono">{{ itemForm.selectedAsset.codigo_completo }}</div>
                                <div class="text-xs text-slate-600 truncate">{{ itemForm.selectedAsset.denominacion }}</div>
                            </div>
                            <button v-if="!editingItemId" type="button" @click="clearAssetSelection"
                                class="text-slate-400 hover:text-red-500 transition-colors flex-shrink-0">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Búsqueda -->
                        <template v-else>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input v-model="assetSearchQuery" type="text"
                                    placeholder="Buscar por código o denominación..."
                                    class="w-full pl-9 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all"
                                    @input="onAssetSearch" />
                                <Loader2 v-if="assetSearching" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 animate-spin" />
                            </div>
                            <!-- Sugerencias -->
                            <div v-if="assetSuggestions.length > 0"
                                class="mt-1 border border-slate-200 rounded-xl overflow-hidden shadow-lg bg-white z-10">
                                <button v-for="asset in assetSuggestions" :key="asset.id"
                                    type="button"
                                    @click="selectAsset(asset)"
                                    class="w-full text-left px-4 py-3 hover:bg-purple-50 transition-colors border-b border-slate-100 last:border-0">
                                    <div class="text-sm font-semibold text-slate-800 font-mono">{{ asset.codigo_completo }}</div>
                                    <div class="text-xs text-slate-500 truncate">{{ asset.denominacion }}</div>
                                </button>
                            </div>
                            <p v-if="assetSearchQuery.length >= 2 && !assetSearching && assetSuggestions.length === 0"
                                class="mt-1 text-xs text-slate-400">No se encontraron bienes.</p>
                        </template>
                    </div>

                    <!-- Estado encontrado -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado encontrado</label>
                        <select v-model="itemForm.estado_id"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Sin especificar</option>
                            <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                        </select>
                    </div>

                    <!-- Oficina donde se encontró -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Oficina / Ubicación</label>
                        <select v-model="itemForm.oficina_id"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Sin especificar</option>
                            <option v-for="of in offices" :key="of.id" :value="of.id">{{ of.nombre }}</option>
                        </select>
                    </div>

                    <!-- Responsable -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">
                            {{ selectedInventario?.tipo === 'ROTACION' ? 'Nuevo responsable (reasignado)' : 'Responsable' }}
                        </label>
                        <select v-model="itemForm.employee_id"
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm bg-white transition-all">
                            <option value="">Sin especificar</option>
                            <option v-for="emp in employees" :key="emp.id" :value="emp.id">
                                {{ emp.nombre_completo }}
                            </option>
                        </select>
                    </div>

                    <!-- Fecha verificación -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de verificación *</label>
                        <input v-model="itemForm.fecha_verificacion" type="date" required
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all" />
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Observaciones</label>
                        <textarea v-model="itemForm.observaciones" rows="2"
                            placeholder="Notas sobre el estado del bien..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 text-sm transition-all resize-none"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" @click="closeItemModal"
                            class="px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="itemSaving || !itemForm.selectedAsset"
                            class="inline-flex items-center px-6 py-2.5 text-sm font-bold rounded-xl shadow-lg text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:ring-4 focus:ring-purple-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="itemSaving" class="w-4 h-4 mr-2 animate-spin" />
                            {{ itemSaving ? 'Guardando...' : (editingItemId ? 'Guardar cambios' : 'Registrar verificación') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';
import {
    ClipboardList, Clock, CheckCircle, Loader2, Plus, X,
    Pencil, Trash2, Play, RotateCcw, Eye, ArrowLeft, ArrowRight,
    Search, ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight,
    CalendarDays, UserMinus, Zap,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

const props = defineProps({
    states:    { type: Array, default: () => [] },
    offices:   { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});

// ===== VISTA =====
const view               = ref('list');   // 'list' | 'detail'
const selectedInventario = ref(null);

const openDetail = async (inv) => {
    selectedInventario.value = inv;
    view.value = 'detail';
    await Promise.all([fetchItems(1), fetchItemStats()]);
};

// ===== STATS INVENTARIOS =====
const stats = ref({ total: 0, pendientes: 0, en_proceso: 0, cerrados: 0 });

const fetchStats = async () => {
    try {
        const res = await axios.get('/assets/inventarios/stats');
        stats.value = res.data;
    } catch (e) { console.error(e); }
};

// ===== LISTA INVENTARIOS =====
const currentYear    = new Date().getFullYear();
const availableYears = computed(() => {
    const years = [];
    for (let y = currentYear + 1; y >= 2020; y--) years.push(y);
    return years;
});

const filterAnio   = ref('');
const filterEstado = ref('');
const filterTipo   = ref('');
const inventarios  = ref([]);
const loading      = ref(false);
const currentPage  = ref(1);
const lastPage     = ref(1);
const total        = ref(0);
const perPage      = ref(15);

const fetchInventarios = async (page = 1) => {
    loading.value = true;
    try {
        const res = await axios.get('/assets/inventarios', {
            params: {
                anio:     filterAnio.value   || undefined,
                estado:   filterEstado.value || undefined,
                tipo:     filterTipo.value   || undefined,
                per_page: perPage.value,
                page,
            },
        });
        inventarios.value = res.data.data;
        currentPage.value = res.data.current_page;
        lastPage.value    = res.data.last_page;
        total.value       = res.data.total;
    } catch (e) { console.error(e); }
    finally { loading.value = false; }
};

watch([filterAnio, filterEstado, filterTipo], () => fetchInventarios(1));
watch(perPage, () => fetchInventarios(1));

const clearFilters = () => { filterAnio.value = ''; filterEstado.value = ''; filterTipo.value = ''; fetchInventarios(1); };

// ===== CAMBIO DE ESTADO =====
const changeEstado = async (inv, nuevoEstado) => {
    const labels = { PENDIENTE: 'Pendiente', EN_PROCESO: 'En Proceso', CERRADO: 'Cerrado' };
    const result = await Swal.fire({
        title: `Cambiar a "${labels[nuevoEstado]}"`,
        text: nuevoEstado === 'CERRADO'
            ? 'Se cerrará el inventario. Se registrará la fecha de hoy si no tiene fecha de fin.'
            : `El inventario pasará al estado "${labels[nuevoEstado]}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: nuevoEstado === 'CERRADO' ? '#16a34a' : '#d97706',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;

    try {
        const res = await axios.put(`/assets/inventarios/${inv.id}`, { estado: nuevoEstado });
        Swal.fire({ icon: 'success', title: 'Estado actualizado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        // Actualizar en lista sin recargar todo
        const idx = inventarios.value.findIndex(i => i.id === inv.id);
        if (idx >= 0) inventarios.value[idx] = { ...inventarios.value[idx], ...res.data };
        if (selectedInventario.value?.id === inv.id) selectedInventario.value = { ...selectedInventario.value, ...res.data };
        fetchStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'No se pudo actualizar.' });
    }
};

// ===== MODAL INVENTARIO =====
const showInvModal  = ref(false);
const editingInvId  = ref(null);
const invSaving     = ref(false);

const tipoOpciones = [
    { value: 'ANUAL',         label: 'Anual',       icon: CalendarDays },
    { value: 'ROTACION',      label: 'Rotación',    icon: UserMinus },
    { value: 'EXTRAORDINARIO', label: 'Extraordinario', icon: Zap },
];

const emptyInvForm = () => ({
    anio:                    currentYear,
    tipo:                    'ANUAL',
    nombre:                  `Inventario Anual ${currentYear}`,
    descripcion:             '',
    fecha_inicio:            new Date().toISOString().split('T')[0],
    fecha_fin:               '',
    estado:                  'PENDIENTE',
    responsable_saliente_id: '',
});

const invForm = ref(emptyInvForm());

const openCreateModal = () => { editingInvId.value = null; invForm.value = emptyInvForm(); showInvModal.value = true; };
const openEditModal   = (inv) => {
    editingInvId.value = inv.id;
    invForm.value = {
        anio:                    inv.anio,
        tipo:                    inv.tipo,
        nombre:                  inv.nombre,
        descripcion:             inv.descripcion || '',
        fecha_inicio:            inv.fecha_inicio,
        fecha_fin:               inv.fecha_fin || '',
        estado:                  inv.estado,
        responsable_saliente_id: inv.responsable_saliente_id || '',
    };
    showInvModal.value = true;
};
const closeInvModal = () => { showInvModal.value = false; editingInvId.value = null; };

const saveInventario = async () => {
    invSaving.value = true;
    try {
        const payload = { ...invForm.value };
        if (!payload.fecha_fin)               delete payload.fecha_fin;
        if (!payload.descripcion)             delete payload.descripcion;
        if (!payload.responsable_saliente_id) delete payload.responsable_saliente_id;

        if (editingInvId.value) {
            await axios.put(`/assets/inventarios/${editingInvId.value}`, payload);
        } else {
            await axios.post('/assets/inventarios', payload);
        }
        Swal.fire({ icon: 'success', title: editingInvId.value ? 'Inventario actualizado' : 'Inventario creado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        closeInvModal();
        fetchInventarios(currentPage.value);
        fetchStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'Ocurrió un error.' });
    } finally { invSaving.value = false; }
};

const confirmDelete = async (inv) => {
    const result = await Swal.fire({
        title: '¿Eliminar inventario?',
        html: `<p class="text-sm text-gray-500">Se eliminará permanentemente:</p><p class="font-bold mt-2">${inv.nombre} (${inv.anio})</p>`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    try {
        await axios.delete(`/assets/inventarios/${inv.id}`);
        Swal.fire({ icon: 'success', title: 'Inventario eliminado', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        fetchInventarios(currentPage.value);
        fetchStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'No se pudo eliminar.' });
    }
};

// ===== ITEMS (verificaciones) =====
const items          = ref([]);
const itemsLoading   = ref(false);
const itemCurrentPage = ref(1);
const itemLastPage   = ref(1);
const itemTotal      = ref(0);
const itemPerPage    = ref(15);
const itemSearch     = ref('');
const itemFilterEstado = ref('');
const itemStats      = ref({ total: 0, por_estado: {} });

const fetchItems = async (page = 1) => {
    if (!selectedInventario.value) return;
    itemsLoading.value = true;
    try {
        const res = await axios.get(`/assets/inventarios/${selectedInventario.value.id}/items`, {
            params: {
                search:    itemSearch.value    || undefined,
                estado_id: itemFilterEstado.value || undefined,
                per_page:  itemPerPage.value,
                page,
            },
        });
        items.value          = res.data.data;
        itemCurrentPage.value = res.data.current_page;
        itemLastPage.value   = res.data.last_page;
        itemTotal.value      = res.data.total;
    } catch (e) { console.error(e); }
    finally { itemsLoading.value = false; }
};

const fetchItemStats = async () => {
    if (!selectedInventario.value) return;
    try {
        const res = await axios.get(`/assets/inventarios/${selectedInventario.value.id}/items/stats`);
        itemStats.value = res.data;
    } catch (e) { console.error(e); }
};

let itemSearchTimeout = null;
watch(itemSearch, () => { clearTimeout(itemSearchTimeout); itemSearchTimeout = setTimeout(() => fetchItems(1), 400); });
watch(itemFilterEstado, () => fetchItems(1));
watch(itemPerPage, () => fetchItems(1));

const clearItemFilters = () => { itemSearch.value = ''; itemFilterEstado.value = ''; fetchItems(1); };

// ===== MODAL ITEM =====
const showItemModal = ref(false);
const editingItemId = ref(null);
const itemSaving    = ref(false);

const emptyItemForm = () => ({
    selectedAsset:      null,
    asset_id:           null,
    estado_id:          '',
    oficina_id:         '',
    employee_id:        '',
    fecha_verificacion: new Date().toISOString().split('T')[0],
    observaciones:      '',
});
const itemForm = ref(emptyItemForm());

// Asset autocomplete
const assetSearchQuery  = ref('');
const assetSuggestions  = ref([]);
const assetSearching    = ref(false);
let   assetSearchTimer  = null;

const onAssetSearch = () => {
    clearTimeout(assetSearchTimer);
    if (assetSearchQuery.value.length < 2) { assetSuggestions.value = []; return; }
    assetSearchTimer = setTimeout(async () => {
        assetSearching.value = true;
        try {
            const res = await axios.get('/assets/list', { params: { search: assetSearchQuery.value, per_page: 8, page: 1 } });
            assetSuggestions.value = res.data.data;
        } catch (e) { console.error(e); }
        finally { assetSearching.value = false; }
    }, 350);
};

const selectAsset = (asset) => {
    itemForm.value.selectedAsset = asset;
    itemForm.value.asset_id = asset.id;
    assetSuggestions.value = [];
    assetSearchQuery.value = '';
};

const clearAssetSelection = () => {
    itemForm.value.selectedAsset = null;
    itemForm.value.asset_id = null;
};

const openItemModal = (item) => {
    if (item) {
        editingItemId.value = item.id;
        // Pre-fill with existing data
        const empId = item.responsable?.employee?.id ?? '';
        itemForm.value = {
            selectedAsset:      item.asset,
            asset_id:           item.asset?.id,
            estado_id:          item.estado?.id    ?? '',
            oficina_id:         item.oficina?.id   ?? '',
            employee_id:        empId,
            fecha_verificacion: item.fecha_verificacion,
            observaciones:      item.observaciones ?? '',
        };
    } else {
        editingItemId.value = null;
        itemForm.value = emptyItemForm();
    }
    assetSearchQuery.value = '';
    assetSuggestions.value = [];
    showItemModal.value = true;
};

const closeItemModal = () => {
    showItemModal.value = false;
    editingItemId.value = null;
    assetSuggestions.value = [];
};

const saveItem = async () => {
    if (!itemForm.value.asset_id) return;
    itemSaving.value = true;
    try {
        const payload = {
            asset_id:           itemForm.value.asset_id,
            estado_id:          itemForm.value.estado_id   || null,
            oficina_id:         itemForm.value.oficina_id  || null,
            employee_id:        itemForm.value.employee_id || null,
            fecha_verificacion: itemForm.value.fecha_verificacion,
            observaciones:      itemForm.value.observaciones || null,
        };
        await axios.post(`/assets/inventarios/${selectedInventario.value.id}/items`, payload);
        Swal.fire({ icon: 'success', title: editingItemId.value ? 'Verificación actualizada' : 'Verificación registrada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2500 });
        closeItemModal();
        fetchItems(itemCurrentPage.value);
        fetchItemStats();
    } catch (e) {
        const msg = e.response?.data?.message || 'Ocurrió un error.';
        Swal.fire({ icon: 'error', title: 'Error', text: msg });
    } finally { itemSaving.value = false; }
};

const confirmDeleteItem = async (item) => {
    const result = await Swal.fire({
        title: '¿Eliminar verificación?',
        text: `Se eliminará la verificación del bien ${item.asset?.codigo_patrimonio}.`,
        icon: 'warning', showCancelButton: true,
        confirmButtonColor: '#d33', cancelButtonColor: '#6b7280',
        confirmButtonText: 'Sí, eliminar', cancelButtonText: 'Cancelar',
    });
    if (!result.isConfirmed) return;
    try {
        await axios.delete(`/assets/inventarios/${selectedInventario.value.id}/items/${item.id}`);
        Swal.fire({ icon: 'success', title: 'Verificación eliminada', toast: true, position: 'top-end', showConfirmButton: false, timer: 2000 });
        fetchItems(itemCurrentPage.value);
        fetchItemStats();
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Error', text: e.response?.data?.message || 'No se pudo eliminar.' });
    }
};

// ===== HELPERS =====
const formatDate = (dateStr) => {
    if (!dateStr) return '—';
    const [y, m, d] = String(dateStr).split('T')[0].split('-');
    return `${d}/${m}/${y}`;
};

const estadoLabel = (e) => ({ PENDIENTE: 'Pendiente', EN_PROCESO: 'En Proceso', CERRADO: 'Cerrado' }[e] || e);
const estadoClass = (e) => ({ PENDIENTE: 'bg-blue-100 text-blue-700', EN_PROCESO: 'bg-amber-100 text-amber-700', CERRADO: 'bg-green-100 text-green-700' }[e] || 'bg-slate-100 text-slate-600');

const tipoLabel = (t) => ({ ANUAL: 'Anual', ROTACION: 'Rotación', EXTRAORDINARIO: 'Extraordinario' }[t] || t);
const tipoClass = (t) => ({
    ANUAL:          'bg-slate-100 text-slate-600',
    ROTACION:       'bg-orange-100 text-orange-700',
    EXTRAORDINARIO: 'bg-indigo-100 text-indigo-700',
}[t] || 'bg-slate-100 text-slate-600');

const nombreEmpleado = (emp) => {
    if (!emp) return null;
    const p = emp.person;
    if (p) return `${p.nombres ?? ''} ${p.apellido_paterno ?? ''}`.trim();
    return emp.nombre_completo ?? null;
};
const estadoItemClass = (nombre) => {
    const map = { BUENO: 'bg-green-100 text-green-700', REGULAR: 'bg-yellow-100 text-yellow-700', MALO: 'bg-red-100 text-red-700' };
    return map[nombre?.toUpperCase()] || 'bg-slate-100 text-slate-600';
};
const estadoCountClass = (nombre) => {
    const map = { BUENO: 'text-green-600', REGULAR: 'text-yellow-600', MALO: 'text-red-600' };
    return map[nombre?.toUpperCase()] || 'text-slate-700';
};

const responsableNombre = (responsable) => {
    if (!responsable) return null;
    const p = responsable.employee?.person;
    if (p) return `${p.nombres} ${p.apellido_paterno}`.trim();
    return responsable.nombre_original;
};

onMounted(() => {
    fetchStats();
    fetchInventarios(1);
});
</script>
