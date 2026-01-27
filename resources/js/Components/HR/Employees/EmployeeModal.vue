<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <UserPlus v-if="!isEditing" class="w-6 h-6" />
                            <Pencil v-else class="w-6 h-6" />
                            {{ isEditing ? 'Editar Empleado' : 'Registrar Nuevo Empleado' }}
                        </h3>
                        <p class="text-emerald-50 text-sm mt-1">
                            {{ isEditing ? 'Modifique los datos del personal' : 'Complete la información' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-emerald-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="onSubmit" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-1 gap-6">
                        <!-- DNI Section with Consultation -->
                        <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-4 border border-emerald-100">
                            <div class="flex items-center gap-2 mb-3">
                                <ScanBarcode class="w-5 h-5 text-emerald-600" />
                                <span class="font-semibold text-emerald-900">Consulta de DNI</span>
                                <button type="button" @click="isDniEditable = !isDniEditable" v-if="!isEditing"
                                    class="ml-auto text-xs flex items-center gap-1 px-2 py-1 rounded-md transition-colors"
                                    :class="isDniEditable ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                    :title="isDniEditable ? 'Bloquear edición' : 'Habilitar edición'">
                                    <component :is="isDniEditable ? Unlock : Lock" class="w-3 h-3" />
                                    {{ isDniEditable ? 'Editable' : 'Bloqueado' }}
                                </button>
                            </div>

                            <div class="flex gap-3">
                                <div class="flex-1 relative">
                                    <input v-model="dni" v-bind="dniProps" type="text" maxlength="8"
                                        placeholder="Escanee o digite el DNI"
                                        @keypress.enter.prevent="consultarDni"
                                        @input="handleDniInput"
                                        :disabled="!isDniEditable"
                                        class="w-full px-4 py-3 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-slate-50 disabled:text-slate-500 transition-all text-lg font-mono tracking-wider outline-none"
                                        :class="[
                                            formErrors.dni ? 'border-red-400 bg-red-50' : 'border-emerald-200 bg-white',
                                            isConsultando ? 'opacity-50' : ''
                                        ]" />
                                    <div v-if="isConsultando" class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="w-5 h-5 animate-spin text-emerald-600" />
                                    </div>
                                </div>
                                <button type="button" @click="consultarDni"
                                    :disabled="dni.length !== 8 || isConsultando || !isDniEditable"
                                    class="px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                    <Search class="w-5 h-5" />
                                    <span class="hidden sm:inline">Buscar</span>
                                </button>
                            </div>

                            <p v-if="formErrors.dni" class="mt-2 text-sm text-red-600">{{ formErrors.dni }}</p>

                            <!-- Consulta Result Message -->
                            <div v-if="consultaMessage" class="mt-3 p-3 rounded-lg flex items-center gap-2"
                                :class="consultaSuccess ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                                <span class="text-sm font-medium">{{ consultaMessage }}</span>
                            </div>

                            <!-- Manual Edit Toggle -->
                            <div v-if="nombreAutocompletado && !camposEditables" class="mt-3">
                                <button type="button" @click="camposEditables = true"
                                    class="text-sm text-emerald-700 hover:text-emerald-800 font-medium underline">
                                    ¿Necesita editar los nombres manualmente?
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tipo de Contrato moved here -->

                        <!-- Tipo de Contrato -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo de Contrato <span class="text-red-500">*</span>
                            </label>
                            <select v-model="contractTypeId" v-bind="contractTypeIdProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white transition-colors"
                                :class="formErrors.contract_type_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione tipo...</option>
                                <option v-for="type in contractTypes" :key="type.id" :value="type.id">
                                    {{ type.nombre }}
                                </option>
                                <option v-if="contractTypes.length === 0" disabled>No hay tipos registrados</option>
                            </select>
                            <p v-if="formErrors.contract_type_id" class="mt-1 text-sm text-red-600">{{
                                formErrors.contract_type_id }}</p>
                        </div>

                        <!-- Nombres -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres <span class="text-red-500">*</span>
                            </label>
                            <input v-model="nombres" v-bind="nombresProps" type="text" placeholder="Ingrese nombres"
                                @input="nombres = $event.target.value.toUpperCase()"
                                :disabled="nombreAutocompletado && !camposEditables"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors disabled:bg-slate-50 disabled:text-slate-600"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}</p>
                        </div>

                        <!-- Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input v-model="apellidos" v-bind="apellidosProps" type="text"
                                placeholder="Ingrese apellidos" @input="apellidos = $event.target.value.toUpperCase()"
                                :disabled="nombreAutocompletado && !camposEditables"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors disabled:bg-slate-50 disabled:text-slate-600"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}
                            </p>
                        </div>

                        <!-- Fecha Nacimiento -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Nacimiento</label>
                            <input v-model="fechaNacimiento" v-bind="fechaNacimientoProps" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Género -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Género</label>
                            <select v-model="genero" v-bind="generoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="">Seleccionar...</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                        </div>

                        <!-- Cargo (SELECT) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Cargo / Puesto <span class="text-red-500">*</span>
                            </label>
                            <select v-model="cargo" v-bind="cargoProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white transition-colors"
                                :class="formErrors.cargo ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione cargo...</option>
                                <option v-for="pos in positions" :key="pos.id" :value="pos.nombre">
                                    {{ pos.nombre }}
                                </option>
                                <option v-if="positions.length === 0" disabled>No hay cargos registrados</option>
                            </select>
                            <p v-if="formErrors.cargo" class="mt-1 text-sm text-red-600">{{ formErrors.cargo }}</p>
                        </div>

                        <!-- Área (SELECT) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Área / Oficina
                            </label>
                            <select v-model="area" v-bind="areaProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white transition-colors"
                                :class="formErrors.area ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione área...</option>
                                <option v-for="a in areas" :key="a.id" :value="a.nombre">
                                    {{ a.nombre }}
                                </option>
                                <option v-if="areas.length === 0" disabled>No hay áreas registradas</option>
                            </select>
                            <p v-if="formErrors.area" class="mt-1 text-sm text-red-600">{{ formErrors.area }}</p>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono</label>
                            <input v-model="telefono" v-bind="telefonoProps" type="text" placeholder="999 999 999"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Correo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Correo Institucional</label>
                            <input v-model="correo" v-bind="correoProps" type="email"
                                placeholder="usuario@drehco.gob.pe"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                                :class="formErrors.correo ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.correo" class="mt-1 text-sm text-red-600">{{ formErrors.correo }}</p>
                        </div>

                        <!-- Fecha Ingreso -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de Ingreso</label>
                            <input v-model="fechaIngreso" v-bind="fechaIngresoProps" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Estado (Solo edición) -->
                        <div v-if="isEditing">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del Personal</label>
                            <select v-model="estado" v-bind="estadoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="ACTIVO">Activo</option>
                                <option value="INACTIVO">Inactivo</option>
                                <option value="LICENCIA">Licencia</option>
                                <option value="VACACIONES">Vacaciones</option>
                            </select>
                        </div>
                    </div>

                    <!-- Dirección -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Dirección de Domicilio</label>
                        <input v-model="direccion" v-bind="direccionProps" type="text" placeholder="Jr. / Av. ..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="observaciones" v-bind="observacionesProps" rows="3"
                            placeholder="Información adicional relevante..."
                            class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 resize-none"></textarea>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl hover:from-emerald-700 hover:to-teal-700 transition-all shadow-lg shadow-emerald-600/20 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Personal' : 'Registrar Empleado')
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch, ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, UserPlus, Pencil, Loader2, Lock, Unlock, Search, ScanBarcode } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    employee: {
        type: Object,
        default: null
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    submitting: {
        type: Boolean,
        default: false
    },
    areas: {
        type: Array,
        default: () => []
    },
    positions: {
        type: Array,
        default: () => []
    },
    contractTypes: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'submit']);

