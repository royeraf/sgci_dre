<script setup>
import { computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { CheckCircle, XCircle, X, Loader2 } from 'lucide-vue-next';

// Modal único para las dos acciones de una papeleta ("Aprobar" y
// "Desaprobar"): son el mismo formulario (comentario + clave de firma
// opcional) con reglas de validación distintas según `mode`, así que se
// resuelven con un solo componente en vez de dos casi idénticos.
const props = defineProps({
    mode: { type: String, required: true }, // 'aprobar' | 'desaprobar'
    papeleta: { type: Object, default: null },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const isAprobar = computed(() => props.mode === 'aprobar');

// Reglas espejo de PapeletaAdminController::aprobar()/desaprobar():
// signing_pin required|min:6|max:20 solo al aprobar; comentario
// required|max:500 solo al desaprobar (al aprobar es opcional).
const schema = computed(() => toTypedSchema(yup.object({
    comentario: isAprobar.value
        ? yup.string().transform((v) => v || '').max(500, 'Máximo 500 caracteres.')
        : yup.string().required('Debe indicar el motivo del rechazo.').max(500, 'Máximo 500 caracteres.'),
    signing_pin: isAprobar.value
        ? yup.string()
            .required('Ingrese su clave de firma.')
            .min(6, 'La clave de firma debe tener al menos 6 caracteres.')
            .max(20, 'La clave de firma no debe superar los 20 caracteres.')
        : yup.string().transform(() => undefined).nullable(),
})));

const { errors, defineField, handleSubmit: validateForm, setFieldError } = useForm({
    validationSchema: schema,
    initialValues: { comentario: '', signing_pin: '' },
});

const [comentario, comentarioProps] = defineField('comentario');
const [signingPin, signingPinProps] = defineField('signing_pin');

const onSubmit = validateForm((values) => emit('submit', values));

// Permite al padre mapear un error 422 del backend (p. ej. PIN incorrecto)
// de vuelta al campo correspondiente sin cerrar el modal.
defineExpose({ setFieldError });
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>
            <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <!-- Header -->
                <div class="px-6 py-4 flex justify-between items-center"
                    :class="isAprobar ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gradient-to-r from-red-600 to-rose-600'">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CheckCircle v-if="isAprobar" class="h-6 w-6" />
                            <XCircle v-else class="h-6 w-6" />
                            {{ isAprobar ? 'Aprobar Papeleta' : 'Desaprobar Papeleta' }}
                        </h3>
                        <p class="text-sm mt-1" :class="isAprobar ? 'text-emerald-100' : 'text-rose-100'">
                            <template v-if="isAprobar">
                                {{ papeleta?.estado === 'PENDIENTE' ? 'Firma digital como jefe inmediato' : 'Firma digital como Recursos Humanos' }}
                            </template>
                            <template v-else>Indique el motivo del rechazo</template>
                        </p>
                    </div>
                    <button type="button" @click="emit('close')"
                        class="cursor-pointer bg-white/10 rounded-xl p-2 inline-flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-95">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-lg font-bold text-white shadow-lg bg-gradient-to-br"
                            :class="isAprobar ? 'from-green-500 to-emerald-600' : 'from-red-500 to-rose-600'">
                            {{ (papeleta?.employee?.person?.apellidos || '?').charAt(0) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-slate-900">{{ papeleta?.employee?.person?.apellidos }}, {{ papeleta?.employee?.person?.nombres }}</p>
                            <p class="text-xs text-slate-500">Papeleta N° <span class="font-mono font-bold text-blue-600">{{ papeleta?.numero_papeleta }}</span></p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            {{ isAprobar ? 'Comentario' : 'Motivo del rechazo' }}
                            <span v-if="isAprobar" class="text-xs font-normal text-slate-400">(opcional)</span>
                            <span v-else class="text-red-500">*</span>
                        </label>
                        <textarea v-model="comentario" v-bind="comentarioProps" rows="3" maxlength="500"
                            class="w-full resize-none rounded-xl border-2 px-4 py-3 text-sm outline-none transition-colors"
                            :class="[errors.comentario ? 'border-red-400' : 'border-slate-200', isAprobar ? 'focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500' : 'focus:ring-4 focus:ring-red-500/20 focus:border-red-500']"
                            :placeholder="isAprobar ? 'Agregue un comentario...' : 'Indique el motivo del rechazo...'"></textarea>
                        <p v-if="errors.comentario" class="mt-1 text-xs text-red-600">{{ errors.comentario }}</p>
                    </div>

                    <div v-if="isAprobar" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <label class="mb-1.5 block text-sm font-bold text-indigo-900">Clave de firma digital <span class="text-red-500">*</span></label>
                        <input v-model="signingPin" v-bind="signingPinProps" type="password" autocomplete="off"
                            class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            :class="errors.signing_pin ? 'border-red-400' : 'border-indigo-200'" placeholder="Clave de su certificado" />
                        <p class="mt-1 text-xs text-indigo-700">La aprobación continuará únicamente si se genera su firma digital.</p>
                        <p v-if="errors.signing_pin" class="mt-1 text-xs text-red-600">{{ errors.signing_pin }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer rounded-xl border-2 border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50 active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="processing"
                            class="cursor-pointer inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white transition-all shadow-lg disabled:opacity-50 active:scale-95 bg-gradient-to-r"
                            :class="isAprobar ? 'from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 shadow-emerald-600/20' : 'from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 shadow-red-600/20'">
                            <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                            {{ isAprobar ? 'Confirmar Aprobación' : 'Confirmar Rechazo' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
