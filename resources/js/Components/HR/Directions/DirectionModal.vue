<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Building2 class="w-6 h-6" />
                            {{ isEditing ? 'Editar Dirección' : 'Registrar Nueva Dirección' }}
                        </h3>
                        <p class="text-blue-50 text-sm mt-1">Configure las direcciones o áreas de la institución</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-6">
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Nombre de la Dirección <span class="text-red-500">*</span>
                                </label>
                                <input v-model="nombre" v-bind="nombreProps" type="text"
                                    placeholder="Ej. DIRECCIÓN REGIONAL DE EDUCACIÓN"
                                    @input="nombre = $event.target.value.toUpperCase()"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                    :class="formErrors.nombre ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                                <p v-if="formErrors.nombre" class="mt-1 text-sm text-red-600">{{ formErrors.nombre }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Abreviación</label>
                                <input v-model="abreviacion" v-bind="abreviacionProps" type="text" placeholder="Ej. DRE"
                                    @input="abreviacion = $event.target.value.toUpperCase()"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                    :class="formErrors.abreviacion ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                                <p v-if="formErrors.abreviacion" class="mt-1 text-sm text-red-600">{{
                                    formErrors.abreviacion }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Código Interno</label>
                                <input v-model="codigo" v-bind="codigoProps" type="text" placeholder="Ej. DIR-01"
                                    @input="codigo = $event.target.value.toUpperCase()"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                    :class="formErrors.codigo ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                                <p v-if="formErrors.codigo" class="mt-1 text-sm text-red-600">{{ formErrors.codigo }}
                                </p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono / Anexo</label>
                                <input v-model="telefono_interno" v-bind="telefonoInternoProps" type="text"
                                    placeholder="Ej. Anexo 100"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                    :class="formErrors.telefono_interno ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                                <p v-if="formErrors.telefono_interno" class="mt-1 text-sm text-red-600">{{
                                    formErrors.telefono_interno }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Ubicación Física</label>
                            <div class="relative">
                                <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                                <input v-model="ubicacion" v-bind="ubicacionProps" type="text"
                                    placeholder="Ej. 1er Piso, Sede Central"
                                    class="w-full pl-10 pr-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none"
                                    :class="formErrors.ubicacion ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                            </div>
                            <p v-if="formErrors.ubicacion" class="mt-1 text-sm text-red-600">{{ formErrors.ubicacion }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Descripción</label>
                            <textarea v-model="descripcion" v-bind="descripcionProps" rows="2"
                                placeholder="Breve descripción de la dirección..."
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500 transition-all duration-200 outline-none resize-none"
                                :class="formErrors.descripcion ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'"></textarea>
                            <p v-if="formErrors.descripcion" class="mt-1 text-sm text-red-600">{{ formErrors.descripcion
                                }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Jefe Inmediato</label>
                            <EmployeeSearchSelect v-model="jefe_inmediato_id" :employees="employees" accent="indigo"
                                placeholder="Buscar jefe por nombre o DNI..." empty-label="Sin jefe asignado" />
                            <p v-if="formErrors.jefe_inmediato_id" class="mt-1 text-sm text-red-600">{{
                                formErrors.jefe_inmediato_id }}</p>
                            <p class="text-[10px] text-slate-500 mt-1 italic">
                                Aprobará las papeletas de los empleados que no tengan un jefe asignado a nivel de
                                oficina.
                            </p>
                        </div>

                        <!-- Suplentes -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                    <Users class="w-4 h-4 text-indigo-600" />
                                    Suplentes autorizados
                                </label>
                                <button type="button" @click="addSuplente"
                                    class="flex items-center gap-1 text-xs font-bold text-indigo-700 hover:text-indigo-900 transition-colors">
                                    <Plus class="w-3.5 h-3.5" /> Agregar suplente
                                </button>
                            </div>

                            <div v-if="suplentes.length === 0" class="text-xs text-slate-400 italic py-1">
                                Sin suplentes. Solo el titular podrá aprobar papeletas.
                            </div>

                            <div v-for="(s, idx) in suplentes" :key="idx"
                                class="flex flex-col gap-2 p-3 mb-2 last:mb-0 bg-white rounded-xl border border-slate-200">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 min-w-0">
                                        <EmployeeSearchSelect v-model="s.employee_id" :employees="employees" accent="indigo"
                                            placeholder="Buscar empleado por nombre o DNI..." :allow-empty="false" />
                                    </div>
                                    <button type="button" @click="removeSuplente(idx)"
                                        class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                <!-- Aviso de certificado RENIEC comentado por ahora.
                                <div v-if="suplenteSinCertificado(s.employee_id)"
                                    class="flex items-center gap-1 text-[10px] text-amber-600 font-bold">
                                    <AlertTriangle class="w-3 h-3" />
                                    Este empleado no tiene certificado RENIEC vigente; no podrá firmar aún.
                                </div>
                                -->
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Desde
                                            (opcional)</label>
                                        <input v-model="s.vigente_desde" type="date"
                                            class="w-full px-2 py-1.5 text-xs border-2 border-slate-200 rounded-lg text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] font-bold text-slate-500 mb-1">Hasta
                                            (opcional)</label>
                                        <input v-model="s.vigente_hasta" type="date"
                                            class="w-full px-2 py-1.5 text-xs border-2 border-slate-200 rounded-lg text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 outline-none" />
                                    </div>
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-1 italic">
                                Sin fechas, el suplente puede aprobar en cualquier momento. Con fechas, solo durante
                                ese periodo (encargatura).
                            </p>
                        </div>

                        <!-- Office Selection Section -->
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                            <div class="flex items-center justify-between mb-3">
                                <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                    <LayoutGrid class="w-4 h-4 text-indigo-600" />
                                    Asignar Oficinas ({{ office_ids.length }})
                                </label>
                                <div class="relative w-48">
                                    <Search
                                        class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" />
                                    <input v-model="officeQuery" type="text" placeholder="Filtrar oficinas..."
                                        class="w-full pl-8 pr-3 py-1.5 text-xs border-2 border-slate-200 rounded-lg outline-none text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 bg-white transition-all duration-200" />
                                </div>
                            </div>

                            <div class="max-h-52 overflow-y-auto space-y-2 pr-1 custom-scrollbar">
                                <div v-for="office in filteredOffices" :key="office.id"
                                    class="flex items-center justify-between p-2 rounded-xl border transition-all"
                                    :class="office_ids.includes(office.id) ? 'bg-white border-indigo-300 shadow-sm' : 'bg-white/50 border-slate-200 hover:border-slate-300'">
                                    <label class="flex items-center gap-3 cursor-pointer flex-1 py-1">
                                        <div class="relative flex items-center justify-center">
                                            <input type="checkbox" :value="office.id" v-model="office_ids"
                                                class="w-4.5 h-4.5 text-indigo-600 border-slate-300 rounded-md focus:ring-indigo-500" />
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-800">{{ office.nombre }}</span>
                                            <div v-if="office.direction_id && office.direction_id !== direction?.id"
                                                class="flex items-center gap-1 text-[10px] text-amber-600 font-bold mt-0.5">
                                                <AlertTriangle class="w-3 h-3" />
                                                Ya pertenece a: {{ office.direction?.nombre }}
                                            </div>
                                            <span v-else
                                                class="text-[10px] text-slate-400 font-medium whitespace-nowrap overflow-hidden text-ellipsis">
                                                {{ office.codigo || 'Sin código' }}
                                            </span>
                                        </div>
                                    </label>
                                    <div v-if="office_ids.includes(office.id)"
                                        class="text-indigo-600 px-2 animate-in zoom-in-50 duration-200">
                                        <CheckCircle2 class="w-4 h-4" />
                                    </div>
                                </div>
                                <div v-if="filteredOffices.length === 0"
                                    class="py-10 text-center text-slate-400 text-xs">
                                    No se encontraron oficinas
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-500 mt-3 italic">
                                * Al seleccionar una oficina que ya pertenece a otra dirección, se reasignará
                                automáticamente a esta.
                            </p>
                        </div>
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2">
                        <input type="checkbox" v-model="activo" id="direction-activo"
                            class="w-4 h-4 text-blue-600 border-slate-300 rounded focus:ring-blue-500" />
                        <label for="direction-activo" class="text-sm font-bold text-slate-700 cursor-pointer">Dirección
                            Activa</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Dirección' : 'Guardar Dirección')
                            }}
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
import { Building2, X, Loader2, Search, LayoutGrid, CheckCircle2, AlertTriangle, MapPin, Users, Plus, Trash2 } from 'lucide-vue-next';
import EmployeeSearchSelect from '@/Components/Common/EmployeeSearchSelect.vue';

