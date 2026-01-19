<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <UserCog class="w-6 h-6" />
                            {{ isEditing ? 'Editar Usuario' : 'Nuevo Usuario' }}
                        </h3>
                        <p class="text-indigo-100 text-sm mt-1">
                            {{ modalSubtitle }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="handleSubmit" class="p-6 space-y-6">
                    <!-- Importar desde Empleado -->
                    <div v-if="!isEditing && employees.length > 0"
                        class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-2">
                        <label class="block text-sm font-bold text-indigo-700 mb-2">
                            Importar datos desde Registro de Personal
                        </label>
                        <div class="relative">
                            <Search
                                class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-indigo-400" />
                            <input type="text" v-model="employeeSearch" placeholder="Buscar por nombre o DNI..."
                                @focus="showEmployeeDropdown = true" @blur="closeDropdown"
                                class="w-full pl-9 pr-4 py-2.5 border border-indigo-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-white text-indigo-900 placeholder-indigo-300" />

                            <div v-if="showEmployeeDropdown && filteredEmployees.length > 0"
                                class="absolute z-50 w-full mt-1 bg-white rounded-xl shadow-lg border border-indigo-100 max-h-60 overflow-auto">
                                <ul class="py-1">
                                    <li v-for="emp in filteredEmployees" :key="emp.id" @click="selectEmployee(emp)"
                                        class="px-4 py-2 hover:bg-indigo-50 cursor-pointer flex flex-col border-b border-indigo-50 last:border-0">
                                        <span class="font-bold text-indigo-900 text-sm">{{ emp.nombres }} {{
                                            emp.apellidos }}</span>
                                        <span class="text-xs text-indigo-500">DNI: {{ emp.dni }} • {{ emp.cargo || 'Sin cargo' }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <p class="text-xs text-indigo-500 mt-2 font-medium">
                            * Busque y seleccione un empleado para llenar automáticamente sus datos
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                DNI <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="dni" v-bind="dniProps" maxlength="8" placeholder="12345678"
                                @input="dni = $event.target.value.replace(/\D/g, '')"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.dni ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.dni" class="mt-1 text-sm text-red-600">{{ formErrors.dni }}</p>
                        </div>

                        <!-- Título -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Título
                            </label>
                            <input type="text" v-model="titulo" v-bind="tituloProps" maxlength="20" placeholder="Ing., Dr., Lic., etc."
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors" />
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="name" v-bind="nameProps" placeholder="JUAN"
                                @input="name = $event.target.value.toUpperCase()"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.name ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</p>
                        </div>

                        <!-- Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="apellidos" v-bind="apellidosProps" placeholder="PÉREZ GARCÍA"
                                @input="apellidos = $event.target.value.toUpperCase()"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Email <span class="text-red-500">*</span>
                            </label>
                            <input type="email" v-model="email" v-bind="emailProps" placeholder="usuario@ejemplo.com"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.email ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.email" class="mt-1 text-sm text-red-600">{{ formErrors.email }}</p>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Teléfono
                            </label>
                            <input type="text" v-model="telefono" v-bind="telefonoProps" placeholder="999888777" maxlength="9"
                                @input="telefono = $event.target.value.replace(/\D/g, '')"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.telefono ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.telefono" class="mt-1 text-sm text-red-600">{{ formErrors.telefono }}
                            </p>
                        </div>

                        <!-- Cargo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Cargo
                            </label>
                            <select v-model="cargo" v-bind="cargoProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <option value="">Seleccionar cargo...</option>
                                <option v-for="position in positions" :key="position.id" :value="position.nombre">
                                    {{ position.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Área -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Área
                            </label>
                            <select v-model="area" v-bind="areaProps"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                                <option value="">Seleccionar área...</option>
                                <option v-for="a in areas" :key="a.id" :value="a.nombre">
                                    {{ a.nombre }}
                                </option>
                            </select>
                        </div>

                        <!-- Rol -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Rol <span class="text-red-500">*</span>
                            </label>
                            <select v-model="rolId" v-bind="rolIdProps"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.rol_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccionar rol...</option>
                                <option v-for="role in roles" :key="role.rol_id" :value="role.rol_id">
                                    {{ role.nombre }}
                                </option>
                            </select>
                            <p v-if="formErrors.rol_id" class="mt-1 text-sm text-red-600">{{ formErrors.rol_id }}</p>
                        </div>

                        <!-- Estado Activo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Estado
                            </label>
                            <div class="flex items-center h-10">
                                <input type="checkbox" v-model="isActive" v-bind="isActiveProps" id="is_active"
                                    class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500" />
                                <label for="is_active" class="ml-2 text-sm text-slate-700">
                                    Usuario activo
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Password Section (only for new users) -->
                    <div v-if="!isEditing" class="pt-6 border-t border-slate-200">
                        <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2">
                            <Key class="w-4 h-4" />
                            Contraseña de Acceso
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Contraseña -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Contraseña <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPassword ? 'text' : 'password'" v-model="password" v-bind="passwordProps"
                                        placeholder="Mínimo 8 caracteres"
                                        class="w-full px-4 py-2.5 pr-12 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        :class="formErrors.password ? 'border-red-400' : 'border-slate-200'" />
                                    <button type="button" @click="showPassword = !showPassword"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                        tabindex="-1">
                                        <Eye v-if="!showPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="formErrors.password" class="mt-1 text-sm text-red-600">{{ formErrors.password }}</p>
                            </div>

                            <!-- Confirmar Contraseña -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Confirmar Contraseña <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input :type="showPasswordConfirmation ? 'text' : 'password'"
                                        v-model="passwordConfirmation" v-bind="passwordConfirmationProps" placeholder="Repetir contraseña"
                                        class="w-full px-4 py-2.5 pr-12 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        :class="formErrors.password_confirmation ? 'border-red-400' : 'border-slate-200'" />
                                    <button type="button" @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                        tabindex="-1">
                                        <Eye v-if="!showPasswordConfirmation" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="formErrors.password_confirmation" class="mt-1 text-sm text-red-600">{{
                                    formErrors.password_confirmation }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                        <button type="button" @click="$emit('close')" :disabled="submitting"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all disabled:opacity-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-bold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ isEditing ? 'Actualizar Usuario' : 'Crear Usuario' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, UserCog, Key, Loader2, Eye, EyeOff, Search } from 'lucide-vue-next';

const props = defineProps({
    user: {
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
    roles: {
        type: Array,
        default: () => []
    },
    areas: {
        type: Array,
        default: () => []
    },
    positions: {
        type: Array,
        default: () => []
    },
    employees: {
        type: Array,
        default: () => []
    }
});

const emit = defineEmits(['close', 'submit']);

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

// Validation Schema
const userSchema = toTypedSchema(
    yup.object({
        dni: yup.string()
            .required('El DNI es obligatorio')
            .matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos'),
        titulo: yup.string().transform((value) => value || null).nullable(),
        name: yup.string().required('El nombre es obligatorio'),
        apellidos: yup.string().required('Los apellidos son obligatorios'),
        email: yup.string()
            .required('El email es obligatorio')
            .email('El email no es válido'),
        telefono: yup.string()
            .transform((value) => value || null)
            .nullable()
            .test('length', 'El teléfono debe tener exactamente 9 dígitos', (value) => {
                if (!value) return true;
                return /^\d{9}$/.test(value);
            }),
        cargo: yup.string().transform((value) => value || null).nullable(),
        area: yup.string().transform((value) => value || null).nullable(),
        rol_id: yup.string().required('El rol es obligatorio'),
        is_active: yup.boolean(),
        password: yup.string().when('$isEditing', {
            is: false,
            then: (schema) => schema
                .required('La contraseña es obligatoria')
                .min(8, 'La contraseña debe tener al menos 8 caracteres'),
            otherwise: (schema) => schema.transform((value) => value || null).nullable()
        }),
        password_confirmation: yup.string().when('$isEditing', {
            is: false,
            then: (schema) => schema
                .required('Debe confirmar la contraseña')
                .oneOf([yup.ref('password')], 'Las contraseñas no coinciden'),
            otherwise: (schema) => schema.transform((value) => value || null).nullable()
        })
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: userSchema,
    initialValues: {
        dni: '',
        titulo: '',
        name: '',
        apellidos: '',
        email: '',
        telefono: '',
        cargo: '',
        area: '',
        rol_id: '',
        is_active: true,
        password: '',
        password_confirmation: ''
    },
    context: computed(() => ({ isEditing: props.isEditing }))
});

const [dni, dniProps] = defineField('dni');
const [titulo, tituloProps] = defineField('titulo');
const [name, nameProps] = defineField('name');
const [apellidos, apellidosProps] = defineField('apellidos');
const [email, emailProps] = defineField('email');
const [telefono, telefonoProps] = defineField('telefono');
const [cargo, cargoProps] = defineField('cargo');
const [area, areaProps] = defineField('area');
const [rolId, rolIdProps] = defineField('rol_id');
const [isActive, isActiveProps] = defineField('is_active');
const [password, passwordProps] = defineField('password');
const [passwordConfirmation, passwordConfirmationProps] = defineField('password_confirmation');

watch(() => props.user, (newUser) => {
    if (newUser) {
        setValues({
            dni: newUser.dni || '',
            titulo: newUser.titulo || '',
            name: newUser.name || '',
            apellidos: newUser.apellidos || '',
            email: newUser.email || '',
            telefono: newUser.telefono || '',
            cargo: newUser.cargo || '',
            area: newUser.area || '',
            rol_id: newUser.rol_id || '',
            is_active: newUser.is_active ?? true,
            password: '',
            password_confirmation: ''
        });
    } else {
        resetForm();
    }
}, { immediate: true });

const modalSubtitle = computed(() => {
    return props.isEditing
        ? 'Actualice la información del usuario'
        : 'Complete los datos del nuevo usuario del sistema';
});

const employeeSearch = ref('');
const showEmployeeDropdown = ref(false);

const filteredEmployees = computed(() => {
    if (!employeeSearch.value) return [];
    const q = employeeSearch.value.toLowerCase();
    return props.employees.filter(e =>
        (e.nombres + ' ' + e.apellidos).toLowerCase().includes(q) ||
        String(e.dni).includes(q)
    ).slice(0, 10);
});

const selectEmployee = (emp) => {
    employeeSearch.value = `${emp.nombres} ${emp.apellidos}`;
    showEmployeeDropdown.value = false;

    setValues({
        dni: emp.dni || '',
        titulo: titulo.value,
        name: (emp.nombres || '').toUpperCase(),
        apellidos: (emp.apellidos || '').toUpperCase(),
        email: emp.correo || '',
        telefono: emp.telefono || '',
        cargo: emp.cargo || '',
        area: emp.area || '',
        rol_id: '',
        is_active: emp.estado === 'ACTIVO',
        password: password.value,
        password_confirmation: passwordConfirmation.value
    });
};

const closeDropdown = () => {
    setTimeout(() => {
        showEmployeeDropdown.value = false;
    }, 200);
};

const handleSubmit = validateForm((values) => {
    emit('submit', values);
});
</script>
