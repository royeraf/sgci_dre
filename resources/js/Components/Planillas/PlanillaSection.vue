<template>
    <BaseTableCard :title="title" :description="description">
        <template v-if="$slots.icon" #icon>
            <slot name="icon" />
        </template>

        <template v-if="$slots.actions" #actions>
            <slot name="actions" />
        </template>

        <div v-if="$slots.default">
            <slot />
        </div>

        <div v-else class="py-20 text-center bg-white">
            <div class="w-16 h-16 bg-teal-50 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <component :is="icon" class="w-8 h-8 text-teal-600" />
            </div>
            <h3 class="text-lg font-bold text-slate-800">{{ emptyTitle || title }}</h3>
            <p class="text-slate-500 font-medium mt-2 max-w-md mx-auto px-6">
                {{ emptyDescription || 'Módulo en construcción. Esta sección estará disponible próximamente.' }}
            </p>
        </div>
    </BaseTableCard>
</template>

<script setup>
import BaseTableCard from '@/Components/Common/BaseTableCard.vue';

defineProps({
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    icon: {
        type: [Object, Function],
        required: true,
    },
    emptyTitle: {
        type: String,
        default: '',
    },
    emptyDescription: {
        type: String,
        default: '',
    },
});
</script>
