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
                                    <select v-model="estado_id" v-bind="estadoIdProps"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :class="formErrors.estado_id ? 'border-red-400' : 'border-slate-200'"
                                        :disabled="isSubmitting">
                                        <option value="">Seleccione estado</option>
                                        <option v-for="state in states" :key="state.id" :value="state.id">
                                            {{ state.nombre }}
                                        </option>
                                    </select>
                                    <p v-if="formErrors.estado_id" class="mt-1 text-sm text-red-600">{{
                                        formErrors.estado_id }}</p>
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
                                    <select v-model="marca_id" v-bind="marcaIdProps"
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :disabled="isSubmitting">
                                        <option value="">Sin Marca</option>
                                        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{
                                            brand.nombre }}
                                        </option>
                                    </select>
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
                                    <select v-model="color_id" v-bind="colorIdProps"
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :disabled="isSubmitting">
                                        <option value="">Sin Color</option>
                                        <option v-for="color in colors" :key="color.id" :value="color.id">{{
                                            color.nombre }}
                                        </option>
                                    </select>
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

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Origen</label>
                                    <select v-model="origen_id" v-bind="origenIdProps"
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                        :disabled="isSubmitting">
                                        <option value="">Sin especificar</option>
                                        <option v-for="origin in origins" :key="origin.id" :value="origin.id">{{
                                            origin.nombre }}
                                        </option>
                                    </select>
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
                                <!-- Ubicación: Área / Oficina toggle -->
                                <div class="md:col-span-2 space-y-2">
                                    <label class="block text-sm font-bold text-slate-700">Ubicación</label>
                                    <div class="flex gap-2 mb-2">
                                        <label
                                            class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border rounded-xl transition-all"
                                            :class="ubicacionTipo === 'area' ? 'border-slate-500 bg-slate-50 text-slate-700 shadow-sm' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                            <input type="radio" value="area" v-model="ubicacionTipo"
                                                @change="toggleUbicacion('area')" class="hidden">
                                            <span class="text-sm font-bold">Área / Dirección</span>
                                        </label>
                                        <label
                                            class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border rounded-xl transition-all"
                                            :class="ubicacionTipo === 'office' ? 'border-slate-500 bg-slate-50 text-slate-700 shadow-sm' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                            <input type="radio" value="office" v-model="ubicacionTipo"
                                                @change="toggleUbicacion('office')" class="hidden">
                                            <span class="text-sm font-bold">Oficina / Unidad</span>
                                        </label>
                                    </div>

                                    <div v-if="ubicacionTipo === 'area'">
                                        <select v-model="area_id" v-bind="areaIdProps"
                                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                            :disabled="isSubmitting">
                                            <option value="">Seleccione un área...</option>
                                            <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                                        </select>
                                    </div>

                                    <div v-if="ubicacionTipo === 'office'">
                                        <select v-model="oficina_id" v-bind="oficinaIdProps"
                                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 bg-white transition-colors outline-none"
                                            :disabled="isSubmitting">
                                            <option value="">Seleccione una oficina...</option>
                                            <option v-for="office in offices" :key="office.id" :value="office.id">
                                                {{ office.nombre }} {{ office.area ? `(${office.area.nombre})` : '' }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <div class="relative" ref="dropdownContainerRef">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Responsable
                                        Inicial</label>
                                    <div class="relative">
                                        <input type="text" :value="searchQuery"
                                            @input="handleResponsableInput"
                                            @focus="showDropdown = true"
                                            placeholder="Buscar personal por nombre o DNI..."
                                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-slate-500 focus:border-slate-500 transition-colors outline-none pr-10"
                                            :disabled="isSubmitting" />
                                        <ChevronDown
                                            class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                    </div>
                                    <!-- Dropdown -->
                                    <div v-if="showDropdown && filteredEmployees.length > 0"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
                                        <button type="button" v-for="emp in filteredEmployees" :key="emp.id"
                                            @click="selectEmployee(emp)"
                                            class="w-full text-left px-4 py-2 hover:bg-slate-50 transition-colors flex items-center justify-between group">
                                            <div>
                                                <p class="font-medium text-slate-700 group-hover:text-slate-900 text-sm">
                                                    {{ emp.nombre_completo }}</p>
                                                <p class="text-xs text-slate-400">{{ emp.dni }}</p>
                                            </div>
                                            <Check v-if="employee_id === String(emp.id)" class="w-4 h-4 text-slate-600" />
                                        </button>
                                    </div>
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
                            <button type="submit" :disabled="isSubmitting || codeExists"
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
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { Plus, X, Loader2, AlertCircle, ChevronDown, Check } from 'lucide-vue-next';
import axios from 'axios';
import { useEmployeeSearch } from '@/Composables/useEmployeeSearch';

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
    },
    brands: {
        type: Array,
        default: () => []
    },
    colors: {
        type: Array,
        default: () => []
    },
    states: {
        type: Array,
        default: () => []
    },
    origins: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    offices: {
        type: Array,
        default: () => []
    },
    employees: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'success']);

const isSubmitting = ref(false);
const today = new Date().toISOString().split('T')[0];

// Encontrar el estado "BUENO" por defecto
const defaultStateId = props.states.find(s => s.nombre === 'BUENO')?.id || '';

