<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="entry ? LogIn : Plus" class="w-6 h-6" />
                            {{ entry ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </h3>
                        <p class="text-green-100 text-sm mt-1">
                            {{ entry ? `Registrar hora de retorno` : 'Complete los datos de salida' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-green-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Return mode: just show entry info and ask for return time -->
                    <template v-if="entry">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                                    {{ (entry.personal || '?').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-lg">{{ entry.personal }}</p>
                                    <p class="text-sm text-slate-600">DNI: {{ entry.dni }}</p>
                                    <p class="text-sm text-slate-500">Salió a las {{ entry.hora_salida }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                                Hora de Retorno
                                <span class="flex h-2 w-2 relative">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span class="text-[10px] text-emerald-600 font-medium uppercase tracking-tight">Automático</span>
                            </label>
                            <div class="relative">
                                <input type="time" :value="horaRetorno" disabled
                                    class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 font-bold outline-none cursor-not-allowed" />
                                <div class="absolute inset-0 z-10" title="La hora se asigna automáticamente"></div>
                            </div>
                        </div>
                    </template>

                    <!-- New entry mode -->
                    <template v-else>
                        <!-- Barcode Scanner + Employee Search -->
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-100">
                            <div class="flex items-center gap-2 mb-3">
                                <ScanBarcode class="w-5 h-5 text-green-600" />
                                <span class="font-semibold text-green-900">Buscar Personal (DNI o Nombre)</span>
                            </div>

                            <!-- Selected Person Card -->
                            <div v-if="selectedPersonDisplay" class="bg-white rounded-xl p-4 border border-green-200 shadow-sm">
                                <div class="flex items-center gap-4">
                                    <div class="h-14 w-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                                        {{ selectedPersonDisplay.nombre.charAt(0) }}
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-bold text-slate-900 text-lg">{{ selectedPersonDisplay.nombre }}</p>
                                        <div class="flex items-center gap-3 mt-0.5">
                                            <p class="text-sm text-slate-600">DNI: {{ selectedPersonDisplay.dni }}</p>
                                            <span v-if="selectedPersonDisplay.regimen"
                                                class="px-2 py-0.5 text-xs font-bold rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                                                {{ selectedPersonDisplay.regimen }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-0.5">{{ selectedPersonDisplay.cargo }} - {{ selectedPersonDisplay.area }}</p>
                                    </div>
                                    <button type="button" @click="clearSelection" class="text-slate-400 hover:text-red-500 transition-colors p-1">
                                        <X class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <!-- Search Input (hidden when person is selected) -->
                            <template v-else>
                                <div class="relative" ref="dropdownContainerRef">
                                    <input ref="searchInputRef" v-model="searchQuery" @focus="showDropdown = true"
                                        @keypress.enter.prevent
                                        type="text" placeholder="Escanee el DNI o busque por nombre..."
                                        class="w-full px-4 py-3 pl-10 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all outline-none"
                                        :class="
                                            scanStatus === 'success' ? 'border-green-400 bg-green-50' :
                                            scanStatus === 'error' ? 'border-red-400 bg-red-50' :
                                            'border-green-200 bg-white'
                                        " />
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2">
                                        <Loader2 v-if="isSearching" class="w-4 h-4 text-green-600 animate-spin" />
                                        <Search v-else class="w-4 h-4 text-slate-400" />
                                    </div>

                                    <!-- Dropdown with filtered results -->
                                    <div v-if="showDropdown"
                                        class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                        <div v-if="isSearching"
                                            class="px-4 py-3 text-center text-slate-500 text-sm flex items-center justify-center gap-2">
                                            <Loader2 class="w-4 h-4 animate-spin" /> Buscando...
                                        </div>
                                        <div v-else-if="searchResults.length === 0 && searchQuery.length >= 3"
                                            class="px-4 py-3 text-center text-slate-500 text-sm">
                                            No se encontraron resultados.
                                        </div>
                                        <div v-else-if="searchResults.length === 0"
                                            class="px-4 py-3 text-center text-slate-400 text-sm">
                                            Escriba para buscar...
                                        </div>

                                        <button v-for="person in searchResults" :key="person.id" type="button"
                                            @click="selectPerson(person)"
                                            class="w-full px-4 py-3 text-left hover:bg-green-50 transition-colors border-b border-slate-100 last:border-b-0">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <p class="font-semibold text-slate-900">{{ person.nombre }}</p>
                                                    <p class="text-sm text-slate-600">DNI: {{ person.dni }}</p>
                                                    <p class="text-xs text-slate-500">{{ person.cargo }} - {{ person.area }}</p>
                                                </div>
                                                <span class="text-xs px-2 py-1 rounded-full bg-slate-100 text-slate-600">
                                                    {{ person.regimen }}
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Scan status message -->
                            <div v-if="scanMessage" class="mt-2 flex items-center gap-2 p-2 rounded-lg text-xs"
                                :class="scanStatus === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                <component :is="scanStatus === 'success' ? CheckCircle : AlertCircle" class="w-4 h-4 shrink-0" />
                                <span class="font-medium">{{ scanMessage }}</span>
                            </div>

                            <!-- Validation error for employee_id -->
                            <p v-if="formErrors.employee_id" class="mt-2 text-sm text-red-600 font-medium">
                                {{ formErrors.employee_id }}
                            </p>

                            <p v-if="!selectedPersonDisplay" class="text-xs text-slate-500 mt-2">Escanee el código de barras del DNI o escriba para buscar</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Hora Salida (Automática) -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                                    Hora de Salida
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                    </span>
                                    <span class="text-[10px] text-emerald-600 font-medium uppercase tracking-tight">Automático</span>
                                </label>
                                <div class="relative">
                                    <input type="time" :value="horaSalida" disabled
                                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 font-bold outline-none cursor-not-allowed" />
                                    <div class="absolute inset-0 z-10" title="La hora se asigna automáticamente"></div>
                                </div>
                            </div>

                            <!-- Turno (Automático) -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Turno <span class="text-green-500 text-xs font-normal">(automático)</span>
                                </label>
                                <div class="relative">
                                    <div
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl bg-slate-50 flex items-center gap-2">
                                        <Clock class="w-4 h-4 text-slate-400" />
                                        <span class="font-medium" :class="{
                                            'text-amber-600': turno === 'Mañana',
                                            'text-orange-600': turno === 'Tarde',
                                            'text-indigo-600': turno === 'Noche'
                                        }">{{ turno || 'Seleccione una hora' }}</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">El turno se determina según la hora de salida</p>
                            </div>
                        </div>

                        <!-- Tipo de Motivo (UI filter only, not submitted) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo de Motivo <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4">
                                <label
                                    class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition-colors flex-1"
                                    :class="filterTipo === 'comision' ? 'ring-2 ring-green-500 border-green-500 bg-green-50' : 'border-slate-200'">
                                    <input type="radio" v-model="filterTipo" value="comision"
                                        class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300">
                                    <span class="ml-2 text-sm font-medium text-slate-700">Comisión de Servicios</span>
                                </label>
                                <label
                                    class="flex items-center p-3 border rounded-xl cursor-pointer hover:bg-slate-50 transition-colors flex-1"
                                    :class="filterTipo === 'permiso' ? 'ring-2 ring-green-500 border-green-500 bg-green-50' : 'border-slate-200'">
                                    <input type="radio" v-model="filterTipo" value="permiso"
                                        class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300">
                                    <span class="ml-2 text-sm font-medium text-slate-700">Permiso Personal</span>
                                </label>
                            </div>
                            <p v-if="formErrors.entry_exit_reason_id" class="mt-1 text-sm text-red-600">{{
                                formErrors.entry_exit_reason_id }}</p>
                        </div>

                        <!-- Razones predefinidas -->
                        <div v-if="filteredReasons.length">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Motivo predefinido <span class="text-slate-400 font-normal text-xs">(opcional)</span>
                            </label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="reason in filteredReasons" :key="reason.id"
                                    type="button"
                                    @click="selectReason(reason)"
                                    class="px-3 py-1.5 rounded-xl text-sm font-medium border-2 transition-all"
                                    :class="selectedReasonId === reason.id
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-emerald-300 hover:text-emerald-700'">
                                    {{ reason.nombre }}
                                </button>
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Descripción del Motivo <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="motivo" v-bind="motivoProps" rows="3"
                                placeholder="Indique el motivo de la salida..."
                                class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none resize-none transition-colors"
                                :class="formErrors.motivo ? 'border-red-400' : 'border-slate-200'"></textarea>
                            <p v-if="formErrors.motivo" class="mt-1 text-sm text-red-600">{{ formErrors.motivo }}</p>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-bold rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ entry ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { shallowRef, computed, watch, onMounted, onUnmounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { Plus, X, Loader2, LogIn, Clock, ScanBarcode, Search, CheckCircle, AlertCircle } from 'lucide-vue-next';
import { usePersonnelSearch } from '@/Composables/usePersonnelSearch';
import type { PersonResult } from '@/Composables/usePersonnelSearch';

interface Reason {
    id: string;
    nombre: string;
    tipo: 'comision' | 'permiso' | 'ambos';
}

const props = defineProps<{
    entry?: Record<string, any> | null;
    reasons?: Reason[];
}>();

const emit = defineEmits<{
    close: [];
}>();

const isSubmitting = shallowRef(false);
const selectedReasonId = shallowRef<string>('');
// UI-only filter for reasons (not submitted)
const filterTipo = shallowRef<'comision' | 'permiso'>('comision');

// Personnel search composable
const {
    searchQuery,
    showDropdown,
    searchResults,
    isSearching,
    scanMessage,
    scanStatus,
    selectedPersonDisplay,
    selectPerson,
    clearSelection: clearPersonSelection,
    handleClickOutside,
    focusSearchInput,
} = usePersonnelSearch({
    onPersonSelected: (person: PersonResult) => {
        setFieldValue('employee_id', person.id);
    },
    onPersonCleared: () => {
        setFieldValue('employee_id', '');
    },
});

const clearSelection = (): void => {
    clearPersonSelection();
};

// Reasons filtered by UI tipo filter
const filteredReasons = computed<Reason[]>(() => {
    if (!props.reasons?.length) return [];
    return props.reasons.filter(r => r.tipo === filterTipo.value || r.tipo === 'ambos');
});

const selectReason = (reason: Reason): void => {
    if (selectedReasonId.value === reason.id) {
        selectedReasonId.value = '';
        setFieldValue('entry_exit_reason_id', '');
    } else {
        selectedReasonId.value = reason.id;
        setFieldValue('entry_exit_reason_id', reason.id);
        if (!motivo.value) {
            setFieldValue('motivo', reason.nombre);
        }
    }
};

// Reset reason when UI filter changes
watch(filterTipo, () => {
    selectedReasonId.value = '';
    setFieldValue('entry_exit_reason_id', '');
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
    // Auto-focus barcode/search input when creating new entry
    if (!props.entry) {
        focusSearchInput();
    }
    // Update time automatically every 30 seconds
    timeInterval = setInterval(() => {
        currentTime.value = new Date().toTimeString().slice(0, 5);
        setFieldValue('hora_salida', currentTime.value);
        setReturnFieldValue('hora_retorno', currentTime.value);
    }, 30000);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (timeInterval) clearInterval(timeInterval);
});

// Get shift based on hour (HH:mm format or hour number)
const getShiftFromHour = (timeValue) => {
    let hour;
    if (typeof timeValue === 'string' && timeValue.includes(':')) {
        hour = parseInt(timeValue.split(':')[0], 10);
    } else {
        hour = typeof timeValue === 'number' ? timeValue : new Date().getHours();
    }

    if (hour >= 6 && hour < 14) return 'Mañana';
    if (hour >= 14 && hour < 22) return 'Tarde';
    return 'Noche';
};

// Get current shift based on current time
const getCurrentShift = () => getShiftFromHour(new Date().getHours());

const currentTime = shallowRef(new Date().toTimeString().slice(0, 5));
let timeInterval = null;

// Validation Schema for exit form
const exitSchema = toTypedSchema(
    yup.object({
        employee_id:          yup.string().required('Debe seleccionar un empleado'),
        hora_salida:          yup.string().required('La hora de salida es obligatoria'),
        turno:                yup.string().required('El turno es obligatorio'),
        entry_exit_reason_id: yup.string().required('Debe seleccionar un motivo'),
        motivo:               yup.string()
            .required('La descripción del motivo es obligatoria')
            .min(5, 'El motivo debe tener al menos 5 caracteres'),
    })
);

// Validation Schema for return form
const returnSchema = toTypedSchema(
    yup.object({
        hora_retorno: yup.string().required('La hora de retorno es obligatoria'),
    })
);

// Exit form
const { errors: formErrors, defineField: defineExitField, handleSubmit: validateExitForm, resetForm, setFieldValue } = useForm({
    validationSchema: exitSchema,
    initialValues: {
        employee_id:          '',
        hora_salida:          currentTime.value,
        turno:                getCurrentShift(),
        entry_exit_reason_id: '',
        motivo:               '',
    }
});

const [employeeId] = defineExitField('employee_id');
const [horaSalida] = defineExitField('hora_salida');
const [turno] = defineExitField('turno');
const [motivo, motivoProps] = defineExitField('motivo');

// Return form
const { errors: returnFormErrors, defineField: defineReturnField, handleSubmit: validateReturnForm, resetForm: resetReturnForm, setFieldValue: setReturnFieldValue } = useForm({
    validationSchema: returnSchema,
    initialValues: {
        hora_retorno: currentTime.value,
    }
});

const [horaRetorno, horaRetornoProps] = defineReturnField('hora_retorno');

// Watch for hour changes and automatically update the shift
watch(horaSalida, (newHora) => {
    if (newHora) {
        turno.value = getShiftFromHour(newHora);
    }
});

// Submit exit form
const onSubmitExit = validateExitForm(async (values) => {
    isSubmitting.value = true;

    router.post('/entry-exits', values, {
        onSuccess: () => {
            resetForm();
            selectedPersonDisplay.value = null;
            emit('close');
            Swal.fire({
                title: '¡Papeleta registrada!',
                text: 'La salida fue registrada correctamente.',
                icon: 'success',
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

// Submit return form
const onSubmitReturn = validateReturnForm(async (values) => {
    isSubmitting.value = true;

    router.patch(`/entry-exits/${props.entry.id}/return`, values, {
        onSuccess: () => {
            resetReturnForm();
            emit('close');
            Swal.fire({
                title: '¡Retorno registrado!',
                text: 'El retorno fue registrado correctamente.',
                icon: 'success',
                timer: 2500,
                showConfirmButton: false,
                timerProgressBar: true,
            });
        },
        onFinish: () => {
            isSubmitting.value = false;
        }
    });
});

const handleSubmit = () => {
    if (props.entry) {
        onSubmitReturn();
    } else {
        onSubmitExit();
    }
};
</script>
