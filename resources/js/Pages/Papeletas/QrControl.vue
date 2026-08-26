<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import { LogOut, LogIn, MapPin, ShieldCheck, Loader2, CheckCircle2 } from 'lucide-vue-next';
import SignaturePad from '@/Components/Common/SignaturePad.vue';

const props = defineProps({
    token: { type: String, required: true },
    papeleta: { type: Object, required: true },
});

const esComision = computed(() => props.papeleta.motivo_tipo === 'comision');
const necesitaDestino = computed(() => esComision.value && props.papeleta.salida_real_at && !props.papeleta.destino_firmado_at);
const destinoRegistrado = computed(() => esComision.value && !!props.papeleta.destino_firmado_at);
const puedeMarcarRetorno = computed(() =>
    props.papeleta.salida_real_at && !props.papeleta.retorno_real_at && (!esComision.value || props.papeleta.destino_firmado_at)
);

// ===== Paso 1: marcar salida =====
const marcandoSalida = ref(false);
const marcarSalida = () => {
    marcandoSalida.value = true;
    router.post(`/control-papeleta/${props.token}/salida`, {}, {
        preserveScroll: true,
        onSuccess: () => window.Swal?.fire({ icon: 'success', title: 'Salida registrada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
        onError: () => window.Swal?.fire({ icon: 'error', title: 'No se pudo registrar la salida' }),
        onFinish: () => { marcandoSalida.value = false; },
    });
};

// ===== Paso final: marcar retorno =====
const marcandoRetorno = ref(false);
const marcarRetorno = () => {
    marcandoRetorno.value = true;
    router.post(`/control-papeleta/${props.token}/retorno`, {}, {
        preserveScroll: true,
        onSuccess: () => window.Swal?.fire({ icon: 'success', title: 'Retorno registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 }),
        onError: () => window.Swal?.fire({ icon: 'error', title: 'No se pudo registrar el retorno' }),
        onFinish: () => { marcandoRetorno.value = false; },
    });
};

// ===== Paso 2: constancia de destino =====
const destinoSchema = toTypedSchema(yup.object({
    dni: yup.string().required('El DNI es obligatorio.').matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos.'),
    cargo: yup.string().required('Indique el cargo del responsable.').max(150, 'Máximo 150 caracteres.'),
}));

const { errors: destinoErrors, defineField: defineDestinoField, handleSubmit: validateDestino, setFieldError: setDestinoFieldError } = useForm({
    validationSchema: destinoSchema,
    initialValues: { dni: '', cargo: '' },
});

const [dni, dniProps] = defineDestinoField('dni');
const [cargo, cargoProps] = defineDestinoField('cargo');

const dniValidating = ref(false);
const nombreValidado = ref('');
const dniStatus = ref('');

// Si el DNI cambia después de una validación exitosa, el nombre queda obsoleto.
watch(dni, () => { nombreValidado.value = ''; });

const handleValidarDni = async () => {
    if (!/^\d{8}$/.test(dni.value || '')) {
        setDestinoFieldError('dni', 'Ingrese un DNI de 8 dígitos.');
        return;
    }
    dniValidating.value = true;
    nombreValidado.value = '';
    dniStatus.value = 'Validando DNI, puede tardar unos segundos…';
    try {
        const { data } = await axios.get(`/control-papeleta/${props.token}/responsable-dni`, { params: { dni: dni.value } });
        if (!data.success || !data.data?.nombre_completo) throw new Error(data.message || 'No se encontró el DNI.');
        nombreValidado.value = data.data.nombre_completo;
        dniStatus.value = 'Identidad validada.';
    } catch (e) {
        dniStatus.value = e.response?.data?.message || e.message || 'No se pudo validar el DNI.';
    } finally {
        dniValidating.value = false;
    }
};

const gpsStatus = ref('Toque «Obtener ubicación» y autorice el GPS del teléfono. Se guardarán coordenadas, precisión y fecha/hora.');
const gpsLoading = ref(false);
const latitude = ref('');
const longitude = ref('');
const accuracy = ref('');

// Vista previa del mapa (OpenStreetMap, sin necesidad de API key) una vez capturado el GPS.
const mapEmbedUrl = computed(() => {
    if (!latitude.value || !longitude.value) return null;
    const lat = parseFloat(latitude.value);
    const lon = parseFloat(longitude.value);
    const delta = 0.003;
    const bbox = [lon - delta, lat - delta, lon + delta, lat + delta].join(',');
    return `https://www.openstreetmap.org/export/embed.html?bbox=${encodeURIComponent(bbox)}&layer=mapnik&marker=${lat}%2C${lon}`;
});

const mapLinkUrl = computed(() => {
    if (!latitude.value || !longitude.value) return null;
    return `https://www.openstreetmap.org/?mlat=${latitude.value}&mlon=${longitude.value}#map=17/${latitude.value}/${longitude.value}`;
});

const obtenerUbicacion = () => {
    if (!navigator.geolocation) {
        gpsStatus.value = 'Este dispositivo no ofrece ubicación GPS.';
        return;
    }
    gpsLoading.value = true;
    gpsStatus.value = 'Solicitando ubicación precisa…';
    navigator.geolocation.getCurrentPosition(
        (pos) => {
            latitude.value = pos.coords.latitude.toFixed(7);
            longitude.value = pos.coords.longitude.toFixed(7);
            accuracy.value = Math.round(pos.coords.accuracy);
            gpsStatus.value = `Ubicación capturada (precisión aproximada ±${accuracy.value} m).`;
            gpsLoading.value = false;
        },
        (err) => {
            gpsLoading.value = false;
            gpsStatus.value = `No se pudo obtener el GPS: ${err.message}. Autorice la ubicación y vuelva a intentarlo.`;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
};

const signaturePadRef = ref(null);
const signatureInvalid = ref(false);
const signatureStatus = ref('');
const submittingDestino = ref(false);

const handleSubmitDestino = validateDestino(async (values) => {
    if (!nombreValidado.value) {
        dniStatus.value = 'Primero valide el DNI del responsable.';
        return;
    }
    if (!latitude.value || !longitude.value) {
        obtenerUbicacion();
        return;
    }
    if (!signaturePadRef.value?.hasInk) {
        signatureInvalid.value = true;
        signatureStatus.value = 'Solicite la firma táctil horizontal antes de registrar.';
        return;
    }
    signatureInvalid.value = false;
    submittingDestino.value = true;
    router.post(`/control-papeleta/${props.token}/destino`, {
        dni: values.dni,
        cargo: values.cargo,
        firma: signaturePadRef.value.getDataUrl(),
        latitude: latitude.value,
        longitude: longitude.value,
        accuracy: accuracy.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            window.Swal?.fire({ icon: 'success', title: 'Constancia de destino registrada', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        },
        onError: (errors) => {
            if (errors.dni) setDestinoFieldError('dni', errors.dni);
            const message = Object.values(errors).flat().join('\n') || 'No se pudo registrar la constancia.';
            window.Swal?.fire({ icon: 'error', title: 'No se pudo registrar', text: message });
        },
        onFinish: () => { submittingDestino.value = false; },
    });
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 px-3 py-6 sm:px-4 sm:py-8 md:px-6">
        <div class="mx-auto max-w-lg">
            <div class="mb-4 text-center">
                <h1 class="text-2xl font-black tracking-tight text-slate-800 sm:text-3xl">Control de salida</h1>
                <p class="mt-1 text-sm font-medium text-slate-500">Dirección Regional de Educación Huánuco</p>
            </div>

            <!-- Tarjeta de la papeleta -->
            <div class="mb-4 rounded-2xl border-2 border-white bg-white/90 p-5 shadow-xl backdrop-blur-sm">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Papeleta N.°</p>
                <p class="font-mono text-lg font-black text-blue-600">{{ papeleta.numero_papeleta }}</p>
                <p class="mt-2 font-bold text-slate-800">{{ papeleta.empleado_nombre }}</p>
                <p class="text-sm text-slate-500">{{ papeleta.motivo_nombre }}</p>
                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl border p-3"
                        :class="papeleta.salida_real_at ? 'border-transparent bg-slate-50' : 'border-red-300 bg-red-50'">
                        <p class="text-xs font-bold uppercase" :class="papeleta.salida_real_at ? 'text-slate-400' : 'text-red-500'">Salida</p>
                        <p class="font-semibold" :class="papeleta.salida_real_at ? 'text-slate-700' : 'text-red-600'">{{ papeleta.salida_real_at || 'Pendiente' }}</p>
                    </div>
                    <div class="rounded-xl border p-3"
                        :class="papeleta.retorno_real_at ? 'border-transparent bg-slate-50' : 'border-red-300 bg-red-50'">
                        <p class="text-xs font-bold uppercase" :class="papeleta.retorno_real_at ? 'text-slate-400' : 'text-red-500'">Retorno</p>
                        <p class="font-semibold" :class="papeleta.retorno_real_at ? 'text-slate-700' : 'text-red-600'">{{ papeleta.retorno_real_at || 'Pendiente' }}</p>
                    </div>
                </div>
            </div>

            <!-- Paso 1: marcar salida -->
            <div v-if="!papeleta.salida_real_at" class="space-y-3">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                    <p class="font-bold">Primer paso: salida.</p>
                    <p>El responsable de portería debe marcar la salida antes de solicitar la constancia en la entidad destino.</p>
                </div>
                <button type="button" @click="marcarSalida" :disabled="marcandoSalida"
                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-500/20 transition-all hover:from-blue-700 hover:to-indigo-700 disabled:opacity-50">
                    <Loader2 v-if="marcandoSalida" class="h-4 w-4 animate-spin" />
                    <LogOut v-else class="h-4 w-4" />
                    Marcar salida
                </button>
            </div>

            <!-- Paso 2: constancia de destino -->
            <form v-else-if="necesitaDestino" @submit.prevent="handleSubmitDestino"
                class="space-y-4 rounded-2xl border-2 border-white bg-white/90 p-5 shadow-xl backdrop-blur-sm">
                <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-800">
                    <p class="font-bold">Segundo paso: constancia de destino.</p>
                    <p>Valide el DNI, capture el GPS y solicite la firma táctil horizontal.</p>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">DNI del responsable de destino <span class="text-red-500">*</span></label>
                    <div class="flex gap-2">
                        <input v-model="dni" v-bind="dniProps" inputmode="numeric" maxlength="8" placeholder="8 dígitos"
                            class="w-full rounded-xl border-2 px-4 py-2.5 text-sm outline-none transition-colors focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500"
                            :class="destinoErrors.dni ? 'border-red-400' : 'border-slate-200'" />
                        <button type="button" @click="handleValidarDni" :disabled="dniValidating"
                            class="flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-teal-800 disabled:opacity-60">
                            <Loader2 v-if="dniValidating" class="h-4 w-4 animate-spin" />
                            {{ dniValidating ? 'Validando…' : 'Validar DNI' }}
                        </button>
                    </div>
                    <p v-if="destinoErrors.dni" class="mt-1 text-xs text-red-600">{{ destinoErrors.dni }}</p>
                    <p v-else-if="dniStatus" class="mt-1 text-xs text-slate-500">{{ dniStatus }}</p>
                    <input :value="nombreValidado" readonly placeholder="Nombre validado por DNI"
                        class="mt-2 w-full rounded-xl border-2 border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 outline-none" />
                </div>

                <div>
                    <label class="mb-2 block text-sm font-bold text-slate-700">Cargo del responsable <span class="text-red-500">*</span></label>
                    <input v-model="cargo" v-bind="cargoProps" maxlength="150" placeholder="Ej. Director, Secretaria..."
                        class="w-full rounded-xl border-2 px-4 py-2.5 text-sm outline-none transition-colors focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500"
                        :class="destinoErrors.cargo ? 'border-red-400' : 'border-slate-200'" />
                    <p v-if="destinoErrors.cargo" class="mt-1 text-xs text-red-600">{{ destinoErrors.cargo }}</p>
                </div>

                <div class="rounded-xl border border-teal-200 bg-teal-50 p-4">
                    <div class="mb-1.5 flex items-center gap-1.5 text-sm font-bold text-teal-900">
                        <MapPin class="h-4 w-4" />
                        Ubicación GPS de la entidad destino
                    </div>
                    <p class="text-xs text-teal-700">{{ gpsStatus }}</p>
                    <button type="button" @click="obtenerUbicacion" :disabled="gpsLoading"
                        class="mt-2 flex w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-teal-700 px-4 py-2.5 text-sm font-bold text-white transition-colors hover:bg-teal-800 disabled:opacity-60">
                        <Loader2 v-if="gpsLoading" class="h-4 w-4 animate-spin" />
                        <MapPin v-else class="h-4 w-4" />
                        Obtener ubicación
                    </button>
                    <div v-if="mapEmbedUrl" class="mt-3 overflow-hidden rounded-xl border border-teal-200 shadow-sm">
                        <iframe :src="mapEmbedUrl" class="h-40 w-full pointer-events-none select-none" tabindex="-1"
                            loading="lazy" title="Mapa de la ubicación capturada"></iframe>
                    </div>
                    <a v-if="mapLinkUrl" :href="mapLinkUrl" target="_blank" rel="noopener"
                        class="mt-1.5 inline-block text-xs font-bold text-teal-700 underline underline-offset-2 hover:text-teal-900">
                        Ver en el mapa completo
                    </a>
                </div>

                <SignaturePad ref="signaturePadRef" label="Firma táctil horizontal" :invalid="signatureInvalid" />
                <p v-if="signatureStatus" class="-mt-2 text-xs text-red-600">{{ signatureStatus }}</p>

                <button type="submit" :disabled="submittingDestino"
                    class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-teal-500/20 transition-all hover:from-teal-700 hover:to-emerald-700 disabled:opacity-50">
                    <Loader2 v-if="submittingDestino" class="h-4 w-4 animate-spin" />
                    <ShieldCheck v-else class="h-4 w-4" />
                    Registrar constancia de destino
                </button>
            </form>

            <!-- Constancia de destino ya registrada -->
            <div v-else-if="destinoRegistrado" class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                <CheckCircle2 class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <p class="font-bold">Constancia de destino registrada</p>
                    <p>{{ papeleta.destino_firmante_nombre }} · {{ papeleta.destino_firmante_cargo }}</p>
                    <p class="text-xs text-emerald-700">{{ papeleta.destino_firmado_at }}</p>
                </div>
            </div>

            <!-- Paso final: marcar retorno -->
            <button v-if="puedeMarcarRetorno" type="button" @click="marcarRetorno" :disabled="marcandoRetorno"
                class="mt-3 flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-500/20 transition-all hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50">
                <Loader2 v-if="marcandoRetorno" class="h-4 w-4 animate-spin" />
                <LogIn v-else class="h-4 w-4" />
                Marcar retorno
            </button>
        </div>
    </div>
</template>
