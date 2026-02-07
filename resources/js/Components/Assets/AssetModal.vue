<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-4xl w-full z-10 overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-slate-700 to-gray-700 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Plus class="w-6 h-6" />
                            Registrar Nuevo Bien
                        </h3>
                        <p class="text-slate-100 text-sm mt-1">Ingrese los detalles del activo patrimonial</p>
                    </div>
                    <button @click="$emit('close')" class="text-slate-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <div class="overflow-y-auto p-6">
                    <form @submit.prevent="handleSubmit" class="space-y-8">

                        <!-- Sección 1: Identificación -->
                        <div>
                            <h4
                                class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-8 h-[1px] bg-slate-200"></span> Identificación
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Código Patrimonial <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" v-model="codigo_patrimonio" v-bind="codigoPatrimonioProps"
                                        @input="codigo_patrimonio = $event.target.value.replace(/\D/g, '')"
                                        placeholder="Ej. 74080500" maxlength="8"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.codigo_patrimonio ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.codigo_patrimonio" class="mt-1 text-sm text-red-600">{{
                                        formErrors.codigo_patrimonio }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Código Interno <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" v-model="codigo_interno" v-bind="codigoInternoProps"
                                        @input="codigo_interno = $event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                                        placeholder="Ej. A001" maxlength="4"
                                        class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.codigo_interno ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.codigo_interno" class="mt-1 text-sm text-red-600">{{
                                        formErrors.codigo_interno }}</p>

                                    <div v-if="codeExists"
                                        class="mt-2 p-3 bg-red-50 border border-red-200 rounded-xl flex items-start gap-2 text-red-700 animate-pulse">
                                        <AlertCircle class="w-5 h-5 shrink-0 mt-0.5" />
                                        <div class="text-xs font-bold">
                                            ¡Código Duplicado! El código {{ codigo_patrimonio }}{{ codigo_interno }} ya
                                            está registrado en el sistema.
                                        </div>
                                    </div>

                                    <div v-else-if="isCheckingCode"
                                        class="mt-2 flex items-center gap-2 text-slate-400 text-xs font-medium">
                                        <Loader2 class="w-3 h-3 animate-spin" />
                                        Verificando disponibilidad...
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        Estado <span class="text-red-500">*</span>
                                    </label>
                                    <select v-model="estado" v-bind="estadoProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :class="formErrors.estado ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting">
                                        <option value="BUENO">BUENO</option>
                                        <option value="REGULAR">REGULAR</option>
                                        <option value="MALO">MALO</option>
                                        <option value="BAJA">BAJA</option>
                                    </select>
                                    <p v-if="formErrors.estado" class="mt-1 text-sm text-red-600">{{ formErrors.estado
                                    }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 2: Detalles del Bien -->
                        <div>
                            <h4
                                class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-8 h-[1px] bg-slate-200"></span> Detalles del Activo
                            </h4>

                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Denominación <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="denominacion" v-bind="denominacionProps"
                                    placeholder="Nombre del bien (Ej. Laptop Dell Latitude 5420)"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                    :class="formErrors.denominacion ? 'border-red-400' : 'border-slate-200'"
                                    :disabled="isSubmitting" />
                                <p v-if="formErrors.denominacion" class="mt-1 text-sm text-red-600">{{
                                    formErrors.denominacion }}</p>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Descripción
                                </label>
                                <textarea v-model="descripcion" v-bind="descripcionProps" rows="2"
                                    placeholder="Descripción detallada o características adicionales (opcional)..."
                                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 resize-none transition-colors outline-none"
                                    :class="formErrors.descripcion ? 'border-red-400' : 'border-slate-200'"
                                    :disabled="isSubmitting"></textarea>
                                <p v-if="formErrors.descripcion" class="mt-1 text-sm text-red-600">{{
                                    formErrors.descripcion }}</p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Categoría</label>
                                    <select v-model="categoria_id" v-bind="categoriaIdProps"
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :disabled="isSubmitting">
                                        <option value="">Sin Categoría</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.nombre }}
                                        </option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Marca</label>
                                    <input type="text" v-model="marca" v-bind="marcaProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.marca ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.marca" class="mt-1 text-sm text-red-600">{{ formErrors.marca }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Modelo</label>
                                    <input type="text" v-model="modelo" v-bind="modeloProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.modelo ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.modelo" class="mt-1 text-sm text-red-600">{{ formErrors.modelo
                                        }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Nro. Serie</label>
                                    <input type="text" v-model="numero_serie" v-bind="numeroSerieProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.numero_serie ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.numero_serie" class="mt-1 text-sm text-red-600">{{
                                        formErrors.numero_serie }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Color</label>
                                    <input type="text" v-model="color" v-bind="colorProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.color ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.color" class="mt-1 text-sm text-red-600">{{ formErrors.color }}
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Dimensiones</label>
                                    <input type="text" v-model="dimension" v-bind="dimensionProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none"
                                        :class="formErrors.dimension ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.dimension" class="mt-1 text-sm text-red-600">{{
                                        formErrors.dimension }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Sección 3: Asignación Inicial -->
                        <div class="bg-slate-50 p-6 rounded-xl border border-slate-200">
                            <h4
                                class="text-sm font-bold text-slate-500 uppercase tracking-widest mb-4 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-600"></span> Asignación Inicial (Movimiento)
                            </h4>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Ubicación /
                                        Oficina</label>
                                    <input type="text" v-model="ubicacion" v-bind="ubicacionProps"
                                        placeholder="Ej. Oficina de Administración"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white outline-none"
                                        :class="formErrors.ubicacion ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.ubicacion" class="mt-1 text-sm text-red-600">{{
                                        formErrors.ubicacion }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Responsable
                                        Inicial</label>
                                    <input type="text" v-model="responsable_nombre" v-bind="responsableNombreProps"
                                        placeholder="Nombre del responsable (si aplica)"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white outline-none"
                                        :class="formErrors.responsable_nombre ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.responsable_nombre" class="mt-1 text-sm text-red-600">{{
                                        formErrors.responsable_nombre }}</p>
                                    <!-- Here we could add a proper select for existing employees/responsibles -->
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de
                                        Asignación</label>
                                    <input type="date" v-model="fecha_asignacion" v-bind="fechaAsignacionProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors bg-white outline-none"
                                        :class="formErrors.fecha_asignacion ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting" />
                                    <p v-if="formErrors.fecha_asignacion" class="mt-1 text-sm text-red-600">{{
                                        formErrors.fecha_asignacion }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                            <button type="button" @click="$emit('close')"
                                class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                                Cancelar
                            </button>
                            <button type="submit" :disabled="isSubmitting"
                                class="px-6 py-2.5 bg-gradient-to-r from-slate-700 to-gray-700 text-white font-bold rounded-xl hover:from-slate-800 hover:to-gray-800 transition-all disabled:opacity-50 shadow-lg shadow-slate-600/20">
                                <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                                {{ isSubmitting ? 'Guardando...' : 'Guardar Activo' }}
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { Plus, X, Loader2, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

let debounceTimer = null;
const debounce = (callback, delay) => {
    return (...args) => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => callback(...args), delay);
    };
};

const props = defineProps({
    categories: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'success']);

const isSubmitting = ref(false);
const today = new Date().toISOString().split('T')[0];

const assetSchema = toTypedSchema(
    yup.object({
        codigo_patrimonio: yup.string()
            .required('El código patrimonial es obligatorio')
            .matches(/^\d{8}$/, 'El código patrimonial debe tener exactamente 8 dígitos'),
        codigo_interno: yup.string()
            .required('El código interno es obligatorio')
            .matches(/^[A-Z0-9]{4}$/i, 'El código interno debe tener exactamente 4 caracteres (letras o números)'),
        estado: yup.string()
            .required('El estado es obligatorio')
            .oneOf(['BUENO', 'REGULAR', 'MALO', 'BAJA'], 'Estado no válido'),
        denominacion: yup.string()
            .required('La denominación es obligatoria')
            .min(3, 'La denominación debe tener al menos 3 caracteres')
            .max(200, 'La denominación no puede exceder 200 caracteres'),
        descripcion: yup.string()
            .nullable()
            .max(500, 'La descripción no puede exceder 500 caracteres'),
        categoria_id: yup.number().nullable(),
        marca: yup.string()
            .nullable()
            .max(100, 'La marca no puede exceder 100 caracteres'),
        modelo: yup.string()
            .nullable()
            .max(100, 'El modelo no puede exceder 100 caracteres'),
        numero_serie: yup.string()
            .nullable()
            .max(100, 'El número de serie no puede exceder 100 caracteres'),
        color: yup.string()
            .nullable()
            .max(50, 'El color no puede exceder 50 caracteres'),
        dimension: yup.string()
            .nullable()
            .max(100, 'Las dimensiones no pueden exceder 100 caracteres'),
        ubicacion: yup.string()
            .nullable()
            .max(200, 'La ubicación no puede exceder 200 caracteres'),
        responsable_nombre: yup.string()
            .nullable()
            .min(3, 'El nombre del responsable debe tener al menos 3 caracteres')
            .max(200, 'El nombre del responsable no puede exceder 200 caracteres'),
        fecha_asignacion: yup.string()
            .nullable()
            .test('valid-date', 'Fecha no válida', function (value) {
                if (!value) return true; // nullable
                const date = new Date(value);
                return !isNaN(date.getTime());
            })
            .test('not-future', 'La fecha no puede ser futura', function (value) {
                if (!value) return true;
                const inputDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return inputDate <= today;
            }),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm } = useForm({
    validationSchema: assetSchema,
    initialValues: {
        codigo_patrimonio: '',
        codigo_interno: '',
        estado: 'BUENO',
        denominacion: '',
        descripcion: '',
        categoria_id: '',
        marca: '',
        modelo: '',
        numero_serie: '',
        color: '',
        dimension: '',
        ubicacion: '',
        responsable_nombre: '',
        fecha_asignacion: today,
    }
});

const [codigo_patrimonio, codigoPatrimonioProps] = defineField('codigo_patrimonio');
const [codigo_interno, codigoInternoProps] = defineField('codigo_interno');
const [estado, estadoProps] = defineField('estado');
const [denominacion, denominacionProps] = defineField('denominacion');
const [descripcion, descripcionProps] = defineField('descripcion');
const [categoria_id, categoriaIdProps] = defineField('categoria_id');
const [marca, marcaProps] = defineField('marca');
const [modelo, modeloProps] = defineField('modelo');
const [numero_serie, numeroSerieProps] = defineField('numero_serie');
const [color, colorProps] = defineField('color');
const [dimension, dimensionProps] = defineField('dimension');
const [ubicacion, ubicacionProps] = defineField('ubicacion');
const [responsable_nombre, responsableNombreProps] = defineField('responsable_nombre');
const [fecha_asignacion, fechaAsignacionProps] = defineField('fecha_asignacion');

const codeExists = ref(false);
const isCheckingCode = ref(false);

const checkCodeAvailability = debounce(async () => {
    const pCode = codigo_patrimonio.value;
    const iCode = codigo_interno.value;

    if (pCode && iCode && pCode.length === 8 && iCode.length === 4) {
        isCheckingCode.value = true;
        try {
            const fullCode = pCode + iCode;
            const response = await axios.get(`/assets/check-code?code=${fullCode}`);
            codeExists.value = response.data.exists;

            if (codeExists.value) {
                // We can manually set an error in vee-validate if we want, 
                // but showing a specific message is also good.
            }
        } catch (error) {
            console.error('Error checking code:', error);
        } finally {
            isCheckingCode.value = false;
        }
    } else {
        codeExists.value = false;
    }
}, 500);

watch([codigo_patrimonio, codigo_interno], () => {
    checkCodeAvailability();
});

onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
});

const onSubmitForm = validateForm(async (values) => {
    if (codeExists.value) return;

    isSubmitting.value = true;

    // Clean up empty values
    const payload = { ...values };
    if (!payload.categoria_id) delete payload.categoria_id;

    router.post('/assets', payload, {
        onSuccess: () => {
            resetForm();
            emit('success');
            emit('close');
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
