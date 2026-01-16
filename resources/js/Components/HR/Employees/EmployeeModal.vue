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
                <form @submit.prevent="handleSubmit"
                    class="p-6 space-y-6 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- DNI -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                DNI <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.dni" type="text" maxlength="8" required :disabled="isEditing"
                                placeholder="Ingrese DNI"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 disabled:bg-slate-50 disabled:text-slate-500" />
                        </div>

                        <!-- Tipo de Contrato -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo de Contrato <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.tipo_contrato" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="Nombrado">Nombrado</option>
                                <option value="CAS">CAS</option>
                                <option value="Locador">Locador</option>
                                <option value="Practicante">Practicante</option>
                            </select>
                        </div>

                        <!-- Nombres -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombres <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.nombres" type="text" required placeholder="Ingrese nombres"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Apellidos -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Apellidos <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.apellidos" type="text" required placeholder="Ingrese apellidos"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Fecha Nacimiento -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha Nacimiento</label>
                            <input v-model="form.fecha_nacimiento" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Género -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Género</label>
                            <select v-model="form.genero"
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
                            <select v-model="form.cargo" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="">Seleccione cargo...</option>
                                <option v-for="pos in positions" :key="pos.id" :value="pos.nombre">
                                    {{ pos.nombre }}
                                </option>
                                <option v-if="positions.length === 0" disabled>No hay cargos registrados</option>
                            </select>
                        </div>

                        <!-- Área (SELECT) -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Área / Oficina <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.area" required
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 bg-white">
                                <option value="">Seleccione área...</option>
                                <option v-for="area in areas" :key="area.id" :value="area.nombre">
                                    {{ area.nombre }}
                                </option>
                                <option v-if="areas.length === 0" disabled>No hay áreas registradas</option>
                            </select>
                        </div>

                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono</label>
                            <input v-model="form.telefono" type="text" placeholder="999 999 999"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Correo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Correo Institucional</label>
                            <input v-model="form.correo" type="email" placeholder="usuario@drehco.gob.pe"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Fecha Ingreso -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de Ingreso</label>
                            <input v-model="form.fecha_ingreso" type="date"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                        </div>

                        <!-- Estado (Solo edición) -->
                        <div v-if="isEditing">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Estado del Personal</label>
                            <select v-model="form.estado"
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
                        <input v-model="form.direccion" type="text" placeholder="Jr. / Av. ..."
                            class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" />
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="3" placeholder="Información adicional relevante..."
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
import { ref, watch } from 'vue';
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
