<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <IdCard class="w-6 h-6" />
                            Licencia de Conducir
                        </h3>
                        <p class="text-cyan-100 text-sm mt-1">{{ driver?.nombre_completo }}</p>
                    </div>
                    <button @click="$emit('close')" class="cursor-pointer text-cyan-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-5">
                    <div class="bg-slate-50 border border-slate-100 rounded-xl px-4 py-3">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Conductor</p>
                        <p class="text-sm font-bold text-slate-800">{{ driver?.nombre_completo }}</p>
                        <p class="text-xs text-slate-500">
                            DNI {{ driver?.dni }} · {{ driver?.cargo }}
                            <span v-if="driver?.encargatura"> (Encargatura: {{ driver.encargatura }})</span>
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Número de licencia -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Número de Licencia <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="numero" v-bind="numeroProps" maxlength="20"
                                class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-cyan-500/20 focus:border-cyan-500 outline-none transition-colors"
                                :class="formErrors.numero ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Ej: Q12345678">
                            <p v-if="formErrors.numero" class="mt-1 text-sm text-red-600">{{ formErrors.numero }}</p>
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <select v-model="categoria" v-bind="categoriaProps"
                                class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-cyan-500/20 focus:border-cyan-500 bg-white outline-none cursor-pointer transition-colors"
                                :class="formErrors.categoria ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione...</option>
                                <option value="A-I">A-I</option>
                                <option value="A-IIa">A-IIa</option>
                                <option value="A-IIb">A-IIb</option>
                                <option value="A-IIIa">A-IIIa</option>
                                <option value="A-IIIb">A-IIIb</option>
                                <option value="A-IIIc">A-IIIc</option>
                            </select>
                            <p v-if="formErrors.categoria" class="mt-1 text-sm text-red-600">{{ formErrors.categoria }}</p>
                        </div>

                        <!-- Fecha de revalidación -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de Revalidación <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="fechaVencimiento" v-bind="fechaVencimientoProps"
                                class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-cyan-500/20 focus:border-cyan-500 outline-none transition-colors"
                                :class="formErrors.fecha_vencimiento ? 'border-red-400' : 'border-slate-200'">
                            <p v-if="formErrors.fecha_vencimiento" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_vencimiento }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-cyan-600 to-blue-600 text-white font-bold rounded-xl hover:from-cyan-700 hover:to-blue-700 shadow-lg shadow-cyan-500/20 active:scale-95 transition-all disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin" />
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Licencia' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, IdCard, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    driver: { type: Object, default: null },
});
const emit = defineEmits(['close', 'saved']);

const isSubmitting = ref(false);

const licenseSchema = toTypedSchema(
    yup.object({
        numero: yup.string().required('El número de licencia es obligatorio').max(20),
        categoria: yup.string().required('Debe seleccionar una categoría'),
        fecha_vencimiento: yup.string().required('La fecha de revalidación es obligatoria'),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: licenseSchema,
    initialValues: {
        numero: '',
        categoria: '',
        fecha_vencimiento: '',
    }
});

const [numero, numeroProps] = defineField('numero');
const [categoria, categoriaProps] = defineField('categoria');
const [fechaVencimiento, fechaVencimientoProps] = defineField('fecha_vencimiento');

watch(() => props.driver, (driver) => {
    setValues({
        numero: driver?.licencia_numero || '',
        categoria: driver?.licencia_categoria || '',
        fecha_vencimiento: driver?.licencia_vencimiento || '',
    });
}, { immediate: true });

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        await axios.put(`/vehicles/drivers/${props.driver.id}/license`, values);
        emit('saved');
    } catch (e) {
        alert('Error al guardar: ' + (e.response?.data?.message || e.message));
    } finally {
        isSubmitting.value = false;
    }
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
