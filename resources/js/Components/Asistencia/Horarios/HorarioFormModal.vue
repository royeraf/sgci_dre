<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { AlertTriangle, Loader2, X } from 'lucide-vue-next';

const emit = defineEmits<{ saved: [horario: any]; }>();

const show    = ref(false);
const isEdit  = ref(false);
const saving  = ref(false);
const horarioId = ref<number | null>(null);
const errors  = ref<Record<string, string>>({});

const emptyForm = () => ({
    nombre: '', descripcion: '',
    entrada_manana: '08:00', salida_manana: '13:00',
    entrada_tarde: '15:00', salida_tarde: '18:00',
    activo: true,
});
const form = ref(emptyForm());

const open = (horario?: any) => {
    isEdit.value    = !!horario;
    horarioId.value = horario?.id ?? null;
    errors.value    = {};
    form.value = horario ? {
        nombre:         horario.nombre,
        descripcion:    horario.descripcion ?? '',
        entrada_manana: horario.entrada_manana,
        salida_manana:  horario.salida_manana,
        entrada_tarde:  horario.entrada_tarde ?? '',
        salida_tarde:   horario.salida_tarde  ?? '',
        activo:         horario.activo,
    } : emptyForm();
    show.value = true;
};

const save = async () => {
    saving.value = true;
    errors.value = {};
    try {
        const payload = {
            ...form.value,
            entrada_tarde: form.value.entrada_tarde || null,
            salida_tarde:  form.value.salida_tarde  || null,
        };
        const res = isEdit.value
            ? await axios.put(`/asistencia/api/horarios/${horarioId.value}`, payload, { headers: { Accept: 'application/json' } })
            : await axios.post('/asistencia/api/horarios', payload, { headers: { Accept: 'application/json' } });
        emit('saved', res.data);
        show.value = false;
        window.Swal.fire({ icon: 'success', title: isEdit.value ? 'Actualizado' : '¡Creado!', text: 'El horario fue guardado correctamente.', timer: 1800, showConfirmButton: false });
    } catch (err: any) {
        const data = err.response?.data;
        if (data?.errors) {
            const m: Record<string, string> = {};
            for (const [k, v] of Object.entries(data.errors as Record<string, string[]>)) m[k] = v[0];
            errors.value = m;
        } else {
            window.Swal.fire({ icon: 'error', title: 'Error', text: data?.message ?? 'No se pudo guardar.' });
        }
    } finally {
        saving.value = false;
    }
};

defineExpose({ open });
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="show = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="bg-gradient-to-r from-indigo-600 to-blue-600 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">{{ isEdit ? 'Editar horario' : 'Nuevo horario' }}</h3>
                        <p class="text-indigo-100 text-sm mt-0.5">Configure los horarios de entrada y salida</p>
                    </div>
                    <button @click="show = false" class="p-1.5 rounded-lg text-indigo-200 hover:text-white hover:bg-indigo-700/40 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.nombre" placeholder="Ej: Horario General DRE, Turno Mañana…"
                            :class="['w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none', errors.nombre ? 'border-red-400 bg-red-50' : 'border-slate-200']" />
                        <p v-if="errors.nombre" class="mt-1 text-xs text-red-500 flex items-center gap-1"><AlertTriangle class="w-3 h-3"/>{{ errors.nombre }}</p>
                    </div>

                    <!-- Jornada mañana -->
                    <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                        <p class="text-xs font-bold text-blue-700 mb-2 uppercase tracking-wide">Jornada mañana</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Entrada <span class="text-red-500">*</span></label>
                                <input type="time" v-model="form.entrada_manana"
                                    :class="['w-full px-3 py-2 border rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none', errors.entrada_manana ? 'border-red-400' : 'border-slate-200']" />
                                <p v-if="errors.entrada_manana" class="mt-0.5 text-xs text-red-500">{{ errors.entrada_manana }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Salida <span class="text-red-500">*</span></label>
                                <input type="time" v-model="form.salida_manana"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Jornada tarde (opcional) -->
                    <div class="bg-orange-50 rounded-xl p-3 border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 mb-2 uppercase tracking-wide">Jornada tarde <span class="text-slate-400 font-normal normal-case">(dejar vacío si turno continuo)</span></p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Entrada</label>
                                <input type="time" v-model="form.entrada_tarde"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Salida</label>
                                <input type="time" v-model="form.salida_tarde"
                                    class="w-full px-3 py-2 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Descripción</label>
                        <textarea v-model="form.descripcion" rows="2" maxlength="500"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"
                            placeholder="Observaciones sobre este horario..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="show = false"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="save" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 disabled:opacity-50 transition-all shadow-md">
                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
