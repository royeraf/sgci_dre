<script setup>
defineProps({ variant: { type: String, default: 'files' } });
</script>

<template>
    <div class="resolution-skeleton" :class="`skeleton-${variant}`" role="status" aria-live="polite">
        <span class="sr-only">{{ variant === 'document' ? 'Cargando página del PDF…' : 'Cargando resoluciones…' }}</span>
        <div v-if="variant === 'document'" class="skeleton-paper" aria-hidden="true">
            <span class="placeholder paper-title"></span><span class="placeholder paper-subtitle"></span>
            <div v-for="paragraph in 4" :key="paragraph" class="paper-paragraph"><span v-for="line in 4" :key="line" class="placeholder"></span></div>
        </div>
        <div v-else aria-hidden="true">
            <span class="placeholder skeleton-title"></span>
            <template v-if="variant === 'summary'">
                <div class="skeleton-metrics"><div v-for="metric in 4" :key="metric"><span class="placeholder short"></span><span class="placeholder value"></span></div></div>
                <div class="skeleton-charts"><div class="placeholder chart"></div><div class="placeholder chart small-chart"></div></div>
            </template>
            <div v-else-if="variant === 'grid'" class="skeleton-grid"><div v-for="item in 6" :key="item" class="skeleton-card"><span class="placeholder cover"></span><span class="placeholder"></span><span class="placeholder short"></span></div></div>
            <div v-else class="skeleton-rows"><div v-for="row in 6" :key="row" class="skeleton-row"><span class="placeholder icon"></span><div><span class="placeholder name" :style="{width: `${78 - (row % 3) * 12}%`}"></span><span class="placeholder short"></span></div><span class="placeholder meta"></span><span class="placeholder meta"></span></div></div>
        </div>
    </div>
</template>

<style scoped>
.resolution-skeleton{padding:27px 0;min-width:0}.placeholder{display:block;height:12px;border-radius:4px;background:#e8edf3;animation:skeleton-breathe 1.5s ease-in-out infinite alternate}.skeleton-title{width:180px;height:25px;margin-bottom:27px}.short{width:28%;height:9px}.skeleton-row{display:grid;grid-template-columns:32px minmax(0,1fr) 65px 65px;gap:20px;align-items:center;min-height:72px;border-bottom:1px solid #edf0f4}.skeleton-row>div{display:flex;flex-direction:column;gap:12px}.skeleton-row .icon{width:26px;height:30px}.skeleton-folders .icon{height:25px;width:32px}.skeleton-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));border:1px solid #e3eaf2;border-radius:8px;margin-bottom:26px}.skeleton-metrics>div{padding:22px;border-right:1px solid #e3eaf2}.skeleton-metrics>div:last-child{border:0}.skeleton-metrics .short{width:65%}.value{width:60%;height:34px;margin-top:18px}.skeleton-charts{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:28px}.chart{height:330px;background:#f0f3f7}.skeleton-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.skeleton-card{display:flex;flex-direction:column;gap:15px;padding:14px;border:1px solid #e3eaf2;border-radius:8px}.cover{height:125px;margin-bottom:4px;background:#f0f3f7}.skeleton-paper{width:min(680px,100%);aspect-ratio:210/297;margin:auto;padding:10% 8%;background:white;box-shadow:0 4px 18px #172b4d1f}.paper-title{width:60%;height:22px;margin:0 auto 14px}.paper-subtitle{width:40%;margin:0 auto 50px}.paper-paragraph{display:flex;flex-direction:column;gap:12px;margin-bottom:28px}.paper-paragraph .placeholder:last-child{width:65%}.skeleton-document{padding:0}.skeleton-document .placeholder{background:#edf0f4}
@keyframes skeleton-breathe{from{opacity:1}to{opacity:.45}}
@media(max-width:1000px){.skeleton-charts{grid-template-columns:1fr}.small-chart{height:180px}.skeleton-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:639px){.resolution-skeleton{padding:23px 0}.skeleton-row{grid-template-columns:25px minmax(0,1fr) 45px;gap:12px;min-height:79px}.skeleton-row>.meta:last-child{display:none}.skeleton-row .icon{width:22px;height:27px}.skeleton-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}.skeleton-metrics>div{padding:17px}.skeleton-metrics>div:nth-child(2){border-right:0}.skeleton-metrics>div:nth-child(-n+2){border-bottom:1px solid #e3eaf2}.skeleton-grid{gap:10px}.skeleton-card{padding:10px}.cover{height:105px}.skeleton-paper{padding:12% 8%;min-height:480px}.paper-subtitle{margin-bottom:35px}.paper-paragraph{gap:10px;margin-bottom:20px}.skeleton-document{padding:0}}
@media(prefers-reduced-motion:reduce){.placeholder{animation:none}}
</style>
