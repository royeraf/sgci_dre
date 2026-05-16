<template>
    <div class="bg-white shadow-xl rounded-2xl border border-slate-200 overflow-hidden">
        <!-- Table Title & Sub-tabs -->
        <div
            class="p-5 sm:p-6 border-b border-slate-200 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <div v-if="$slots.icon"
                    class="p-2 bg-white rounded-xl shadow-sm border border-slate-100 flex items-center justify-center">
                    <slot name="icon" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800">{{ title }}</h2>
                    <p v-if="description" class="text-sm text-slate-500 font-medium mt-1">{{ description }}</p>
                </div>
            </div>

            <!-- Sub-tabs or additional header elements -->
            <div v-if="$slots.subtabs" class="shrink-0">
                <slot name="subtabs" />
            </div>
        </div>

        <!-- Search and Actions Row -->
        <div v-if="$slots.filters || $slots.actions || searchPlaceholder"
            class="p-4 sm:p-5 border-b border-slate-100 flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-4 bg-white">
            
            <!-- Left side (Search or Filters) -->
            <div class="flex items-center gap-3 flex-1">
                <div v-if="searchPlaceholder" class="relative flex-1 max-w-md">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-400" />
                    <input :value="searchValue" @input="$emit('update:searchValue', $event.target.value)" type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full pl-10 pr-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all duration-200 text-sm outline-none cursor-pointer" />
                </div>
                <slot name="filters" />
            </div>

            <!-- Right side (Actions) -->
            <div v-if="$slots.actions" class="flex items-center justify-end gap-3 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <!-- Main Content (Table) -->
        <slot />
    </div>
</template>

<script setup>
import { Search } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        required: true
    },
    description: {
        type: String,
        default: ''
    },
    searchPlaceholder: {
        type: String,
        default: ''
    },
    searchValue: {
        type: String,
        default: ''
    }
});

defineEmits(['update:searchValue']);
</script>
