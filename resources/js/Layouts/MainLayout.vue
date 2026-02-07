<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 flex">
        <!-- Sidebar -->
        <aside
            :class="[isCollapsed ? 'w-20' : 'w-72', 'bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-white flex-shrink-0 hidden md:flex flex-col transition-all duration-300 ease-in-out shadow-2xl h-screen sticky top-0']">

            <!-- Toggle Button -->
            <button @click="toggleSidebar"
                class="absolute -right-3 top-24 bg-blue-600 text-white p-1 rounded-full shadow-lg hover:bg-blue-700 focus:outline-none z-50 transform hover:scale-110 transition-all duration-200">
                <ChevronLeft v-if="!isCollapsed" class="h-4 w-4" />
                <ChevronRight v-else class="h-4 w-4" />
            </button>

            <!-- Logo Header -->
            <div class="h-24 flex items-center border-b border-slate-700/50 bg-slate-900/80 backdrop-blur-sm transition-all duration-300"
                :class="isCollapsed ? 'justify-center px-0' : 'justify-center px-4'">
                <div class="flex items-center font-bold text-xl tracking-wider transition-all duration-300"
                    :class="isCollapsed ? 'space-x-0' : 'space-x-3'">
                    <div
                        class="bg-gradient-to-br from-blue-600 to-indigo-700 p-2 rounded-xl shadow-lg ring-2 ring-blue-400/30 transform hover:scale-105 transition-transform duration-200">
                        <img src="/images/logo.png" alt="DRE Huánuco"
                            class="h-10 w-10 object-contain brightness-0 invert" />
                    </div>
                    <div class="flex flex-col transition-opacity duration-200" v-if="!isCollapsed">
                        <span
                            class="bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent font-extrabold whitespace-nowrap uppercase">SGCI-DREH</span>
                        <span
                            class="text-[9px] text-slate-500 font-medium tracking-wide uppercase whitespace-nowrap leading-tight">Sistema
                            de Gestión<br />y Control Institucional</span>
                    </div>
                </div>
            </div>

            <!-- Navigation - Scrollable area -->
            <div
                class="flex-1 overflow-y-auto py-6 scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
                <div class="px-4 mb-4 transition-opacity duration-200" v-if="!isCollapsed">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-3">Menú Principal</p>
                </div>
                <nav class="space-y-1.5" :class="isCollapsed ? 'px-2' : 'px-4'">
                    <!-- Dashboard Link -->
                    <Link v-if="hasModulePermission('dashboard', 'ver')" href="/dashboard"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component === 'Dashboard' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30 ring-1 ring-blue-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Dashboard' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component === 'Dashboard' ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <LayoutDashboard class="h-5 w-5"
                                :class="$page.component === 'Dashboard' ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed"
                            class="whitespace-nowrap transition-opacity duration-200">Dashboard</span>
                    </Link>

                    <!-- Occurrences Link -->
                    <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/occurrences"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Occurrences/') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg shadow-blue-500/30 ring-1 ring-blue-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Libro de Ocurrencias' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Occurrences/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <ClipboardList class="h-5 w-5"
                                :class="$page.component.startsWith('Occurrences/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Libro de
                            Ocurrencias</span>
                    </Link>

                    <!-- Entry/Exit Link -->
                    <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/entry-exits"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('EntryExits/') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30 ring-1 ring-emerald-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Control de Personal' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('EntryExits/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <UserCheck class="h-5 w-5"
                                :class="$page.component.startsWith('EntryExits/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Control de
                            Personal</span>
                    </Link>

                    <!-- Visitas Externas -->
                    <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/visitors"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Visitors/') ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-lg shadow-purple-500/30 ring-1 ring-purple-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Visitas Externas' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Visitors/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Users class="h-5 w-5"
                                :class="$page.component.startsWith('Visitors/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Visitas
                            Externas</span>
                    </Link>

                    <!-- Control Vehicular -->
                    <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/vehicles"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Vehicles/') ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg shadow-cyan-500/30 ring-1 ring-cyan-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Control Vehicular' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Vehicles/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Car class="h-5 w-5"
                                :class="$page.component.startsWith('Vehicles/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Control
                            Vehicular</span>
                    </Link>

                    <!-- Patrimonio -->
                    <Link v-if="hasModulePermission('patrimonio', 'ver')" href="/assets"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Assets/') ? 'bg-gradient-to-r from-slate-700 to-gray-700 text-white shadow-lg shadow-slate-500/30 ring-1 ring-slate-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Patrimonio' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Assets/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Box class="h-5 w-5"
                                :class="$page.component.startsWith('Assets/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Patrimonio
                            (Bienes)</span>
                    </Link>

                    <!-- Gestión de Citas -->
                    <Link v-if="hasModulePermission('secretaria', 'ver')" href="/citas"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Appointments/') ? 'bg-gradient-to-r from-pink-600 to-rose-600 text-white shadow-lg shadow-pink-500/30 ring-1 ring-pink-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Gestión de Citas' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Appointments/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Calendar class="h-5 w-5"
                                :class="$page.component.startsWith('Appointments/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Gestión de
                            Citas</span>
                    </Link>

                    <!-- Bienestar Social - Licencias -->
                    <Link v-if="hasModulePermission('bienestar', 'ver')" href="/bienestar"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Bienestar/') ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30 ring-1 ring-purple-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Bienestar Social' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Bienestar/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Heart class="h-5 w-5"
                                :class="$page.component.startsWith('Bienestar/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Bienestar
                            Social</span>
                    </Link>

                    <!-- Recursos Humanos -->
                    <Link v-if="hasModulePermission('recursos_humanos', 'ver')" href="/hr"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('HR/') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg shadow-emerald-500/30 ring-1 ring-emerald-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Recursos Humanos' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('HR/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <UserGroup class="h-5 w-5"
                                :class="$page.component.startsWith('HR/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Recursos
                            Humanos</span>
                    </Link>

                    <!-- Divider for Admin Section -->
                    <div v-if="isAdmin()" class="my-4 transition-opacity duration-200"
                        :class="isCollapsed ? 'px-0' : 'px-3'">
                        <div class="border-t border-slate-700/50"></div>
                        <p v-if="!isCollapsed"
                            class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mt-4 px-3">
                            Administración</p>
                    </div>

                    <!-- User Management (Admin only) -->
                    <Link v-if="isAdmin()" href="/users"
                        class="group flex items-center text-sm font-semibold rounded-xl transition-all duration-200 ease-in-out relative"
                        :class="[
                            $page.component.startsWith('Users/') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg shadow-indigo-500/30 ring-1 ring-indigo-400/50' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white',
                            isCollapsed ? 'justify-center py-3 px-0' : 'px-4 py-3.5'
                        ]" :title="isCollapsed ? 'Gestión de Usuarios' : ''">
                        <div class="rounded-lg transition-colors duration-200 ease-in-out flex-shrink-0" :class="[
                            $page.component.startsWith('Users/') ? 'bg-white/20' : 'bg-slate-700/80 group-hover:bg-slate-600',
                            isCollapsed ? 'p-2' : 'mr-4 p-2'
                        ]">
                            <Shield class="h-5 w-5"
                                :class="$page.component.startsWith('Users/') ? 'text-white' : 'text-slate-400 group-hover:text-white'" />
                        </div>
                        <span v-if="!isCollapsed" class="whitespace-nowrap transition-opacity duration-200">Gestión de
                            Usuarios</span>
                    </Link>
                </nav>
            </div>

            <!-- User Profile - Fixed at bottom -->
            <div class="flex-shrink-0 border-t border-slate-700/50 p-4 bg-gradient-to-t from-slate-900 to-slate-800/80 backdrop-blur-sm transition-all duration-300"
                :class="isCollapsed ? 'items-center justify-center p-2' : 'p-4'">
                <div class="flex items-center gap-3 rounded-xl bg-slate-800/50 hover:bg-slate-700/50 transition-colors duration-200 cursor-pointer group"
                    :class="isCollapsed ? 'p-2 justify-center' : 'p-3'" @click="showProfileModal = true">
                    <div class="flex-shrink-0">
                        <div
                            class="h-11 w-11 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-sm font-bold text-white shadow-lg ring-2 ring-blue-400/30 uppercase group-hover:ring-blue-300/50 transition-all">
                            {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0 transition-opacity duration-200" v-if="!isCollapsed">
                        <p class="text-sm font-bold text-white truncate">{{ $page.props.auth?.user?.name || 'Usuario' }}
                        </p>
                        <p class="text-xs text-slate-400 flex items-center gap-1">
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                            {{ $page.props.auth?.user?.customRole?.nombre || 'Usuario' }}
                        </p>
                    </div>
                    <button @click.stop="logout"
                        class="flex-shrink-0 bg-slate-700/80 rounded-lg text-slate-400 hover:text-white hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 transition-all duration-200 ease-in-out"
                        :class="isCollapsed ? 'hidden group-hover:block absolute left-full ml-2 p-2' : 'p-2.5'"
                        title="Cerrar Sesión">
                        <LogOut class="h-5 w-5" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile Header -->
        <div
            class="md:hidden fixed top-0 w-full bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white z-40 flex items-center justify-between px-4 h-16 shadow-lg">
            <div class="flex items-center space-x-2 font-bold text-lg">
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 p-1.5 rounded-lg border border-white/20">
                    <img src="/images/logo.png" alt="DREH" class="h-6 w-6 object-contain brightness-0 invert" />
                </div>
                <span class="tracking-tight uppercase">DRE Huánuco</span>
            </div>
            <button @click="mobileMenuOpen = !mobileMenuOpen"
                class="text-slate-300 hover:text-white focus:outline-none p-2 rounded-lg hover:bg-slate-700 transition-colors duration-200 ease-in-out">
                <Menu v-if="!mobileMenuOpen" class="h-6 w-6" />
                <X v-else class="h-6 w-6" />
            </button>
        </div>

        <!-- Mobile Menu Overlay -->
        <Transition enter-active-class="transition-opacity duration-300 ease-in-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-300 ease-in-out"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="mobileMenuOpen" class="fixed inset-0 z-40 md:hidden flex">
                <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" @click="mobileMenuOpen = false"></div>
                <div
                    class="relative flex-1 flex flex-col max-w-xs w-full bg-gradient-to-b from-slate-900 to-slate-800 shadow-2xl">
                    <div class="pt-20 pb-4 px-4 overflow-y-auto h-full space-y-2">
                        <Link v-if="hasModulePermission('dashboard', 'ver')" href="/dashboard"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component === 'Dashboard' ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <LayoutDashboard class="h-5 w-5" />
                            Dashboard
                        </Link>
                        <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/occurrences"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Occurrences/') ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <ClipboardList class="h-5 w-5" />
                            Libro de Ocurrencias
                        </Link>
                        <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/entry-exits"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('EntryExits/') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <UserCheck class="h-5 w-5" />
                            Control de Personal
                        </Link>
                        <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/visitors"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Visitors/') ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Users class="h-5 w-5" />
                            Visitas Externas
                        </Link>
                        <Link v-if="hasModulePermission('vigilancia', 'ver')" href="/vehicles"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Vehicles/') ? 'bg-gradient-to-r from-cyan-600 to-blue-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Car class="h-5 w-5" />
                            Control Vehicular
                        </Link>
                        <Link v-if="hasModulePermission('patrimonio', 'ver')" href="/assets"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Assets/') ? 'bg-gradient-to-r from-slate-700 to-gray-700 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Box class="h-5 w-5" />
                            Patrimonio (Bienes)
                        </Link>
                        <Link v-if="hasModulePermission('secretaria', 'ver')" href="/citas"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Appointments/') ? 'bg-gradient-to-r from-pink-600 to-rose-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Calendar class="h-5 w-5" />
                            Gestión de Citas
                        </Link>
                        <Link v-if="hasModulePermission('bienestar', 'ver')" href="/bienestar"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Bienestar/') ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Heart class="h-5 w-5" />
                            Bienestar Social
                        </Link>
                        <Link v-if="hasModulePermission('recursos_humanos', 'ver')" href="/hr"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('HR/') ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Users class="h-5 w-5" />
                            Recursos Humanos
                        </Link>

                        <!-- Admin Section Divider -->
                        <div v-if="isAdmin()" class="my-3 border-t border-slate-700 pt-3">
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest px-4 mb-2">
                                Administración</p>
                        </div>

                        <!-- User Management (Admin only) -->
                        <Link v-if="isAdmin()" href="/users" @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold transition-all duration-200"
                            :class="$page.component.startsWith('Users/') ? 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white'">
                            <Shield class="h-5 w-5" />
                            Gestión de Usuarios
                        </Link>

                        <button @click="logout"
                            class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-base font-semibold text-slate-300 hover:bg-red-600 hover:text-white transition-all duration-200 mt-6 pt-6 border-t border-slate-700">
                            <LogOut class="h-5 w-5" />
                            Cerrar Sesión
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto pt-16 md:pt-0">
            <div class="h-full">
                <slot />
            </div>
        </main>

        <!-- User Profile Modal -->
        <UserProfileModal :show="showProfileModal" @close="showProfileModal = false" />
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ChevronLeft,
    ChevronRight,
    LayoutDashboard,
    X,
    Users,
    Car,
    Calendar,
    BookOpen,
    UserCheck,
    ClipboardList,
    FileText,
    LogOut,
    Menu,
    Bell,
    Heart,
    Shield,
    Box
} from 'lucide-vue-next';
import UserProfileModal from '@/Components/Profile/UserProfileModal.vue';

