<script setup lang="ts">
import { shallowRef, computed, nextTick, onMounted, onUnmounted, useTemplateRef } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { LogOut, X, Loader2, ScanBarcode, CheckCircle2, AlertCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

import { Visit } from '@/Types/visitor';

const props = defineProps<{
    visit: Visit;
}>();

const emit = defineEmits<{
    close: [];
    success: [];
}>();

const isSubmitting = shallowRef(false);
const currentTime = shallowRef('');
const loadingTime = shallowRef(true);
const verifyDni = shallowRef('');
const verifyDniInput = useTemplateRef<HTMLInputElement>('verifyDniInput');

const fetchServerTime = async () => {
    try {
        const res = await fetch('/api/server-time');
        const data = await res.json();
        currentTime.value = data.time;
        setFieldValue('hora_salida', data.time);
    } catch {
        currentTime.value = new Date().toTimeString().slice(0, 5);
        setFieldValue('hora_salida', currentTime.value);
    } finally {
        loadingTime.value = false;
    }
};

// DNI Verification
const isDniVerified = computed(() => {
    return props.visit && verifyDni.value === props.visit.dni;
});

// Validation Schema
const schema = toTypedSchema(
    yup.object({
        hora_salida: yup.string().required('La hora de salida es obligatoria'),
        observacion_salida: yup.string().nullable().max(500, 'Máximo 500 caracteres'),
    })
);

const { errors, defineField, handleSubmit: validateForm, setFieldValue } = useForm({
    validationSchema: schema,
    initialValues: {
        hora_salida: '',
        observacion_salida: '',
    }
});

const [horaSalida] = defineField('hora_salida');
const [observacionSalida] = defineField('observacion_salida');

// Auto-submit if verified via enter or scanner
const checkDniAndSubmit = async () => {
    if (isDniVerified.value) {
        await fetchServerTime();
        handleFormSubmit();
    }
};

const handleFormSubmit = validateForm(async (values) => {
    if (!isDniVerified.value) {
        Swal.fire({
            icon: 'warning',
            title: 'Verificación Requerida',
            text: 'Debe escanear o ingresar el DNI del visitante.',
            timer: 3000,
            showConfirmButton: false
        });
        verifyDniInput.value?.focus();
        return;
    }

    isSubmitting.value = true;

    router.patch(`/visitors/${props.visit.id}/exit`, values as any, {
        onSuccess: () => {
            Swal.fire({
                icon: 'success',
                title: 'Salida Registrada',
                text: 'La salida de la visita fue registrada correctamente.',
                timer: 2000,
                showConfirmButton: false
            });
            emit('success');
            emit('close');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

const handleSubmit = () => {
    handleFormSubmit();
};

let timeInterval: ReturnType<typeof setInterval>;

onMounted(() => {
    fetchServerTime();
    // Update exit time every 30 seconds using server time
    timeInterval = setInterval(fetchServerTime, 30000);

    // Focus DNI input on open
    nextTick(() => {
        verifyDniInput.value?.focus();
    });
});

onUnmounted(() => {
    clearInterval(timeInterval);
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <LogOut class="w-6 h-6" />
                            Registrar Salida
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">
                            Confirmar salida de visita
                        </p>
                    </div>
                    <button type="button" @click="emit('close')"
                        class="cursor-pointer bg-white/10 rounded-xl p-2 inline-flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-95">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Scanner Section -->
                    <div class="bg-gradient-to-br from-purple-50 to-fuchsia-50 rounded-xl p-3 border border-purple-100">
                        <div class="flex items-center gap-2 mb-2">
                            <ScanBarcode class="w-4 h-4 text-purple-600" />
                            <span class="text-sm font-semibold text-purple-900">Consulta de DNI con Lector</span>
                        </div>
                        <div class="flex gap-2">
                            <input ref="verifyDniInput" type="text" v-model="verifyDni"
                                placeholder="Escanee o escriba DNI..." maxlength="8"
                                @keyup.enter.prevent="checkDniAndSubmit"
                                class="flex-1 w-full px-3 py-2 border-2 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 transition-all text-sm outline-none bg-white"
                                :class="verifyDni && !isDniVerified ? 'border-red-400 bg-red-50' : 'border-purple-200'" />
                            <button type="button" @click="checkDniAndSubmit"
                                :disabled="!isDniVerified || isSubmitting"
                                class="cursor-pointer px-4 py-2 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-sm font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-md shadow-purple-500/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5 active:scale-95">
                                <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                                <LogOut v-else class="w-4 h-4" />
                                <span class="hidden sm:inline">Confirmar</span>
                            </button>
                        </div>
                        <p v-if="verifyDni && !isDniVerified" class="text-xs text-red-600 mt-1 font-bold flex items-center gap-1">
                            <AlertCircle class="w-3 h-3" /> DNI no coincide
                        </p>
                        <p v-if="isDniVerified" class="text-xs text-green-600 mt-1 font-bold flex items-center gap-1">
                            <CheckCircle2 class="w-3 h-3" /> Identidad Verificada
                        </p>
                    </div>

                    <!-- Visit Info -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 opacity-75">
                        <div class="flex items-center gap-4">
                            <div
                                class="h-14 w-14 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-xl font-bold text-white shadow-lg shrink-0">
                                {{ (visit.nombres || '?').charAt(0) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 text-lg truncate">{{ visit.nombres }}</p>
                                <p class="text-sm text-slate-600">DNI: {{ visit.dni }}</p>
                                <p class="text-xs text-slate-500 mt-1">Ingreso: {{ visit.hora_ingreso }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Time Input (Automatic) -->
                    <div class="relative">
                        <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                            Hora de Salida
                            <span class="flex h-2 w-2 relative">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span
                                class="text-[10px] text-emerald-600 font-medium uppercase tracking-tight">Automático</span>
                        </label>
                        <div class="relative">
                            <input type="time" :value="horaSalida" disabled
                                class="w-full px-4 py-2.5 bg-slate-50 border-2 border-slate-200 rounded-xl text-slate-500 font-bold outline-none cursor-not-allowed" />
                            <div class="absolute inset-0 z-10 flex items-center justify-end pr-3"
                                title="Hora obtenida del servidor">
                                <span v-if="loadingTime"
                                    class="text-xs text-slate-400 animate-pulse">cargando...</span>
                                <span v-else
                                    class="text-xs text-emerald-600 font-semibold">servidor ✓</span>
                            </div>
                        </div>
                        <p v-if="errors.hora_salida" class="mt-1 text-sm text-red-600">{{ errors.hora_salida }}</p>
                    </div>

                    <!-- Observación (Opcional) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones (Opcional)</label>
                        <textarea v-model="observacionSalida" rows="2" placeholder="Notas opcionales sobre la salida..."
                            class="w-full px-4 py-3 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-purple-500/20 focus:border-purple-500 resize-none transition-colors outline-none text-sm shadow-sm"
                            :class="{ 'border-red-400': errors.observacion_salida }"></textarea>
                        <p v-if="errors.observacion_salida" class="mt-1 text-sm text-red-600">{{
                            errors.observacion_salida }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting || !isDniVerified"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all shadow-lg shadow-purple-500/20 disabled:opacity-50 disabled:cursor-not-allowed active:scale-95">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            Confirmar Salida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
