<template>
    <div v-if="links.length > 3" class="flex items-center justify-between px-4 py-3 sm:px-6">
        <div class="flex flex-1 justify-between sm:hidden">
            <Link v-if="prevLink" :href="prevLink.url"
                class="relative inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm"
                v-html="prevLink.label" />
            <Link v-if="nextLink" :href="nextLink.url"
                class="relative ml-3 inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 transition-all shadow-sm"
                v-html="nextLink.label" />
        </div>
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-xl shadow-sm bg-white border border-slate-200 p-1"
                    aria-label="Pagination">
                    <template v-for="(link, key) in links" :key="key">
                        <div v-if="link.url === null"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-semibold text-slate-300 focus:z-20 focus:outline-offset-0"
                            v-html="link.label" />
                        <Link v-else :href="link.url"
                            class="relative inline-flex items-center px-4 py-2 text-sm font-bold focus:z-20 focus:outline-offset-0 transition-all duration-200 mx-0.5 rounded-lg"
                            :class="link.active
                                ? 'z-10 bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600'" v-html="link.label" />
                    </template>
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    links: Array,
});

const prevLink = computed(() => props.links[0]);
const nextLink = computed(() => props.links[props.links.length - 1]);
</script>
