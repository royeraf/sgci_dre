<script setup>
import { reactive, ref } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    reasons: { type: Array, default: () => [] },
    myEmployee: { type: Object, default: null },
});

const emit = defineEmits(['close', 'created']);

const form = reactive({
    entry_exit_reason_id: '',
    motivo: '',
    fecha_salida: '',
    hora_salida_estimada: '',
    hora_retorno_estimada: '',
    turno: '',
});
const errors = ref({});
const submitting = ref(false);

const reset = () => {
    Object.assign(form, {
        entry_exit_reason_id: '',
        motivo: '',
        fecha_salida: '',
        hora_salida_estimada: '',
        hora_retorno_estimada: '',
        turno: '',
    });
    errors.value = {};
};

const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        const res = await axios.post('/papeletas/solicitar', form);
        emit('created', res.data);
        window.Swal?.fire({ icon: 'success', title: `Papeleta #${res.data.numero_papeleta} creada`, toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
    } catch (err) {
        if (err.response?.data?.errors) {
            errors.value = err.response.data.errors;
        } else {
            window.Swal?.fire({ icon: 'error', title: err.response?.data?.message || 'Error al crear la papeleta', toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        }
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">Nueva Solicitud de Papeleta</h3>
                    <p class="text-indigo-100 text-sm mt-0.5">
                        {{ myEmployee?.person?.apellidos }}, {{ myEmployee?.person?.nombres }}
                    </p>
                </div>
                <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Motivo de salida <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.entry_exit_reason_id"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white outline-none"
                                :class="errors.entry_exit_reason_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione motivo</option>
                                <option v-for="r in reasons" :key="r.id" :value="r.id">{{ r.nombre }}</option>
                            </select>
                            <p v-if="errors.entry_exit_reason_id" class="mt-1 text-xs text-red-600">
                                {{ errors.entry_exit_reason_id[0] }}
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Justificación <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="form.motivo" rows="3" maxlength="500"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none resize-none"
                                :class="errors.motivo ? 'border-red-400' : 'border-slate-200'"
                                placeholder="Describa brevemente el motivo de la salida..."></textarea>
                            <p v-if="errors.motivo" class="mt-1 text-xs text-red-600">{{ errors.motivo[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Fecha de salida <span class="text-red-500">*</span>
                            </label>
                            <input type="date" v-model="form.fecha_salida"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                :class="errors.fecha_salida ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.fecha_salida" class="mt-1 text-xs text-red-600">{{ errors.fecha_salida[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Turno <span class="text-red-500">*</span>
                            </label>
                            <select v-model="form.turno"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white outline-none"
                                :class="errors.turno ? 'border-red-400' : 'border-slate-200'">
                                <option value="">Seleccione turno</option>
                                <option value="Manana">Mañana</option>
                                <option value="Tarde">Tarde</option>
                                <option value="Noche">Noche</option>
                            </select>
                            <p v-if="errors.turno" class="mt-1 text-xs text-red-600">{{ errors.turno[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Hora de salida <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="form.hora_salida_estimada"
                                class="w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                :class="errors.hora_salida_estimada ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="errors.hora_salida_estimada" class="mt-1 text-xs text-red-600">{{ errors.hora_salida_estimada[0] }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Hora de retorno
                                <span class="text-slate-400 font-normal">(opcional)</span>
                            </label>
                            <input type="time" v-model="form.hora_retorno_estimada"
                                class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="$emit('close')"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 disabled:opacity-50 transition-all shadow-md">
                            <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                            {{ submitting ? 'Enviando...' : 'Enviar Solicitud' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
