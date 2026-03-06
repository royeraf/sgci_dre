<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
        <!-- Top Bar -->
        <header class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white shadow-lg">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center space-x-3">
                        <div class="bg-gradient-to-br from-blue-600 to-indigo-700 p-1.5 rounded-lg">
                            <img src="/images/logo.png" alt="DRE Huanuco" class="h-8 w-8 object-contain brightness-0 invert" />
                        </div>
                        <div class="hidden sm:block">
                            <span class="font-bold text-sm uppercase tracking-wide bg-gradient-to-r from-blue-400 to-indigo-400 bg-clip-text text-transparent">
                                Portal del Empleado
                            </span>
                            <span class="block text-[10px] text-slate-400">DRE Huanuco</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <div class="hidden sm:flex items-center gap-2 text-sm">
                            <div class="h-8 w-8 rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold uppercase">
                                {{ $page.props.auth?.user?.name?.charAt(0) || 'U' }}
                            </div>
                            <span class="text-slate-300 font-medium">{{ $page.props.auth?.user?.name }}</span>
                        </div>
                        <button @click="logout"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-slate-300 hover:text-white hover:bg-red-600 rounded-lg transition-all duration-200">
                            <LogOut class="h-4 w-4" />
                            <span class="hidden sm:inline">Salir</span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="py-6 sm:py-8 px-3 sm:px-4 md:px-6 lg:px-8">
            <div class="max-w-5xl mx-auto">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3';
import { LogOut } from 'lucide-vue-next';

const $page = usePage();

const logout = () => {
    router.post('/portal/logout');
};
</script>
