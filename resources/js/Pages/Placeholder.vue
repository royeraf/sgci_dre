<template>
    <MainLayout>
        <div class="p-8">
            <div class="bg-white rounded-3xl p-12 text-center shadow-sm border border-slate-200">
                <div class="w-24 h-24 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <Construction class="w-12 h-12 text-blue-600" />
                </div>
                <h1 class="text-3xl font-bold text-slate-900 mb-4">{{ module }}</h1>
                <p class="text-slate-600 text-lg mb-8 max-w-md mx-auto">
                    Este módulo está siendo migrado desde el sistema anterior.
                    Estará disponible próximamente con todas sus funciones originales.
                </p>
                <Link href="/dashboard"
                    class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-bold rounded-xl hover:bg-blue-700 transition-colors">
                    <ArrowLeft class="w-5 h-5 mr-2" />
                    Volver al Dashboard
                </Link>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import MainLayout from '@/Layouts/MainLayout.vue';
import { Link } from '@inertiajs/vue3';
import { Construction, ArrowLeft } from 'lucide-vue-next';

defineProps({
    module: String
});
</script>
