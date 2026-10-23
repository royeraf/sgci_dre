<script setup>
import { router } from '@inertiajs/vue3';
import ResolutionUploader from './ResolutionUploader.vue';
import { resolutionUploads, revealResolutionUpload } from '@/Composables/useResolutionUploads';

function finished() {
    if (window.location.pathname === '/resoluciones') {
        router.reload({ only: ['records', 'summary', 'years', 'yearCounts', 'annualBySeries', 'folders', 'coverage'], preserveScroll: true });
    }
}
</script>

<template>
    <div class="upload-dock-stack">
        <ResolutionUploader v-for="job in resolutionUploads.jobs" :key="job.id" :job-id="job.id" :label="job.label" :open="job.open" :max-mb="job.maxMb" :current-year="job.currentYear" @close="job.open = false" @reveal="revealResolutionUpload(job.id)" @dismiss="resolutionUploads.jobs = resolutionUploads.jobs.filter(item => item.id !== job.id)" @label="job.label = $event" @busy="job.busy = $event" @finished="finished" />
    </div>
</template>

<style scoped>
.upload-dock-stack{position:fixed;right:22px;bottom:20px;z-index:1000;display:flex;flex-direction:column;gap:12px;width:350px;max-width:calc(100vw - 24px);max-height:calc(100dvh - 40px);overflow-y:auto;overscroll-behavior:contain;padding:4px;scrollbar-width:thin}
@media(max-width:639px){.upload-dock-stack{right:8px;bottom:8px;width:338px;max-height:calc(100dvh - 24px)}}
</style>
