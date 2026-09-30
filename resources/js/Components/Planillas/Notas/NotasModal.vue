<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <NotebookText class="w-6 h-6" />
                            Notas del empleado
                        </h3>
                        <p class="text-amber-50 text-sm mt-1">
                            {{ empleado?.nombre_completo || '—' }}<span v-if="empleado?.dni"> · {{ empleado.dni }}</span>
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-amber-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[65vh] overflow-y-auto">
                    <div class="flex items-center justify-between">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-slate-500">
                            Anotaciones
                            <span class="ml-1 text-slate-400">({{ notas.length }})</span>
                        </p>
                        <button v-if="mode === 'idle'" @click="startCreate"
                            class="cursor-pointer inline-flex items-center text-xs font-bold text-amber-600 hover:text-amber-700">
                            <Plus class="w-4 h-4 mr-1" />
                            Agregar nota
                        </button>
                    </div>

                    <div v-if="loading" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                        <Loader2 class="w-6 h-6 mx-auto text-slate-300 animate-spin" />
                    </div>

                    <div v-else-if="!notas.length && mode === 'idle'"
                        class="rounded-xl border border-dashed border-slate-200 bg-slate-50 py-10 text-center">
                        <NotebookText class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                        <p class="text-sm font-medium text-slate-500">Sin anotaciones para este empleado</p>
                        <p class="text-xs text-slate-400 mt-1">Use las notas para dejar detalles de cara a la próxima planilla</p>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="nota in notas" :key="nota.id"
                            class="rounded-xl border p-4 transition-colors"
                            :class="editingId === nota.id ? 'border-amber-300 bg-amber-50/50' : 'border-slate-200 bg-white'">
                            <p class="text-sm text-slate-700 whitespace-pre-line">{{ nota.texto }}</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <p class="text-[11px] text-slate-400">
                                    {{ formatDate(nota.fecha) }} {{ nota.hora }}
                                    <template v-if="nota.autor"> · {{ nota.autor }}</template>
                                    <span v-if="nota.editado" class="text-amber-600"> · editado</span>
                                </p>
                                <div v-if="editingId !== nota.id" class="flex items-center gap-1 shrink-0">
                                    <button @click="startEdit(nota)" title="Editar"
                                        class="cursor-pointer p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="$emit('remove', nota)" title="Eliminar"
                                        class="cursor-pointer p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form v-if="mode !== 'idle'" @submit.prevent="onSubmit"
                        class="rounded-xl border-2 border-amber-200 bg-amber-50/40 p-4 space-y-3">
                        <label class="block text-sm font-bold text-slate-700">
                            {{ editingId ? 'Editar anotación' : 'Nueva anotación' }}
                            <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="texto" rows="4" maxlength="1000"
                            placeholder="Ej. Pendiente actualizar la remuneración básica desde el próximo mes..."
                            class="w-full px-4 py-2.5 border-2 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 outline-none resize-y"
                            :class="formErrors.texto ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <p v-if="formErrors.texto" class="text-sm text-red-600">{{ formErrors.texto }}</p>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-xs text-slate-400 tabular-nums">{{ texto.length }}/1000</span>
                            <div class="flex gap-2">
                                <button type="button" @click="cancelEdit"
                                    class="cursor-pointer px-4 py-2 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 text-sm font-bold">
                                    Cancelar
                                </button>
                                <button type="submit" :disabled="saving"
                                    class="cursor-pointer px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-xl text-sm font-bold disabled:opacity-50 flex items-center gap-2">
                                    <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                                    {{ saving ? 'Guardando...' : 'Guardar' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { NotebookText, X, Plus, Pencil, Trash2, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    empleado: { type: Object, default: null },
    notas: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit', 'remove']);

const mode = ref('idle');
const editingId = ref(null);

const schema = toTypedSchema(yup.object({
    texto: yup.string().trim().required('Ingrese el texto de la nota').max(1000, 'Máximo 1000 caracteres'),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: schema,
    initialValues: { texto: '' },
});

const [texto] = defineField('texto');

const startCreate = () => {
    resetForm();
    editingId.value = null;
    mode.value = 'create';
};

const startEdit = (nota) => {
    resetForm();
    setValues({ texto: nota.texto });
    editingId.value = nota.id;
    mode.value = 'edit';
};

const cancelEdit = () => {
    resetForm();
    editingId.value = null;
    mode.value = 'idle';
};

const onSubmit = validateForm((values) => {
    emit('submit', { texto: values.texto.trim(), id: editingId.value });
});

const formatDate = (value) => {
    if (!value) return '—';
    const [y, m, d] = value.split('-');
    return `${d}/${m}/${y}`;
};

defineExpose({ cancelEdit });
</script>
