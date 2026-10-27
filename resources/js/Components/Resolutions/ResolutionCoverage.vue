<script setup>
import { computed } from 'vue';
import { ChevronDown, ListChecks } from 'lucide-vue-next';
const props = defineProps({ report: Object, canReview: Boolean });
const emit = defineEmits(['repeated']);
const groups = computed(() => props.report?.groups || []);
const format = value => new Intl.NumberFormat('es-PE').format(value || 0);
const identifier = value => String(value).padStart(5,'0');
const ranges = gaps => gaps.map(([first,last])=>first===last ? identifier(first) : `${identifier(first)}–${identifier(last)}`).join(', ');
</script>

<template>
    <details v-if="report && groups.length" class="coverage-control">
        <summary><ListChecks :size="18" /><span>Control de numeración<strong>{{ report.missing ? `${format(report.missing)} números por comprobar` : 'Sin saltos en lo registrado' }}</strong></span><ChevronDown :size="17" /></summary>
        <p>Del 00001 a la última resolución confirmada de cada año; si aún no se confirmó, hasta el mayor número cargado.</p>
        <p v-if="report.unidentified" class="coverage-warning">{{ format(report.unidentified) }} PDF tienen datos pendientes; pueden explicar algunos saltos.</p>
        <section v-for="group in groups" :key="`${group.year}-${group.series}`"><header><strong>{{ group.series }} · {{ group.year }}</strong><span>Hasta {{ identifier(group.target ?? group.last) }}</span></header><p v-if="group.unique != null">{{ format(group.unique) }} números distintos · {{ format(group.files) }} PDF<span v-if="group.extra_files"> · {{ format(group.extra_files) }} archivos adicionales con número repetido</span></p><button v-if="canReview && group.repeated_numbers" type="button" class="underline text-xs mt-2" @click="emit('repeated', group.year, group.series)">Revisar números repetidos</button><p v-if="group.missing">{{ ranges(group.gaps) }}</p><p v-else class="coverage-ok">{{ group.expected_last ? 'Secuencia completa hasta la última resolución confirmada.' : 'Sin saltos en lo cargado.' }}</p></section>
    </details>
</template>

<style scoped>
.coverage-control{margin:0 0 23px;border:1px solid #dce5f1;border-radius:9px;background:#f8fbff;color:#3d5878}.coverage-control summary{display:flex;align-items:center;gap:10px;padding:14px 16px;cursor:pointer;list-style:none;font-size:12px}.coverage-control summary::-webkit-details-marker{display:none}.coverage-control summary>span{flex:1}.coverage-control summary strong{display:block;font-size:11px;font-weight:400;color:#6c809a;margin-top:4px}.coverage-control[open] summary>svg:last-child{transform:rotate(180deg)}.coverage-control>p{font-size:11px;line-height:1.8;margin:0 16px 13px;color:#6c7e94}.coverage-control>section{padding:14px 16px;border-top:1px solid #e1e9f4}.coverage-control section header{display:flex;justify-content:space-between;gap:12px;font-size:12px}.coverage-control section header strong{font-weight:600}.coverage-control section header span{font-size:11px;color:#788ba3}.coverage-control section p{font-size:11px;line-height:1.9;overflow-wrap:anywhere;margin-top:8px;font-variant-numeric:tabular-nums}.coverage-control .coverage-warning{color:#98611d}.coverage-control .coverage-ok{color:#2f7b60}.coverage-control summary:focus-visible{outline:2px solid #3472d0;outline-offset:3px}@media(max-width:639px){.coverage-control section header{flex-direction:column;gap:5px}.coverage-control summary{padding:13px}.coverage-control section{padding:13px}.coverage-control>p{margin-inline:13px}}
</style>
