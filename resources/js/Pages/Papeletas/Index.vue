<template>
    <MainLayout>
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900">Papeletas de Salida</h1>
                <p class="text-sm text-slate-500 mt-1">Gestion y aprobacion de solicitudes de papeletas</p>
            </div>

            <div v-if="myEmployee" class="mb-6 rounded-2xl border p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                :class="registeredCertificate ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50'">
                <div class="flex items-start gap-3">
                    <ShieldCheck class="h-6 w-6 mt-0.5" :class="registeredCertificate ? 'text-emerald-600' : 'text-amber-600'" />
                    <div>
                        <p class="font-bold" :class="registeredCertificate ? 'text-emerald-900' : 'text-amber-900'">
                            {{ registeredCertificate ? 'Certificado RENIEC vinculado' : 'Debe vincular su certificado RENIEC' }}
                        </p>
                        <p class="text-xs mt-0.5" :class="registeredCertificate ? 'text-emerald-700' : 'text-amber-700'">
                            {{ registeredCertificate ? `Vigente hasta ${formatDate(registeredCertificate.valid_to)}` : 'Es obligatorio para firmar y enviar una papeleta.' }}
                        </p>
                    </div>
                </div>
                <button @click="showCertificateModal = true" class="px-4 py-2 rounded-xl text-sm font-bold text-white bg-slate-900 hover:bg-slate-800">
                    {{ registeredCertificate ? 'Renovar certificado' : 'Registrar .pfx' }}
                </button>
            </div>

            <!-- Stats (admin only) -->
            <div v-if="isAdminRole" class="grid grid-cols-2 sm:grid-cols-5 gap-3 sm:gap-4 mb-6">
                <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Total</p>
                    <p class="text-2xl font-black text-slate-900">{{ stats.total }}</p>
                </div>
                <div class="bg-yellow-50 rounded-xl p-4 shadow-sm border border-yellow-100 cursor-pointer hover:ring-2 ring-yellow-300 transition-all"
                    @click="activeTab = 'pendientes'">
                    <p class="text-xs font-semibold text-yellow-600 uppercase">Pendientes</p>
                    <p class="text-2xl font-black text-yellow-700">{{ stats.pendientes }}</p>
                </div>
                <div v-if="isHrRole" class="bg-orange-50 rounded-xl p-4 shadow-sm border border-orange-100 cursor-pointer hover:ring-2 ring-orange-300 transition-all"
                    @click="activeTab = 'pendientes_rrhh'">
                    <p class="text-xs font-semibold text-orange-600 uppercase">P. RRHH</p>
                    <p class="text-2xl font-black text-orange-700">{{ stats.pendientes_rrhh }}</p>
                </div>
                <div class="bg-green-50 rounded-xl p-4 shadow-sm border border-green-100">
                    <p class="text-xs font-semibold text-green-600 uppercase">Aprobadas</p>
                    <p class="text-2xl font-black text-green-700">{{ stats.aprobadas }}</p>
                </div>
                <div class="bg-red-50 rounded-xl p-4 shadow-sm border border-red-100">
                    <p class="text-xs font-semibold text-red-600 uppercase">Desaprobadas</p>
                    <p class="text-2xl font-black text-red-700">{{ stats.desaprobadas }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="border-b border-slate-100">
                    <nav class="flex">
                        <button v-if="myEmployee" @click="activeTab = 'mis_papeletas'"
                            :class="[activeTab === 'mis_papeletas' ? 'border-indigo-600 text-indigo-600 bg-indigo-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <ClipboardList class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Mis Papeletas
                        </button>
                        <button v-if="isBossRole && canViewTab('pendientes')" @click="activeTab = 'pendientes'"
                            :class="[activeTab === 'pendientes' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <Clock class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Pendientes
                            <span v-if="stats.pendientes > 0" class="ml-1.5 bg-yellow-100 text-yellow-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ stats.pendientes }}
                            </span>
                        </button>
                        <button v-if="isHrRole && canViewTab('pendientes_rrhh')" @click="activeTab = 'pendientes_rrhh'"
                            :class="[activeTab === 'pendientes_rrhh' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <Clock class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Pendientes RRHH
                            <span v-if="stats.pendientes_rrhh > 0" class="ml-1.5 bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ stats.pendientes_rrhh }}
                            </span>
                        </button>
                        <button v-if="isAdminRole && canViewTab('historial')" @click="activeTab = 'historial'"
                            :class="[activeTab === 'historial' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <History class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Historial
                        </button>
                        <button v-if="(userRole === 'ROL009' || userRole === 'ROL001') && canViewTab('reportes')" @click="activeTab = 'reportes'"
                            :class="[activeTab === 'reportes' ? 'border-blue-600 text-blue-600 bg-blue-50/50' : 'border-transparent text-slate-500 hover:text-slate-700', 'flex-1 sm:flex-none px-6 py-4 border-b-2 font-bold text-sm transition-all']">
                            <FileBarChart class="h-4 w-4 inline-block mr-1.5 -mt-0.5" />
                            Reportes
                        </button>
                    </nav>
                </div>

                <!-- Tab: Mis Papeletas -->
                <div v-if="activeTab === 'mis_papeletas'" class="p-4 sm:p-6">
                    <!-- Mini stats -->
                    <div class="grid grid-cols-3 gap-3 mb-5">
                        <div class="bg-slate-50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-slate-500 uppercase">Total</p>
                            <p class="text-xl font-black text-slate-800">{{ misPapeletas.length }}</p>
                        </div>
                        <div class="bg-green-50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-green-600 uppercase">Aprobadas</p>
                            <p class="text-xl font-black text-green-700">{{ misPapeletas.filter(p => p.estado === 'APROBADO').length }}</p>
                        </div>
                        <div class="bg-red-50 rounded-xl p-3 text-center">
                            <p class="text-xs font-semibold text-red-600 uppercase">Rechazadas</p>
                            <p class="text-xl font-black text-red-700">{{ misPapeletas.filter(p => p.estado === 'DESAPROBADO').length }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-slate-700">Mis solicitudes</h3>
                        <button @click="openCreateModal"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 shadow-md shadow-indigo-500/20 transition-all">
                            <Plus class="h-4 w-4" />
                            Nueva Papeleta
                        </button>
                    </div>

                    <div v-if="misPapeletasLoading" class="text-center py-10 text-slate-400">
                        <Loader2 class="h-7 w-7 animate-spin mx-auto mb-2" />
                    </div>

                    <div v-else-if="misPapeletas.length === 0" class="text-center py-12 text-slate-400">
                        <ClipboardList class="h-12 w-12 mx-auto mb-3 opacity-40" />
                        <p class="font-semibold">No tiene papeletas registradas</p>
                        <p class="text-sm mt-1">Cree una nueva papeleta para comenzar</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha Salida</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Control QR</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Documento</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="p in misPapeletas" :key="p.id" class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ p.numero_papeleta }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ formatDate(p.fecha_salida) }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">
                                        <div class="text-xs leading-5">
                                            <div><span class="font-semibold text-slate-600">Salida:</span> {{ formatQrTime(p.salida_real_at) }}</div>
                                            <div><span class="font-semibold text-slate-600">Retorno:</span> {{ formatQrTime(p.retorno_real_at) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="estadoBadgeClass(p.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                            {{ p.estado === 'PENDIENTE' ? 'Pendiente (Jefe)' : p.estado === 'PENDIENTE_RRHH' ? 'Pendiente (RRHH)' : p.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a v-if="p.estado === 'APROBADO' && (p.signatures?.length || 0) >= 3" :href="`/papeletas/${p.id}/pdf`" target="_blank" class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition-colors" title="PDF con las tres firmas digitales">
                                            <FileText class="h-4 w-4" />
                                            Papeleta firmada
                                        </a>
                                        <a v-else :href="`/papeletas/${p.id}/pdf`" target="_blank" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800 transition-colors" title="Ver papeleta pendiente de aprobación">
                                            <FileText class="h-4 w-4" />
                                            Vista previa
                                        </a>
                                        <button v-if="p.estado === 'APROBADO' && p.qr_token" @click="showControlQr(p)" class="ml-2 inline-flex items-center gap-1 text-xs font-semibold text-indigo-700 hover:text-indigo-900" title="QR para salida y retorno">
                                            QR control
                                        </button>
                                        <a v-if="p.estado === 'APROBADO'" :href="`/papeletas/${p.id}/constancia-qr.pdf`" target="_blank" class="ml-2 inline-flex text-xs font-semibold text-slate-600 hover:text-slate-900" title="Constancia de salida, retorno y destino">Constancia QR</a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Pendientes -->
                <div v-if="activeTab === 'pendientes'" class="p-4 sm:p-6">
                    <div v-if="loading" class="text-center py-12 text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
                        <p>Cargando...</p>
                    </div>

                    <div v-else-if="pendientes.length === 0" class="text-center py-12 text-slate-400">
                        <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No hay papeletas pendientes</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="p in pendientes" :key="p.id"
                            class="bg-white border border-slate-200 rounded-xl p-4 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono font-bold text-blue-600 text-sm">{{ p.numero_papeleta }}</span>
                                        <span :class="estadoBadgeClass(p.estado)" class="text-xs font-bold px-2 py-0.5 rounded-full">
                                            {{ p.estado === 'PENDIENTE' ? 'PENDIENTE (Jefe)' : 'PENDIENTE (RRHH)' }}
                                        </span>
                                        <span v-if="p.aprobado_por_jefe" class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                            <CheckCircle class="h-3 w-3 inline mr-1" />
                                            Jefe
                                        </span>
                                    </div>
                                    <p class="font-bold text-slate-800 text-sm">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ p.employee?.direction?.nombre ?? '-' }} |
                                        {{ formatDate(p.fecha_salida) }} |
                                        {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }} |
                                        {{ p.reason?.nombre }}
                                    </p>
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-1">{{ p.motivo }}</p>
                                </div>
                                <div v-if="canProcess(p)" class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="openApproveModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <CheckCircle class="h-3.5 w-3.5" />
                                        {{ p.estado === 'PENDIENTE' ? 'Aprobar (Jefe)' : 'Aprobar (RRHH)' }}
                                    </button>
                                    <button @click="openRejectModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <XCircle class="h-3.5 w-3.5" />
                                        Desaprobar
                                    </button>
                                    <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Ver PDF">
                                        <FileText class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Pendientes RRHH -->
                <div v-if="isHrRole && activeTab === 'pendientes_rrhh'" class="p-4 sm:p-6">
                    <div v-if="loading" class="text-center py-12 text-slate-400">
                        <Loader2 class="h-8 w-8 animate-spin mx-auto mb-2" />
                        <p>Cargando...</p>
                    </div>

                    <div v-else-if="pendientesRrhh.length === 0" class="text-center py-12 text-slate-400">
                        <CheckCircle class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No hay papeletas pendientes de RRHH</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="p in pendientesRrhh" :key="p.id"
                            class="bg-white border border-orange-200 rounded-xl p-4 hover:shadow-md transition-all">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-mono font-bold text-blue-600 text-sm">{{ p.numero_papeleta }}</span>
                                        <span class="bg-orange-100 text-orange-700 text-xs font-bold px-2 py-0.5 rounded-full">PENDIENTE RRHH</span>
                                        <span v-if="p.aprobado_por_jefe" class="bg-green-100 text-green-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                            <CheckCircle class="h-3 w-3 inline mr-1" />
                                            Jefe: {{ p.aprobador_jefe?.person?.apellidos }}
                                        </span>
                                    </div>
                                    <p class="font-bold text-slate-800 text-sm">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ p.employee?.direction?.nombre ?? '-' }} |
                                        {{ formatDate(p.fecha_salida) }} |
                                        {{ p.hora_salida_estimada }} - {{ p.hora_retorno_estimada || '--:--' }} |
                                        {{ p.reason?.nombre }}
                                    </p>
                                    <p class="text-xs text-slate-600 mt-1 line-clamp-1">{{ p.motivo }}</p>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="openApproveModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-green-600 hover:bg-green-700 transition-colors">
                                        <CheckCircle class="h-3.5 w-3.5" />
                                        Aprobar (RRHH)
                                    </button>
                                    <button @click="openRejectModal(p)"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-bold text-white bg-red-600 hover:bg-red-700 transition-colors">
                                        <XCircle class="h-3.5 w-3.5" />
                                        Desaprobar
                                    </button>
                                    <a :href="`/papeletas/${p.id}/pdf`" target="_blank"
                                        class="p-2 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors" title="Ver PDF">
                                        <FileText class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Historial -->
                <div v-if="activeTab === 'historial'" class="p-4 sm:p-6">
                    <!-- Filters -->
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-4">
                        <select v-model="filtros.estado" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20">
                            <option value="TODOS">Todos los estados</option>
                            <option value="PENDIENTE">Pendiente (Jefe)</option>
                            <option value="PENDIENTE_RRHH">Pendiente RRHH</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="DESAPROBADO">Desaprobado</option>
                        </select>
                        <input type="date" v-model="filtros.fecha_desde" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                            placeholder="Desde" />
                        <input type="date" v-model="filtros.fecha_hasta" @change="fetchHistorial"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 focus:border-blue-500 focus:ring-1 focus:ring-blue-500/20"
                            placeholder="Hasta" />
                        <button @click="resetFiltros"
                            class="rounded-lg border border-slate-200 text-sm px-3 py-2 text-slate-500 hover:bg-slate-50 font-semibold transition-colors">
                            Limpiar Filtros
                        </button>
                    </div>

                    <div v-if="loading" class="text-center py-8 text-slate-400">
                        <Loader2 class="h-6 w-6 animate-spin mx-auto" />
                    </div>

                    <div v-else-if="historial.length === 0" class="text-center py-12 text-slate-400">
                        <FileText class="h-12 w-12 mx-auto mb-3 opacity-50" />
                        <p class="font-semibold">No se encontraron papeletas</p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">N°</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Servidor</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden md:table-cell">Direccion</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Fecha</th>
                                    <th class="text-left px-4 py-3 font-semibold text-slate-600 hidden sm:table-cell">Motivo</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">Estado</th>
                                    <th class="text-center px-4 py-3 font-semibold text-slate-600">PDF</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr v-for="p in historial" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-4 py-3 font-mono font-bold text-blue-600">{{ p.numero_papeleta }}</td>
                                    <td class="px-4 py-3 text-slate-700">{{ p.employee?.person?.apellidos }}, {{ p.employee?.person?.nombres }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden md:table-cell">{{ p.employee?.direction?.nombre ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ formatDate(p.fecha_salida) }}</td>
                                    <td class="px-4 py-3 text-slate-500 hidden sm:table-cell">{{ p.reason?.nombre ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <span :class="estadoBadgeClass(p.estado)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold">
                                            {{ p.estado }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <a :href="`/papeletas/${p.id}/pdf`" target="_blank" class="text-slate-400 hover:text-blue-600 transition-colors">
                                            <FileText class="h-4 w-4 inline-block" />
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Reportes (RRHH only) -->
                <div v-if="activeTab === 'reportes'" class="p-4 sm:p-6">
                    <div class="max-w-lg mx-auto space-y-4">
                        <h3 class="text-lg font-bold text-slate-800">Generar Reporte PDF</h3>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                            <select v-model="reportFiltros.estado"
                                class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2">
                                <option value="TODOS">Todos</option>
                                <option value="PENDIENTE">Pendiente</option>
                                <option value="APROBADO">Aprobado</option>
                                <option value="DESAPROBADO">Desaprobado</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Desde</label>
                                <input type="date" v-model="reportFiltros.fecha_desde"
                                    class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Hasta</label>
                                <input type="date" v-model="reportFiltros.fecha_hasta"
                                    class="w-full rounded-lg border border-slate-200 text-sm px-3 py-2" />
                            </div>
                        </div>
                        <a :href="reportUrl" target="_blank"
                            class="w-full inline-flex justify-center items-center gap-2 px-4 py-3 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/20 transition-all">
                            <FileBarChart class="h-4 w-4" />
                            Generar Reporte
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create Papeleta Modal -->
        <Teleport to="body">
            <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCreateModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
                    <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <Plus class="h-6 w-6" />
                                Nueva Papeleta
                            </h3>
                            <p class="text-green-100 text-sm mt-1">Datos personales tomados automáticamente de Recursos Humanos</p>
                        </div>
                        <button type="button" @click="showCreateModal = false" class="p-1 text-green-100 hover:text-white transition-colors" aria-label="Cerrar">
                            <XCircle class="h-6 w-6" />
                        </button>
                    </div>
                    <form @submit.prevent="handleStorePapeleta" class="p-6 space-y-6">
                        <div class="rounded-xl border border-green-100 bg-gradient-to-br from-green-50 to-emerald-50 p-4">
                            <div class="flex items-center gap-4 rounded-xl border border-green-200 bg-white p-4 shadow-sm">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-green-500 to-emerald-600 text-xl font-bold text-white shadow-lg">
                                    {{ employeeInitial }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-lg font-bold text-slate-900">{{ employeeFullName }}</p>
                                    <div class="mt-1 grid grid-cols-1 gap-x-4 gap-y-1 text-xs text-slate-600 sm:grid-cols-2">
                                        <span><b>DNI:</b> {{ myEmployee?.dni || myEmployee?.person?.dni || '-' }}</span>
                                        <span><b>OFICINA:</b> {{ myEmployee?.office?.nombre || myEmployee?.direction?.nombre || 'Sin oficina registrada' }}</span>
                                        <span><b>CONDICIÓN LABORAL:</b> {{ myEmployee?.contract_type?.nombre || myEmployee?.tipo_contrato || 'Sin condición registrada' }}</span>
                                        <span><b>CARGO:</b> {{ myEmployee?.position?.nombre || myEmployee?.cargo || 'Sin cargo registrado' }}</span>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-xs font-medium text-green-800">La fecha, hora de creación y turno se registran automáticamente con la hora institucional. La salida real se registrará al escanear el QR en portería.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    Hora de creación de la papeleta
                                    <span class="relative flex h-2 w-2">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <span class="text-[10px] font-medium uppercase tracking-tight text-emerald-600">Automático</span>
                                </label>
                                <input type="time" :value="automaticTime" disabled class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-bold text-slate-500 outline-none" />
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">Turno <span class="text-xs font-normal text-green-500">(automático)</span></label>
                                <div class="flex w-full items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5">
                                    <Clock class="h-4 w-4 text-slate-400" />
                                    <span class="font-medium" :class="turnoColor">{{ automaticTurno }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">El turno se determina según la hora de creación.</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Motivo de salida <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'comision' ? 'border-green-500 bg-green-50 ring-1 ring-green-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="comision" class="h-4 w-4 border-slate-300 text-green-600 focus:ring-green-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Comisión de Servicios</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'particular_compensable' ? 'border-green-500 bg-green-50 ring-1 ring-green-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="particular_compensable" class="h-4 w-4 border-slate-300 text-green-600 focus:ring-green-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Particular Compensable</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'por_salud' ? 'border-green-500 bg-green-50 ring-1 ring-green-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="por_salud" class="h-4 w-4 border-slate-300 text-green-600 focus:ring-green-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Por Salud</span>
                                </label>
                            </div>
                            <p v-if="createErrors.motivo_salida" class="mt-1 text-xs text-red-600">{{ createErrors.motivo_salida[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Destino <span class="text-red-500">*</span></label>
                            <input v-model="createForm.destino" maxlength="250" class="w-full rounded-xl border px-4 py-3 text-sm outline-none transition-colors focus:border-green-500 focus:ring-2 focus:ring-green-500" :class="createErrors.destino ? 'border-red-400' : 'border-slate-200'" placeholder="Indique la entidad o lugar de destino" />
                            <p v-if="createErrors.destino" class="mt-1 text-xs text-red-600">{{ createErrors.destino[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Justificación <span class="text-red-500">*</span></label>
                            <textarea v-model="createForm.motivo" rows="3" maxlength="500" class="w-full resize-none rounded-xl border px-4 py-3 text-sm outline-none transition-colors focus:border-green-500 focus:ring-2 focus:ring-green-500" :class="createErrors.motivo ? 'border-red-400' : 'border-slate-200'" placeholder="Indique el motivo de la salida..."></textarea>
                            <p v-if="createErrors.motivo" class="mt-1 text-xs text-red-600">{{ createErrors.motivo[0] }}</p>
                        </div>

                        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                            <label class="mb-1.5 block text-sm font-bold text-indigo-900">Clave de firma <span class="text-red-500">*</span></label>
                                <input type="password" v-model="createForm.signing_pin" autocomplete="off"
                                    class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :class="createErrors.signing_pin ? 'border-red-400' : 'border-indigo-200'" placeholder="Clave creada al registrar el PFX" />
                            <p class="mt-1 text-xs text-indigo-700">Al enviar, firma digitalmente la solicitud y se remite a su jefe inmediato. No se usa QR.</p>
                            <p v-if="createErrors.signing_pin" class="mt-1 text-xs text-red-600">{{ createErrors.signing_pin[0] }}</p>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-slate-200 pt-4">
                            <button type="button" @click="showCreateModal = false"
                                class="rounded-xl border-2 border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="createSubmitting"
                                class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-2.5 text-sm font-bold text-white transition-all hover:from-green-700 hover:to-emerald-700 disabled:opacity-50">
                                <Loader2 v-if="createSubmitting" class="h-4 w-4 animate-spin" />
                                {{ createSubmitting ? 'Registrando...' : 'Firmar y enviar papeleta' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Approve Modal -->
        <Teleport to="body">
            <div v-if="showApproveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showApproveModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Aprobar Papeleta</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Papeleta <strong class="text-blue-600">{{ selectedPapeleta?.numero_papeleta }}</strong> de
                        <strong>{{ selectedPapeleta?.employee?.person?.apellidos }}, {{ selectedPapeleta?.employee?.person?.nombres }}</strong>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Comentario (opcional)</label>
                        <textarea v-model="modalComentario" rows="3"
                            class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm resize-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                            placeholder="Agregue un comentario..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Clave de firma digital *</label>
                        <input v-model="approvalPin" type="password" autocomplete="off" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-green-500 focus:ring-2 focus:ring-green-500/20" placeholder="Clave de su certificado" />
                        <p class="mt-1 text-xs text-slate-500">La aprobación continuará únicamente si se genera su firma digital.</p>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button @click="showApproveModal = false"
                            class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button @click="handleAprobar" :disabled="approvalProcessing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-green-600 hover:bg-green-700 disabled:opacity-50 transition-colors">
                            <Loader2 v-if="approvalProcessing" class="h-4 w-4 animate-spin" />
                            Confirmar Aprobacion
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Reject Modal -->
        <Teleport to="body">
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showRejectModal = false"></div>
                <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Desaprobar Papeleta</h3>
                    <p class="text-sm text-slate-600 mb-4">
                        Papeleta <strong class="text-blue-600">{{ selectedPapeleta?.numero_papeleta }}</strong> de
                        <strong>{{ selectedPapeleta?.employee?.person?.apellidos }}, {{ selectedPapeleta?.employee?.person?.nombres }}</strong>
                    </p>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Motivo del rechazo *</label>
                        <textarea v-model="modalComentario" rows="3"
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
                        <button @click="handleDesaprobar" :disabled="approvalProcessing"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold text-white bg-red-600 hover:bg-red-700 disabled:opacity-50 transition-colors">
                            <Loader2 v-if="approvalProcessing" class="h-4 w-4 animate-spin" />
                            Confirmar Rechazo
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <Teleport to="body">
            <div v-if="showCertificateModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4">
                <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showCertificateModal = false"></div>
                <form @submit.prevent="uploadCertificate" class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4">
                    <div>
                        <h3 class="text-xl font-black text-slate-900">Vincular certificado RENIEC</h3>
                        <p class="text-sm text-slate-500 mt-1">Acción voluntaria del titular. El archivo se cifra antes de almacenarse.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Archivo .pfx o .p12</label>
                        <input ref="certificateFileInput" type="file" accept=".pfx,.p12" required class="block w-full text-sm border border-slate-200 rounded-xl p-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Contraseña original del PFX</label>
                        <input v-model="certificateForm.pfx_password" type="password" required autocomplete="off" class="w-full rounded-xl border border-slate-200 px-4 py-3" />
                    </div>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Crear clave de firma</label>
                            <input v-model="certificateForm.signing_pin" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-4 py-3" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Confirmar clave</label>
                            <input v-model="certificateForm.signing_pin_confirmation" type="password" required minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-slate-200 px-4 py-3" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-500">La nueva clave debe contener letras y números. Se solicitará en cada firma y no se almacena.</p>
                    <label class="flex items-start gap-2 rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-700">
                        <input v-model="certificateForm.consent" type="checkbox" required class="mt-1 rounded border-slate-300" />
                        <span>Autorizo voluntariamente el uso de este certificado para firmar mis papeletas. Confirmo que soy su titular y que conozco que cada firma requerirá mi clave personal.</span>
                    </label>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="showCertificateModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 font-semibold text-slate-700">Cancelar</button>
                        <button type="submit" :disabled="certificateUploading" class="px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-bold disabled:opacity-50">
                            {{ certificateUploading ? 'Validando...' : 'Cifrar y vincular' }}
                        </button>
                    </div>
                </form>
            </div>
        </Teleport>
    </MainLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch } from 'vue';
import { useTabPermission } from '@/composables/useTabPermission';
import MainLayout from '@/Layouts/MainLayout.vue';
import { usePapeletaList } from '@/Composables/usePapeletaList';
import { usePapeletaApproval } from '@/Composables/usePapeletaApproval';
import { Clock, History, FileBarChart, FileText, CheckCircle, XCircle, Loader2, Plus, ClipboardList, ShieldCheck } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    userRole: String,
    myEmployee: { type: Object, default: null },
    reasons: { type: Array, default: () => [] },
    certificate: { type: Object, default: null },
});

const isBossRole = computed(() => ['ROL001', 'ROL011'].includes(props.userRole));
const isHrRole = computed(() => ['ROL001', 'ROL009'].includes(props.userRole));
const isAdminRole = computed(() => isBossRole.value || isHrRole.value);

const { canViewTab, firstAllowedTab } = useTabPermission('papeletas', ['pendientes', 'pendientes_rrhh', 'historial', 'reportes']);

// First tab: if employee → 'mis_papeletas', else use permission-based first tab
const activeTab = ref(props.myEmployee ? 'mis_papeletas' : firstAllowedTab.value);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const selectedPapeleta = ref(null);
const modalComentario = ref('');
const rejectError = ref('');
const approvalPin = ref('');
const registeredCertificate = ref(props.certificate);
const showCertificateModal = ref(false);
const certificateUploading = ref(false);
const certificateFileInput = ref(null);
const certificateForm = reactive({ pfx_password: '', signing_pin: '', signing_pin_confirmation: '', consent: false });

// ===== MIS PAPELETAS =====
const misPapeletas = ref([]);
const misPapeletasLoading = ref(false);
const showCreateModal = ref(false);
const createForm = reactive({
    destino: '',
    motivo: '',
    motivo_salida: 'comision',
    signing_pin: '',
});
const createErrors = ref({});
const createSubmitting = ref(false);
const currentDateTime = ref(new Date());
let automaticClock = null;

const employeeFullName = computed(() => {
    const person = props.myEmployee?.person;
    return [person?.nombres || props.myEmployee?.nombres, person?.apellidos || props.myEmployee?.apellidos]
        .filter(Boolean)
        .join(' ') || 'Servidor';
});

const employeeInitial = computed(() => employeeFullName.value.charAt(0).toUpperCase() || 'S');

const automaticTime = computed(() => currentDateTime.value.toTimeString().slice(0, 5));

const automaticTurno = computed(() => {
    const hour = currentDateTime.value.getHours();
    if (hour >= 6 && hour < 14) return 'Mañana';
    if (hour >= 14 && hour < 22) return 'Tarde';
    return 'Noche';
});

const turnoColor = computed(() => ({
    'Mañana': 'text-amber-600',
    'Tarde': 'text-orange-600',
    'Noche': 'text-indigo-600',
}[automaticTurno.value]));

const fetchMisPapeletas = async () => {
    misPapeletasLoading.value = true;
    try {
        const res = await axios.get('/papeletas/api/mis');
        misPapeletas.value = res.data;
    } finally {
        misPapeletasLoading.value = false;
    }
};

const openCreateModal = () => {
    if (!registeredCertificate.value) {
        showCertificateModal.value = true;
        window.Swal?.fire({ icon: 'info', title: 'Primero vincule su certificado', text: 'La papeleta debe salir firmada por el funcionario.' });
        return;
    }
    Object.assign(createForm, {
        destino: '',
        motivo: '',
        motivo_salida: 'comision',
        signing_pin: '',
    });
    createErrors.value = {};
    showCreateModal.value = true;
};

const uploadCertificate = async () => {
    const file = certificateFileInput.value?.files?.[0];
    if (!file) return;
    certificateUploading.value = true;
    const data = new FormData();
    data.append('pfx_file', file);
    data.append('pfx_password', certificateForm.pfx_password);
    data.append('signing_pin', certificateForm.signing_pin);
    data.append('signing_pin_confirmation', certificateForm.signing_pin_confirmation);
    data.append('consent', certificateForm.consent ? '1' : '0');
    try {
        const response = await axios.post('/papeletas/mi-certificado', data, { headers: { 'Content-Type': 'multipart/form-data' } });
        registeredCertificate.value = response.data.certificate;
        showCertificateModal.value = false;
        Object.assign(certificateForm, { pfx_password: '', signing_pin: '', signing_pin_confirmation: '', consent: false });
        if (certificateFileInput.value) certificateFileInput.value.value = '';
        window.Swal?.fire({ icon: 'success', title: 'Certificado vinculado', text: 'Ya puede firmar sus papeletas con la nueva clave.' });
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = errors ? Object.values(errors).flat().join('\n') : (error.response?.data?.message || 'No se pudo registrar el certificado.');
        window.Swal?.fire({ icon: 'error', title: 'Certificado no registrado', text: message });
    } finally {
        certificateUploading.value = false;
    }
};

const handleStorePapeleta = async () => {
    createSubmitting.value = true;
    createErrors.value = {};
    try {
        const res = await axios.post('/papeletas/solicitar', createForm);
        misPapeletas.value.unshift(res.data);
        showCreateModal.value = false;
        window.Swal?.fire({ icon: 'success', title: `Papeleta #${res.data.numero_papeleta} creada`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
    } catch (err) {
        if (err.response?.data?.errors) {
            createErrors.value = err.response.data.errors;
        } else {
            window.Swal?.fire({ icon: 'error', title: err.response?.data?.message || 'Error al crear la papeleta', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        }
    } finally {
        createSubmitting.value = false;
    }
};

const showControlQr = (papeleta) => {
    window.Swal?.fire({
        title: `QR de control · ${papeleta.numero_papeleta}`,
        html: `<p style="margin-bottom:12px;font-size:13px">Escanéelo en portería para marcar salida y retorno.</p><img alt="QR de control" src="/papeletas/${papeleta.id}/qr-control" style="width:300px;max-width:100%">`,
        confirmButtonText: 'Listo',
    });
};

const { pendientes, historial, stats, loading, filtros, fetchPendientes, fetchHistorial, fetchStats } = usePapeletaList();
const { processing: approvalProcessing, aprobar, desaprobar } = usePapeletaApproval();

const pendientesRrhh = computed(() => {
    return pendientes.value.filter(p => p.estado === 'PENDIENTE_RRHH');
});

const canProcess = (papeleta) => {
    if (papeleta?.estado === 'PENDIENTE') return isBossRole.value;
    if (papeleta?.estado === 'PENDIENTE_RRHH') return isHrRole.value;
    return false;
};

const reportFiltros = reactive({
    estado: 'TODOS',
    fecha_desde: '',
    fecha_hasta: '',
});

const reportUrl = computed(() => {
    const params = new URLSearchParams();
    if (reportFiltros.estado !== 'TODOS') params.set('estado', reportFiltros.estado);
    if (reportFiltros.fecha_desde) params.set('fecha_desde', reportFiltros.fecha_desde);
    if (reportFiltros.fecha_hasta) params.set('fecha_hasta', reportFiltros.fecha_hasta);
    return `/papeletas/report/pdf?${params.toString()}`;
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

// La hora de creación de la solicitud no es una marca de asistencia. Solo se
// muestran las horas que portería registró mediante el QR.
const formatQrTime = (dateTime) => {
    if (!dateTime) return 'Pendiente QR';
    return new Date(dateTime).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' });
};

const estadoBadgeClass = (estado) => {
    const classes = {
        PENDIENTE: 'bg-yellow-100 text-yellow-800',
        PENDIENTE_RRHH: 'bg-orange-100 text-orange-800',
        APROBADO: 'bg-green-100 text-green-800',
        DESAPROBADO: 'bg-red-100 text-red-800',
    };
    return classes[estado] || 'bg-slate-100 text-slate-800';
};

const openApproveModal = (papeleta) => {
    if (!canProcess(papeleta)) return;
    selectedPapeleta.value = papeleta;
    modalComentario.value = '';
    approvalPin.value = '';
    showApproveModal.value = true;
};

const openRejectModal = (papeleta) => {
    if (!canProcess(papeleta)) return;
    selectedPapeleta.value = papeleta;
    modalComentario.value = '';
    rejectError.value = '';
    showRejectModal.value = true;
};

const handleAprobar = async () => {
    if (!registeredCertificate.value) {
        showApproveModal.value = false;
        showCertificateModal.value = true;
        return;
    }
    if (!approvalPin.value) {
        window.Swal?.fire({ icon: 'warning', title: 'Ingrese su clave de firma' });
        return;
    }
    try {
        await aprobar(selectedPapeleta.value.id, modalComentario.value, approvalPin.value);
        showApproveModal.value = false;
        window.Swal?.fire({ icon: 'success', title: 'Papeleta aprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        refreshData();
    } catch (error) {
        const errors = error.response?.data?.errors;
        const message = errors ? Object.values(errors).flat().join('\n') : (error.response?.data?.message || 'No se pudo firmar la aprobación.');
        window.Swal?.fire({ icon: 'error', title: 'Firma no realizada', text: message });
    }
};

const handleDesaprobar = async () => {
    if (!modalComentario.value.trim()) {
        rejectError.value = 'Debe indicar el motivo del rechazo';
        return;
    }
    try {
        await desaprobar(selectedPapeleta.value.id, modalComentario.value);
        showRejectModal.value = false;
        window.Swal?.fire({ icon: 'success', title: 'Papeleta desaprobada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        refreshData();
    } catch {
        // error handled in composable
    }
};

const resetFiltros = () => {
    filtros.estado = 'TODOS';
    filtros.fecha_desde = '';
    filtros.fecha_hasta = '';
    filtros.direction_id = '';
    filtros.office_id = '';
    fetchHistorial();
};

const refreshData = () => {
    fetchPendientes();
    fetchStats();
    if (activeTab.value === 'historial') fetchHistorial();
};

watch(activeTab, (newTab) => {
    if (newTab === 'pendientes_rrhh' || newTab === 'pendientes') {
        fetchPendientes();
    }
    if (newTab === 'historial') {
        fetchHistorial();
    }
    if (newTab === 'mis_papeletas') {
        fetchMisPapeletas();
    }
});

watch(() => createForm.tipo_motivo, () => {
    const currentReason = props.reasons.find(reason => reason.id === createForm.entry_exit_reason_id);
    if (!currentReason || (currentReason.tipo !== createForm.tipo_motivo && currentReason.tipo !== 'ambos')) {
        createForm.entry_exit_reason_id = filteredReasons.value[0]?.id || '';
    }
}, { immediate: true });

onMounted(() => {
    automaticClock = window.setInterval(() => {
        currentDateTime.value = new Date();
    }, 30000);
    if (props.myEmployee) fetchMisPapeletas();
    if (isAdminRole.value) {
        fetchPendientes();
        fetchHistorial();
        fetchStats();
    }
});

onBeforeUnmount(() => {
    if (automaticClock) window.clearInterval(automaticClock);
});
</script>
