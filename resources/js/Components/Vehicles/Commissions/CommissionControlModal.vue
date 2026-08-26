<script setup>
import { computed, ref } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import { Clock, X, Loader2, Truck } from 'lucide-vue-next';

// Modal dedicado al control físico de salida/retorno de una comisión ya
// confirmada por el conductor. Antes vivía como una sección más dentro del
// modal "Gestionar" (CommissionModal.vue); se separó para que quede claro
// que la comisión está EN COMISIÓN (fuera, con el vehículo) hasta que
// alguien complete el retorno aquí.
const props = defineProps({
    commission: { type: Object, required: true },
});
const emit = defineEmits(['close', 'saved']);

const isRetorno = computed(() => props.commission.estado === 'EN_COMISION');
const today = new Date().toISOString().split('T')[0];
const currentTime = new Date().toTimeString().slice(0, 5);

// El backend entrega la fecha en 'YYYY-MM-DD' y la hora puede traer
// segundos ('HH:MM:SS'); aquí se muestran en el formato local dd/mm/aaaa.
const formatDateDisplay = (value) => {
    if (!value) return null;
    const [year, month, day] = value.split('-');
    return year && month && day ? `${day}/${month}/${year}` : value;
};
const formatTimeDisplay = (value) => (value ? value.slice(0, 5) : null);

const salidaResumen = computed(() => {
    const fecha = formatDateDisplay(props.commission.fecha_salida);
    const hora = formatTimeDisplay(props.commission.hora_salida);
    if (fecha && hora) return `desde el ${fecha} a las ${hora}`;
    if (hora) return `desde las ${hora}`;
    if (fecha) return `desde el ${fecha}`;
    return '';
});

const schema = computed(() => toTypedSchema(
    isRetorno.value
        ? yup.object({
            fecha_retorno: yup.string().required('La fecha de retorno es obligatoria.'),
            hora_regreso: yup.string().required('La hora de retorno es obligatoria.'),
            km_retorno: yup.string()
                .required('El kilometraje de retorno es obligatorio.')
                .test('is-numeric', 'Debe ser un número entero no negativo.', v => !v || /^\d+$/.test(v))
                .test('gte-salida', 'Debe ser mayor o igual al kilometraje de salida.', v => {
                    if (!v || !props.commission.km_salida) return true;
                    return parseInt(v, 10) >= parseInt(props.commission.km_salida, 10);
                }),
        })
        : yup.object({
            fecha_salida: yup.string().required('La fecha de salida es obligatoria.'),
            hora_salida: yup.string().required('La hora de salida es obligatoria.'),
            km_salida: yup.string()
                .required('El kilometraje de salida es obligatorio.')
                .test('is-numeric', 'Debe ser un número entero no negativo.', v => !v || /^\d+$/.test(v)),
        })
));

const { errors, defineField, handleSubmit: validateForm } = useForm({
    validationSchema: schema,
    initialValues: {
        fecha_salida: props.commission.fecha_salida || today,
        hora_salida: currentTime,
        // El kilometraje de salida parte del odómetro que el inventario del
        // vehículo ya tiene registrado, en vez de pedirlo de nuevo.
        km_salida: props.commission.km_salida || props.commission.vehicle_kilometraje || '',
        fecha_retorno: props.commission.fecha_retorno || today,
        hora_regreso: currentTime,
        km_retorno: '',
    },
});

const [fechaSalida, fechaSalidaProps] = defineField('fecha_salida');
const [horaSalida, horaSalidaProps] = defineField('hora_salida');
const [kmSalida, kmSalidaProps] = defineField('km_salida');
const [fechaRetorno, fechaRetornoProps] = defineField('fecha_retorno');
const [horaRegreso, horaRegresoProps] = defineField('hora_regreso');
const [kmRetorno, kmRetornoProps] = defineField('km_retorno');

const totalKmRecorrido = computed(() => {
    const salida = parseInt(props.commission.km_salida, 10);
    const retorno = parseInt(kmRetorno.value, 10);
    if (isNaN(salida) || isNaN(retorno) || retorno < salida) return null;
    return retorno - salida;
});

const isSubmitting = ref(false);
const submitError = ref('');

