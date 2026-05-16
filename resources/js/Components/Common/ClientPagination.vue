<template>
    <div v-if="totalPages > 1" class="bg-slate-50 px-6 py-4 border-t border-slate-200">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <span>Mostrar</span>
                <select :value="perPage" @change="$emit('update:perPage', Number($event.target.value))"
                    class="cursor-pointer border-2 border-slate-200 rounded-xl px-3 py-1.5 text-sm focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white outline-none transition-all">
                    <option v-for="option in perPageOptions" :key="option" :value="option">{{ option }}</option>
                </select>
                <span>por página de {{ totalItems }}</span>
            </div>
            <div class="flex items-center gap-1 flex-wrap justify-center">
                <!-- Primera página -->
                <button @click="$emit('update:currentPage', 1)" :disabled="currentPage === 1"
                    class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <ChevronsLeft class="w-4 h-4" />
                </button>
                <!-- Anterior -->
                <button @click="$emit('update:currentPage', currentPage - 1)" :disabled="currentPage === 1"
                    class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <ChevronLeft class="w-4 h-4" />
                </button>

                <!-- Números de página -->
                <template v-for="item in pageWindow" :key="item">
                    <span v-if="item === '...'" class="px-1 text-slate-400 select-none">…</span>
                    <button v-else @click="$emit('update:currentPage', item)" :class="[
                        'cursor-pointer min-w-[36px] h-9 px-2 rounded-xl border text-sm font-semibold transition-all',
                        item === currentPage
                            ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                            : 'border-slate-200 text-slate-600 hover:bg-slate-100'
                    ]">
                        {{ item }}
                    </button>
                </template>

                <!-- Siguiente -->
                <button @click="$emit('update:currentPage', currentPage + 1)" :disabled="currentPage === totalPages"
                    class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <ChevronRight class="w-4 h-4" />
                </button>
                <!-- Última página -->
                <button @click="$emit('update:currentPage', totalPages)" :disabled="currentPage === totalPages"
                    class="cursor-pointer p-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <ChevronsRight class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';

const props = defineProps({
    totalItems: {
        type: Number,
        required: true
    },
    currentPage: {
        type: Number,
        default: 1
    },
    perPage: {
        type: Number,
        default: 10
    },
    perPageOptions: {
        type: Array,
        default: () => [10, 25, 50, 100]
    }
});

const emit = defineEmits(['update:currentPage', 'update:perPage']);

const totalPages = computed(() => {
    return Math.ceil(props.totalItems / props.perPage) || 1;
});

const pageWindow = computed(() => {
    const current = props.currentPage;
    const last = totalPages.value;
    if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
    const pages = [1];
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) pages.push(i);
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
});
</script>
