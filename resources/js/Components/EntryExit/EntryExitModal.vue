<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="entry ? LogIn : Plus" class="w-6 h-6" />
                            {{ entry ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </h3>
                        <p class="text-green-100 text-sm mt-1">
                            {{ entry ? `Registrar hora de retorno` : 'Complete los datos de salida' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-green-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Return mode: just show entry info and ask for return time -->
                    <template v-if="entry">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-full bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                                    {{ (entry.personal || '?').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-lg">{{ entry.personal }}</p>
                                    <p class="text-sm text-slate-600">DNI: {{ entry.dni }}</p>
                                    <p class="text-sm text-slate-500">Salió a las {{ entry.hora_salida }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora de Retorno <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="returnForm.hora_retorno"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                :class="{ 'border-red-300': returnForm.errors.hora_retorno }" />
                            <p v-if="returnForm.errors.hora_retorno" class="mt-1 text-sm text-red-600">{{
                                returnForm.errors.hora_retorno }}</p>
                        </div>
                    </template>

                    <!-- New entry mode -->
                    <template v-else>
                        <!-- Staff Selection -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Seleccionar Personal
                            </label>
                            <select v-model="selectedStaffId" @change="onStaffSelect"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                                <option value="">-- Seleccionar del listado --</option>
                                <option v-for="s in staff" :key="s.id" :value="s.id">
                                    {{ s.nombre }} (DNI: {{ s.dni }})
                                </option>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">O ingrese los datos manualmente</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- DNI -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    DNI <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="form.dni" maxlength="8" placeholder="12345678"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    :class="{ 'border-red-300': form.errors.dni }" />
                                <p v-if="form.errors.dni" class="mt-1 text-sm text-red-600">{{ form.errors.dni }}</p>
                            </div>

                            <!-- Turno -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Turno <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.turno"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white"
                                    :class="{ 'border-red-300': form.errors.turno }">
                                    <option value="">Seleccione turno</option>
                                    <option value="Mañana">Mañana</option>
                                    <option value="Tarde">Tarde</option>
                                    <option value="Noche">Noche</option>
                                </select>
                                <p v-if="form.errors.turno" class="mt-1 text-sm text-red-600">{{ form.errors.turno }}
                                </p>
                            </div>
                        </div>

                        <!-- Nombre Personal -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre del Personal <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.nombre_personal" placeholder="Nombres y Apellidos"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                :class="{ 'border-red-300': form.errors.nombre_personal }" />
                            <p v-if="form.errors.nombre_personal" class="mt-1 text-sm text-red-600">{{
                                form.errors.nombre_personal }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Hora Salida -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Hora de Salida <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="form.hora_salida"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                    :class="{ 'border-red-300': form.errors.hora_salida }" />
                                <p v-if="form.errors.hora_salida" class="mt-1 text-sm text-red-600">{{
                                    form.errors.hora_salida }}</p>
                            </div>

                            <!-- Regimen -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Régimen Laboral
                                </label>
                                <select v-model="form.regimen"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                                    <option value="">Seleccione régimen</option>
                                    <option value="276">D.L. 276</option>
                                    <option value="728">D.L. 728</option>
                                    <option value="CAS">CAS</option>
                                    <option value="Nombrado">Nombrado</option>
                                    <option value="Designado">Designado</option>
                                    <option value="Otro">Otro</option>
                                </select>
                            </div>
                        </div>

                        <!-- Tipo de Motivo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Tipo de Motivo <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-4">
                                <label
                                    class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors flex-1"
                                    :class="{ 'ring-2 ring-green-500 border-green-500 bg-green-50': form.tipo_motivo === 'comision' }">
                                    <input type="radio" v-model="form.tipo_motivo" value="comision"
                                        class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300">
                                    <span class="ml-2 text-sm font-medium text-slate-700">Comisión de Servicios</span>
                                </label>
                                <label
                                    class="flex items-center p-3 border border-slate-200 rounded-xl cursor-pointer hover:bg-slate-50 transition-colors flex-1"
                                    :class="{ 'ring-2 ring-green-500 border-green-500 bg-green-50': form.tipo_motivo === 'permiso' }">
                                    <input type="radio" v-model="form.tipo_motivo" value="permiso"
                                        class="w-4 h-4 text-green-600 focus:ring-green-500 border-slate-300">
                                    <span class="ml-2 text-sm font-medium text-slate-700">Permiso Personal</span>
                                </label>
                            </div>
                            <p v-if="form.errors.tipo_motivo" class="mt-1 text-sm text-red-600">{{
                                form.errors.tipo_motivo }}</p>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Descripción del Motivo <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="form.motivo" rows="3" placeholder="Indique el motivo de la salida..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"
                                :class="{ 'border-red-300': form.errors.motivo }"></textarea>
                            <p v-if="form.errors.motivo" class="mt-1 text-sm text-red-600">{{ form.errors.motivo }}</p>
                        </div>
                    </template>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="isProcessing"
                            class="px-6 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-bold rounded-xl hover:from-green-700 hover:to-emerald-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isProcessing" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ entry ? 'Registrar Retorno' : 'Registrar Salida' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, X, Loader2, LogIn } from 'lucide-vue-next';

const props = defineProps({
    staff: {
        type: Array,
        default: () => []
    },
    entry: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const selectedStaffId = ref('');

// Get current shift
const getCurrentShift = () => {
    const hour = new Date().getHours();
    if (hour >= 6 && hour < 14) return 'Mañana';
    if (hour >= 14 && hour < 22) return 'Tarde';
    return 'Noche';
};

const currentTime = new Date().toTimeString().slice(0, 5);

// Form for new entry
const form = useForm({
    dni: '',
    nombre_personal: '',
    hora_salida: currentTime,
    turno: getCurrentShift(),
    regimen: '',
    tipo_motivo: 'comision',
    motivo: '',
    staff_id: null,
});

// Form for return
const returnForm = useForm({
    hora_retorno: currentTime,
});

const isProcessing = computed(() => form.processing || returnForm.processing);

const onStaffSelect = () => {
    const selected = props.staff.find(s => s.id === selectedStaffId.value);
    if (selected) {
        form.dni = selected.dni;
        form.nombre_personal = selected.nombre;
        form.regimen = selected.regimen || '';
        form.staff_id = selected.id;
    }
};

const submitForm = () => {
    if (props.entry) {
        // Register return
        returnForm.patch(`/entry-exits/${props.entry.id}/return`, {
            onSuccess: () => emit('close'),
        });
    } else {
        // Create new entry
        form.post('/entry-exits', {
            onSuccess: () => emit('close'),
        });
    }
};
</script>
