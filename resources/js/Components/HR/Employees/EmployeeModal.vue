<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 z-10 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-slate-900">
                        {{ isEditing ? 'Editar Empleado' : 'Registrar Nuevo Empleado' }}
                    </h3>
                    <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600">
                        <X class="w-6 h-6" />
                    </button>
                </div>
                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">DNI *</label>
                            <input v-model="form.dni" type="text" maxlength="8" required :disabled="isEditing"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 disabled:bg-slate-100" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Tipo Contrato</label>
                            <select v-model="form.tipo_contrato"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                                <option value="Nombrado">Nombrado</option>
                                <option value="CAS">CAS</option>
                                <option value="Locador">Locador</option>
                                <option value="Practicante">Practicante</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Nombres *</label>
                            <input v-model="form.nombres" type="text" required
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Apellidos *</label>
                            <input v-model="form.apellidos" type="text" required
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Nacimiento</label>
                            <input v-model="form.fecha_nacimiento" type="date"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Género</label>
                            <select v-model="form.genero"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                                <option value="">Seleccionar...</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Cargo</label>
                            <input v-model="form.cargo" type="text"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Área</label>
                            <input v-model="form.area" type="text"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Teléfono</label>
                            <input v-model="form.telefono" type="text"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Correo</label>
                            <input v-model="form.correo" type="email"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Dirección</label>
                        <input v-model="form.direccion" type="text"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Fecha de Ingreso</label>
                            <input v-model="form.fecha_ingreso" type="date"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div v-if="isEditing">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Estado</label>
                            <select v-model="form.estado"
                                class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500">
                                <option value="ACTIVO">Activo</option>
                                <option value="INACTIVO">Inactivo</option>
                                <option value="LICENCIA">Licencia</option>
                                <option value="VACACIONES">Vacaciones</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="2"
                            class="w-full px-4 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500"></textarea>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" @click="$emit('close')"
                            class="flex-1 px-4 py-3 border border-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="flex-1 px-4 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-xl hover:from-emerald-700 hover:to-teal-700 disabled:opacity-50">
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Guardar Empleado') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { X } from 'lucide-vue-next';

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
    }
});

const emit = defineEmits(['close', 'submit']);

const getDefaultForm = () => ({
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
    observaciones: ''
});

const form = ref(getDefaultForm());

watch(() => props.employee, (emp) => {
    if (emp && props.isEditing) {
        form.value = {
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
            observaciones: emp.observaciones || ''
        };
    } else {
        form.value = getDefaultForm();
    }
}, { immediate: true });

const handleSubmit = () => {
    emit('submit', form.value);
};
</script>
