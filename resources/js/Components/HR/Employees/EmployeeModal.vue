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
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                DNI <span class="text-red-500">*</span>
                            </label>
                            <input v-model="dni" type="text" maxlength="8" :disabled="isEditing"
                                placeholder="Ingrese DNI"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-slate-50 disabled:text-slate-500 transition-colors"
                                :class="formErrors.dni ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.dni" class="mt-1 text-sm text-red-600">{{ formErrors.dni }}</p>
                        </div>

                        <!-- Tipo de Contrato -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo de Contrato <span class="text-red-500">*</span>
                            </label>
                            <select v-model="tipoContrato"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white transition-colors"
                                :class="formErrors.tipo_contrato ? 'border-red-400' : 'border-slate-200'">
                                <option value="Nombrado">Nombrado</option>
                                <option value="CAS">CAS</option>
                                <option value="Locador">Locador</option>
                                <option value="Practicante">Practicante</option>
                            </select>
                            <p v-if="formErrors.tipo_contrato" class="mt-1 text-sm text-red-600">{{
                                formErrors.tipo_contrato }}</p>
                        </div>

                        <!-- Nombres -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres <span class="text-red-500">*</span>
                            </label>
                            <input v-model="nombres" type="text" placeholder="Ingrese nombres"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}</p>
                        </div>

                        <!-- Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input v-model="apellidos" type="text" placeholder="Ingrese apellidos"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-colors"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}
                            </p>
                        </div>

                        <!-- Fecha Nacimiento -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Nacimiento</label>
                            <input v-model="fechaNacimiento" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Género -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Género</label>
                            <select v-model="genero"
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
                            <select v-model="cargo"
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
                                Área / Oficina <span class="text-red-500">*</span>
                            </label>
                            <select v-model="area"
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
                            <input v-model="telefono" type="text" placeholder="999 999 999"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Correo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Correo Institucional</label>
                            <input v-model="correo" type="email" placeholder="usuario@drehco.gob.pe"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Fecha Ingreso -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de Ingreso</label>
                            <input v-model="fechaIngreso" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Estado (Solo edición) -->
                        <div v-if="isEditing">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del Personal</label>
                            <select v-model="estado"
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
                        <input v-model="direccion" type="text" placeholder="Jr. / Av. ..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="observaciones" rows="3" placeholder="Información adicional relevante..."
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
import { watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, UserPlus, Pencil, Loader2 } from 'lucide-vue-next';

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
        fecha_nacimiento: yup.string().nullable(),
        genero: yup.string().nullable(),
        direccion: yup.string().nullable(),
        telefono: yup.string().nullable(),
        correo: yup.string().email('Ingrese un correo válido').nullable(),
        cargo: yup.string().required('Debe seleccionar un cargo'),
        area: yup.string().required('Debe seleccionar un área'),
        fecha_ingreso: yup.string().nullable(),
        tipo_contrato: yup.string().required('Debe seleccionar un tipo de contrato'),
        estado: yup.string().nullable(),
        observaciones: yup.string().nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
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
        fecha_ingreso: '',
        tipo_contrato: 'Nombrado',
        estado: 'ACTIVO',
        observaciones: '',
    }
});

const [dni] = defineField('dni');
const [nombres] = defineField('nombres');
const [apellidos] = defineField('apellidos');
const [fechaNacimiento] = defineField('fecha_nacimiento');
const [genero] = defineField('genero');
const [direccion] = defineField('direccion');
const [telefono] = defineField('telefono');
const [correo] = defineField('correo');
const [cargo] = defineField('cargo');
const [area] = defineField('area');
const [fechaIngreso] = defineField('fecha_ingreso');
const [tipoContrato] = defineField('tipo_contrato');
const [estado] = defineField('estado');
const [observaciones] = defineField('observaciones');

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
            fecha_ingreso: emp.fecha_ingreso ? emp.fecha_ingreso.split('T')[0] : '',
            tipo_contrato: emp.tipo_contrato || 'Nombrado',
            estado: emp.estado || 'ACTIVO',
            observaciones: emp.observaciones || '',
        });
    } else {
        setValues({
            dni: '', nombres: '', apellidos: '', fecha_nacimiento: '', genero: '',
            direccion: '', telefono: '', correo: '', cargo: '', area: '',
            fecha_ingreso: '', tipo_contrato: 'Nombrado', estado: 'ACTIVO', observaciones: '',
        });
    }
}, { immediate: true });

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
