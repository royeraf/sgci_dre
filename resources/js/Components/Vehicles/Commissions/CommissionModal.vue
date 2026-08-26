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

                            <!-- Ámbito del Destino -->
                            <div class="col-span-1">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Ámbito del Destino</label>
                                <select v-model="ambitoDestino" v-bind="ambitoDestinoProps"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                                    <option value="">Seleccionar ámbito</option>
                                    <option value="LOCAL">Local</option>
                                    <option value="REGIONAL">Regional</option>
                                    <option value="NACIONAL">Nacional</option>
                                </select>
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

                    <!-- Sección 3: Combustible (solo al gestionar; no en el registro de salida/retorno).
                         P/Nº ya no se pide aquí: es la placa del vehículo asignado y se
                         muestra automáticamente en el PDF (ver pdf.vehicle_exit_authorization). -->
                    <div v-if="isEditing" class="space-y-4 pt-2">
                        <h4 class="font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2 text-sm uppercase tracking-wide">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.25 6.087c0-.355.186-.676.401-.959.221-.29.349-.634.349-1.003 0-1.036-1.007-1.875-2.25-1.875s-2.25.84-2.25 1.875c0 .369.128.713.349 1.003.215.283.401.604.401.959v0a.64.64 0 01-.657.643 48.39 48.39 0 01-4.163-.3c.186 1.613.293 3.25.315 4.907a.656.656 0 01-.658.663v0c-.355 0-.676-.186-.959-.401a1.647 1.647 0 00-1.003-.349c-1.036 0-1.875 1.007-1.875 2.25s.84 2.25 1.875 2.25c.369 0 .713-.128 1.003-.349.283-.215.604-.401.959-.401v0c.31 0 .555.26.532.57a48.039 48.039 0 01-.642 5.056c1.518.19 3.058.309 4.616.354a.64.64 0 00.657-.643v0c0-.355-.186-.676-.401-.959a1.647 1.647 0 01-.349-1.003c0-1.035 1.008-1.875 2.25-1.875 1.243 0 2.25.84 2.25 1.875 0 .369-.128.713-.349 1.003-.215.283-.4.604-.4.959v0c0 .333.277.599.61.58a48.1 48.1 0 005.427-.63 48.05 48.05 0 00.582-4.717.532.532 0 00-.533-.57v0c-.355 0-.676.186-.959.401-.29.221-.634.349-1.003.349-1.035 0-1.875-1.007-1.875-2.25s.84-2.25 1.875-2.25c.37 0 .713.128 1.003.349.283.215.604.401.96.401v0a.656.656 0 00.658-.663 48.422 48.422 0 00-.37-5.36c-1.886.342-3.81.574-5.766.689a.578.578 0 01-.61-.58v0z" />
                            </svg>
                            Combustible
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Combustible</label>
                                <select v-model="combustible" v-bind="combustibleProps"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 outline-none bg-white">
                                    <option value="">Seleccionar combustible</option>
                                    <option value="Gasolina">Gasolina</option>
                                    <option value="Diesel">Diesel</option>
                                    <option value="GLP">GLP</option>
                                    <option value="GNV">GNV</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Firma digital: solo al crear la solicitud (el solicitante firma con su PFX RENIEC) -->
                    <div v-if="!isEditing" class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <label class="mb-1.5 block text-sm font-bold text-indigo-900">Clave de firma digital <span class="text-red-500">*</span></label>
                        <input type="password" autocomplete="off" v-model="signingPin" v-bind="signingPinProps"
                            class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            :class="formErrors.signing_pin ? 'border-red-400' : 'border-indigo-200'" placeholder="Clave de su certificado RENIEC" />
                        <p class="mt-1 text-xs text-indigo-700">La solicitud continuará únicamente si se genera su firma digital.</p>
                        <p v-if="formErrors.signing_pin" class="mt-1 text-xs text-red-600">{{ formErrors.signing_pin }}</p>
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

// Current date and time
const currentDate = new Date().toISOString().split('T')[0];
const currentTime = new Date().toTimeString().slice(0, 5);

// Validation Schema. signing_pin solo se exige al crear: el solicitante
// firma con su certificado RENIEC (VehicleController::storeCommission).
// Editar una comisión existente no re-firma nada.
const commissionSchema = toTypedSchema(
    yup.object({
        signing_pin: isEditing.value
            ? yup.string().transform(() => undefined).nullable()
            : yup.string()
                .required('Ingrese su clave de firma.')
                .min(6, 'La clave de firma debe tener al menos 6 caracteres.')
                .max(20, 'La clave de firma no debe superar los 20 caracteres.'),
        lugar: yup.string()
            .required('El lugar de destino es obligatorio')
            .min(3, 'El lugar debe tener al menos 3 caracteres'),
        ambito_destino: yup.string().nullable(),
        referencia: yup.string().nullable(),
        dia: yup.string().required('La fecha es obligatoria'),
        hora: yup.string().required('La hora programada es obligatoria'),
        vehicle_id: yup.string().nullable(),
        conductor_employee_id: yup.string()
            .required('Debe seleccionar el conductor'),
        pasajero_ids: yup.array().of(yup.string()).default([]),
        motivo: yup.string().nullable(),
        combustible: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, resetForm, setValues, setFieldError } = useForm({
    validationSchema: commissionSchema,
    initialValues: {
        signing_pin: '',
        lugar: '',
        ambito_destino: '',
        referencia: '',
        dia: currentDate,
        hora: currentTime,
        vehicle_id: '',
        conductor_employee_id: '',
        pasajero_ids: [],
        motivo: '',
        combustible: '',
    }
});

const [signingPin, signingPinProps] = defineField('signing_pin');
const [lugar, lugarProps] = defineField('lugar');
const [ambitoDestino, ambitoDestinoProps] = defineField('ambito_destino');
const [referencia, referenciaProps] = defineField('referencia');
const [dia, diaProps] = defineField('dia');
const [hora, horaProps] = defineField('hora');
const [vehicleId, vehicleIdProps] = defineField('vehicle_id');
const [conductorEmployeeId, conductorEmployeeIdProps] = defineField('conductor_employee_id');
const [pasajeroIds] = defineField('pasajero_ids');
const [motivo, motivoProps] = defineField('motivo');
const [combustible, combustibleProps] = defineField('combustible');

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

// Load existing commission data if editing
onMounted(() => {
    if (props.commission) {
        selectedPassengers.value = props.commission.pasajeros || [];
        setValues({
            lugar: props.commission.lugar || '',
            ambito_destino: props.commission.ambito_destino || '',
            referencia: props.commission.referencia || '',
            dia: props.commission.dia || currentDate,
            hora: props.commission.hora || currentTime,
            vehicle_id: props.commission.vehicle_id || '',
            conductor_employee_id: props.commission.conductor_employee_id || '',
            pasajero_ids: selectedPassengers.value.map(p => p.id),
            motivo: props.commission.motivo || '',
            // El combustible parte del que ya tiene registrado el vehículo en
            // Inventario, en vez de pedirlo de nuevo cada vez.
            combustible: props.commission.combustible || props.commission.vehicle_combustible || '',
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
        const errors = e.response?.data?.errors;
        if (errors?.signing_pin) {
            setFieldError('signing_pin', Array.isArray(errors.signing_pin) ? errors.signing_pin[0] : errors.signing_pin);
        } else {
            alert('Error al guardar: ' + (e.response?.data?.message || e.message));
        }
    } finally {
        isSubmitting.value = false;
    }
});

const handleSubmit = () => {
    onSubmitForm();
};
</script>