// Validation Schema
const employeeSchema = toTypedSchema(
    yup.object({
        dni: yup.string()
            .required('El DNI es obligatorio')
            .matches(/^\d{8}$/, 'El DNI debe tener exactamente 8 dígitos'),
        nombres: yup.string()
            .required('Los nombres son obligatorios')
            .min(2, 'Debe tener al menos 2 caracteres'),
        apellidos: yup.string()
            .required('Los apellidos son obligatorios')
            .min(2, 'Debe tener al menos 2 caracteres'),
        fecha_nacimiento: yup.string().transform((value) => value || null).nullable(),
        genero: yup.string().transform((value) => value || null).nullable(),
        direccion: yup.string().transform((value) => value || null).nullable(),
        telefono: yup.string().transform((value) => value || null).nullable(),
        correo: yup.string().transform((value) => value || null).email('Ingrese un correo válido').nullable(),
        cargo: yup.string().required('Debe seleccionar un cargo'),
        area: yup.string().transform((value) => value || null).nullable(),
        fecha_ingreso: yup.string().transform((value) => value || null).nullable(),
        contract_type_id: yup.string().required('Debe seleccionar un tipo de contrato'),
        estado: yup.string().transform((value) => value || null).nullable(),
        observaciones: yup.string().transform((value) => value || null).nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: employeeSchema,
    initialValues: {
        dni: '',
        nombres: '',
        apellidos: '',
        fecha_nacimiento: '',
        genero: '',
        direccion: '',
        telefono: '',
        correo: '',
        cargo: '',
        area: '',
        area: '',
        fecha_ingreso: '',
        contract_type_id: '',
        estado: 'ACTIVO',
        observaciones: '',
    }
});

const [dni, dniProps] = defineField('dni');
const [nombres, nombresProps] = defineField('nombres');
const [apellidos, apellidosProps] = defineField('apellidos');
const [fechaNacimiento, fechaNacimientoProps] = defineField('fecha_nacimiento');
const [genero, generoProps] = defineField('genero');
const [direccion, direccionProps] = defineField('direccion');
const [telefono, telefonoProps] = defineField('telefono');
const [correo, correoProps] = defineField('correo');
const [cargo, cargoProps] = defineField('cargo');
const [area, areaProps] = defineField('area');
const [fechaIngreso, fechaIngresoProps] = defineField('fecha_ingreso');
const [contractTypeId, contractTypeIdProps] = defineField('contract_type_id');
const [estado, estadoProps] = defineField('estado');
const [observaciones, observacionesProps] = defineField('observaciones');

const isDniEditable = ref(true);
const isConsultando = ref(false);
const consultaMessage = ref('');
const consultaSuccess = ref(false);
const nombreAutocompletado = ref(false);
const camposEditables = ref(true);

watch(() => props.employee, (emp) => {
    if (emp && props.isEditing) {
        setValues({
            dni: emp.dni || '',
            nombres: emp.nombres || '',
            apellidos: emp.apellidos || '',
            fecha_nacimiento: emp.fecha_nacimiento ? emp.fecha_nacimiento.split('T')[0] : '',
            genero: emp.genero || '',
            direccion: emp.direccion || '',
            telefono: emp.telefono || '',
            correo: emp.correo || '',
            cargo: emp.cargo || '',
            area: emp.area || '',
            area: emp.area || '',
            fecha_ingreso: emp.fecha_ingreso ? emp.fecha_ingreso.split('T')[0] : '',
            contract_type_id: emp.contract_type_id || '',
            estado: emp.estado || 'ACTIVO',
            observaciones: emp.observaciones || '',
        });
        isDniEditable.value = false;
    } else {
        resetForm();
        isDniEditable.value = true;
    }
}, { immediate: true });

// Función para manejar el input del DNI
const handleDniInput = (event) => {
    const cleanValue = event.target.value.replace(/\D/g, '');
    dni.value = cleanValue;
    consultaMessage.value = '';
    nombreAutocompletado.value = false;
    camposEditables.value = true;

    // Auto-consulta cuando se completa el DNI (8 dígitos)
    if (cleanValue.length === 8 && !isEditing) {
        setTimeout(() => {
            if (dni.value.length === 8) consultarDni();
        }, 100);
    }
};

// Función para consultar DNI (local o RENIEC)
const consultarDni = async () => {
    if (dni.value.length !== 8 || !isDniEditable.value) return;

    isConsultando.value = true;
    consultaMessage.value = '';

    try {
        const response = await axios.get('/visitors/api/consultar-dni', {
            params: { dni: dni.value }
        });

        if (response.data.success && response.data.data) {
            const persona = response.data.data;

            // Auto-llenar nombres
            nombres.value = persona.nombres;

            // Auto-llenar apellidos si están disponibles
            if (persona.apellido_paterno || persona.apellido_materno) {
                const apellidosStr = `${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
                apellidos.value = apellidosStr;
            }

            nombreAutocompletado.value = true;
            camposEditables.value = false;
            consultaMessage.value = `Datos encontrados: ${persona.nombre_completo || persona.nombres + ' ' + (apellidos.value || '')}`;
            consultaSuccess.value = true;
        } else {
            consultaMessage.value = response.data.message || 'DNI no encontrado. Puede ingresar los datos manualmente.';
            consultaSuccess.value = false;
            camposEditables.value = true;
        }
    } catch (error) {
        console.error('Error al consultar DNI:', error);
        consultaMessage.value = 'Error al consultar DNI. Puede ingresar los datos manualmente.';
        consultaSuccess.value = false;
        camposEditables.value = true;
    } finally {
        isConsultando.value = false;
    }
};

const onSubmit = validateForm((values) => {
    emit('submit', values);
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>
