<script setup lang="ts">
import { ref, computed, nextTick, onMounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { LogOut, X, Loader2, ScanBarcode, CheckCircle2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

import { Visit } from '@/Types/visitor';

const props = defineProps<{
    visit: Visit;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

const isSubmitting = ref(false);
const currentTime = ref(new Date().toTimeString().slice(0, 5));

// Update time every 30 seconds
let timeInterval: any;
onMounted(() => {
    timeInterval = setInterval(() => {
        currentTime.value = new Date().toTimeString().slice(0, 5);
        setFieldValue('hora_salida', currentTime.value);
    }, 30000);
});

import { onUnmounted } from 'vue';
onUnmounted(() => {
    if (timeInterval) clearInterval(timeInterval);
});
const verifyDni = ref('');
const verifyDniInput = ref<HTMLInputElement | null>(null);

// DNI Verification
const isDniVerified = computed(() => {
    return props.visit && verifyDni.value === props.visit.dni;
});

// Focus on mount
onMounted(() => {
    nextTick(() => {
        verifyDniInput.value?.focus();
    });
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
        hora_salida: currentTime.value,
        observacion_salida: '',
    }
});

const [horaSalida] = defineField('hora_salida');
const [observacionSalida] = defineField('observacion_salida');

// Auto-submit if verified via enter or scanner
const checkDniAndSubmit = () => {
    if (isDniVerified.value) {
        // Update exit time to current moment on verification
        const now = new Date().toTimeString().slice(0, 5);
        if (horaSalida) horaSalida.value = now;

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
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

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
                    <button type="button" @click="$emit('close')"
                        class="bg-white/10 rounded-md p-2 inline-flex items-center justify-center text-white hover:text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white/50 transition-colors">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Scanner Section -->
                    <div class="bg-indigo-50 border-l-4 border-indigo-500 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <ScanBarcode class="h-5 w-5 text-indigo-500" />
                            </div>
                            <div class="ml-3 w-full">
                                <p class="text-sm text-indigo-700 font-bold mb-2">
                                    Escanee el DNI para confirmar
                                </p>
                                <input ref="verifyDniInput" type="text" v-model="verifyDni"
                                    placeholder="Escanee o escriba DNI..." maxlength="8"
                                    @keyup.enter.prevent="checkDniAndSubmit"
                                    class="block w-full rounded-md border-indigo-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm p-2 border bg-white outline-none" />

                                <p v-if="verifyDni && !isDniVerified" class="text-xs text-red-600 mt-1 font-bold">
                                    No coincide ({{ visit.dni }})
                                </p>
                                <p v-if="isDniVerified"
                                    class="text-xs text-green-600 mt-1 font-bold flex items-center gap-1">
                                    <CheckCircle2 class="w-3 h-3" /> Identidad Verificada
                                </p>
                            </div>
                        </div>
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
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 font-bold outline-none cursor-not-allowed" />
                            <div class="absolute inset-0 z-10" title="La hora se asigna automáticamente"></div>
                        </div>
                        <p v-if="errors.hora_salida" class="mt-1 text-sm text-red-600">{{ errors.hora_salida }}</p>
                    </div>

                    <!-- Observación (Opcional) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones (Opcional)</label>
                        <textarea v-model="observacionSalida" rows="2" placeholder="Notas opcionales sobre la salida..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none transition-colors outline-none text-sm shadow-sm"
                            :class="{ 'border-red-400': errors.observacion_salida }"></textarea>
                        <p v-if="errors.observacion_salida" class="mt-1 text-sm text-red-600">{{
                            errors.observacion_salida }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting || !isDniVerified"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            Confirmar Salida
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
