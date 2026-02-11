<template>
    <div class="min-h-screen bg-slate-950 flex items-center justify-center p-6 relative overflow-hidden">
        <!-- Premium Gradient Background -->
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_50%_50%,rgba(37,99,235,0.1),transparent_50%)]"></div>
        <div
            class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5">
        </div>

        <!-- Decorative Animated Glows -->
        <div
            class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-blue-600/10 rounded-full blur-[120px] animate-pulse">
        </div>
        <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-indigo-600/10 rounded-full blur-[120px] animate-pulse"
            style="animation-delay: 2s;"></div>

        <div class="max-w-2xl w-full text-center relative z-10">
            <!-- 404 Large Visual -->
            <div class="relative mb-12">
                <h1
                    class="text-[12rem] md:text-[18rem] font-black text-white/5 select-none leading-none tracking-tighter animate-bounce-slow">
                    404
                </h1>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div
                        class="bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-2xl border border-white/20 p-8 md:p-12 rounded-[3.5rem] shadow-2xl ring-1 ring-white/10 transform rotate-3 hover:rotate-0 transition-all duration-700">
                        <div class="relative">
                            <div class="absolute inset-0 bg-blue-500/20 blur-2xl rounded-full"></div>
                            <Search class="w-24 h-24 md:w-32 md:h-32 text-blue-400 relative z-10 animate-pulse" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Text Content -->
            <div class="space-y-6 mb-12">
                <h2 class="text-4xl md:text-6xl font-black text-white tracking-tight">
                    ¡Ups! Página <span
                        class="bg-gradient-to-r from-blue-400 via-indigo-400 to-purple-400 bg-clip-text text-transparent">no
                        encontrada</span>
                </h2>

                <p class="text-slate-400 text-lg md:text-xl font-medium max-w-lg mx-auto leading-relaxed">
                    Parece que te has perdido en el sistema. La ruta que buscas no existe o ha sido movida a una nueva
                    ubicación.
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                <Link href="/dashboard"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 text-white font-bold rounded-2xl shadow-2xl shadow-blue-500/40 hover:shadow-blue-500/60 transition-all duration-300 transform hover:scale-105 active:scale-95 group">
                    <Home class="w-6 h-6 mr-3 group-hover:-translate-y-1 transition-transform" />
                    Ir al Inicio
                </Link>
                <button @click="goBack"
                    class="w-full sm:w-auto inline-flex items-center justify-center px-10 py-5 bg-white/5 hover:bg-white/10 border border-white/10 text-white font-bold rounded-2xl backdrop-blur-xl transition-all duration-300 transform hover:scale-105 active:scale-95 group">
                    <ArrowLeft class="w-6 h-6 mr-3 group-hover:-translate-x-1 transition-transform" />
                    Regresar
                </button>
            </div>

            <!-- System Footer -->
            <div class="mt-24 pt-8 border-t border-white/5 flex flex-col items-center gap-6 opacity-40">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-white/10 rounded-xl">
                        <img src="/images/logo.png" alt="Logo" class="h-8 brightness-0 invert" />
                    </div>
                    <div class="text-left">
                        <p class="text-white font-black tracking-wider text-sm leading-none">SGCI-DREH</p>
                        <p class="text-slate-500 text-[10px] font-bold uppercase mt-1">Gestión y Control Institucional
                        </p>
                    </div>
                </div>
                <p class="text-slate-500 text-[10px] uppercase tracking-[0.3em] font-black">
                    Dirección Regional de Educación Huánuco
                </p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { Search, Home, ArrowLeft } from 'lucide-vue-next';

const goBack = () => {
    window.history.back();
};
</script>

<style scoped>
@keyframes bounce-slow {

    0%,
    100% {
        transform: translateY(-5%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }

    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}

.animate-bounce-slow {
    animation: bounce-slow 4s infinite;
}
</style>
