<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-10 overflow-hidden my-8">
                <div class="bg-gradient-to-r from-violet-600 to-purple-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <UserPlus class="w-6 h-6" />
                            Nuevo Empleado
                        </h3>
                        <p class="text-violet-100 text-sm mt-1">Alta completa: datos básicos, laborales y de planilla (régimen CAS)</p>
                    </div>
                    <button @click="$emit('close')" class="text-violet-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="max-h-[70vh] overflow-y-auto p-6 space-y-6">
                    <section class="space-y-4">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-violet-600 flex items-center gap-2">
                            <User class="w-4 h-4" />
                            Datos básicos
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    DNI <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input v-model="dni" type="text" maxlength="8" placeholder="8 dígitos"
                                        inputmode="numeric" @input="handleDniInput"
                                        @keypress.enter.prevent="consultarDni"
                                        class="w-full px-4 py-2.5 pr-11 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 font-mono tracking-wider focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                        :class="[formErrors.dni ? 'border-red-400' : 'border-slate-200', consultandoDni ? 'opacity-60' : '']" />
                                    <div v-if="consultandoDni"
                                        class="absolute right-3 top-1/2 -translate-y-1/2">
                                        <Loader2 class="w-5 h-5 animate-spin text-violet-600" />
                                    </div>
                                    <button v-else type="button" @click="consultarDni"
                                        :disabled="dni.length !== 8"
                                        title="Buscar en RENIEC"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1.5 rounded-lg text-violet-600 hover:bg-violet-50 transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                        <Search class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="formErrors.dni" class="mt-1 text-sm text-red-600">{{ formErrors.dni }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Nombres <span class="text-red-500">*</span>
                                </label>
                                <input v-model="nombres" type="text"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                    :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Apellidos <span class="text-red-500">*</span>
                                </label>
                                <input v-model="apellidos" type="text"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                    :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Fecha de nacimiento</label>
                                <input v-model="fecha_nacimiento" type="date"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none bg-white cursor-pointer" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Teléfono</label>
                                <input v-model="telefono" type="text" maxlength="20" placeholder="Ej. 999888777"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Correo</label>
                                <input v-model="correo" type="email" placeholder="correo@ejemplo.com"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                    :class="formErrors.correo ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.correo" class="mt-1 text-sm text-red-600">{{ formErrors.correo }}</p>
                            </div>
                        </div>

                        <div v-if="dniRegistrado" class="p-3 rounded-xl bg-red-50 border border-red-200 flex items-start gap-2">
                            <TriangleAlert class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                            <div class="text-sm text-red-700">
                                <p class="font-bold">{{ consultaMensaje }}</p>
                                <p v-if="empleadoExistente" class="mt-0.5 text-red-600">
                                    {{ empleadoExistente.nombre_completo }}
                                    · {{ empleadoExistente.estado }}
                                    · registrado con anterioridad
                                </p>
                            </div>
                        </div>
                        <div v-else-if="consultaMensaje"
                            class="p-3 rounded-xl flex items-center gap-2 text-sm font-medium"
                            :class="consultaOk ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-amber-50 border border-amber-200 text-amber-700'">
                            <CheckCircle2 v-if="consultaOk" class="w-4 h-4 shrink-0" />
                            <Info v-else class="w-4 h-4 shrink-0" />
                            {{ consultaMensaje }}
                        </div>
                    </section>

                    <section class="space-y-4 pt-4 border-t border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-violet-600 flex items-center gap-2">
                            <Briefcase class="w-4 h-4" />
                            Datos laborales
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Cargo <span class="text-red-500">*</span>
                                </label>
                                <select v-model="cargo_id" :disabled="loadingCatalogs"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none disabled:bg-slate-50">
                                    <option value="">Seleccione</option>
                                    <option v-for="cargo in cargos" :key="cargo.id" :value="cargo.id">{{ cargo.nombre }}</option>
                                </select>
                                <p v-if="formErrors.cargo_id" class="mt-1 text-sm text-red-600">{{ formErrors.cargo_id }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Fecha de ingreso <span class="text-red-500">*</span>
                                </label>
                                <input v-model="fecha_ingreso" type="date"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none bg-white cursor-pointer"
                                    :class="formErrors.fecha_ingreso ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.fecha_ingreso" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_ingreso }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de contrato</label>
                                <div class="px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-slate-50 text-slate-700 font-bold text-sm">
                                    {{ casTypeName || 'Cargando...' }}
                                </div>
                                <p class="mt-1 text-xs text-slate-400">Fijo CAS (régimen de este módulo)</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Dirección / Sede</label>
                                <select v-model="direccion_id" :disabled="loadingCatalogs"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none disabled:bg-slate-50">
                                    <option value="">Sin asignar</option>
                                    <option v-for="dir in direcciones" :key="dir.id" :value="dir.id">{{ dir.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Oficina / Área</label>
                                <select v-model="office_id" :disabled="loadingCatalogs"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none disabled:bg-slate-50">
                                    <option value="">Sin asignar</option>
                                    <option v-for="oficina in oficinas" :key="oficina.id" :value="oficina.id">{{ oficina.nombre }}</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-4 pt-4 border-t border-slate-200">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-violet-600 flex items-center gap-2">
                            <Wallet class="w-4 h-4" />
                            Datos de planilla
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Remuneración base (S/) <span class="text-red-500">*</span>
                                </label>
                                <input v-model="remuneracion" type="number" step="0.01" min="0" placeholder="0.00"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                    :class="formErrors.remuneracion ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.remuneracion" class="mt-1 text-sm text-red-600">{{ formErrors.remuneracion }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Vigente desde <span class="text-red-500">*</span>
                                </label>
                                <input v-model="remuneracion_desde" type="date"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none bg-white cursor-pointer"
                                    :class="formErrors.remuneracion_desde ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.remuneracion_desde" class="mt-1 text-sm text-red-600">{{ formErrors.remuneracion_desde }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Modalidad CAS <span class="text-red-500">*</span>
                                </label>
                                <select v-model="modalidad_cas"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none"
                                    :class="formErrors.modalidad_cas ? 'border-red-400' : 'border-slate-200'">
                                    <option value="INDETERMINADO">Indeterminado</option>
                                    <option value="TRANSITORIO">Transitorio</option>
                                </select>
                                <p v-if="formErrors.modalidad_cas" class="mt-1 text-sm text-red-600">{{ formErrors.modalidad_cas }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Inicio de contrato <span class="text-red-500">*</span>
                                </label>
                                <input v-model="fecha_inicio_contrato" type="date"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none bg-white cursor-pointer"
                                    :class="formErrors.fecha_inicio_contrato ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.fecha_inicio_contrato" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_inicio_contrato }}</p>
                            </div>
                            <div v-if="modalidad_cas === 'TRANSITORIO'">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Fin de contrato</label>
                                <input v-model="fecha_fin_contrato" type="date"
                                    class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none bg-white cursor-pointer"
                                    :class="formErrors.fecha_fin_contrato ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.fecha_fin_contrato" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_fin_contrato }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Sistema de pensiones</label>
                                <select v-model="regimen_pensionario_id"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none">
                                    <option value="">Sin asignar (completar luego)</option>
                                    <option v-for="reg in regimenes" :key="reg.id" :value="reg.id">{{ reg.nombre }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Banco</label>
                                <select v-model="banco_id"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none">
                                    <option value="">Sin asignar (completar luego)</option>
                                    <option v-for="banco in bancos" :key="banco.id" :value="banco.id">
                                        {{ banco.nombre }}{{ banco.activo ? '' : ' (inactivo)' }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div v-if="esAfp" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">CUSPP</label>
                                <input v-model="cuspp" type="text" maxlength="30" placeholder="Ej. 601600JRARA8"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de comisión</label>
                                <select v-model="tipo_comision"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none">
                                    <option value="">Sin asignar</option>
                                    <option value="FLUJO">Flujo</option>
                                    <option value="MIXTA">Mixta</option>
                                    <option value="SALDO">Saldo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Cuenta de abono</label>
                                <input v-model="cuenta_ahorro" type="text" maxlength="50" placeholder="Ej. 04-481-563467"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none" />
                            </div>
                        </div>
                        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Cuenta de abono</label>
                                <input v-model="cuenta_ahorro" type="text" maxlength="50" placeholder="Ej. 04-481-563467"
                                    class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none" />
                            </div>
                        </div>

                        <p class="text-xs text-slate-500 bg-violet-50 border border-violet-100 rounded-xl px-4 py-3">
                            Pensión y banco son opcionales: si se omiten, complete luego con el botón
                            <span class="font-bold">Perfil de pensión</span> de la tabla. El empleado solo entra en la
                            planilla al volver a pulsar <span class="font-bold">Generar</span>.
                        </p>
                    </section>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving || loadingCatalogs || !casTypeId || dniRegistrado || consultandoDni"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Registrar empleado' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import { UserPlus, User, Briefcase, Wallet, X, Loader2, Search, TriangleAlert, CheckCircle2, Info } from 'lucide-vue-next';
import { usePlanillaBancos } from '@/Composables/usePlanillaBancos';

const props = defineProps({
    regimenes: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const { bancos, fetchBancos } = usePlanillaBancos();

const cargos = ref([]);
const direcciones = ref([]);
const oficinas = ref([]);
const tiposContrato = ref([]);
const loadingCatalogs = ref(true);

const consultandoDni = ref(false);
const consultaMensaje = ref('');
const consultaOk = ref(false);
const dniRegistrado = ref(false);
const empleadoExistente = ref(null);

const schema = toTypedSchema(yup.object({
    dni: yup.string().trim().required('El DNI es obligatorio').matches(/^\d{8}$/, 'Ingrese 8 dígitos'),
    nombres: yup.string().trim().required('Los nombres son obligatorios').max(255, 'Máximo 255 caracteres'),
    apellidos: yup.string().trim().required('Los apellidos son obligatorios').max(255, 'Máximo 255 caracteres'),
    fecha_nacimiento: yup.string().nullable(),
    telefono: yup.string().nullable().max(20, 'Máximo 20 caracteres'),
    correo: yup.string().nullable().email('Correo inválido').max(255, 'Máximo 255 caracteres'),
    cargo_id: yup.string().nullable(),
    direccion_id: yup.string().nullable(),
    office_id: yup.string().nullable(),
    fecha_ingreso: yup.string().required('La fecha es obligatoria'),
    remuneracion: yup.number().typeError('Ingrese un monto válido').required('El monto es obligatorio').min(0.01, 'Debe ser mayor a 0'),
    remuneracion_desde: yup.string().required('La fecha es obligatoria'),
    modalidad_cas: yup.string().required('Seleccione la modalidad').oneOf(['', 'INDETERMINADO', 'TRANSITORIO']),
    fecha_inicio_contrato: yup.string().required('La fecha es obligatoria'),
    fecha_fin_contrato: yup.string().nullable().test('fin-posterior', 'Debe ser igual o posterior al inicio', function (value) {
        const inicio = this.parent.fecha_inicio_contrato;
        if (!value || !inicio) return true;
        return value >= inicio;
    }),
    regimen_pensionario_id: yup.string().nullable(),
    cuspp: yup.string().nullable().max(30, 'Máximo 30 caracteres'),
    tipo_comision: yup.string().nullable().oneOf(['', 'FLUJO', 'MIXTA', 'SALDO']),
    banco_id: yup.string().nullable(),
    cuenta_ahorro: yup.string().nullable().max(50, 'Máximo 50 caracteres'),
}));

const today = new Date().toISOString().slice(0, 10);

const { errors: formErrors, defineField, handleSubmit: validateForm } = useForm({
    validationSchema: schema,
    initialValues: {
        dni: '',
        nombres: '',
        apellidos: '',
        fecha_nacimiento: '',
        telefono: '',
        correo: '',
        cargo_id: '',
        direccion_id: '',
        office_id: '',
        fecha_ingreso: today,
        remuneracion: '',
        remuneracion_desde: today,
        modalidad_cas: 'INDETERMINADO',
        fecha_inicio_contrato: today,
        fecha_fin_contrato: '',
        regimen_pensionario_id: '',
        cuspp: '',
        tipo_comision: '',
        banco_id: '',
        cuenta_ahorro: '',
    },
});

const [dni] = defineField('dni');
const [nombres] = defineField('nombres');
const [apellidos] = defineField('apellidos');
const [fecha_nacimiento] = defineField('fecha_nacimiento');
const [telefono] = defineField('telefono');
const [correo] = defineField('correo');
const [cargo_id] = defineField('cargo_id');
const [direccion_id] = defineField('direccion_id');
const [office_id] = defineField('office_id');
const [fecha_ingreso] = defineField('fecha_ingreso');
const [remuneracion] = defineField('remuneracion');
const [remuneracion_desde] = defineField('remuneracion_desde');
const [modalidad_cas] = defineField('modalidad_cas');
const [fecha_inicio_contrato] = defineField('fecha_inicio_contrato');
const [fecha_fin_contrato] = defineField('fecha_fin_contrato');
const [regimen_pensionario_id] = defineField('regimen_pensionario_id');
const [cuspp] = defineField('cuspp');
const [tipo_comision] = defineField('tipo_comision');
const [banco_id] = defineField('banco_id');
const [cuenta_ahorro] = defineField('cuenta_ahorro');

const casTypeId = computed(() => {
    const tipo = tiposContrato.value.find((t) => (t.nombre || '').toUpperCase() === 'CAS');
    return tipo ? tipo.id : null;
});

const casTypeName = computed(() => (casTypeId.value ? 'CAS' : null));

const esAfp = computed(() => {
    const regimen = props.regimenes.find((r) => r.id === regimen_pensionario_id.value);
    return regimen?.tipo === 'AFP';
});

watch(esAfp, (afp) => {
    if (!afp) tipo_comision.value = '';
});

watch(modalidad_cas, (modalidad) => {
    if (modalidad !== 'TRANSITORIO') fecha_fin_contrato.value = '';
});

const resetConsulta = () => {
    consultaMensaje.value = '';
    consultaOk.value = false;
    dniRegistrado.value = false;
    empleadoExistente.value = null;
};

const handleDniInput = () => {
    dni.value = dni.value.replace(/\D/g, '').slice(0, 8);
    resetConsulta();
    if (dni.value.length === 8) {
        setTimeout(() => {
            if (dni.value.length === 8) consultarDni();
        }, 300);
    }
};

const consultarDni = async () => {
    if (dni.value.length !== 8 || consultandoDni.value) return;

    consultandoDni.value = true;
    resetConsulta();

    try {
        const { data } = await axios.get('/planillas/consultar-dni', {
            params: { dni: dni.value },
        });

        if (data.registrado) {
            dniRegistrado.value = true;
            empleadoExistente.value = data.empleado || null;
            consultaMensaje.value = data.message || 'El DNI ya está registrado como empleado.';
            return;
        }

        if (data.success && data.data) {
            const persona = data.data;
            if (persona.nombres) nombres.value = persona.nombres;

            const apellidosReniec = `${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
            if (apellidosReniec) apellidos.value = apellidosReniec;

            consultaOk.value = true;
            consultaMensaje.value = persona.nombre_completo
                ? `RENIEC: ${persona.nombre_completo}`
                : 'Datos obtenidos correctamente.';
            return;
        }

        consultaMensaje.value = data.message || 'DNI no encontrado. Puede ingresar los datos manualmente.';
    } catch (error) {
        consultaMensaje.value = error.response?.data?.message
            || 'Error al consultar el DNI. Puede ingresar los datos manualmente.';
    } finally {
        consultandoDni.value = false;
    }
};

const onSubmit = validateForm((values) => {
    if (!casTypeId.value || dniRegistrado.value) return;
    emit('submit', {
        dni: values.dni,
        nombres: values.nombres,
        apellidos: values.apellidos,
        fecha_nacimiento: values.fecha_nacimiento || null,
        telefono: values.telefono || null,
        correo: values.correo || null,
        cargo_id: values.cargo_id || null,
        direccion_id: values.direccion_id || null,
        office_id: values.office_id || null,
        fecha_ingreso: values.fecha_ingreso,
        contract_type_id: casTypeId.value,
        remuneracion: Number(values.remuneracion),
        remuneracion_desde: values.remuneracion_desde,
        modalidad_cas: values.modalidad_cas,
        fecha_inicio_contrato: values.fecha_inicio_contrato,
        fecha_fin_contrato: values.fecha_fin_contrato || null,
        regimen_pensionario_id: values.regimen_pensionario_id || null,
        cuspp: values.cuspp || null,
        tipo_comision: values.tipo_comision || null,
        banco_id: values.banco_id || null,
        cuenta_ahorro: values.cuenta_ahorro || null,
    });
});

onMounted(async () => {
    try {
        const [catalogos] = await Promise.all([
            axios.get('/planillas/catalogos-empleados'),
            fetchBancos(),
        ]);
        cargos.value = catalogos.data.cargos;
        direcciones.value = catalogos.data.direcciones;
        oficinas.value = catalogos.data.oficinas;
        tiposContrato.value = catalogos.data.tipos_contrato;
    } finally {
        loadingCatalogs.value = false;
    }
});
</script>