const UserGroup = Users;

const isCollapsed = ref(false);
const mobileMenuOpen = ref(false);
const showProfileModal = ref(false);
const page = usePage();
const pendingAppointmentsCount = ref(0);

// SweetAlert Toast Configuration
const Toast = window.Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', window.Swal.stopTimer)
        toast.addEventListener('mouseleave', window.Swal.resumeTimer)
    }
});

import { watch } from 'vue';

// Watch for flash messages
watch(() => page.props.flash?.success, (success) => {
    if (success) {
        Toast.fire({
            icon: 'success',
            title: success
        });
        // Important: Clear the flash message so it can be triggered again even if the string is identical
        page.props.flash.success = null;
    }
}, { immediate: true });

watch(() => page.props.flash?.error, (error) => {
    if (error) {
        Toast.fire({
            icon: 'error',
            title: error
        });
        // Clear the error message as well
        page.props.flash.error = null;
    }
}, { immediate: true });

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

const logout = () => {
    router.post('/logout');
};

const hasModulePermission = (module, action = 'ver') => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Admin has access to everything
    if (user.rol_id === 'ROL001' || user.rol_id === '1') return true;

    // Special case for Jefe de Bienestar Social (ROL008)
    // Shows: Dashboard + Bienestar Social only
    if (user.rol_id === 'ROL008' || user.customRole?.codigo === 'jefe_bienestar') {
        return module === 'bienestar' || module === 'dashboard';
    }

    // Special case for Jefe de RRHH (ROL009)
    // Shows: Dashboard + Recursos Humanos only
    if (user.rol_id === 'ROL009' || user.customRole?.codigo === 'jefe_rrhh') {
        return module === 'recursos_humanos' || module === 'dashboard';
    }

    // Special case for Gestor de Citas (ROL010)
    // Shows: Dashboard + Gestión de Citas only
    if (user.rol_id === 'ROL010' || user.customRole?.codigo === 'gestor_citas') {
        return module === 'secretaria' || module === 'dashboard';
    }

    const permisos = user.customRole?.permisos_json || {};

    // Mapping sidebar modules to database permission keys
    const mapping = {
        'dashboard': true, // Everyone else has dashboard
        'vigilancia': ['ocurrencias', 'personal', 'visitas', 'vehiculos'],
        'secretaria': ['citas'],
        'recursos_humanos': ['recursos_humanos', 'vacaciones', 'areas', 'cargos'],
        'bienestar': ['licencias'],
        'patrimonio': ['patrimonio', 'bienes'],
    };

    if (module === 'dashboard') return true;

    const dbKeys = mapping[module];
    if (!dbKeys) return false;

    return dbKeys.some(key => permisos[key] !== undefined);
};

