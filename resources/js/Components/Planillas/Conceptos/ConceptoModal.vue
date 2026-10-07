<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-teal-600 to-cyan-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="tipoIcon" class="w-6 h-6" />
                            {{ isEditing ? 'Editar Concepto' : 'Nuevo Concepto' }}
                        </h3>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-white/90 bg-white/15 rounded-full px-2.5 py-0.5 mt-1">
                            {{ tipoLabel }}
                        </span>
                    </div>
                    <button @click="$emit('close')" class="text-teal-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <input v-model="codigo" type="text" placeholder="Ej. DS313_2023"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none uppercase"
                                :class="formErrors.codigo ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.codigo" class="mt-1 text-sm text-red-600">{{ formErrors.codigo }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Categoría</label>
                            <input v-model="categoria" type="text" placeholder="Ej. BONIFICACION"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input v-model="nombre" type="text" placeholder="Ej. DS 313-2023"
                            class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                            :class="formErrors.nombre ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="formErrors.nombre" class="mt-1 text-sm text-red-600">{{ formErrors.nombre }}</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Tipo de valor</label>
                            <select v-model="es_porcentaje"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none">
                                <option :value="false">Monto fijo (S/)</option>
                                <option :value="true">Porcentaje (%)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Valor</label>
                            <input v-model="valor" type="number" step="0.00001" min="0" placeholder="0.00"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none"
                                :class="formErrors.valor ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.valor" class="mt-1 text-sm text-red-600">{{ formErrors.valor }}</p>
                            <p class="mt-1 text-xs text-slate-400">Para porcentaje use decimal (0.09 = 9%)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Orden</label>
                            <input v-model="orden" type="number" min="0" step="1"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-teal-500/20 focus:border-teal-500 transition-all outline-none" />
                        </div>
                    </div>

                    <p class="text-xs text-slate-500 bg-slate-50 rounded-xl px-3 py-2">
                        Si el concepto tiene <strong>valor</strong>, se aplica automáticamente a todo el personal CAS
                        al generar la planilla. Para montos distintos por persona o grupo, déjelo vacío y use
                        <strong>Asignaciones</strong>.
                    </p>

                    <div class="rounded-2xl border border-slate-200 p-4">
                        <p class="text-sm font-bold text-slate-700 mb-3">Afectaciones</p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <label v-for="afecto in afectos" :key="afecto.key"
                                class="flex items-center gap-2 cursor-pointer text-sm font-semibold text-slate-600">
                                <input type="checkbox" v-model="afecto.model.value"
                                    class="w-4 h-4 rounded text-teal-600 border-slate-300 focus:ring-teal-500" />
                                {{ afecto.label }}
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="activo" id="concepto-activo"
                            class="w-4 h-4 rounded text-teal-600 border-slate-300 focus:ring-teal-500" />
                        <label for="concepto-activo" class="text-sm font-bold text-slate-700 cursor-pointer">
                            Concepto activo
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-teal-600 to-cyan-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : (isEditing ? 'Actualizar' : 'Registrar') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, watch } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { Coins, Percent, PiggyBank, X, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    concepto: { type: Object, default: null },
    tipo: { type: String, required: true },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const isEditing = computed(() => !!props.concepto);

const TIPO_META = {
    INGRESO: { label: 'Ingreso / Remuneración', icon: Coins },
    DESCUENTO: { label: 'Retención / Descuento', icon: Percent },
    APORTACION: { label: 'Aportación del Empleador', icon: PiggyBank },
};

const tipoMeta = computed(() => TIPO_META[props.tipo] || TIPO_META.INGRESO);
const tipoLabel = computed(() => tipoMeta.value.label);
const tipoIcon = computed(() => tipoMeta.value.icon);

const schema = toTypedSchema(yup.object({
    codigo: yup.string().required('El código es obligatorio').max(50, 'Máximo 50 caracteres'),
    nombre: yup.string().required('El nombre es obligatorio').max(150, 'Máximo 150 caracteres'),
    categoria: yup.string().nullable(),
    es_porcentaje: yup.boolean(),
    valor: yup.number().typeError('Ingrese un valor válido').nullable().min(0, 'No puede ser negativo'),
    orden: yup.number().typeError('Ingrese un orden válido').min(0, 'No puede ser negativo'),
    afecto_renta5: yup.boolean(),
    afecto_essalud: yup.boolean(),
    afecto_onp: yup.boolean(),
    afecto_afp: yup.boolean(),
    activo: yup.boolean(),
}));

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: {
        codigo: '',
        nombre: '',
        categoria: '',
        es_porcentaje: false,
        valor: '',
        orden: 0,
        afecto_renta5: false,
        afecto_essalud: true,
        afecto_onp: true,
        afecto_afp: true,
        activo: true,
    },
});

const [codigo] = defineField('codigo');
const [nombre] = defineField('nombre');
const [categoria] = defineField('categoria');
const [es_porcentaje] = defineField('es_porcentaje');
const [valor] = defineField('valor');
const [orden] = defineField('orden');
const [afecto_renta5] = defineField('afecto_renta5');
const [afecto_essalud] = defineField('afecto_essalud');
const [afecto_onp] = defineField('afecto_onp');
const [afecto_afp] = defineField('afecto_afp');
const [activo] = defineField('activo');

const afectos = [
    { key: 'renta5', label: 'Renta 5ta', model: afecto_renta5 },
    { key: 'essalud', label: 'EsSalud', model: afecto_essalud },
    { key: 'onp', label: 'ONP', model: afecto_onp },
    { key: 'afp', label: 'AFP', model: afecto_afp },
];

const esRemunerativo = computed(() => props.tipo === 'INGRESO');

watch(() => props.concepto, (concepto) => {
    if (concepto) {
        setValues({
            codigo: concepto.codigo || '',
            nombre: concepto.nombre || '',
            categoria: concepto.categoria || '',
            es_porcentaje: !!concepto.es_porcentaje,
            valor: concepto.valor === null || concepto.valor === undefined ? '' : Number(concepto.valor),
            orden: concepto.orden ?? 0,
            afecto_renta5: !!concepto.afecto_renta5,
            afecto_essalud: !!concepto.afecto_essalud,
            afecto_onp: !!concepto.afecto_onp,
            afecto_afp: !!concepto.afecto_afp,
            activo: concepto.activo !== undefined ? !!concepto.activo : true,
        });
    } else {
        setValues({
            codigo: '',
            nombre: '',
            categoria: '',
            es_porcentaje: false,
            valor: '',
            orden: 0,
            afecto_renta5: false,
            afecto_essalud: esRemunerativo.value,
            afecto_onp: esRemunerativo.value,
            afecto_afp: esRemunerativo.value,
            activo: true,
        });
    }
}, { immediate: true });

const onSubmit = validateForm((values) => {
    emit('submit', {
        codigo: values.codigo.toUpperCase(),
        nombre: values.nombre,
        tipo: props.tipo,
        categoria: values.categoria || null,
        es_porcentaje: !!values.es_porcentaje,
        valor: values.valor === '' || values.valor === null || values.valor === undefined ? null : Number(values.valor),
        orden: Number(values.orden) || 0,
        afecto_renta5: !!values.afecto_renta5,
        afecto_essalud: !!values.afecto_essalud,
        afecto_onp: !!values.afecto_onp,
        afecto_afp: !!values.afecto_afp,
        activo: !!values.activo,
    });
});
</script>
