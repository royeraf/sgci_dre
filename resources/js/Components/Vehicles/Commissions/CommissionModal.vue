<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-sky-500 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            {{ isEditing ? 'Gestionar Autorización de Salida' : 'Nueva Autorización de Salida' }}
                        </h3>
                        <p class="text-blue-100 text-sm mt-1">{{ isEditing ? 'Actualice los datos de la autorización' :
                            'Complete los datos de la autorización de salida del vehículo' }}</p>
                    </div>
                    <button @click="$emit('close')" class="cursor-pointer text-blue-100 hover:text-white transition-colors p-1">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto">
                    <!-- Sección 1: Datos de la Comisión -->
                    <div class="space-y-4">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2 text-sm uppercase tracking-wide">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Datos de la Comisión
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Servidor o funcionario que solicita: siempre el usuario logueado -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Servidor o Funcionario que Solicita
                                </label>
                                <div class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 bg-slate-50 rounded-xl">
                                    <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ isEditing ? (commission.solicitante || currentUserName) : currentUserName }}
                                    </p>
                                </div>
                            </div>

                            <!-- Lugar -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Lugar de Destino <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="lugar" v-bind="lugarProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-colors bg-white"
                                    :class="formErrors.lugar ? 'border-red-400' : 'border-slate-200'"
                                    placeholder="Ej: Lima - Sede MINEDU">
                                <p v-if="formErrors.lugar" class="mt-1 text-sm text-red-600">{{ formErrors.lugar }}</p>
                            </div>

                            <!-- Referencia -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Referencia / Documento</label>
                                <input type="text" v-model="referencia" v-bind="referenciaProps"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-colors bg-white"
                                    placeholder="Ej: Oficio Nº 001-2026-DREH">
                            </div>

                            <!-- Día -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Fecha Programada <span class="text-red-500">*</span>
                                </label>
                                <input type="date" v-model="dia" v-bind="diaProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-colors bg-white"
                                    :class="formErrors.dia ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="formErrors.dia" class="mt-1 text-sm text-red-600">{{ formErrors.dia }}</p>
                            </div>

                            <!-- Hora -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Hora Programada <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="hora" v-bind="horaProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-colors bg-white"
                                    :class="formErrors.hora ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="formErrors.hora" class="mt-1 text-sm text-red-600">{{ formErrors.hora }}</p>
                            </div>

                            <!-- Motivo -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Motivo de la Salida</label>
                                <textarea v-model="motivo" v-bind="motivoProps" rows="2"
                                    placeholder="Describa el motivo de la comisión..."
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none resize-none bg-white"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 2: Vehículo y Personal -->
                    <div class="space-y-4 pt-2">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2 text-sm uppercase tracking-wide">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Vehículo y Personal Asignado
                        </h4>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Vehículo -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Vehículo</label>
                                <select v-model="vehicleId" v-bind="vehicleIdProps"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                                    <option value="">Seleccionar vehículo</option>
                                    <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.placa }} - {{ v.marca }} {{
                                        v.modelo }}</option>
                                </select>
                            </div>

                            <!-- Conductor -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Conductor <span class="text-red-500">*</span>
                                </label>
                                <select v-model="conductorEmployeeId" v-bind="conductorEmployeeIdProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white"
                                    :class="formErrors.conductor_employee_id ? 'border-red-400' : 'border-slate-200'">
                                    <option value="">Seleccionar conductor (CHOFER II)</option>
                                    <option v-for="d in drivers" :key="d.id" :value="d.id">
                                        {{ d.nombre_completo }} — {{ d.licencia_numero ? `Lic. ${d.licencia_numero}` : 'sin licencia registrada' }}
                                    </option>
                                </select>
                                <p v-if="formErrors.conductor_employee_id" class="mt-1 text-sm text-red-600">{{ formErrors.conductor_employee_id }}</p>
                            </div>

                            <!-- Pasajeros -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Pasajeros (Opcional)</label>

                                <!-- Chips seleccionados -->
                                <div v-if="selectedPassengers.length" class="flex flex-wrap gap-2 mb-2">
                                    <span v-for="p in selectedPassengers" :key="p.id"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 border border-blue-200 rounded-full text-xs font-semibold text-blue-800">
                                        {{ p.nombre_completo }}
                                        <button type="button" @click="removePassenger(p.id)" class="cursor-pointer text-blue-400 hover:text-blue-700">
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </span>
                                </div>

                                <div class="relative">
                                    <input type="text" v-model="passengerQuery" @focus="showPassengerDropdown = true"
                                        placeholder="Buscar empleado por nombre..."
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                                    <div v-if="showPassengerDropdown && filteredPassengerCandidates.length"
                                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                        <button type="button" v-for="c in filteredPassengerCandidates" :key="c.id" @click="addPassenger(c)"
                                            class="cursor-pointer w-full text-left px-4 py-2.5 hover:bg-blue-50 text-sm font-medium text-slate-700 border-b border-slate-50 last:border-0">
                                            {{ c.nombre_completo }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sección 3: Control de Salida y Retorno (solo tras la confirmación del conductor) -->
                    <div v-if="showControlSalida" class="bg-blue-50/50 border border-blue-100 rounded-2xl p-4 space-y-4">
                        <h4 class="font-bold text-blue-800 flex items-center gap-2 border-b border-blue-100/50 pb-2 text-sm uppercase tracking-wide">
                            <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Control de Salida y Retorno
                        </h4>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Salida</label>
                                <input type="time" v-model="horaSalida" v-bind="horaSalidaProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Hora de Regreso</label>
                                <input type="time" v-model="horaRegreso" v-bind="horaRegresoProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Km de Salida</label>
                                <input type="text" v-model="kmSalida" v-bind="kmSalidaProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white transition-colors"
                                    :class="formErrors.km_salida ? 'border-red-400' : 'border-blue-200'">
                                <p v-if="formErrors.km_salida" class="mt-1 text-sm text-red-600">{{ formErrors.km_salida }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Km de Retorno</label>
                                <input type="text" v-model="kmRetorno" v-bind="kmRetornoProps"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white transition-colors"
                                    :class="formErrors.km_retorno ? 'border-red-400' : 'border-blue-200'">
                                <p v-if="formErrors.km_retorno" class="mt-1 text-sm text-red-600">{{ formErrors.km_retorno }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">Combustible</label>
                                <select v-model="combustible" v-bind="combustibleProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                                    <option value="">Seleccionar combustible</option>
                                    <option value="Gasolina">Gasolina</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="GLP">GLP</option>
                                    <option value="GNV">GNV</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-blue-700 mb-2">P/Nº</label>
                                <input type="text" v-model="pnro" v-bind="pnroProps"
                                    class="w-full px-4 py-2.5 border border-blue-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                            </div>
                            <div class="col-span-1 md:col-span-2 bg-blue-100/60 border border-blue-200 rounded-xl p-3.5 flex justify-between items-center mt-1">
                                <span class="text-sm font-bold text-blue-800">Total Kilómetros Recorridos</span>
                                <span class="text-lg font-black text-blue-900 bg-white/80 px-3 py-1 rounded-lg border border-blue-200 shadow-sm">
                                    {{ totalKmRecorrido !== null ? `${totalKmRecorrido} km` : '—' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-blue-600 to-sky-500 text-white font-bold rounded-xl hover:from-blue-700 hover:to-sky-600 shadow-lg shadow-blue-500/20 active:scale-95 transition-all disabled:opacity-50">
                            <svg v-if="isSubmitting" class="w-5 h-5 animate-spin inline mr-2" viewBox="0 0 24 24"
                                fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ isSubmitting ? 'Guardando...' : 'Guardar Autorización' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';

const props = defineProps({
    commission: Object,
    vehicles: Array,
    drivers: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
});
const emit = defineEmits(['close', 'saved']);

const page = usePage();
const currentUserName = computed(() => page.props.auth?.user?.full_name || page.props.auth?.user?.name || 'Usuario actual');

const isEditing = computed(() => !!props.commission?.id);
const isSubmitting = ref(false);

// Salida/retorno solo se registran una vez que el conductor confirmó la autorización.
const showControlSalida = computed(() => ['CONFIRMADA', 'EN_COMISION', 'COMPLETADA'].includes(props.commission?.estado));

// Current date and time
const currentDate = new Date().toISOString().split('T')[0];
const currentTime = new Date().toTimeString().slice(0, 5);

// Validation Schema
const commissionSchema = toTypedSchema(
    yup.object({
        lugar: yup.string()
            .required('El lugar de destino es obligatorio')
            .min(3, 'El lugar debe tener al menos 3 caracteres'),
        referencia: yup.string().nullable(),
        dia: yup.string().required('La fecha es obligatoria'),
        hora: yup.string().required('La hora programada es obligatoria'),
        vehicle_id: yup.string().nullable(),
        conductor_employee_id: yup.string()
            .required('Debe seleccionar el conductor'),
        pasajero_ids: yup.array().of(yup.string()).default([]),
        motivo: yup.string().nullable(),
        hora_salida: yup.string().nullable(),
        hora_regreso: yup.string().nullable(),
        km_salida: yup.string()
            .nullable()
            .test('is-numeric', 'El kilometraje de salida debe ser un número entero no negativo', value => {
                if (!value) return true;
                return /^\d+$/.test(value);
            }),
        km_retorno: yup.string()
            .nullable()
            .test('is-numeric', 'El kilometraje de retorno debe ser un número entero no negativo', value => {
                if (!value) return true;
                return /^\d+$/.test(value);
            })
            .test('greater-than-salida', 'El kilometraje de retorno debe ser mayor o igual al de salida', function(value) {
                const { km_salida } = this.parent;
                if (!value || !km_salida) return true;
                const salidaNum = parseInt(km_salida, 10);
                const retornoNum = parseInt(value, 10);
                if (isNaN(salidaNum) || isNaN(retornoNum)) return true;
                return retornoNum >= salidaNum;
            }),
        combustible: yup.string().nullable(),
        pnro: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm, setValues } = useForm({
    validationSchema: commissionSchema,
    initialValues: {
        lugar: '',
        referencia: '',
        dia: currentDate,
        hora: currentTime,
        vehicle_id: '',
        conductor_employee_id: '',
        pasajero_ids: [],
        motivo: '',
        hora_salida: '',
        hora_regreso: '',
        km_salida: '',
        km_retorno: '',
        combustible: '',
        pnro: '',
    }
});

const [lugar, lugarProps] = defineField('lugar');
const [referencia, referenciaProps] = defineField('referencia');
const [dia, diaProps] = defineField('dia');
const [hora, horaProps] = defineField('hora');
const [vehicleId, vehicleIdProps] = defineField('vehicle_id');
const [conductorEmployeeId, conductorEmployeeIdProps] = defineField('conductor_employee_id');
const [pasajeroIds] = defineField('pasajero_ids');
const [motivo, motivoProps] = defineField('motivo');
const [horaSalida, horaSalidaProps] = defineField('hora_salida');
const [horaRegreso, horaRegresoProps] = defineField('hora_regreso');
const [kmSalida, kmSalidaProps] = defineField('km_salida');
const [kmRetorno, kmRetornoProps] = defineField('km_retorno');
const [combustible, combustibleProps] = defineField('combustible');
const [pnro, pnroProps] = defineField('pnro');

const totalKmRecorrido = computed(() => {
    const salida = parseInt(kmSalida.value, 10);
    const retorno = parseInt(kmRetorno.value, 10);
    if (isNaN(salida) || isNaN(retorno)) {
        return null;
    }
    const diff = retorno - salida;
    return diff >= 0 ? diff : null;
});

// Pasajeros: selector múltiple con búsqueda + chips
const selectedPassengers = ref([]);
const passengerQuery = ref('');
const showPassengerDropdown = ref(false);

const normalizeText = (text) => {
    if (!text) return '';
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
};

const filteredPassengerCandidates = computed(() => {
    const selectedIds = selectedPassengers.value.map(p => p.id);
    let candidates = props.employees.filter(e =>
        !selectedIds.includes(e.id) && e.id !== conductorEmployeeId.value
    );
    if (passengerQuery.value) {
        const q = normalizeText(passengerQuery.value);
        candidates = candidates.filter(e => normalizeText(e.nombre_completo).includes(q));
    }
    return candidates.slice(0, 10);
});

const addPassenger = (candidate) => {
    selectedPassengers.value.push(candidate);
    pasajeroIds.value = selectedPassengers.value.map(p => p.id);
    passengerQuery.value = '';
    showPassengerDropdown.value = false;
};

const removePassenger = (id) => {
    selectedPassengers.value = selectedPassengers.value.filter(p => p.id !== id);
    pasajeroIds.value = selectedPassengers.value.map(p => p.id);
};

// Si el conductor elegido ya estaba marcado como pasajero, se retira de ahí.
watch(conductorEmployeeId, (newConductorId) => {
    if (newConductorId && selectedPassengers.value.some(p => p.id === newConductorId)) {
        removePassenger(newConductorId);
    }
});

// Auto-fill fuel type based on selected vehicle
watch(vehicleId, (newVehicleId) => {
    if (newVehicleId) {
        const vehicle = props.vehicles.find(v => String(v.id) === String(newVehicleId));
        if (vehicle && vehicle.combustible) {
            const fuelUpper = String(vehicle.combustible).trim().toLowerCase();
            const options = ['Gasolina', 'Diesel', 'GLP', 'GNV'];
            const matchedOption = options.find(opt => opt.toLowerCase() === fuelUpper);
            if (matchedOption) {
                combustible.value = matchedOption;
            }
        }
    }
});

// Load existing commission data if editing
onMounted(() => {
    if (props.commission) {
        selectedPassengers.value = props.commission.pasajeros || [];
        setValues({
            lugar: props.commission.lugar || '',
            referencia: props.commission.referencia || '',
            dia: props.commission.dia || currentDate,
            hora: props.commission.hora || currentTime,
            vehicle_id: props.commission.vehicle_id || '',
            conductor_employee_id: props.commission.conductor_employee_id || '',
            pasajero_ids: selectedPassengers.value.map(p => p.id),
            motivo: props.commission.motivo || '',
            hora_salida: props.commission.hora_salida || '',
            hora_regreso: props.commission.hora_regreso || '',
            km_salida: props.commission.km_salida || '',
            km_retorno: props.commission.km_retorno || '',
            combustible: props.commission.combustible || '',
            pnro: props.commission.pnro || '',
        });
    }
});

const onSubmitForm = validateForm(async (values) => {
    isSubmitting.value = true;
    try {
        if (isEditing.value) {
            await axios.put(`/vehicles/commissions/${props.commission.id}`, values);
        } else {
            await axios.post('/vehicles/commissions', values);
        }
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
