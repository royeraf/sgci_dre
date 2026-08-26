<script setup>
import { ref, reactive, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { Plus, X, Loader2 } from 'lucide-vue-next';
import EmployeeSearchSelect from '@/Components/Common/EmployeeSearchSelect.vue';

const props = defineProps({
    myEmployee: { type: Object, default: null },
    jefeSugeridoId: { type: String, default: null },
});

const emit = defineEmits(['close', 'created']);

const createForm = reactive({
    destino: '',
    motivo: '',
    motivo_salida: 'comision',
    jefe_asignado_id: props.jefeSugeridoId || '',
    signing_pin: '',
});
const createErrors = ref({});
const createSubmitting = ref(false);

// Limpia la advertencia de un campo en cuanto el usuario vuelve a escribir
// en él, en vez de esperar al próximo intento de envío.
for (const field of ['destino', 'motivo', 'motivo_salida', 'jefe_asignado_id', 'signing_pin']) {
    watch(() => createForm[field], () => {
        if (createErrors.value[field]) {
            delete createErrors.value[field];
        }
    });
}

// ===== Buscador de "quién firma como jefe" =====
const posiblesJefes = ref([]);
const posiblesJefesLoading = ref(false);
// Aviso de certificado RENIEC comentado por ahora (ver template).
// const jefeSeleccionadoSinCertificado = computed(() => {
//     const emp = posiblesJefes.value.find(e => e.id === createForm.jefe_asignado_id);
//     return emp?.tiene_certificado === false;
// });

const loadPosiblesJefes = async () => {
    if (posiblesJefes.value.length) return;
    posiblesJefesLoading.value = true;
    try {
        const res = await axios.get('/papeletas/api/posibles-jefes');
        posiblesJefes.value = res.data;
    } finally {
        posiblesJefesLoading.value = false;
    }
};

const employeeFullName = computed(() => {
    const person = props.myEmployee?.person;
    return [person?.nombres || props.myEmployee?.nombres, person?.apellidos || props.myEmployee?.apellidos]
        .filter(Boolean)
        .join(' ') || 'Servidor';
});

const employeeInitial = computed(() => employeeFullName.value.charAt(0).toUpperCase() || 'S');

const currentDateTime = ref(new Date());
let automaticClock = null;
const automaticTime = computed(() => currentDateTime.value.toTimeString().slice(0, 5));

// Campo Turno comentado por ahora (ver template).
// const automaticTurno = computed(() => {
//     const hour = currentDateTime.value.getHours();
//     if (hour >= 6 && hour < 14) return 'Mañana';
//     if (hour >= 14 && hour < 22) return 'Tarde';
//     return 'Noche';
// });
//
// const turnoColor = computed(() => ({
//     'Mañana': 'text-amber-600',
//     'Tarde': 'text-orange-600',
//     'Noche': 'text-indigo-600',
// }[automaticTurno.value]));

// ===== Scrollbar personalizado =====
const createScrollRef = ref(null);
const createTrackRef = ref(null);
const createThumbTop = ref(0);
const createThumbHeight = ref(0);
const createShowThumb = ref(false);
let createResizeObserver = null;
let createDragging = false;
let createDragStartY = 0;
let createDragStartScrollTop = 0;

const syncCreateThumb = () => {
    if (!createScrollRef.value || !createTrackRef.value) return;
    const { scrollTop, scrollHeight, clientHeight } = createScrollRef.value;
    const trackH = createTrackRef.value.clientHeight;
    const ratio = clientHeight / scrollHeight;
    createShowThumb.value = ratio < 1;
    const tH = Math.max(40, trackH * ratio);
    createThumbHeight.value = tH;
    createThumbTop.value = scrollHeight > clientHeight
        ? (scrollTop / (scrollHeight - clientHeight)) * (trackH - tH)
        : 0;
};

const onCreateMouseMove = (e) => {
    if (!createDragging || !createScrollRef.value || !createTrackRef.value) return;
    const { scrollHeight, clientHeight } = createScrollRef.value;
    const trackH = createTrackRef.value.clientHeight;
    const delta = e.clientY - createDragStartY;
    const trackScrollable = trackH - createThumbHeight.value;
    if (trackScrollable > 0)
        createScrollRef.value.scrollTop = createDragStartScrollTop + (delta / trackScrollable) * (scrollHeight - clientHeight);
};

const stopCreateDrag = () => {
    createDragging = false;
    document.removeEventListener('mousemove', onCreateMouseMove);
    document.removeEventListener('mouseup', stopCreateDrag);
};

const startCreateDrag = (e) => {
    createDragging = true;
    createDragStartY = e.clientY;
    createDragStartScrollTop = createScrollRef.value?.scrollTop ?? 0;
    document.addEventListener('mousemove', onCreateMouseMove);
    document.addEventListener('mouseup', stopCreateDrag);
};

const onCreateTrackClick = (e) => {
    if (!createScrollRef.value || !createTrackRef.value) return;
    const rect = createTrackRef.value.getBoundingClientRect();
    const clickY = e.clientY - rect.top;
    const { scrollHeight, clientHeight } = createScrollRef.value;
    const trackH = createTrackRef.value.clientHeight;
    const ratio = (clickY - createThumbHeight.value / 2) / (trackH - createThumbHeight.value);
    createScrollRef.value.scrollTop = ratio * (scrollHeight - clientHeight);
};

const handleStorePapeleta = async () => {
    createSubmitting.value = true;
    createErrors.value = {};
    try {
        const res = await axios.post('/papeletas/solicitar', createForm);
        window.Swal?.fire({ icon: 'success', title: `Papeleta #${res.data.numero_papeleta} creada`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        emit('created', res.data);
        emit('close');
    } catch (err) {
        if (err.response?.data?.errors) {
            createErrors.value = err.response.data.errors;
        } else {
            window.Swal?.fire({ icon: 'error', title: err.response?.data?.message || 'Error al crear la papeleta', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        }
    } finally {
        createSubmitting.value = false;
    }
};

onMounted(() => {
    automaticClock = window.setInterval(() => {
        currentDateTime.value = new Date();
    }, 30000);
    loadPosiblesJefes();
    nextTick(() => {
        syncCreateThumb();
        if (createScrollRef.value) {
            createResizeObserver = new ResizeObserver(() => syncCreateThumb());
            createResizeObserver.observe(createScrollRef.value);
        }
    });
});

onBeforeUnmount(() => {
    if (automaticClock) window.clearInterval(automaticClock);
    document.removeEventListener('mousemove', onCreateMouseMove);
    document.removeEventListener('mouseup', stopCreateDrag);
    createResizeObserver?.disconnect();
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>

            <!-- Modal + scrollbar custom fuera de la tarjeta -->
            <div class="relative z-10 flex items-stretch gap-2 max-h-[90vh] w-full max-w-2xl">
                <div class="bg-white rounded-2xl shadow-2xl flex flex-col flex-1 min-w-0 overflow-hidden">
                    <!-- Header (fijo) -->
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex justify-between items-center flex-shrink-0 rounded-t-2xl">
                        <div>
                            <h3 class="text-xl font-bold text-white flex items-center gap-2">
                                <Plus class="h-6 w-6" />
                                Nueva Papeleta
                            </h3>
                            <p class="text-emerald-100 text-sm mt-1">Datos personales tomados automáticamente de Recursos Humanos</p>
                        </div>
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer bg-white/10 rounded-xl p-2 inline-flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-95">
                            <span class="sr-only">Cerrar</span>
                            <X class="h-6 w-6" stroke-width="2" />
                        </button>
                    </div>

                    <!-- Form (scrolleable, sin scrollbar nativo) -->
                    <form id="papeleta-form" @submit.prevent="handleStorePapeleta" class="flex-1 min-h-0">
                    <div ref="createScrollRef" @scroll="syncCreateThumb" class="p-6 space-y-6 overflow-y-scroll h-full no-scrollbar">
                        <div class="rounded-xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-teal-50 p-4">
                            <div class="flex items-center gap-4 rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-500 to-teal-600 text-xl font-bold text-white shadow-lg">
                                    {{ employeeInitial }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-lg font-bold text-slate-900">{{ employeeFullName }}</p>
                                    <div class="mt-1 grid grid-cols-1 gap-x-4 gap-y-1 text-xs text-slate-600 sm:grid-cols-2">
                                        <span><b>DNI:</b> {{ myEmployee?.dni || myEmployee?.person?.dni || '-' }}</span>
                                        <span><b>OFICINA:</b> {{ myEmployee?.office?.nombre || myEmployee?.direction?.nombre || 'Sin oficina registrada' }}</span>
                                        <span><b>CONDICIÓN LABORAL:</b> {{ myEmployee?.contract_type?.nombre || myEmployee?.tipo_contrato || 'Sin condición registrada' }}</span>
                                        <span><b>CARGO:</b> {{ myEmployee?.position?.nombre || myEmployee?.cargo || 'Sin cargo registrado' }}</span>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-xs font-medium text-emerald-800">La fecha, hora de creación y turno se registran automáticamente con la hora institucional. La salida real se registrará al escanear el QR en portería.</p>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 flex items-center gap-2 text-sm font-bold text-slate-700">
                                    Hora de creación de la papeleta
                                    <span class="relative flex h-2 w-2">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                                    </span>
                                    <span class="text-[10px] font-medium uppercase tracking-tight text-emerald-600">Automático</span>
                                </label>
                                <input type="time" :value="automaticTime" disabled class="w-full cursor-not-allowed rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-bold text-slate-500 outline-none" />
                            </div>
                            <!-- Campo Turno comentado por ahora.
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700">Turno <span class="text-xs font-normal text-emerald-500">(automático)</span></label>
                                <div class="flex w-full items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5">
                                    <Clock class="h-4 w-4 text-slate-400" />
                                    <span class="font-medium" :class="turnoColor">{{ automaticTurno }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">El turno se determina según la hora de creación.</p>
                            </div>
                            -->
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Motivo de salida <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'comision' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="comision" class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Comisión de Servicios</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'particular_compensable' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="particular_compensable" class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Particular Compensable</span>
                                </label>
                                <label class="flex cursor-pointer items-center rounded-xl border p-3 transition-colors" :class="createForm.motivo_salida === 'por_salud' ? 'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' : 'border-slate-200 hover:bg-slate-50'">
                                    <input v-model="createForm.motivo_salida" type="radio" value="por_salud" class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500" />
                                    <span class="ml-2 text-sm font-medium text-slate-700">Por Salud</span>
                                </label>
                            </div>
                            <p v-if="createErrors.motivo_salida" class="mt-1 text-xs text-red-600">{{ createErrors.motivo_salida[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Destino <span class="text-red-500">*</span></label>
                            <input v-model="createForm.destino" maxlength="250" class="w-full rounded-xl border-2 px-4 py-2.5 text-sm outline-none transition-colors focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500" :class="createErrors.destino ? 'border-red-400' : 'border-slate-200'" placeholder="Indique la entidad o lugar de destino" />
                            <p v-if="createErrors.destino" class="mt-1 text-xs text-red-600">{{ createErrors.destino[0] }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-bold text-slate-700">Justificación <span class="text-xs font-normal text-slate-400">(opcional)</span></label>
                            <textarea v-model="createForm.motivo" rows="3" maxlength="500" class="w-full resize-none rounded-xl border-2 px-4 py-3 text-sm outline-none transition-colors focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500" :class="createErrors.motivo ? 'border-red-400' : 'border-slate-200'" placeholder="Indique el motivo de la salida..."></textarea>
                            <p v-if="createErrors.motivo" class="mt-1 text-xs text-red-600">{{ createErrors.motivo[0] }}</p>
                        </div>

                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
                            <label class="mb-1.5 block text-sm font-bold text-emerald-900">Jefe que aprobará esta papeleta <span class="text-red-500">*</span></label>
                            <EmployeeSearchSelect v-model="createForm.jefe_asignado_id" :employees="posiblesJefes"
                                :allow-empty="false" :disabled="posiblesJefesLoading"
                                :invalid="!!createErrors.jefe_asignado_id" accent="emerald"
                                placeholder="Buscar por DNI o nombre..." />
                            <p v-if="posiblesJefesLoading" class="mt-1 text-xs text-emerald-700">Cargando servidores…</p>
                            <p v-else class="mt-1 text-xs text-emerald-700">Se sugiere su jefe inmediato registrado. Puede elegir a otro servidor si él no se encuentra disponible.</p>
                            <!-- Aviso de certificado RENIEC comentado por ahora.
                            <p v-if="jefeSeleccionadoSinCertificado" class="mt-1 flex items-center gap-1 text-xs font-bold text-amber-600">
                                <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                                La persona seleccionada aún no registra su certificado RENIEC; no podrá firmar hasta que lo haga.
                            </p>
                            -->
                            <p v-if="createErrors.jefe_asignado_id" class="mt-1 text-xs text-red-600">{{ createErrors.jefe_asignado_id[0] }}</p>
                        </div>

                        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                            <label class="mb-1.5 block text-sm font-bold text-indigo-900">Clave de firma <span class="text-red-500">*</span></label>
                                <input type="password" v-model="createForm.signing_pin" autocomplete="off"
                                    class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                                    :class="createErrors.signing_pin ? 'border-red-400' : 'border-indigo-200'" placeholder="Clave creada al registrar el PFX" />
                            <p class="mt-1 text-xs text-indigo-700">Al enviar, firma digitalmente la solicitud y se remite al jefe seleccionado. No se usa QR.</p>
                            <p v-if="createErrors.signing_pin" class="mt-1 text-xs text-red-600">{{ createErrors.signing_pin[0] }}</p>
                        </div>
                    </div>
                    </form>

                    <!-- Footer (fijo) -->
                    <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 flex-shrink-0 rounded-b-2xl bg-white">
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer rounded-xl border-2 border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50 active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" form="papeleta-form" :disabled="createSubmitting"
                            class="cursor-pointer inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-2.5 text-sm font-bold text-white transition-all hover:from-emerald-700 hover:to-teal-700 shadow-lg shadow-emerald-600/20 disabled:opacity-50 active:scale-95">
                            <Loader2 v-if="createSubmitting" class="h-4 w-4 animate-spin" />
                            {{ createSubmitting ? 'Registrando...' : 'Firmar y enviar papeleta' }}
                        </button>
                    </div>
                </div>

                <!-- Scrollbar custom (fuera de la tarjeta blanca) -->
                <div v-show="createShowThumb" class="flex-shrink-0 flex items-stretch my-2">
                    <div class="bg-white rounded-3xl px-2 py-2 flex items-stretch">
                        <div ref="createTrackRef" @click="onCreateTrackClick" class="w-3 relative cursor-pointer rounded-full">
                            <div v-show="createShowThumb"
                                class="absolute left-0 right-0 rounded-full bg-zinc-400 hover:bg-zinc-500 transition-colors cursor-grab active:cursor-grabbing"
                                :style="{ top: createThumbTop + 'px', height: createThumbHeight + 'px' }"
                                @mousedown.prevent="startCreateDrag">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
