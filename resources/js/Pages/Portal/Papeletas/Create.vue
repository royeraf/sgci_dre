<template>
    <PortalLayout>
        <!-- Header -->
        <div class="flex items-center gap-3 mb-6">
            <Link href="/portal/papeletas"
                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors">
                <ArrowLeft class="h-5 w-5" />
            </Link>
            <div>
                <h1 class="text-2xl font-black text-slate-900">Nueva Papeleta de Salida</h1>
                <p class="text-sm text-slate-500">{{ employee.full_name }} - {{ employee.direction_nombre }}</p>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white/80 backdrop-blur-md rounded-2xl shadow-xl border border-white p-6 sm:p-8">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Reason -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Motivo de Salida *</label>
                    <select v-model="form.entry_exit_reason_id"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                        :class="{ 'border-red-300': form.errors.entry_exit_reason_id }">
                        <option value="">Seleccione un motivo</option>
                        <option v-for="r in reasons" :key="r.id" :value="r.id">
                            {{ r.nombre }} ({{ r.tipo === 'comision' ? 'Comision' : r.tipo === 'permiso' ? 'Permiso' : 'Ambos' }})
                        </option>
                    </select>
                    <p v-if="form.errors.entry_exit_reason_id" class="mt-1 text-xs text-red-500">{{ form.errors.entry_exit_reason_id }}</p>
                </div>

                <!-- Date & Shift row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de Salida *</label>
                        <input type="date" v-model="form.fecha_salida" :min="today"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                            :class="{ 'border-red-300': form.errors.fecha_salida }" />
                        <p v-if="form.errors.fecha_salida" class="mt-1 text-xs text-red-500">{{ form.errors.fecha_salida }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Turno *</label>
                        <select v-model="form.turno"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                            :class="{ 'border-red-300': form.errors.turno }">
                            <option value="Manana">Manana</option>
                            <option value="Tarde">Tarde</option>
                            <option value="Noche">Noche</option>
                        </select>
                        <p v-if="form.errors.turno" class="mt-1 text-xs text-red-500">{{ form.errors.turno }}</p>
                    </div>
                </div>

                <!-- Times -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hora Salida Estimada *</label>
                        <input type="time" v-model="form.hora_salida_estimada"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                            :class="{ 'border-red-300': form.errors.hora_salida_estimada }" />
                        <p v-if="form.errors.hora_salida_estimada" class="mt-1 text-xs text-red-500">{{ form.errors.hora_salida_estimada }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Hora Retorno Estimada</label>
                        <input type="time" v-model="form.hora_retorno_estimada"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                            :class="{ 'border-red-300': form.errors.hora_retorno_estimada }" />
                        <p v-if="form.errors.hora_retorno_estimada" class="mt-1 text-xs text-red-500">{{ form.errors.hora_retorno_estimada }}</p>
                    </div>
                </div>

                <!-- Justification -->
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Justificacion / Accion a Cumplir *</label>
                    <textarea v-model="form.motivo" rows="4"
                        class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all resize-none"
                        :class="{ 'border-red-300': form.errors.motivo }"
                        placeholder="Describa el motivo de su salida..."></textarea>
                    <p v-if="form.errors.motivo" class="mt-1 text-xs text-red-500">{{ form.errors.motivo }}</p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <Link href="/portal/papeletas"
                        class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                        Cancelar
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-lg shadow-blue-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        {{ form.processing ? 'Enviando...' : 'Enviar Solicitud' }}
                    </button>
                </div>
            </form>
        </div>
    </PortalLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import PortalLayout from '@/Layouts/PortalLayout.vue';
import { ArrowLeft, Send, Loader2 } from 'lucide-vue-next';

const props = defineProps({
    employee: Object,
    reasons: Array,
});

const today = computed(() => new Date().toISOString().split('T')[0]);

const form = useForm({
    entry_exit_reason_id: '',
    fecha_salida: today.value,
    hora_salida_estimada: '',
    hora_retorno_estimada: '',
    turno: 'Manana',
    motivo: '',
});

const submit = () => {
    form.post('/portal/papeletas');
};
</script>
