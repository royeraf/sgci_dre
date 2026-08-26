<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-sky-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Building2 class="w-6 h-6" />
                            {{ isEditing ? 'Editar Oficina' : 'Registrar Nueva Oficina' }}
                        </h3>
                        <p class="text-sky-50 text-sm mt-1">Configure las unidades orgánicas dependientes</p>
                    </div>
                    <button @click="$emit('close')" class="text-sky-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Nombre de la Oficina <span class="text-red-500">*</span>
                        </label>
                        <input v-model="nombre" v-bind="nombreProps" type="text" placeholder="Ej. UNIDAD DE TESORERÍA"
                            @input="nombre = $event.target.value.toUpperCase()"
                            class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200 outline-none"
                            :class="formErrors.nombre ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                        <p v-if="formErrors.nombre" class="mt-1 text-sm text-red-600">{{ formErrors.nombre }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Código -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Código Interno</label>
                            <input v-model="codigo" v-bind="codigoProps" type="text" placeholder="Ej. TES-01"
                                @input="codigo = $event.target.value.toUpperCase()"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200 outline-none"
                                :class="formErrors.codigo ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                            <p v-if="formErrors.codigo" class="mt-1 text-sm text-red-600">{{ formErrors.codigo }}</p>
                        </div>
                        <!-- Teléfono -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono / Anexo</label>
                            <input v-model="telefono_interno" v-bind="telefonoProps" type="text"
                                placeholder="Ej. Anexo 205"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200 outline-none"
                                :class="formErrors.telefono_interno ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                            <p v-if="formErrors.telefono_interno" class="mt-1 text-sm text-red-600">{{
                                formErrors.telefono_interno }}</p>
                        </div>
                    </div>

                    <!-- Ubicación -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Ubicación Física</label>
                        <div class="relative">
                            <MapPin class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                            <input v-model="ubicacion" v-bind="ubicacionProps" type="text"
                                placeholder="Ej. 2do Piso, Pabellón Administrativo"
                                class="w-full pl-10 pr-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 transition-all duration-200 outline-none"
                                :class="formErrors.ubicacion ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-slate-200 bg-white'" />
                        </div>
                        <p v-if="formErrors.ubicacion" class="mt-1 text-sm text-red-600">{{ formErrors.ubicacion }}</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Jefe Inmediato</label>
                        <EmployeeSearchSelect v-model="jefe_inmediato_id" :employees="employees" accent="sky"
                            placeholder="Buscar jefe por nombre o DNI..." empty-label="Sin jefe asignado" />
                        <p v-if="formErrors.jefe_inmediato_id" class="mt-1 text-sm text-red-600">{{
                            formErrors.jefe_inmediato_id }}</p>
                        <p class="text-[10px] text-slate-500 mt-1 italic">
                            Tiene prioridad sobre el jefe asignado a la dirección.
                        </p>
                    </div>

                    <!-- Suplentes -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                <Users class="w-4 h-4 text-sky-600" />
                                Suplentes autorizados
                            </label>
                            <button type="button" @click="addSuplente"
                                class="flex items-center gap-1 text-xs font-bold text-sky-700 hover:text-sky-900 transition-colors">
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
                                    <EmployeeSearchSelect v-model="s.employee_id" :employees="employees" accent="sky"
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
                                        class="w-full px-2 py-1.5 text-xs border-2 border-slate-200 rounded-lg text-slate-900 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 outline-none" />
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 mb-1">Hasta
                                        (opcional)</label>
                                    <input v-model="s.vigente_hasta" type="date"
                                        class="w-full px-2 py-1.5 text-xs border-2 border-slate-200 rounded-lg text-slate-900 focus:ring-4 focus:ring-sky-500/20 focus:border-sky-500 outline-none" />
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-1 italic">
                            Sin fechas, el suplente puede aprobar en cualquier momento. Con fechas, solo durante ese
                            periodo (encargatura).
                        </p>
                    </div>

                    <div v-if="isEditing" class="flex items-center gap-2 pt-2">
                        <input type="checkbox" v-model="activo" v-bind="activoProps" id="office-activo"
                            class="w-4 h-4 text-sky-600 border-slate-300 rounded focus:ring-sky-500" />
                        <label for="office-activo" class="text-sm font-bold text-slate-700 cursor-pointer">Oficina
                            Activa</label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">Cancelar</button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-sky-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2 shadow-lg shadow-sky-600/20">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Oficina' : 'Guardar Oficina') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { Building2, X, Loader2, MapPin, Users, Plus, Trash2, AlertTriangle } from 'lucide-vue-next';
import EmployeeSearchSelect from '@/Components/Common/EmployeeSearchSelect.vue';

const props = defineProps({
    office: { type: Object, default: null },
    employees: { type: Array, default: () => [] },
    isEditing: { type: Boolean, default: false },
    submitting: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const schema = toTypedSchema(yup.object({
    nombre: yup.string().required('El nombre es obligatorio').min(3, 'Mínimo 3 caracteres'),
    codigo: yup.string().transform((value) => value || null).nullable(),
    ubicacion: yup.string().transform((value) => value || null).nullable(),
    telefono_interno: yup.string().transform((value) => value || null).nullable(),
    activo: yup.boolean(),
    jefe_inmediato_id: yup.string().nullable(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: schema,
    initialValues: {
        nombre: '',
        codigo: '',
        ubicacion: '',
        telefono_interno: '',
        activo: true,
        jefe_inmediato_id: ''
    }
});

const [nombre, nombreProps] = defineField('nombre');
const [codigo, codigoProps] = defineField('codigo');
const [ubicacion, ubicacionProps] = defineField('ubicacion');
const [telefono_interno, telefonoProps] = defineField('telefono_interno');
const [activo, activoProps] = defineField('activo');
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

watch(() => props.office, (o) => {
    if (o && props.isEditing) {
        setValues({
            nombre: o.nombre || '',
            codigo: o.codigo || '',
            ubicacion: o.ubicacion || '',
            telefono_interno: o.telefono_interno || '',
            activo: o.activo !== undefined ? Boolean(o.activo) : true,
            jefe_inmediato_id: o.jefe_inmediato_id || ''
        });
        suplentes.value = (o.suplentes || []).map(s => ({
            employee_id: s.employee_id || '',
            vigente_desde: s.vigente_desde || '',
            vigente_hasta: s.vigente_hasta || '',
        }));
    } else {
        resetForm({
            values: {
                nombre: '',
                codigo: '',
                ubicacion: '',
                telefono_interno: '',
                activo: true,
                jefe_inmediato_id: ''
            }
        });
        suplentes.value = [];
    }
}, { immediate: true });

const onSubmit = validateForm((values) => emit('submit', {
    ...values,
    suplentes: suplentes.value.filter(s => s.employee_id),
}));
</script>
