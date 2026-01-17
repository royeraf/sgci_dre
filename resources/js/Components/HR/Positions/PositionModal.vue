<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Briefcase class="w-6 h-6" />
                            {{ isEditing ? 'Editar Cargo' : 'Registrar Nuevo Cargo' }}
                        </h3>
                        <p class="text-purple-50 text-sm mt-1">Defina los puestos laborales de la institución</p>
                    </div>
                    <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Nombre del Cargo <span class="text-red-500">*</span>
                        </label>
                        <input v-model="nombre" type="text" placeholder="Ej. Especialista Administrativo"
                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 transition-colors"
                            :class="formErrors.nombre ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="formErrors.nombre" class="mt-1 text-sm text-red-600">{{ formErrors.nombre }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Descripción</label>
                        <textarea v-model="descripcion" rows="3" placeholder="Funciones o requisitos del cargo..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 resize-none"></textarea>
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2">
                        <input type="checkbox" v-model="activo" id="pos-activo"
                            class="w-4 h-4 text-purple-600 border-slate-300 rounded focus:ring-purple-500" />
                        <label for="pos-activo" class="text-sm font-bold text-slate-700 cursor-pointer">Cargo
                            Activo</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Cargo' : 'Guardar Cargo') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { Briefcase, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    position: { type: Object, default: null },
    isEditing: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    nombre: yup.string().required('El nombre del cargo es obligatorio').min(3, 'Debe tener al menos 3 caracteres'),
    descripcion: yup.string().nullable(),
    activo: yup.boolean(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: { nombre: '', descripcion: '', activo: true }
});

const [nombre] = defineField('nombre');
const [descripcion] = defineField('descripcion');
const [activo] = defineField('activo');

watch(() => props.position, (p) => {
    if (p && props.isEditing) {
        setValues({ nombre: p.nombre || '', descripcion: p.descripcion || '', activo: p.activo !== undefined ? p.activo : true });
    } else {
        setValues({ nombre: '', descripcion: '', activo: true });
    }
}, { immediate: true });

const onSubmit = validateForm((values) => emit('submit', values));
</script>
