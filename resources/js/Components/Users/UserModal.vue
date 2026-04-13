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

                    <!-- Datos del personal vinculado (solo lectura) -->
                    <div v-if="hasLinkedPerson && linkedPersonData"
                        class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-2">
                        <div class="flex items-center gap-2 mb-3">
                            <UserCheck class="w-4 h-4 text-emerald-600 flex-shrink-0" />
                            <span class="text-sm font-bold text-emerald-700">Personal Vinculado (RRHH)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                            <div>
                                <span class="text-emerald-600 font-medium">DNI:</span>
                                <span class="ml-1 text-slate-800 font-semibold">{{ linkedPersonData.dni }}</span>
                            </div>
                            <div>
                                <span class="text-emerald-600 font-medium">Teléfono:</span>
                                <span class="ml-1 text-slate-700">{{ linkedPersonData.telefono || '—' }}</span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-emerald-600 font-medium">Nombre:</span>
                                <span class="ml-1 text-slate-800 font-semibold">
                                    {{ linkedPersonData.nombres }} {{ linkedPersonData.apellidos }}
                                </span>
                            </div>
                            <div class="col-span-2">
                                <span class="text-emerald-600 font-medium">Email:</span>
                                <span class="ml-1 text-slate-700">{{ linkedPersonData.email || '—' }}</span>
                            </div>
                        </div>
                        <p class="text-xs text-emerald-600 mt-2">
                            Datos personales del registro de RRHH — no se duplican en el sistema de usuarios.
                            Para editar nombre, DNI, email o teléfono, hazlo desde el módulo de Recursos Humanos.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI (solo sin persona vinculada) -->
                        <div v-if="!hasLinkedPerson">
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

                        <!-- Nombre (solo sin persona vinculada) -->
                        <div v-if="!hasLinkedPerson">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="name" v-bind="nameProps" placeholder="JUAN"
                                @input="name = $event.target.value.toUpperCase()"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.name ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.name" class="mt-1 text-sm text-red-600">{{ formErrors.name }}</p>
                        </div>

                        <!-- Apellidos (solo sin persona vinculada) -->
                        <div v-if="!hasLinkedPerson">
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

                        <!-- Email (solo sin persona vinculada — para vinculados viene de people) -->
                        <div v-if="!hasLinkedPerson">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Email
                            </label>
                            <input type="email" v-model="email" v-bind="emailProps" placeholder="usuario@ejemplo.com"
                                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                :class="formErrors.email ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.email" class="mt-1 text-sm text-red-600">{{ formErrors.email }}</p>
                        </div>

                        <!-- Teléfono (solo sin persona vinculada) -->
                        <div v-if="!hasLinkedPerson">
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
                            <select v-model="rolId" v-bind="rolIdProps" @change="applyRolePreset"
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

                    <!-- Módulos Asignados -->
                    <div class="pt-6 border-t border-slate-200">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                <LayoutGrid class="w-4 h-4" />
                                Módulos Asignados
                            </h4>
                            <div class="flex items-center gap-2">
                                <span v-if="!useCustomModules" class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded-lg">
                                    Hereda del rol
                                </span>
                                <button type="button" @click="toggleCustomModules"
                                    class="text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors"
                                    :class="useCustomModules
                                        ? 'bg-indigo-100 text-indigo-700 hover:bg-indigo-200'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'">
                                    {{ useCustomModules ? 'Personalizado' : 'Personalizar' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="useCustomModules" class="space-y-2">
                            <div v-for="mod in ALL_MODULES" :key="mod.key" class="rounded-xl border overflow-hidden transition-colors"
                                :class="selectedModules.includes(mod.key) ? 'border-indigo-300' : 'border-slate-200'">

                                <!-- Module row -->
                                <label class="flex items-center gap-2.5 px-3 py-2.5 cursor-pointer transition-colors"
                                    :class="selectedModules.includes(mod.key) ? 'bg-indigo-50 text-indigo-800' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'">
                                    <input type="checkbox" :checked="selectedModules.includes(mod.key)"
                                        @change="toggleModule(mod.key)"
                                        class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500 flex-shrink-0" />
                                    <component :is="mod.icon" class="w-4 h-4 flex-shrink-0"
                                        :class="selectedModules.includes(mod.key) ? 'text-indigo-500' : 'text-slate-400'" />
                                    <span class="text-xs font-semibold leading-tight flex-1">{{ mod.label }}</span>
                                    <span v-if="selectedModules.includes(mod.key) && MODULE_TABS[mod.key]"
                                        class="text-[10px] text-indigo-500 font-medium">
                                        {{ (selectedTabs[mod.key] || []).length }}/{{ MODULE_TABS[mod.key].length }} tabs
                                    </span>
                                </label>

                                <!-- Tab sub-list (only if module has tabs and is selected) -->
                                <div v-if="selectedModules.includes(mod.key) && MODULE_TABS[mod.key]"
                                    class="border-t border-indigo-100 bg-white px-3 py-2">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Secciones</span>
                                        <button type="button" @click="toggleAllTabs(mod.key)"
                                            class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                            {{ allTabsSelected(mod.key) ? 'Desmarcar todo' : 'Seleccionar todo' }}
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5">
                                        <label v-for="tab in MODULE_TABS[mod.key]" :key="tab.key"
                                            class="flex items-center gap-1.5 px-2 py-1 rounded-lg border cursor-pointer text-[11px] font-medium transition-colors"
                                            :class="isTabSelected(mod.key, tab.key)
                                                ? 'bg-indigo-100 border-indigo-300 text-indigo-700'
                                                : 'bg-slate-50 border-slate-200 text-slate-500 hover:bg-slate-100'">
                                            <input type="checkbox" :checked="isTabSelected(mod.key, tab.key)"
                                                @change="toggleTab(mod.key, tab.key)"
                                                class="w-3 h-3 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500" />
                                            {{ tab.label }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <p v-else class="text-xs text-slate-500">
                            El usuario verá los módulos que su rol tenga asignados. Active "Personalizar" para asignar módulos específicos.
                        </p>
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
import { X, UserCog, UserCheck, Key, Loader2, Eye, EyeOff, Search, LayoutGrid, LayoutDashboard, ClipboardList, Users, Car, Box, CalendarDays, Heart, FileText, Briefcase, Fingerprint } from 'lucide-vue-next';

const ALL_MODULES = [
    { key: 'dashboard',        label: 'Dashboard',              icon: LayoutDashboard },
    { key: 'ocurrencias',      label: 'Libro de Ocurrencias',   icon: ClipboardList },
    { key: 'control_personal', label: 'Control de Personal',    icon: UserCheck },
    { key: 'visitas',          label: 'Visitas Externas',       icon: Users },
    { key: 'vehiculos',        label: 'Control Vehicular',      icon: Car },
    { key: 'patrimonio',       label: 'Patrimonio (Bienes)',     icon: Box },
    { key: 'secretaria',       label: 'Gestión de Citas',       icon: CalendarDays },
    { key: 'bienestar',        label: 'Bienestar Social',       icon: Heart },
    { key: 'papeletas',        label: 'Papeletas de Salida',    icon: FileText },
    { key: 'recursos_humanos', label: 'Recursos Humanos',       icon: Briefcase },
    { key: 'asistencia',       label: 'Marcas de Asistencia',   icon: Fingerprint },
];

// Módulos pre-asignados por defecto según el rol seleccionado
const ROLE_MODULE_PRESETS = {
    'ROL008': ['dashboard', 'bienestar'],
    'ROL009': ['dashboard', 'recursos_humanos', 'papeletas', 'asistencia'],
    'ROL010': ['dashboard', 'secretaria'],
    'ROL011': ['dashboard', 'papeletas'],
    'ROL012': ['dashboard', 'papeletas', 'asistencia', 'patrimonio'],
};

const MODULE_TABS = {
    ocurrencias:      [{ key: 'list', label: 'Lista' }, { key: 'reports', label: 'Reportes' }],
    control_personal: [{ key: 'list', label: 'Lista' }, { key: 'reports', label: 'Reportes' }, { key: 'reasons', label: 'Motivos' }],
    visitas:          [{ key: 'list', label: 'Lista' }, { key: 'reports', label: 'Reportes' }, { key: 'reasons', label: 'Motivos' }],
    vehiculos:        [{ key: 'commissions', label: 'Comisiones' }, { key: 'inventory', label: 'Inventario' }, { key: 'maintenance', label: 'Mantenimiento' }, { key: 'handover', label: 'Acta de Entrega' }, { key: 'service', label: 'Servicios' }],
    patrimonio:       [{ key: 'mis_bienes', label: 'Mis Bienes' }, { key: 'list', label: 'Bienes' }, { key: 'movements', label: 'Movimientos' }, { key: 'barcodes', label: 'Cód. de Barra' }, { key: 'reports', label: 'Reportes' }, { key: 'patrimonio', label: 'Patrimonio SIGA' }, { key: 'inventarios', label: 'Inventarios' }],
    secretaria:       [{ key: 'pending', label: 'Pendientes' }, { key: 'completed', label: 'Completadas' }],
    bienestar:        [{ key: 'licenses', label: 'Licencias' }, { key: 'balances', label: 'Saldos' }],
    papeletas:        [{ key: 'pendientes', label: 'Pendientes' }, { key: 'pendientes_rrhh', label: 'Pendientes RRHH' }, { key: 'historial', label: 'Historial' }, { key: 'reportes', label: 'Reportes' }],
    recursos_humanos: [{ key: 'personal', label: 'Personal' }, { key: 'vacaciones', label: 'Vacaciones' }, { key: 'directions', label: 'Direcciones' }, { key: 'cargos', label: 'Cargos' }, { key: 'tipos_contrato', label: 'Tipos de Contrato' }],
};

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

// --- Persona vinculada ---
const selectedPersonId = ref(null);
const selectedEmployee = ref(null); // empleado seleccionado (para tarjeta de solo lectura)
const employeeSearch = ref('');
const showEmployeeDropdown = ref(false);

// True cuando el usuario tiene o tendrá una persona de RRHH vinculada
const hasLinkedPerson = computed(() => {
    if (props.isEditing) return !!(props.user?.person_id);
    return !!selectedPersonId.value;
});

// Datos de la persona vinculada para mostrar en la tarjeta de solo lectura
const linkedPersonData = computed(() => {
    if (props.isEditing && props.user?.person) {
        return props.user.person;
    }
    if (selectedEmployee.value) {
        return {
            nombres:   selectedEmployee.value.nombres,
            apellidos: selectedEmployee.value.apellidos,
            dni:       selectedEmployee.value.dni,
            email:     selectedEmployee.value.correo,
            telefono:  selectedEmployee.value.telefono,
        };
    }
    return null;
});

const selectedModules = ref([]);
const selectedTabs = ref({}); // { moduleKey: ['tab1', 'tab2', ...] }
const useCustomModules = ref(false);

const toggleCustomModules = () => {
    useCustomModules.value = !useCustomModules.value;
    if (!useCustomModules.value) {
        selectedModules.value = [];
        selectedTabs.value = {};
    }
};

const toggleModule = (modKey) => {
    const idx = selectedModules.value.indexOf(modKey);
    if (idx > -1) {
        selectedModules.value.splice(idx, 1);
        const copy = { ...selectedTabs.value };
        delete copy[modKey];
        selectedTabs.value = copy;
    } else {
        selectedModules.value.push(modKey);
        // Auto-select all tabs for this module
        const allTabKeys = (MODULE_TABS[modKey] || []).map(t => t.key);
        selectedTabs.value = { ...selectedTabs.value, [modKey]: [...allTabKeys] };
    }
};

const isTabSelected = (modKey, tabKey) => (selectedTabs.value[modKey] || []).includes(tabKey);

const toggleTab = (modKey, tabKey) => {
    const current = [...(selectedTabs.value[modKey] || [])];
    const idx = current.indexOf(tabKey);
    if (idx > -1) current.splice(idx, 1);
    else current.push(tabKey);
    selectedTabs.value = { ...selectedTabs.value, [modKey]: current };
};

const allTabsSelected = (modKey) => {
    const all = (MODULE_TABS[modKey] || []).map(t => t.key);
    return all.length > 0 && all.every(k => isTabSelected(modKey, k));
};

const toggleAllTabs = (modKey) => {
    const all = (MODULE_TABS[modKey] || []).map(t => t.key);
    selectedTabs.value = { ...selectedTabs.value, [modKey]: allTabsSelected(modKey) ? [] : [...all] };
};

// Validation Schema
// Computed so that hasLinkedPerson and isEditing are evaluated reactively.
// VeeValidate 4 supports a reactive (computed) validationSchema; passing context
// via useForm options is NOT supported and was causing the schema conditions to
// never fire, which silently blocked form submission.
const userSchema = computed(() => toTypedSchema(
    yup.object({
        dni: hasLinkedPerson.value
            ? yup.string().transform(() => undefined).nullable()
            : yup.string().required('El DNI es obligatorio').matches(/^\d{8}$/, 'El DNI debe tener 8 dígitos'),
        titulo: yup.string().transform((value) => value || null).nullable(),
        name: hasLinkedPerson.value
            ? yup.string().transform(() => undefined).nullable()
            : yup.string().required('El nombre es obligatorio'),
        apellidos: hasLinkedPerson.value
            ? yup.string().transform(() => undefined).nullable()
            : yup.string().required('Los apellidos son obligatorios'),
        email: yup.string()
            .transform((value) => value || null)
            .nullable()
            .email('El email no es válido'),
        telefono: hasLinkedPerson.value
            ? yup.string().transform(() => undefined).nullable()
            : yup.string().transform((v) => v || null).nullable()
                .test('length', 'El teléfono debe tener exactamente 9 dígitos', (v) => !v || /^\d{9}$/.test(v)),
        cargo: yup.string().transform((value) => value || null).nullable(),
        area: yup.string().transform((value) => value || null).nullable(),
        rol_id: yup.string().required('El rol es obligatorio'),
        is_active: yup.boolean(),
        password: props.isEditing
            ? yup.string().transform((value) => value || null).nullable()
            : yup.string().required('La contraseña es obligatoria').min(8, 'La contraseña debe tener al menos 8 caracteres'),
        password_confirmation: props.isEditing
            ? yup.string().transform((value) => value || null).nullable()
            : yup.string().required('Debe confirmar la contraseña').oneOf([yup.ref('password')], 'Las contraseñas no coinciden'),
    })
));

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

// Aplicar preset de módulos cuando el usuario selecciona un rol manualmente (solo al crear)
const applyRolePreset = (event) => {
    if (props.isEditing) return;
    const newRolId = event.target.value;
    const preset = ROLE_MODULE_PRESETS[newRolId];
    if (preset) {
        useCustomModules.value = true;
        selectedModules.value = [...preset];
        const tabs = {};
        for (const modKey of preset) {
            const allTabKeys = (MODULE_TABS[modKey] || []).map(t => t.key);
            if (allTabKeys.length > 0) tabs[modKey] = [...allTabKeys];
        }
        selectedTabs.value = tabs;
    } else {
        useCustomModules.value = false;
        selectedModules.value = [];
        selectedTabs.value = {};
    }
};

watch(() => props.user, (newUser) => {
    if (newUser) {
        // Restaurar persona vinculada al editar
        selectedPersonId.value = newUser.person_id || null;
        selectedEmployee.value = null;

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
        if (newUser.modulos_json && newUser.modulos_json.length > 0) {
            useCustomModules.value = true;
            selectedModules.value = [...newUser.modulos_json];
            const tabs = {};
            for (const modKey of newUser.modulos_json) {
                const allTabKeys = (MODULE_TABS[modKey] || []).map(t => t.key);
                tabs[modKey] = newUser.tabs_json?.[modKey]
                    ? [...newUser.tabs_json[modKey]]
                    : [...allTabKeys];
            }
            selectedTabs.value = tabs;
        } else {
            useCustomModules.value = false;
            selectedModules.value = [];
            selectedTabs.value = {};
        }
    } else {
        resetForm();
        useCustomModules.value = false;
        selectedModules.value = [];
        selectedTabs.value = {};
        selectedPersonId.value = null;
        selectedEmployee.value = null;
        employeeSearch.value = '';
    }
}, { immediate: true });

const modalSubtitle = computed(() => {
    return props.isEditing
        ? 'Actualice la información del usuario'
        : 'Complete los datos del nuevo usuario del sistema';
});

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
    selectedPersonId.value = emp.person_id || null;
    selectedEmployee.value = emp;

    // Solo precargamos los campos del usuario (cargo, área) que pueden diferir
    // de los datos personales que ahora vienen de RRHH sin duplicarse
    setValues({
        titulo: titulo.value,
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
    let tabs_json = null;
    if (useCustomModules.value && selectedModules.value.length > 0) {
        const restricted = {};
        for (const modKey of selectedModules.value) {
            const allTabKeys = (MODULE_TABS[modKey] || []).map(t => t.key);
            const selected   = selectedTabs.value[modKey] || allTabKeys;
            if (allTabKeys.length > 0 && selected.length < allTabKeys.length) {
                restricted[modKey] = selected;
            }
        }
        if (Object.keys(restricted).length > 0) tabs_json = restricted;
    }

    const modulos_json = useCustomModules.value && selectedModules.value.length > 0
        ? selectedModules.value
        : null;

    // Payload base (datos que siempre se envían)
    const payload = {
        person_id:    selectedPersonId.value || null,
        titulo:       values.titulo   || null,
        email:        values.email    || null,
        cargo:        values.cargo    || null,
        area:         values.area     || null,
        rol_id:       values.rol_id,
        is_active:    values.is_active,
        modulos_json,
        tabs_json,
    };

    // Contraseña (solo en creación)
    if (!props.isEditing) {
        payload.password              = values.password;
        payload.password_confirmation = values.password_confirmation;
    }

    // Datos personales solo cuando NO hay persona vinculada
    if (!hasLinkedPerson.value) {
        payload.dni      = values.dni;
        payload.name     = values.name;
        payload.apellidos= values.apellidos;
        payload.telefono = values.telefono || null;
    }

    emit('submit', payload);
});
</script>
