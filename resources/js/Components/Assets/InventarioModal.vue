<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import {
    X,
    ClipboardList,
    PlusCircle,
    Pencil,
    CalendarDays,
    UserMinus,
    Zap,
    Search,
    ChevronDown,
    Check,
    AlertCircle,
    Loader2,
    RotateCcw,
    Info,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

// ===== PROPS & EMITS =====
const props = defineProps({
    employees: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['saved', 'close']);

// ===== STATE =====
const isOpen = ref(false);
const editingInv = ref(null);
const isSaving = ref(false);
const formErrors = ref({});
const isNameManuallyEdited = ref(false);

const currentYear = new Date().getFullYear();

const emptyForm = () => ({
    anio: currentYear,
    tipo: 'ANUAL',
    nombre: `Inventario Anual ${currentYear}`,
    descripcion: '',
    fecha_inicio: new Date().toISOString().split('T')[0],
    fecha_fin: '',
    estado: 'PENDIENTE',
    responsable_saliente_id: '',
});

const form = ref(emptyForm());

// ===== OPTIONS =====
const tipoOpciones = [
    {
        value: 'ANUAL',
        label: 'Anual',
        desc: 'Regular y periódico',
        icon: CalendarDays,
        color: 'purple',
    },
    {
        value: 'ROTACION',
        label: 'Rotación',
        desc: 'Entrega de cargo',
        icon: UserMinus,
        color: 'orange',
    },
    {
        value: 'EXTRAORDINARIO',
        label: 'Extraordinario',
        desc: 'Auditoría / Contingencia',
        icon: Zap,
        color: 'indigo',
    },
];

// ===== EMPLOYEE AUTOCOMPLETE (ROTACION) =====
const empQuery = ref('');
const showEmpDropdown = ref(false);
const selectedEmployee = ref(null);
const empDropdownContainer = ref(null);

const filteredEmployees = computed(() => {
    const q = empQuery.value.trim();
    if (!q) return props.employees.slice(0, 15);

    const norm = q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const terms = norm.split(' ').filter(t => t.length > 0);

    return props.employees.filter(emp => {
        const name = (emp.nombre_completo || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const dni = emp.dni || '';
        if (dni.includes(q)) return true;
        return terms.every(t => name.includes(t));
    }).slice(0, 15);
});

const selectEmployee = (emp) => {
    selectedEmployee.value = emp;
    form.value.responsable_saliente_id = emp.id;
    empQuery.value = emp.nombre_completo;
    showEmpDropdown.value = false;
    delete formErrors.value.responsable_saliente_id;

    if (!isNameManuallyEdited.value) {
        form.value.nombre = generateSuggestedName();
    }
};

const clearEmployeeSelection = () => {
    selectedEmployee.value = null;
    form.value.responsable_saliente_id = '';
    empQuery.value = '';
    if (!isNameManuallyEdited.value) {
        form.value.nombre = generateSuggestedName();
    }
};

const onEmpInput = () => {
    showEmpDropdown.value = true;
    selectedEmployee.value = null;
    form.value.responsable_saliente_id = '';
};

// ===== DYNAMIC NAME GENERATION =====
const generateSuggestedName = () => {
    const anio = form.value.anio || currentYear;
    switch (form.value.tipo) {
        case 'ROTACION':
            if (selectedEmployee.value?.nombre_completo) {
                return `Inventario Rotación - ${selectedEmployee.value.nombre_completo} (${anio})`;
            }
            return `Inventario por Rotación de Personal ${anio}`;
        case 'EXTRAORDINARIO':
            return `Inventario Extraordinario ${anio}`;
        case 'ANUAL':
        default:
            return `Inventario Anual ${anio}`;
    }
};

const onTipoChange = (newTipo) => {
    form.value.tipo = newTipo;
    delete formErrors.value.tipo;

    if (newTipo !== 'ROTACION') {
        clearEmployeeSelection();
    }

    if (!isNameManuallyEdited.value) {
        form.value.nombre = generateSuggestedName();
    }
};

const onAnioChange = () => {
    delete formErrors.value.anio;
    if (!isNameManuallyEdited.value) {
        form.value.nombre = generateSuggestedName();
    }
};

const resetToSuggestedName = () => {
    isNameManuallyEdited.value = false;
    form.value.nombre = generateSuggestedName();
};

const onNameInput = () => {
    isNameManuallyEdited.value = true;
    delete formErrors.value.nombre;
};

// ===== WATCH STATUS CHANGES IN EDIT MODE =====
watch(() => form.value.estado, (newEstado) => {
    if (newEstado === 'CERRADO' && !form.value.fecha_fin) {
        // Sugerir fecha actual al cerrar
        form.value.fecha_fin = new Date().toISOString().split('T')[0];
    }
});

// ===== OPEN / CLOSE MODAL =====
const open = (inv = null) => {
    formErrors.value = {};

    if (inv) {
        // Edit mode
        editingInv.value = inv;
        const normalizeDate = (d) => (d ? String(d).split('T')[0].split(' ')[0] : '');

        form.value = {
            anio: inv.anio || currentYear,
            tipo: inv.tipo || 'ANUAL',
            nombre: inv.nombre || '',
            descripcion: inv.descripcion || '',
            fecha_inicio: normalizeDate(inv.fecha_inicio),
            fecha_fin: normalizeDate(inv.fecha_fin),
            estado: inv.estado || 'PENDIENTE',
            responsable_saliente_id: inv.responsable_saliente_id || '',
        };

        // Match employee
        if (inv.responsable_saliente_id) {
            const found = props.employees.find(e => e.id === inv.responsable_saliente_id);
            if (found) {
                selectedEmployee.value = found;
                empQuery.value = found.nombre_completo;
            } else if (inv.responsable_saliente) {
                const p = inv.responsable_saliente.person;
                const name = p ? `${p.nombres || ''} ${p.apellidos || ''}`.trim() : (inv.responsable_saliente.nombre_completo || '');
                selectedEmployee.value = {
                    id: inv.responsable_saliente.id,
                    nombre_completo: name,
                    dni: inv.responsable_saliente.dni || '',
                };
                empQuery.value = name;
            }
        } else {
            selectedEmployee.value = null;
            empQuery.value = '';
        }

        isNameManuallyEdited.value = true;
    } else {
        // Create mode
        editingInv.value = null;
        form.value = emptyForm();
        selectedEmployee.value = null;
        empQuery.value = '';
        isNameManuallyEdited.value = false;
    }

    isOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const close = () => {
    isOpen.value = false;
    formErrors.value = {};
    showEmpDropdown.value = false;
    document.body.style.overflow = '';
    emit('close');
};

// Click outside dropdown handler
const handleClickOutside = (e) => {
    if (empDropdownContainer.value && !empDropdownContainer.value.contains(e.target)) {
        showEmpDropdown.value = false;
    }
};

const handleKeyDown = (e) => {
    if (e.key === 'Escape' && isOpen.value && !isSaving.value) {
        close();
    }
};

onMounted(() => {
    window.addEventListener('click', handleClickOutside);
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('click', handleClickOutside);
    window.removeEventListener('keydown', handleKeyDown);
    document.body.style.overflow = '';
});

// ===== VALIDATION & SAVE =====
const validateClient = () => {
    const errs = {};

    if (!form.value.anio || isNaN(form.value.anio)) {
        errs.anio = 'El año es obligatorio.';
    } else if (form.value.anio < 2000 || form.value.anio > 2100) {
        errs.anio = 'El año debe estar entre 2000 y 2100.';
    }

    if (!form.value.tipo) {
        errs.tipo = 'Seleccione el tipo de inventario.';
    }

    if (!form.value.nombre || !form.value.nombre.trim()) {
        errs.nombre = 'El nombre del inventario es obligatorio.';
    } else if (form.value.nombre.trim().length > 200) {
        errs.nombre = 'El nombre no puede exceder los 200 caracteres.';
    }

    if (!form.value.fecha_inicio) {
        errs.fecha_inicio = 'La fecha de inicio es obligatoria.';
    }

    if (form.value.fecha_inicio && form.value.fecha_fin) {
        if (form.value.fecha_fin < form.value.fecha_inicio) {
            errs.fecha_fin = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
        }
    }

    if (form.value.tipo === 'ROTACION' && !form.value.responsable_saliente_id) {
        errs.responsable_saliente_id = 'Debe seleccionar al empleado saliente para un inventario de rotación.';
    }

    formErrors.value = errs;
    return Object.keys(errs).length === 0;
};

const save = async () => {
    if (!validateClient()) return;

    isSaving.value = true;
    try {
        const payload = {
            anio: Number(form.value.anio),
            tipo: form.value.tipo,
            nombre: form.value.nombre.trim(),
            fecha_inicio: form.value.fecha_inicio,
            fecha_fin: form.value.fecha_fin || null,
            descripcion: form.value.descripcion ? form.value.descripcion.trim() : null,
            responsable_saliente_id: form.value.tipo === 'ROTACION' && form.value.responsable_saliente_id
                ? form.value.responsable_saliente_id
                : null,
        };

        if (editingInv.value) {
            payload.estado = form.value.estado;
        }

        let res;
        if (editingInv.value) {
            res = await axios.put(`/assets/inventarios/${editingInv.value.id}`, payload);
        } else {
            res = await axios.post('/assets/inventarios', payload);
        }

        Swal.fire({
            icon: 'success',
            title: editingInv.value ? 'Inventario actualizado' : 'Inventario creado',
            text: `El inventario "${payload.nombre}" ha sido guardado exitosamente.`,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });

        const isEditMode = !!editingInv.value;
        const savedData = res.data;
        close();
        emit('saved', savedData, isEditMode);
    } catch (e) {
        console.error('Error al guardar inventario:', e);
        if (e.response?.status === 422 && e.response?.data?.errors) {
            const serverErrors = {};
            for (const [key, msgs] of Object.entries(e.response.data.errors)) {
                serverErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
            }
            formErrors.value = serverErrors;
            Swal.fire({
                icon: 'warning',
                title: 'Verifique los datos ingresados',
                text: 'Hay inconsistencias o campos requeridos pendientes en el formulario.',
                confirmButtonColor: '#7c3aed',
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error al guardar',
                text: e.response?.data?.message || 'Ocurrió un error inesperado al procesar la solicitud.',
                confirmButtonColor: '#7c3aed',
            });
        }
    } finally {
        isSaving.value = false;
    }
};

defineExpose({
    open,
    close,
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
                <!-- Backdrop -->
                <div
                    class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"
                    @click="close"
                ></div>

                <!-- Modal Container -->
                <Transition
                    enter-active-class="transition-all duration-300 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-200 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div
                        class="relative bg-white rounded-2xl shadow-2xl w-full max-w-xl my-8 overflow-hidden z-10 border border-slate-100 flex flex-col max-h-[92vh]"
                    >
                        <!-- Header -->
                        <div class="flex items-center justify-between px-6 py-5 bg-gradient-to-r from-purple-600 via-indigo-600 to-indigo-700 text-white shadow-md flex-shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center backdrop-blur-sm text-white border border-white/20 shadow-inner">
                                    <Pencil v-if="editingInv" class="w-5 h-5" />
                                    <ClipboardList v-else class="w-5 h-5" />
                                </div>
                                <div>
                                    <h2 class="text-lg font-bold text-white leading-tight">
                                        {{ editingInv ? 'Editar Inventario' : 'Nuevo Inventario' }}
                                    </h2>
                                    <p class="text-xs text-purple-100/90 mt-0.5">
                                        {{ editingInv ? 'Modifique los parámetros y estado del inventario' : 'Configure los datos del nuevo proceso de inventario patrimonial' }}
                                    </p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="close"
                                class="p-2 rounded-xl text-white/75 hover:text-white hover:bg-white/10 transition-colors focus:outline-none focus:ring-2 focus:ring-white/40"
                                title="Cerrar modal"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Form Body -->
                        <form @submit.prevent="save" class="p-6 overflow-y-auto space-y-5 flex-1 custom-scrollbar">
                            <!-- Tipo de Inventario (Tarjetas de Selección) -->
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Tipo de Inventario <span class="text-red-500">*</span>
                                    </label>
                                    <span class="text-[11px] text-slate-400 font-medium">Define el alcance del proceso</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2.5">
                                    <button
                                        v-for="opt in tipoOpciones"
                                        :key="opt.value"
                                        type="button"
                                        @click="onTipoChange(opt.value)"
                                        class="flex flex-col items-center justify-center p-3 rounded-xl border-2 transition-all text-center relative group"
                                        :class="form.tipo === opt.value
                                            ? 'border-purple-600 bg-purple-50/80 text-purple-900 shadow-sm ring-1 ring-purple-600/20'
                                            : 'border-slate-200 hover:border-slate-300 bg-white text-slate-600 hover:bg-slate-50/60'"
                                    >
                                        <div
                                            class="w-8 h-8 rounded-lg flex items-center justify-center mb-1.5 transition-colors"
                                            :class="form.tipo === opt.value ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-500 group-hover:bg-slate-200'"
                                        >
                                            <component :is="opt.icon" class="w-4 h-4" />
                                        </div>
                                        <span class="text-xs font-bold leading-none mb-1" :class="form.tipo === opt.value ? 'text-purple-900' : 'text-slate-700'">
                                            {{ opt.label }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 leading-tight">
                                            {{ opt.desc }}
                                        </span>
                                        <div
                                            v-if="form.tipo === opt.value"
                                            class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-purple-600"
                                        ></div>
                                    </button>
                                </div>
                                <p v-if="formErrors.tipo" class="text-xs text-red-600 font-medium mt-1.5 flex items-center gap-1">
                                    <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.tipo }}
                                </p>
                            </div>

                            <!-- Selector de Empleado Saliente (Solo ROTACION) -->
                            <div
                                v-if="form.tipo === 'ROTACION'"
                                class="p-4 rounded-xl border border-orange-200 bg-orange-50/60 space-y-3 transition-all"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <label class="block text-xs font-bold text-orange-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <UserMinus class="w-4 h-4 text-orange-600" />
                                            Empleado Saliente / Entrega de Cargo <span class="text-red-500">*</span>
                                        </label>
                                        <p class="text-xs text-orange-700/80 mt-0.5">
                                            Seleccione el servidor cuyos bienes asignados serán inventariados y reasignados.
                                        </p>
                                    </div>
                                </div>

                                <!-- Buscador y dropdown -->
                                <div class="relative" ref="empDropdownContainer">
                                    <div class="relative">
                                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                        <input
                                            type="text"
                                            v-model="empQuery"
                                            @focus="showEmpDropdown = true"
                                            @input="onEmpInput"
                                            placeholder="Buscar por DNI o apellido/nombre del empleado..."
                                            class="w-full pl-9 pr-9 py-2.5 bg-white border rounded-xl focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-sm transition-all shadow-sm"
                                            :class="formErrors.responsable_saliente_id ? 'border-red-400 ring-1 ring-red-400/20' : 'border-orange-200'"
                                        />
                                        <button
                                            v-if="selectedEmployee"
                                            type="button"
                                            @click="clearEmployeeSelection"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-red-500 transition-colors p-0.5 rounded-full hover:bg-slate-100"
                                            title="Quitar empleado seleccionado"
                                        >
                                            <X class="w-4 h-4" />
                                        </button>
                                        <ChevronDown
                                            v-else
                                            class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none"
                                        />
                                    </div>

                                    <!-- Dropdown Suggestions -->
                                    <div
                                        v-if="showEmpDropdown && filteredEmployees.length > 0"
                                        class="absolute z-50 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl max-h-52 overflow-y-auto divide-y divide-slate-100 custom-scrollbar"
                                    >
                                        <button
                                            type="button"
                                            v-for="emp in filteredEmployees"
                                            :key="emp.id"
                                            @click="selectEmployee(emp)"
                                            class="w-full text-left px-4 py-2.5 hover:bg-orange-50/80 transition-colors flex items-center justify-between group"
                                        >
                                            <div class="min-w-0 pr-2">
                                                <p class="font-bold text-slate-800 text-sm truncate group-hover:text-orange-800">
                                                    {{ emp.nombre_completo }}
                                                </p>
                                                <p class="text-xs text-slate-500 flex items-center gap-1.5 mt-0.5">
                                                    <span class="font-mono bg-slate-100 px-1.5 py-0.2 rounded text-[11px] text-slate-600">
                                                        DNI: {{ emp.dni || 'Sin DNI' }}
                                                    </span>
                                                </p>
                                            </div>
                                            <Check
                                                v-if="selectedEmployee?.id === emp.id"
                                                class="w-4 h-4 text-orange-600 shrink-0 font-bold"
                                            />
                                        </button>
                                    </div>

                                    <div
                                        v-if="showEmpDropdown && empQuery.length >= 2 && filteredEmployees.length === 0"
                                        class="absolute z-50 w-full mt-1.5 bg-white border border-slate-200 rounded-xl shadow-xl p-3 text-center text-xs text-slate-500"
                                    >
                                        No se encontraron empleados coincidentes con "{{ empQuery }}".
                                    </div>
                                </div>

                                <!-- Tarjeta de empleado seleccionado -->
                                <div
                                    v-if="selectedEmployee"
                                    class="flex items-center gap-3 p-2.5 bg-white border border-orange-200 rounded-xl shadow-sm"
                                >
                                    <div class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ (selectedEmployee.nombre_completo || '?').charAt(0) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-slate-800 truncate">
                                            {{ selectedEmployee.nombre_completo }}
                                        </p>
                                        <p class="text-[11px] text-slate-500 font-mono">
                                            DNI: {{ selectedEmployee.dni || '—' }}
                                        </p>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-700 shrink-0">
                                        Saliente
                                    </span>
                                </div>

                                <p v-if="formErrors.responsable_saliente_id" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                    <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.responsable_saliente_id }}
                                </p>
                            </div>

                            <!-- Fila: Año y Estado -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Año -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Año Fiscal <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="form.anio"
                                        type="number"
                                        min="2000"
                                        max="2100"
                                        required
                                        @input="onAnioChange"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold transition-all shadow-sm"
                                        :class="formErrors.anio ? 'border-red-400 ring-1 ring-red-400/20' : 'border-slate-200'"
                                    />
                                    <p v-if="formErrors.anio" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.anio }}
                                    </p>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Estado del Inventario
                                    </label>

                                    <!-- Modo Edición: Selector de Estado -->
                                    <select
                                        v-if="editingInv"
                                        v-model="form.estado"
                                        class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-semibold bg-white transition-all shadow-sm"
                                        :class="formErrors.estado ? 'border-red-400' : 'border-slate-200'"
                                    >
                                        <option value="PENDIENTE">🔵 Pendiente (Configuración)</option>
                                        <option value="EN_PROCESO">🟡 En Proceso (Verificación abierta)</option>
                                        <option value="CERRADO">🟢 Cerrado (Concluido / Solo lectura)</option>
                                    </select>

                                    <!-- Modo Creación: Indicador Informativo -->
                                    <div
                                        v-else
                                        class="px-4 py-2.5 border border-purple-100 rounded-xl bg-purple-50/50 flex items-center justify-between text-sm"
                                    >
                                        <span class="inline-flex items-center gap-1.5 font-bold text-xs text-purple-800">
                                            <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                                            PENDIENTE (Inicial)
                                        </span>
                                        <span class="text-[11px] text-purple-600/80">Se activará al iniciar</span>
                                    </div>
                                    <p v-if="formErrors.estado" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.estado }}
                                    </p>
                                </div>
                            </div>

                            <!-- Nombre del Inventario -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                                        Nombre Descriptivo <span class="text-red-500">*</span>
                                    </label>
                                    <button
                                        v-if="isNameManuallyEdited"
                                        type="button"
                                        @click="resetToSuggestedName"
                                        class="text-[11px] font-semibold text-purple-600 hover:text-purple-800 transition-colors flex items-center gap-1"
                                        title="Restablecer al nombre automático generado según año y tipo"
                                    >
                                        <RotateCcw class="w-3 h-3" /> Auto-generar
                                    </button>
                                </div>
                                <input
                                    v-model="form.nombre"
                                    type="text"
                                    required
                                    maxlength="200"
                                    @input="onNameInput"
                                    :placeholder="`Ej: ${generateSuggestedName()}`"
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-medium transition-all shadow-sm"
                                    :class="formErrors.nombre ? 'border-red-400 ring-1 ring-red-400/20' : 'border-slate-200'"
                                />
                                <div class="flex items-center justify-between mt-1">
                                    <p v-if="formErrors.nombre" class="text-xs text-red-600 font-medium flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.nombre }}
                                    </p>
                                    <span v-else class="text-[11px] text-slate-400">
                                        Nombre oficial con el que se identificará este proceso
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-mono">
                                        {{ (form.nombre || '').length }}/200
                                    </span>
                                </div>
                            </div>

                            <!-- Fila: Fecha Inicio y Fecha Fin -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                        Fecha de Inicio <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.fecha_inicio"
                                        type="date"
                                        required
                                        @input="delete formErrors.fecha_inicio"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-medium transition-all shadow-sm"
                                        :class="formErrors.fecha_inicio ? 'border-red-400 ring-1 ring-red-400/20' : 'border-slate-200'"
                                    />
                                    <p v-if="formErrors.fecha_inicio" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.fecha_inicio }}
                                    </p>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1.5">
                                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                                            Fecha de Cierre
                                        </label>
                                        <span class="text-[11px] text-slate-400">Opcional</span>
                                    </div>
                                    <input
                                        v-model="form.fecha_fin"
                                        type="date"
                                        :min="form.fecha_inicio"
                                        @input="delete formErrors.fecha_fin"
                                        class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm font-medium transition-all shadow-sm"
                                        :class="formErrors.fecha_fin ? 'border-red-400 ring-1 ring-red-400/20' : 'border-slate-200'"
                                    />
                                    <p v-if="formErrors.fecha_fin" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.fecha_fin }}
                                    </p>
                                </div>
                            </div>

                            <!-- Alerta al cerrar inventario -->
                            <div
                                v-if="editingInv && form.estado === 'CERRADO'"
                                class="p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-2.5 text-xs text-amber-800"
                            >
                                <Info class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                                <div>
                                    <span class="font-bold">Advertencia de Cierre:</span> Al marcar el inventario como <b>Cerrado</b>, no se podrán agregar ni modificar verificaciones de bienes a menos que sea reabierto.
                                </div>
                            </div>

                            <!-- Descripción / Observaciones -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                    Observaciones / Justificación
                                </label>
                                <textarea
                                    v-model="form.descripcion"
                                    rows="2"
                                    placeholder="Ej: Conforme a la Resolución Directoral N°... o detalles del equipo auditor..."
                                    class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm transition-all resize-none shadow-sm"
                                    :class="formErrors.descripcion ? 'border-red-400 ring-1 ring-red-400/20' : 'border-slate-200'"
                                ></textarea>
                                <p v-if="formErrors.descripcion" class="text-xs text-red-600 font-medium mt-1 flex items-center gap-1">
                                    <AlertCircle class="w-3.5 h-3.5" /> {{ formErrors.descripcion }}
                                </p>
                            </div>

                            <!-- Footer Buttons -->
                            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100 flex-shrink-0">
                                <button
                                    type="button"
                                    @click="close"
                                    :disabled="isSaving"
                                    class="px-5 py-2.5 text-sm font-semibold text-slate-600 border border-slate-200 rounded-xl hover:bg-slate-100 hover:text-slate-800 transition-all disabled:opacity-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    type="submit"
                                    :disabled="isSaving"
                                    class="inline-flex items-center px-6 py-2.5 text-sm font-bold rounded-xl shadow-lg shadow-purple-600/20 text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 focus:ring-4 focus:ring-purple-300 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Loader2 v-if="isSaving" class="w-4 h-4 mr-2 animate-spin" />
                                    <PlusCircle v-else-if="!editingInv" class="w-4 h-4 mr-2" />
                                    <Check v-else class="w-4 h-4 mr-2" />
                                    {{ isSaving ? 'Guardando...' : (editingInv ? 'Guardar Cambios' : 'Crear Inventario') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}
</style>
