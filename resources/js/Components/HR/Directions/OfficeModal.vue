<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Building2 class="w-6 h-6" />
                            {{ isEditing ? 'Editar Oficina' : 'Registrar Nueva Oficina' }}
                        </h3>
                        <p class="text-sky-50 text-sm mt-1">Configure las unidades orgánicas dependientes</p>
                    </div>
                    <button @click="$emit('close')" class="text-sky-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Nombre de la Oficina <span class="text-red-500">*</span>
                        </label>
                        <input v-model="nombre" v-bind="nombreProps" type="text" placeholder="Ej. UNIDAD DE TESORERÍA"
                            @input="nombre = $event.target.value.toUpperCase()"
                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-sky-500 transition-colors"
                            :class="formErrors.nombre ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="formErrors.nombre" class="mt-1 text-sm text-red-600">{{ formErrors.nombre }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Código -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Código Interno</label>
                            <input v-model="codigo" v-bind="codigoProps" type="text" placeholder="Ej. TES-01"
                                @input="codigo = $event.target.value.toUpperCase()"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 transition-colors"
                                :class="formErrors.codigo ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                            <p v-if="formErrors.codigo" class="mt-1 text-sm text-red-600">{{ formErrors.codigo }}</p>
                        </div>
                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono / Anexo</label>
                            <input v-model="telefono_interno" v-bind="telefonoProps" type="text"
                                placeholder="Ej. Anexo 205"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 transition-colors"
                                :class="formErrors.telefono_interno ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                            <p v-if="formErrors.telefono_interno" class="mt-1 text-sm text-red-600">{{
                                formErrors.telefono_interno }}</p>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Ubicación Física</label>
                        <div class="relative">
                            <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="ubicacion" v-bind="ubicacionProps" type="text"
                                placeholder="Ej. 2do Piso, Pabellón Administrativo"
                                class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-sky-500 transition-colors"
                                :class="formErrors.ubicacion ? 'border-red-400 bg-red-50' : 'border-slate-200'" />
                        </div>
                        <p v-if="formErrors.ubicacion" class="mt-1 text-sm text-red-600">{{ formErrors.ubicacion }}</p>
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2 pt-2">
                        <input type="checkbox" v-model="activo" v-bind="activoProps" id="office-activo"
                            class="w-4 h-4 text-sky-600 border-slate-300 rounded focus:ring-sky-500" />
                        <label for="office-activo" class="text-sm font-bold text-slate-700 cursor-pointer">Oficina
                            Activa</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">Cancelar</button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-sky-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-sky-600/20">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Oficina' : 'Guardar Oficina') }}
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
import { Building2, X, Loader2, MapPin } from 'lucide-vue-next';

const props = defineProps({
    office: { type: Object, default: null },
    isEditing: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    nombre: yup.string().required('El nombre es obligatorio').min(3, 'Mínimo 3 caracteres'),
    codigo: yup.string().transform((value) => value || null).nullable(),
    ubicacion: yup.string().transform((value) => value || null).nullable(),
    telefono_interno: yup.string().transform((value) => value || null).nullable(),
    activo: yup.boolean(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: schema,
    initialValues: {
        nombre: '',
        codigo: '',
        ubicacion: '',
        telefono_interno: '',
        activo: true
    }
});

const [nombre, nombreProps] = defineField('nombre');
const [codigo, codigoProps] = defineField('codigo');
const [ubicacion, ubicacionProps] = defineField('ubicacion');
const [telefono_interno, telefonoProps] = defineField('telefono_interno');
const [activo, activoProps] = defineField('activo');

watch(() => props.office, (o) => {
    if (o && props.isEditing) {
        setValues({
            nombre: o.nombre || '',
            codigo: o.codigo || '',
            ubicacion: o.ubicacion || '',
            telefono_interno: o.telefono_interno || '',
            activo: o.activo !== undefined ? Boolean(o.activo) : true
        });
    } else {
        resetForm({
            values: {
                nombre: '',
                codigo: '',
                ubicacion: '',
                telefono_interno: '',
                activo: true
            }
        });
    }
}, { immediate: true });

const onSubmit = validateForm((values) => emit('submit', values));
</script>
