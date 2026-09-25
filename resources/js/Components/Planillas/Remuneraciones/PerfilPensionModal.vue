<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-lg w-full z-10 overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <Landmark class="w-6 h-6" />
                            Perfil de Planilla
                        </h3>
                        <p class="text-indigo-50 text-sm mt-1">{{ row?.nombre_completo }}</p>
                    </div>
                    <button @click="$emit('close')" class="text-indigo-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <form @submit.prevent="onSubmit" class="p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Sistema de Pensiones</label>
                        <div class="flex gap-2">
                            <select v-model="regimen_pensionario_id"
                                class="flex-1 px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none">
                                <option value="">Sin asignar</option>
                                <option v-for="reg in regimenes" :key="reg.id" :value="reg.id">
                                    {{ reg.nombre }}
                                </option>
                            </select>
                            <button type="button" @click="showRegimenesModal = true" title="Administrar regímenes y tasas"
                                class="cursor-pointer p-2.5 rounded-xl bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition-all">
                                <Settings class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">CUSPP</label>
                        <input v-model="cuspp" type="text" placeholder="Ej. 601600JRARA8"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">
                            Tipo de comisión
                            <span class="ml-2 text-[11px] font-normal text-slate-400">Solo AFP · SBS</span>
                        </label>
                        <select v-model="tipo_comision" :disabled="!esAfp"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none disabled:bg-slate-50 disabled:text-slate-400">
                            <option value="">Sin asignar</option>
                            <option value="FLUJO">Flujo (descuenta comisión en planilla)</option>
                            <option value="MIXTA">Mixta (componente flujo 0% desde feb-2023)</option>
                            <option value="SALDO">Saldo (se cobra sobre saldo, sin retención)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Banco</label>
                        <div class="flex gap-2">
                            <select v-model="banco_id"
                                class="flex-1 px-4 py-2.5 border-2 border-slate-200 rounded-xl bg-white text-slate-900 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none">
                                <option value="">Sin asignar</option>
                                <option v-for="banco in bancos" :key="banco.id" :value="banco.id">
                                    {{ banco.nombre }}{{ banco.activo ? '' : ' (inactivo)' }}
                                </option>
                            </select>
                            <button type="button" @click="showBancosModal = true" title="Administrar bancos"
                                class="cursor-pointer p-2.5 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 transition-all">
                                <Settings class="w-5 h-5" />
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Cuenta de abono</label>
                        <input v-model="cuenta_ahorro" type="text" placeholder="Ej. 04-481-563467"
                            class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all outline-none" />
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="cursor-pointer px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50">Cancelar</button>
                        <button type="submit" :disabled="saving"
                            class="cursor-pointer px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-blue-600 text-white rounded-xl disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="saving" class="w-5 h-5 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <BancosManagerModal v-if="showBancosModal" @close="showBancosModal = false" @changed="fetchBancos" />

        <RegimenesManagerModal v-if="showRegimenesModal" @close="showRegimenesModal = false"
            @changed="emit('changed')" />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { Landmark, X, Loader2, Settings } from 'lucide-vue-next';
import BancosManagerModal from '@/Components/Planillas/Remuneraciones/BancosManagerModal.vue';
import RegimenesManagerModal from '@/Components/Planillas/Remuneraciones/RegimenesManagerModal.vue';
import { usePlanillaBancos } from '@/Composables/usePlanillaBancos';

const { bancos, fetchBancos } = usePlanillaBancos();
const showBancosModal = ref(false);
const showRegimenesModal = ref(false);

const props = defineProps({
    row: { type: Object, required: true },
    regimenes: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit', 'changed']);

const schema = toTypedSchema(yup.object({
    regimen_pensionario_id: yup.string().nullable(),
    cuspp: yup.string().nullable(),
    tipo_comision: yup.string().nullable().oneOf(['', 'FLUJO', 'MIXTA', 'SALDO']),
    banco_id: yup.string().nullable(),
    cuenta_ahorro: yup.string().nullable(),
}));

const { defineField, handleSubmit: validateForm, setValues } = useForm({
    validationSchema: schema,
    initialValues: { regimen_pensionario_id: '', cuspp: '', tipo_comision: '', banco_id: '', cuenta_ahorro: '' },
});

const [regimen_pensionario_id] = defineField('regimen_pensionario_id');
const [cuspp] = defineField('cuspp');
const [tipo_comision] = defineField('tipo_comision');
const [banco_id] = defineField('banco_id');
const [cuenta_ahorro] = defineField('cuenta_ahorro');

const esAfp = computed(() => {
    const regimen = props.regimenes.find((r) => r.id === regimen_pensionario_id.value);
    return regimen?.tipo === 'AFP';
});

watch(esAfp, (afp) => {
    if (!afp) {
        tipo_comision.value = '';
    }
});

watch(() => props.row, (row) => {
    setValues({
        regimen_pensionario_id: row?.regimen_pensionario_id || '',
        cuspp: row?.cuspp || '',
        tipo_comision: row?.tipo_comision || '',
        banco_id: row?.banco_id || '',
        cuenta_ahorro: row?.cuenta_ahorro || '',
    });
}, { immediate: true });

const onSubmit = validateForm((values) => {
    emit('submit', {
        regimen_pensionario_id: values.regimen_pensionario_id || null,
        cuspp: values.cuspp || null,
        tipo_comision: values.tipo_comision || null,
        banco_id: values.banco_id || null,
        cuenta_ahorro: values.cuenta_ahorro || null,
    });
});

onMounted(fetchBancos);
</script>
