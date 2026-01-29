<script setup lang="ts">
import { ref, onMounted, nextTick, computed, onUnmounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import { LogIn, X, Loader2, ScanBarcode, Search, CheckCircle, AlertCircle, ChevronDown, Check } from 'lucide-vue-next';
import Swal from 'sweetalert2';

// Composables
import { useDniConsultation } from '@/Composables/useDniConsultation';
import { useEmployeeSearch, type Employee } from '@/Composables/useEmployeeSearch';

interface Area {
    id: string;
    nombre: string;
}

interface Office {
    id: string;
    nombre: string;
    area?: { nombre: string };
}

interface VisitReason {
    id: string;
    nombre: string;
}

const props = defineProps<{
    areas: Area[];
    offices: Office[];
    employees: Employee[];
    reasons: VisitReason[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

// State
const isSubmitting = ref(false);
const nombreAutocompletado = ref(false);
const dniInputRef = ref<HTMLInputElement | null>(null);
const currentTime = new Date().toTimeString().slice(0, 5);
const destinoTipo = ref<'area' | 'office'>('area');
const camposEditables = ref(true);
const dropdownContainerRef = ref<HTMLElement | null>(null);

// Composables logic
const { isConsultando, consultaMessage, consultaSuccess, consultarDni } = useDniConsultation();
const { searchQuery, showDropdown, filteredEmployees } = useEmployeeSearch(props.employees);

// Form Setup
const entrySchema = toTypedSchema(yup.object({
    dni: yup.string().required('El DNI es obligatorio').matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos numéricos'),
    nombres: yup.string().required('El nombre es obligatorio').min(2, 'Min 2 caracteres'),
    apellidos: yup.string().required('El apellido es obligatorio').min(2, 'Min 2 caracteres'),
    hora_ingreso: yup.string().required('Obligatorio'),
    area_id: yup.string().nullable().test('area-required', 'Debe seleccionar un área', function (val) {
        return destinoTipo.value === 'office' || (!!val && val.length > 0);
    }),
    office_id: yup.string().nullable().test('office-required', 'Debe seleccionar una oficina', function (val) {
        return destinoTipo.value === 'area' || (!!val && val.length > 0);
    }),
    a_quien_visita: yup.string().nullable(),
    employee_id: yup.string().nullable(),
    visit_reason_id: yup.string().required('Debe seleccionar un motivo'),
    motivo: yup.string().nullable(),
}));

const { errors: formErrors, defineField: defineEntryField, handleSubmit: validateEntryForm, resetForm, setFieldValue } = useForm({
    validationSchema: entrySchema,
    initialValues: {
        dni: '',
        nombres: '',
        apellidos: '',
        hora_ingreso: currentTime,
        area_id: '',
        office_id: '',
        a_quien_visita: '',
        employee_id: '',
        visit_reason_id: '',
        motivo: '',
    }
});

const [dni] = defineEntryField('dni');
const [nombres] = defineEntryField('nombres');
const [apellidos] = defineEntryField('apellidos');
const [horaIngreso] = defineEntryField('hora_ingreso');
const [areaId] = defineEntryField('area_id');
const [officeId] = defineEntryField('office_id');
const [aQuienVisita] = defineEntryField('a_quien_visita');
const [employeeId] = defineEntryField('employee_id');
const [visitReasonId] = defineEntryField('visit_reason_id');
const [motivo] = defineEntryField('motivo');

// Logic for employee search interaction
const selectEmployee = (emp: Employee) => {
    setFieldValue('employee_id', String(emp.id));
    setFieldValue('a_quien_visita', emp.nombre_completo);
    searchQuery.value = emp.nombre_completo;
    showDropdown.value = false;
};

const handleAQuienVisitaInput = (e: Event) => {
    const val = (e.target as HTMLInputElement).value;
    setFieldValue('employee_id', null);
    setFieldValue('a_quien_visita', val);
    searchQuery.value = val;
    showDropdown.value = true;
};

const toggleDestino = (tipo: 'area' | 'office') => {
    destinoTipo.value = tipo;
    if (tipo === 'area') setFieldValue('office_id', '');
    else setFieldValue('area_id', '');
};

const handleDniInput = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const cleanValue = target.value.replace(/\D/g, '');
    dni.value = cleanValue;

    if (cleanValue.length === 8) {
        setTimeout(() => {
            if (dni.value && dni.value.length === 8) handleConsultarDni();
        }, 100);
    }
};

const handleConsultarDni = async () => {
    if (!dni.value) return;
    const persona = await consultarDni(dni.value);
    if (persona) {
        setFieldValue('nombres', persona.nombres);

        if (persona.apellido_paterno || persona.apellido_materno) {
            const apellidosStr = `${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
            setFieldValue('apellidos', apellidosStr);
        }

        nombreAutocompletado.value = true;
        camposEditables.value = false;
    }
};

const onSubmit = validateEntryForm(async (values) => {
    isSubmitting.value = true;

    // Explicitly handle office/area mutually exclusive fields
    const payload = { ...values };
    if (destinoTipo.value === 'area') payload.office_id = null as any;
    else payload.area_id = null as any;

    router.post('/visitors', payload as any, {
        onSuccess: (page: any) => {
            resetForm();
            emit('close');
            const newVisitId = page.props.flash?.new_visit_id;
            if (newVisitId) {
                Swal.fire({
                    title: 'Visita Registrada',
                    text: '¿Desea imprimir el ticket?',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, Imprimir',
                    cancelButtonText: 'No',
                    confirmButtonColor: '#9333ea',
                    cancelButtonColor: '#64748b',
                }).then((r) => {
                    if (r.isConfirmed) window.open(`/visitors/${newVisitId}/ticket`, '_blank');
                });
            }
        },
        onFinish: () => isSubmitting.value = false
    });
});

// Click outside to close dropdown
const handleClickOutside = (event: MouseEvent) => {
    if (dropdownContainerRef.value && !dropdownContainerRef.value.contains(event.target as Node)) {
        showDropdown.value = false;
    }
};

onMounted(() => {
    nextTick(() => { dniInputRef.value?.focus(); });
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <LogIn class="w-6 h-6" />
                            Registrar Ingreso de Visita
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">
                            Escanee el DNI o ingrese los datos manualmente
                        </p>
                    </div>
                    <button type="button" @click="$emit('close')"
                        class="bg-white/10 rounded-md p-2 inline-flex items-center justify-center text-white hover:text-white hover:bg-white/20 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white/50 transition-colors">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <!-- DNI Scanner Section -->
                    <div class="bg-gradient-to-br from-purple-50 to-fuchsia-50 rounded-xl p-4 border border-purple-100">
                        <div class="flex items-center gap-2 mb-3">
                            <ScanBarcode class="w-5 h-5 text-purple-600" />
                            <span class="font-semibold text-purple-900">Consulta de DNI con Lector</span>
                        </div>
                        <div class="flex gap-3">
                            <div class="flex-1 relative">
                                <input ref="dniInputRef" type="text" v-model="dni" maxlength="8"
                                    placeholder="Escanee o digite el DNI" @keypress.enter.prevent="handleConsultarDni"
                                    @input="handleDniInput"
                                    class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all text-lg font-mono tracking-wider outline-none"
                                    :class="[
                                        formErrors.dni ? 'border-red-400 bg-red-50' : 'border-purple-200 bg-white',
                                        isConsultando ? 'opacity-50' : ''
                                    ]" :disabled="isConsultando" />
                                <div v-if="isConsultando" class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <Loader2 class="w-5 h-5 animate-spin text-purple-600" />
                                </div>
                            </div>
                            <button type="button" @click="handleConsultarDni"
                                :disabled="!dni || dni.length !== 8 || isConsultando"
                                class="px-5 py-3 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                <Search class="w-5 h-5" />
                                <span class="hidden sm:inline">Buscar</span>
                            </button>
                        </div>
                        <p v-if="formErrors.dni" class="mt-2 text-sm text-red-600">{{ formErrors.dni }}</p>

                        <!-- Consulta Result Message -->
                        <div v-if="consultaMessage" class="mt-3 p-3 rounded-lg flex items-center gap-2"
                            :class="consultaSuccess ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
                            <component :is="consultaSuccess ? CheckCircle : AlertCircle"
                                class="w-5 h-5 flex-shrink-0" />
                            <span class="text-sm">{{ consultaMessage }}</span>
                        </div>
                    </div>

                    <!-- Toggle Edición -->
                    <div class="flex items-center justify-between mt-6 -mb-4 px-1">
                        <span class="text-sm font-bold text-slate-700">Datos Personales</span>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Editar manualmente</span>
                            <button type="button" @click="camposEditables = !camposEditables"
                                class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-purple-600 focus:ring-offset-2"
                                :class="camposEditables ? 'bg-purple-600' : 'bg-slate-200'">
                                <span class="sr-only">Habilitar edición</span>
                                <span aria-hidden="true"
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                    :class="camposEditables ? 'translate-x-5' : 'translate-x-0'" />
                            </button>
                        </div>
                    </div>

                    <!-- Nombres y Apellidos -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres <span class="text-red-500">*</span>
                                <span v-if="nombreAutocompletado"
                                    class="ml-2 text-xs font-normal text-green-600 bg-green-100 px-2 py-0.5 rounded-full">✓
                                    Autocompletado</span>
                            </label>
                            <input type="text" v-model="nombres" placeholder="Nombres"
                                @input="target => nombres = (target.target as HTMLInputElement).value.toUpperCase()"
                                :disabled="!camposEditables"
                                class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors disabled:bg-slate-100 disabled:text-slate-500 outline-none"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="apellidos" placeholder="Apellidos"
                                @input="target => apellidos = (target.target as HTMLInputElement).value.toUpperCase()"
                                :disabled="!camposEditables"
                                class="uppercase w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors disabled:bg-slate-100 disabled:text-slate-500 outline-none"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos
                                }}</p>
                        </div>
                    </div>

                    <!-- Destino (Area/Office) -->
                    <div class="space-y-2 mt-4">
                        <label class="block text-sm font-bold text-slate-700">Destino <span
                                class="text-red-500">*</span></label>

                        <div class="flex gap-2 mb-2">
                            <label
                                class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border rounded-xl transition-all"
                                :class="destinoTipo === 'area' ? 'border-purple-500 bg-purple-50 text-purple-700 shadow-sm' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" value="area" v-model="destinoTipo" @change="toggleDestino('area')"
                                    class="hidden">
                                <span class="text-sm font-bold">Área / Dirección</span>
                            </label>
                            <label
                                class="flex-1 flex items-center justify-center gap-2 cursor-pointer p-2 border rounded-xl transition-all"
                                :class="destinoTipo === 'office' ? 'border-purple-500 bg-purple-50 text-purple-700 shadow-sm' : 'border-slate-200 hover:bg-slate-50 text-slate-600'">
                                <input type="radio" value="office" v-model="destinoTipo"
                                    @change="toggleDestino('office')" class="hidden">
                                <span class="text-sm font-bold">Oficina / Unidad</span>
                            </label>
                        </div>

                        <div v-if="destinoTipo === 'area'">
                            <select v-model="areaId"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white outline-none"
                                :class="{ 'border-red-400': formErrors.area_id }">
                                <option value="" disabled>Seleccione un área...</option>
                                <option v-for="a in areas" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                            </select>
                            <p v-if="formErrors.area_id" class="mt-1 text-sm text-red-600">{{ formErrors.area_id }}
                            </p>
                        </div>

                        <div v-if="destinoTipo === 'office'">
                            <select v-model="officeId"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white outline-none"
                                :class="{ 'border-red-400': formErrors.office_id }">
                                <option value="" disabled>Seleccione una oficina...</option>
                                <option v-for="off in offices" :key="off.id" :value="off.id">
                                    {{ off.nombre }} {{ off.area ? `(${off.area.nombre})` : '' }}
                                </option>
                            </select>
                            <p v-if="formErrors.office_id" class="mt-1 text-sm text-red-600">{{ formErrors.office_id
                                }}</p>
                        </div>
                    </div>

                    <!-- A quien visita & Hora Ingreso -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div class="relative" ref="dropdownContainerRef">
                            <label class="block text-sm font-bold text-slate-700 mb-2">A quién visita</label>
                            <div class="relative">
                                <input type="text" :value="aQuienVisita" @input="handleAQuienVisitaInput"
                                    @focus="showDropdown = true" placeholder="Buscar personal o escribir nombre..."
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors outline-none pr-10" />
                                <ChevronDown
                                    class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            </div>
                            <!-- Dropdown -->
                            <div v-if="showDropdown && filteredEmployees.length > 0"
                                class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto custom-scrollbar">
                                <button type="button" v-for="emp in filteredEmployees" :key="emp.id"
                                    @click="selectEmployee(emp)"
                                    class="w-full text-left px-4 py-2 hover:bg-purple-50 transition-colors flex items-center justify-between group">
                                    <div>
                                        <p class="font-medium text-slate-700 group-hover:text-purple-700 text-sm">
                                            {{ emp.nombre_completo }}</p>
                                        <p class="text-xs text-slate-400">{{ emp.dni }}</p>
                                    </div>
                                    <Check v-if="employeeId === emp.id" class="w-4 h-4 text-purple-600" />
                                </button>
                            </div>
                        </div>

                        <!-- Hora Ingreso -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora de Ingreso <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="horaIngreso"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors outline-none"
                                :class="{ 'border-red-400': formErrors.hora_ingreso }" />
                            <p v-if="formErrors.hora_ingreso" class="mt-1 text-sm text-red-600">{{
                                formErrors.hora_ingreso }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Motivo <span
                                    class="text-red-500">*</span></label>
                            <select v-model="visitReasonId"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors bg-white outline-none"
                                :class="{ 'border-red-400': formErrors.visit_reason_id }">
                                <option value="" disabled>Seleccione un motivo...</option>
                                <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                            </select>
                            <p v-if="formErrors.visit_reason_id"
                                class="mt-1 text-sm text-red-600 font-bold flex items-center gap-1">
                                <AlertCircle class="w-3 h-3" /> {{ formErrors.visit_reason_id }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-sm font-bold text-slate-700 mb-2">Detalles / Nota (Opcional)</label>
                        <textarea v-model="motivo" rows="2" placeholder="Información adicional sobre la visita..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none transition-colors outline-none"
                            :class="{ 'border-red-400': formErrors.motivo }"></textarea>
                        <p v-if="formErrors.motivo" class="mt-1 text-sm text-red-600">{{ formErrors.motivo }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            Registrar Ingreso
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
