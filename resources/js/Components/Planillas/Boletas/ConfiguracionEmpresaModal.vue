<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Building2 class="w-6 h-6" />
                            Datos de la Empresa
                        </h3>
                        <p class="text-teal-50 text-sm mt-1">Encabezado de las boletas de pago</p>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                RUC <span class="text-red-500">*</span>
                            </label>
                            <input v-model="ruc" type="text" inputmode="numeric" maxlength="11" placeholder="20123456789"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none font-mono"
                                :class="formErrors.ruc ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.ruc" class="mt-1 text-sm text-red-600">{{ formErrors.ruc }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre abreviado
                            </label>
                            <input v-model="nombreAbreviado" type="text" placeholder="DRE Huánuco"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Razón Social <span class="text-red-500">*</span>
                        </label>
                        <input v-model="razonSocial" type="text"
                            placeholder="Dirección Regional de Educación de Huánuco"
                            class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                            :class="formErrors.razon_social ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="formErrors.razon_social" class="mt-1 text-sm text-red-600">
                            {{ formErrors.razon_social }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Dirección <span class="text-red-500">*</span>
                        </label>
                        <textarea v-model="direccion" rows="2" placeholder="Av. La Merced N° 523, Huánuco"
                            class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none resize-none"
                            :class="formErrors.direccion ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <p v-if="formErrors.direccion" class="mt-1 text-sm text-red-600">{{ formErrors.direccion }}</p>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Guardar' }}
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
import { Building2, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    empresa: { type: Object, default: null },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    ruc: yup.string()
        .required('El RUC es obligatorio')
        .matches(/^\d{11}$/, 'El RUC debe tener 11 dígitos'),
    razon_social: yup.string().required('La razón social es obligatoria'),
    nombre_abreviado: yup.string().nullable(),
    direccion: yup.string().required('La dirección es obligatoria'),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: { ruc: '', razon_social: '', nombre_abreviado: '', direccion: '' },
});

const [ruc] = defineField('ruc');
const [razonSocial] = defineField('razon_social');
const [nombreAbreviado] = defineField('nombre_abreviado');
const [direccion] = defineField('direccion');

watch(() => props.empresa, (empresa) => {
    if (!empresa) return;
    setValues({
        ruc: empresa.ruc ?? '',
        razon_social: empresa.razon_social ?? '',
        nombre_abreviado: empresa.nombre_abreviado ?? '',
        direccion: empresa.direccion ?? '',
    });
}, { immediate: true });

const onSubmit = validateForm((values) => {
    emit('submit', {
        ruc: values.ruc,
        razon_social: values.razon_social,
        nombre_abreviado: values.nombre_abreviado || null,
        direccion: values.direccion,
    });
});
</script>