const onSubmit = validateForm(async (values) => {
    isSubmitting.value = true;
    submitError.value = '';
    try {
        const payload = isRetorno.value
            ? { fecha_retorno: values.fecha_retorno, hora_regreso: values.hora_regreso, km_retorno: values.km_retorno }
            : { fecha_salida: values.fecha_salida, hora_salida: values.hora_salida, km_salida: values.km_salida };
        await axios.put(`/vehicles/commissions/${props.commission.id}`, payload);
        emit('saved');
    } catch (e) {
        submitError.value = e.response?.data?.message || 'No se pudo registrar la información.';
    } finally {
        isSubmitting.value = false;
    }
});
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('close')"></div>
            <div class="relative z-10 bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="px-6 py-4 flex justify-between items-center"
                    :class="isRetorno ? 'bg-gradient-to-r from-green-600 to-emerald-600' : 'bg-gradient-to-r from-blue-600 to-sky-500'">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Truck class="h-6 w-6" />
                            {{ isRetorno ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </h3>
                        <p class="text-sm mt-1" :class="isRetorno ? 'text-emerald-100' : 'text-blue-100'">
                            Nº <span class="font-mono font-bold">{{ String(commission.numero).padStart(3, '0') }}-{{ commission.anio }}</span>
                            · {{ commission.solicitante }}
                        </p>
                    </div>
                    <button type="button" @click="emit('close')"
                        class="cursor-pointer bg-white/10 rounded-xl p-2 inline-flex items-center justify-center text-white hover:bg-white/20 transition-all active:scale-95">
                        <span class="sr-only">Cerrar</span>
                        <X class="h-6 w-6" stroke-width="2" />
                    </button>
                </div>

                <div v-if="isRetorno" class="mx-6 mt-6 flex items-center gap-3 rounded-xl border border-blue-200 bg-blue-50 p-3.5">
                    <Clock class="h-5 w-5 shrink-0 text-blue-600" />
                    <p class="text-sm text-blue-800">
                        <strong>En comisión</strong> {{ salidaResumen }}. Quedará así hasta registrar el retorno.
                    </p>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-4">
                    <template v-if="!isRetorno">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">Fecha de salida <span class="text-red-500">*</span></label>
                                <input type="date" v-model="fechaSalida" v-bind="fechaSalidaProps"
                                    class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500"
                                    :class="errors.fecha_salida ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="errors.fecha_salida" class="mt-1 text-xs text-red-600">{{ errors.fecha_salida }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">Hora de salida <span class="text-red-500">*</span></label>
                                <input type="time" v-model="horaSalida" v-bind="horaSalidaProps"
                                    class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500"
                                    :class="errors.hora_salida ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="errors.hora_salida" class="mt-1 text-xs text-red-600">{{ errors.hora_salida }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-700">Kilometraje de salida <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" v-model="kmSalida" v-bind="kmSalidaProps"
                                class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-blue-500/20 focus:border-blue-500"
                                :class="errors.km_salida ? 'border-red-400' : 'border-slate-200'" placeholder="Ej: 15000">
                            <p v-if="errors.km_salida" class="mt-1 text-xs text-red-600">{{ errors.km_salida }}</p>
                        </div>
                        <p class="text-xs text-slate-500">Al guardar, la comisión pasa a estado <strong>En Comisión</strong> hasta que se registre el retorno.</p>
                    </template>

                    <template v-else>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">Fecha de retorno <span class="text-red-500">*</span></label>
                                <input type="date" v-model="fechaRetorno" v-bind="fechaRetornoProps"
                                    class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500"
                                    :class="errors.fecha_retorno ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="errors.fecha_retorno" class="mt-1 text-xs text-red-600">{{ errors.fecha_retorno }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-bold text-slate-700">Hora de retorno <span class="text-red-500">*</span></label>
                                <input type="time" v-model="horaRegreso" v-bind="horaRegresoProps"
                                    class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500"
                                    :class="errors.hora_regreso ? 'border-red-400' : 'border-slate-200'">
                                <p v-if="errors.hora_regreso" class="mt-1 text-xs text-red-600">{{ errors.hora_regreso }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-700">Kilometraje de retorno <span class="text-red-500">*</span></label>
                            <input type="text" inputmode="numeric" v-model="kmRetorno" v-bind="kmRetornoProps"
                                class="w-full rounded-xl border-2 px-3 py-2.5 text-sm outline-none focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500"
                                :class="errors.km_retorno ? 'border-red-400' : 'border-slate-200'"
                                :placeholder="commission.km_salida ? `Mínimo ${commission.km_salida}` : 'Ej: 15230'">
                            <p v-if="errors.km_retorno" class="mt-1 text-xs text-red-600">{{ errors.km_retorno }}</p>
                        </div>
                        <div class="flex items-center justify-between rounded-xl border border-emerald-200 bg-emerald-50 p-3.5">
                            <span class="text-sm font-bold text-emerald-800">Total kilómetros recorridos</span>
                            <span class="rounded-lg border border-emerald-200 bg-white/80 px-3 py-1 text-lg font-black text-emerald-900 shadow-sm">
                                {{ totalKmRecorrido !== null ? `${totalKmRecorrido} km` : '—' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-500">Al guardar, la comisión pasa a estado <strong>Completada</strong>.</p>
                    </template>

                    <p v-if="submitError" class="text-sm text-red-600">{{ submitError }}</p>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="emit('close')"
                            class="cursor-pointer rounded-xl border-2 border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-600 transition-all hover:bg-slate-50 active:scale-95">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isSubmitting"
                            class="cursor-pointer inline-flex items-center gap-2 rounded-xl px-6 py-2.5 text-sm font-bold text-white transition-all shadow-lg disabled:opacity-50 active:scale-95 bg-gradient-to-r"
                            :class="isRetorno ? 'from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700' : 'from-blue-600 to-sky-500 hover:from-blue-700 hover:to-sky-600'">
                            <Loader2 v-if="isSubmitting" class="h-4 w-4 animate-spin" />
                            {{ isRetorno ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