const isAdmin = () => {
    const user = page.props.auth?.user;
    if (!user) return false;

    // Check if user has admin role using customRole nivel_acceso or specific role codes
    return user.customRole && (user.customRole.nivel_acceso >= 9 || user.customRole.codigo === 'ADMIN');
};

// Real-time Appointment Notifications using WebSockets (Echo/Reverb)
let echoChannel = null;
let appointmentPollingInterval = null;
const useWebSockets = ref(false);

const showNewAppointmentNotification = (appointmentData) => {
    // Skip if we're already on the appointments page
    if (page.component.startsWith('Appointments/')) return;

    window.Swal.fire({
        icon: 'info',
        title: '📅 Nueva cita registrada',
        html: `<p><strong>${appointmentData.nombres} ${appointmentData.apellidos}</strong></p>
               <p class="text-sm text-gray-600">${appointmentData.oficina} - ${appointmentData.fecha} ${appointmentData.hora}</p>
               <p class="text-sm text-gray-500 mt-2">Haga clic en "Ir a Citas" para gestionarla.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Ir a Citas',
        cancelButtonText: 'Cerrar',
        confirmButtonColor: '#db2777',
    }).then((result) => {
        if (result.isConfirmed) {
            router.visit('/citas');
        }
    });
};

const setupWebSocketListener = () => {
    // Only setup if user has secretaria permission
    if (!hasModulePermission('secretaria', 'ver')) return;

    if (typeof window.Echo !== 'undefined') {
        try {
            echoChannel = window.Echo.channel('appointments')
                .listen('.new-appointment', (e) => {
                    console.log('🔔 New appointment received via WebSocket:', e);
                    showNewAppointmentNotification(e);
                });

            useWebSockets.value = true;
            console.log('✅ WebSocket connection established for appointments');
        } catch (error) {
            console.warn('WebSocket setup failed, falling back to polling:', error);
            useWebSockets.value = false;
            startPollingFallback();
        }
    } else {
        console.warn('Echo not available, using polling fallback');
        startPollingFallback();
    }
};

// Polling fallback (used when WebSockets are not available)
const checkNewAppointments = async () => {
    if (!hasModulePermission('secretaria', 'ver')) return;
    if (page.component.startsWith('Appointments/')) return;

    try {
        const response = await axios.get('/citas/list');
        const appointments = response.data;
        const newPendingCount = appointments.filter(c => c.estado === 'PENDIENTE').length;

        if (pendingAppointmentsCount.value > 0 && newPendingCount > pendingAppointmentsCount.value) {
            const diff = newPendingCount - pendingAppointmentsCount.value;
            window.Swal.fire({
                icon: 'info',
                title: '📅 Nueva cita registrada',
                html: `<p>Se ha registrado <strong>${diff}</strong> nueva(s) cita(s) pendiente(s).</p>
                       <p class="text-sm text-gray-500 mt-2">Haga clic en "Ir a Citas" para gestionarlas.</p>`,
                showCancelButton: true,
                confirmButtonText: 'Ir a Citas',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#db2777',
            }).then((result) => {
                if (result.isConfirmed) {
                    router.visit('/citas');
                }
            });
        }

        pendingAppointmentsCount.value = newPendingCount;
    } catch (error) {
        console.warn('Failed to check appointments:', error);
    }
};

const startPollingFallback = () => {
    if (!hasModulePermission('secretaria', 'ver')) return;

    checkNewAppointments();
    appointmentPollingInterval = setInterval(() => {
        checkNewAppointments();
    }, 15000);
};

const stopNotificationListeners = () => {
    // Stop WebSocket listener
    if (echoChannel && typeof window.Echo !== 'undefined') {
        window.Echo.leave('appointments');
        echoChannel = null;
    }

    // Stop polling
    if (appointmentPollingInterval) {
        clearInterval(appointmentPollingInterval);
        appointmentPollingInterval = null;
    }
};

onMounted(() => {
    // Try WebSockets first, fallback to polling
    setupWebSocketListener();
});

onUnmounted(() => {
    stopNotificationListeners();
});
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar {
    width: 4px;
}

.scrollbar-thin::-webkit-scrollbar-track {
    background: transparent;
}

.scrollbar-thin::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 20px;
}
</style>
