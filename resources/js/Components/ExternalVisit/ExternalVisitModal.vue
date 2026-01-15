<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div
                    class="bg-gradient-to-r from-purple-600 to-fuchsia-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <component :is="visit ? LogOut : LogIn" class="w-6 h-6" />
                            {{ visit ? 'Registrar Salida de Visita' : 'Registrar Ingreso de Visita' }}
                        </h3>
                        <p class="text-purple-100 text-sm mt-1">
                            {{ visit ? `Registrar hora de salida` : 'Complete los datos del visitante' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-purple-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <!-- Exit mode: just show visit info and ask for exit time -->
                    <template v-if="visit">
                        <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                            <div class="flex items-center gap-4">
                                <div
                                    class="h-14 w-14 rounded-full bg-gradient-to-br from-purple-500 to-fuchsia-600 flex items-center justify-center text-xl font-bold text-white shadow-lg">
                                    {{ (visit.nombres || '?').charAt(0) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 text-lg">{{ visit.nombres }}</p>
                                    <p class="text-sm text-slate-600">DNI: {{ visit.dni }}</p>
                                    <p class="text-sm text-slate-500">Ingresó a las {{ visit.hora_ingreso }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Hora de Salida <span class="text-red-500">*</span>
                            </label>
                            <input type="time" v-model="exitForm.hora_salida"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                :class="{ 'border-red-300': exitForm.errors.hora_salida }" />
                            <p v-if="exitForm.errors.hora_salida" class="mt-1 text-sm text-red-600">{{
                                exitForm.errors.hora_salida }}</p>
                        </div>
                    </template>

                    <!-- New visit mode -->
                    <template v-else>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- DNI -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    DNI <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="form.dni" maxlength="8" placeholder="12345678"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    :class="{ 'border-red-300': form.errors.dni }" />
                                <p v-if="form.errors.dni" class="mt-1 text-sm text-red-600">{{ form.errors.dni }}</p>
                            </div>

                            <!-- Hora Ingreso -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Hora de Ingreso <span class="text-red-500">*</span>
                                </label>
                                <input type="time" v-model="form.hora_ingreso"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    :class="{ 'border-red-300': form.errors.hora_ingreso }" />
                                <p v-if="form.errors.hora_ingreso" class="mt-1 text-sm text-red-600">{{
                                    form.errors.hora_ingreso }}</p>
                            </div>
                        </div>

                        <!-- Nombre Visitante -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Nombre del Visitante <span class="text-red-500">*</span>
                            </label>
                            <input type="text" v-model="form.nombres" placeholder="Nombres y Apellidos"
                                class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                :class="{ 'border-red-300': form.errors.nombres }" />
                            <p v-if="form.errors.nombres" class="mt-1 text-sm text-red-600">{{
                                form.errors.nombres }}</p>
                        </div>

                        <!-- Área y Persona a visitar -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Área u Oficina <span class="text-red-500">*</span>
                                </label>
                                <input type="text" v-model="form.area" placeholder="Ej. Tesorería"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    :class="{ 'border-red-300': form.errors.area }" />
                                <p v-if="form.errors.area" class="mt-1 text-sm text-red-600">{{ form.errors.area }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    A quién visita
                                </label>
                                <input type="text" v-model="form.a_quien_visita"
                                    placeholder="Nombre personal (Opcional)"
                                    class="w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500"
                                    :class="{ 'border-red-300': form.errors.a_quien_visita }" />
                                <p v-if="form.errors.a_quien_visita" class="mt-1 text-sm text-red-600">{{
                                    form.errors.a_quien_visita }}</p>
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Motivo de Visita <span class="text-red-500">*</span>
                            </label>
                            <textarea v-model="form.motivo" rows="3" placeholder="Indique el motivo de la visita..."
                                class="w-full px-4 py-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 resize-none"
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
                            class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white font-bold rounded-xl hover:from-purple-700 hover:to-fuchsia-700 transition-all disabled:opacity-50">
                            <Loader2 v-if="isProcessing" class="w-5 h-5 animate-spin inline mr-2" />
                            {{ visit ? 'Registrar Salida' : 'Registrar Ingreso' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import { LogIn, LogOut, X, Loader2, Printer } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const props = defineProps({
    visit: {
        type: Object,
        default: null
    }
});

const emit = defineEmits(['close']);

const currentTime = new Date().toTimeString().slice(0, 5);

// Form for new visit
const form = useForm({
    dni: '',
    nombres: '',
    hora_ingreso: currentTime,
    area: '',
    a_quien_visita: '',
    motivo: '',
    imprimir_ticket: false
});

// Form for exit
const exitForm = useForm({
    hora_salida: currentTime,
});

const isProcessing = computed(() => form.processing || exitForm.processing);

const submitForm = () => {
    if (props.visit) {
        // Register exit
        exitForm.patch(`/visitors/${props.visit.id}/exit`, {
            onSuccess: () => emit('close'),
        });
    } else {
        // Create new visit
        form.post('/visitors', {
            onSuccess: (page) => {
                emit('close');

                // Show SweetAlert confirmation to print. Use page.props from callback to ensure fresh data.
                // Note: Inertia passed 'page' contains the response props.
                const newVisitId = page.props.flash.new_visit_id;

                if (newVisitId) {
                    Swal.fire({
                        title: 'Visita Registrada',
                        html: `
                            <p class="mb-2">La visita se registró correctamente.</p>
                            <p class="text-sm text-slate-500">¿Desea imprimir el ticket de ingreso?</p>
                        `,
                        icon: 'success',
                        showCancelButton: true,
                        confirmButtonColor: '#9333ea', // purple-600
                        cancelButtonColor: '#64748b', // slate-500
                        confirmButtonText: 'Sí, Imprimir Ticket',
                        cancelButtonText: 'No, gracias',
                        reverseButtons: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const url = `/visitors/${newVisitId}/ticket`;
                            // Try to open in new tab
                            const win = window.open(url, '_blank');
                            // If blocked, fallback (though usually user action like click allows it, inside promise it might be tricky)
                            if (!win) {
                                window.location.href = url;
                            }
                        }
                    });
                }
            },
        });
    }
};
</script>
