<script setup>
import { computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { CheckCircle, X, Loader2 } from 'lucide-vue-next';

// Modal único para las dos acciones de la salida vehicular que exigen firma
// digital ("Autorizar" y "Confirmar como conductor"): mismo formulario
// (comentario opcional + clave de firma), con el comentario visible solo al
// autorizar. Espejo de Components/Papeletas/ApprovalModal.vue.
const props = defineProps({
    mode: { type: String, required: true }, // 'autorizar' | 'confirmar'
    commission: { type: Object, default: null },
    processing: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const isAutorizar = computed(() => props.mode === 'autorizar');

const schema = computed(() => toTypedSchema(yup.object({
    comentario: yup.string().transform((v) => v || '').max(500, 'Máximo 500 caracteres.'),
    signing_pin: yup.string()
        .required('Ingrese su clave de firma.')
        .min(6, 'La clave de firma debe tener al menos 6 caracteres.')
        .max(20, 'La clave de firma no debe superar los 20 caracteres.'),
})));

const { errors, defineField, handleSubmit: validateForm, setFieldError } = useForm({
    validationSchema: schema,
    initialValues: { comentario: '', signing_pin: '' },
});

const [comentario, comentarioProps] = defineField('comentario');
const [signingPin, signingPinProps] = defineField('signing_pin');

const onSubmit = validateForm((values) => emit('submit', values));

defineExpose({ setFieldError });
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>
            <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 flex justify-between items-center bg-gradient-to-r from-indigo-600 to-blue-600">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <CheckCircle class="h-6 w-6" />
                            {{ isAutorizar ? 'Autorizar Salida Vehicular' : 'Confirmar Salida (Conductor)' }}
                        </h3>
                        <p class="text-sm mt-1 text-indigo-100">Firma digital con su certificado RENIEC</p>
                    </div>
                    <button type="button" @click="emit('close')"
                        class="cursor-pointer bg-white/10 rounded-xl p-2 inline-flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-95">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-bold text-slate-900">{{ commission?.solicitante }}</p>
                            <p class="text-xs text-slate-500">
                                Autorización N°
                                <span class="font-mono font-bold text-blue-600">
                                    {{ String(commission?.numero).padStart(3, '0') }}-{{ commission?.anio }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div v-if="isAutorizar">
                        <label class="mb-2 block text-sm font-bold text-slate-700">
                            Comentario <span class="text-xs font-normal text-slate-400">(opcional)</span>
                        </label>
                        <textarea v-model="comentario" v-bind="comentarioProps" rows="3" maxlength="500"
                            class="w-full resize-none rounded-xl border-2 px-4 py-3 text-sm outline-none transition-colors focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500"
                            :class="errors.comentario ? 'border-red-400' : 'border-slate-200'"
                            placeholder="Agregue un comentario..."></textarea>
                        <p v-if="errors.comentario" class="mt-1 text-xs text-red-600">{{ errors.comentario }}</p>
                    </div>

                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <label class="mb-1.5 block text-sm font-bold text-indigo-900">Clave de firma digital <span class="text-red-500">*</span></label>
                        <input v-model="signingPin" v-bind="signingPinProps" type="password" autocomplete="off"
                            class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            :class="errors.signing_pin ? 'border-red-400' : 'border-indigo-200'" placeholder="Clave de su certificado" />
                        <p class="mt-1 text-xs text-indigo-700">La acción continuará únicamente si se genera su firma digital.</p>
                        <p v-if="errors.signing_pin" class="mt-1 text-xs text-red-600">{{ errors.signing_pin }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer rounded-xl border-2 border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50 active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="processing"
                            class="cursor-pointer inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white transition-all shadow-lg disabled:opacity-50 active:scale-95 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 shadow-indigo-600/20">
                            <Loader2 v-if="processing" class="h-4 w-4 animate-spin" />
                            {{ isAutorizar ? 'Confirmar Autorización' : 'Confirmar Salida' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