const props = defineProps({
    direction: { type: Object, default: null },
    offices: { type: Array, default: () => [] },
    employees: { type: Array, default: () => [] },
    isEditing: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false }
});

const emit = defineEmits(['close', 'submit']);

const officeQuery = ref('');

const schema = toTypedSchema(yup.object({
    nombre: yup.string().required('El nombre de la dirección es obligatorio').min(3, 'Debe tener al menos 3 caracteres'),
    abreviacion: yup.string().nullable().max(20, 'Máximo 20 caracteres'),
    codigo: yup.string().nullable().max(20, 'Máximo 20 caracteres'),
    descripcion: yup.string().nullable(),
    telefono_interno: yup.string().nullable(),
    ubicacion: yup.string().nullable(),
    activo: yup.boolean(),
    office_ids: yup.array().of(yup.string()),
    jefe_inmediato_id: yup.string().nullable(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: {
        nombre: '',
        abreviacion: '',
        codigo: '',
        descripcion: '',
        telefono_interno: '',
        ubicacion: '',
        activo: true,
        office_ids: [],
        jefe_inmediato_id: ''
    }
});

const [nombre, nombreProps] = defineField('nombre');
const [abreviacion, abreviacionProps] = defineField('abreviacion');
const [codigo, codigoProps] = defineField('codigo');
const [descripcion, descripcionProps] = defineField('descripcion');
const [telefono_interno, telefonoInternoProps] = defineField('telefono_interno');
const [ubicacion, ubicacionProps] = defineField('ubicacion');
const [activo, activoProps] = defineField('activo');
const [office_ids, officeIdsProps] = defineField('office_ids');
const [jefe_inmediato_id] = defineField('jefe_inmediato_id');

// Suplentes: no forma parte del esquema yup (es un repetidor, no un campo
// simple); se arma y se envía aparte, junto al resto del formData.
const suplentes = ref([]);

const addSuplente = () => {
    suplentes.value.push({ employee_id: '', vigente_desde: '', vigente_hasta: '' });
};
const removeSuplente = (idx) => {
    suplentes.value.splice(idx, 1);
};
// Aviso de certificado RENIEC comentado por ahora (ver template).
// const suplenteSinCertificado = (employeeId) => {
//     if (!employeeId) return false;
//     const emp = props.employees.find(e => e.id === employeeId);
//     return !!emp && emp.tiene_certificado === false;
// };

const filteredOffices = computed(() => {
    if (!officeQuery.value) return props.offices;
    const q = officeQuery.value.toLowerCase();
    return props.offices.filter(o =>
        o.nombre.toLowerCase().includes(q) ||
        (o.codigo && o.codigo.toLowerCase().includes(q))
    );
});

watch(() => props.direction, (d) => {
    if (d && props.isEditing) {
        setValues({
            nombre: d.nombre || '',
            abreviacion: d.abreviacion || '',
            codigo: d.codigo || '',
            descripcion: d.descripcion || '',
            telefono_interno: d.telefono_interno || '',
            ubicacion: d.ubicacion || '',
            activo: d.activo !== undefined ? d.activo : true,
            office_ids: d.offices ? d.offices.map(o => o.id) : [],
            jefe_inmediato_id: d.jefe_inmediato_id || ''
        });
        suplentes.value = (d.suplentes || []).map(s => ({
            employee_id: s.employee_id || '',
            vigente_desde: s.vigente_desde || '',
            vigente_hasta: s.vigente_hasta || '',
        }));
    } else {
        setValues({
            nombre: '',
            abreviacion: '',
            codigo: '',
            descripcion: '',
            telefono_interno: '',
            ubicacion: '',
            activo: true,
            office_ids: [],
            jefe_inmediato_id: ''
        });
        suplentes.value = [];
    }
}, { immediate: true });

const onSubmit = validateForm((values) => emit('submit', {
    ...values,
    suplentes: suplentes.value.filter(s => s.employee_id),
}));
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
</style>