const assetSchema = toTypedSchema(
    yup.object({
        codigo_patrimonio: yup.string()
            .required('El código patrimonial es obligatorio')
            .matches(/^\d{8}$/, 'El código patrimonial debe tener exactamente 8 dígitos'),
        codigo_interno: yup.string()
            .required('El código interno es obligatorio')
            .matches(/^[A-Z0-9]{4}$/i, 'El código interno debe tener exactamente 4 caracteres (letras o números)'),
        estado_id: yup.number()
            .transform((value, original) => (original === '' ? undefined : value))
            .required('El estado es obligatorio'),
        denominacion: yup.string()
            .required('La denominación es obligatoria')
            .min(3, 'La denominación debe tener al menos 3 caracteres')
            .max(200, 'La denominación no puede exceder 200 caracteres'),
        descripcion: yup.string()
            .nullable()
            .max(500, 'La descripción no puede exceder 500 caracteres'),
        categoria_id: yup.number().nullable().transform((value, original) => (original === '' ? null : value)),
        marca_id: yup.number().nullable().transform((value, original) => (original === '' ? null : value)),
        color_id: yup.number().nullable().transform((value, original) => (original === '' ? null : value)),
        origen_id: yup.number().nullable().transform((value, original) => (original === '' ? null : value)),
        modelo: yup.string()
            .nullable()
            .max(100, 'El modelo no puede exceder 100 caracteres'),
        numero_serie: yup.string()
            .nullable()
            .max(100, 'El número de serie no puede exceder 100 caracteres'),
        dimension: yup.string()
            .nullable()
            .max(100, 'Las dimensiones no pueden exceder 100 caracteres'),
        area_id: yup.string().nullable(),
        oficina_id: yup.string().nullable(),
        employee_id: yup.string().nullable(),
        fecha_asignacion: yup.string()
            .nullable()
            .test('valid-date', 'Fecha no válida', function (value) {
                if (!value) return true;
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

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm, setFieldValue } = useForm({
    validationSchema: assetSchema,
    initialValues: {
        codigo_patrimonio: '',
        codigo_interno: '',
        estado_id: defaultStateId,
        denominacion: '',
        descripcion: '',
        categoria_id: '',
        marca_id: '',
        color_id: '',
        origen_id: '',
        modelo: '',
        numero_serie: '',
        dimension: '',
        area_id: '',
        oficina_id: '',
        employee_id: '',
        fecha_asignacion: today,
    }
});

const [codigo_patrimonio, codigoPatrimonioProps] = defineField('codigo_patrimonio');
const [codigo_interno, codigoInternoProps] = defineField('codigo_interno');
const [estado_id, estadoIdProps] = defineField('estado_id');
const [denominacion, denominacionProps] = defineField('denominacion');
const [descripcion, descripcionProps] = defineField('descripcion');
const [categoria_id, categoriaIdProps] = defineField('categoria_id');
const [marca_id, marcaIdProps] = defineField('marca_id');
const [color_id, colorIdProps] = defineField('color_id');
const [origen_id, origenIdProps] = defineField('origen_id');
const [modelo, modeloProps] = defineField('modelo');
const [numero_serie, numeroSerieProps] = defineField('numero_serie');
const [dimension, dimensionProps] = defineField('dimension');
const [area_id, areaIdProps] = defineField('area_id');
const [oficina_id, oficinaIdProps] = defineField('oficina_id');
const [employee_id, employeeIdProps] = defineField('employee_id');
const [fecha_asignacion, fechaAsignacionProps] = defineField('fecha_asignacion');

const codeExists = ref(false);
const isCheckingCode = ref(false);

// Location toggle (area vs office)
const ubicacionTipo = ref('area');

const toggleUbicacion = (tipo) => {
    ubicacionTipo.value = tipo;
    if (tipo === 'area') {
        setFieldValue('oficina_id', '');
    } else {
        setFieldValue('area_id', '');
    }
};

// Employee search (same pattern as ExternalVisit)
const { searchQuery, showDropdown, filteredEmployees } = useEmployeeSearch(props.employees);
const dropdownContainerRef = ref(null);

const selectEmployee = (emp) => {
    setFieldValue('employee_id', String(emp.id));
    searchQuery.value = emp.nombre_completo;
    showDropdown.value = false;
};

const handleResponsableInput = (e) => {
    const val = e.target.value;
    setFieldValue('employee_id', '');
    searchQuery.value = val;
    showDropdown.value = true;
};

const handleClickOutside = (event) => {
    if (dropdownContainerRef.value && !dropdownContainerRef.value.contains(event.target)) {
        showDropdown.value = false;
    }
};

const checkCodeAvailability = debounce(async () => {
    const pCode = codigo_patrimonio.value;
    const iCode = codigo_interno.value;

    if (pCode && iCode && pCode.length === 8 && iCode.length === 4) {
        isCheckingCode.value = true;
        try {
            const fullCode = pCode + iCode;
            const response = await axios.get(`/assets/check-code?code=${fullCode}`);
            codeExists.value = response.data.exists;
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

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    if (debounceTimer) clearTimeout(debounceTimer);
    document.removeEventListener('click', handleClickOutside);
});

const onSubmitForm = validateForm(async (values) => {
    if (codeExists.value) return;

    isSubmitting.value = true;

    // Clean up empty values
    const payload = { ...values };
    Object.keys(payload).forEach(key => {
        if (payload[key] === '' || payload[key] === null) {
            delete payload[key];
        }
    });

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
