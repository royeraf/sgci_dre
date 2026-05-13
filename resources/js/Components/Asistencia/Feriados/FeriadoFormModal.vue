<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { AlertTriangle, Loader2, Star, X } from 'lucide-vue-next';

const props = defineProps<{
    feriadosByDate: Record<string, any>;
}>();

const emit = defineEmits<{ added: [feriado: any] }>();

const show   = ref(false);
const saving = ref(false);
const form   = ref({ fecha: '', nombre: '', tipo: 'FERIADO' });
const errors = ref<Record<string, string>>({});

const tipoOptions = [
    { value: 'FERIADO',      label: 'Feriado Nacional' },
    { value: 'NO_LABORABLE', label: 'No Laborable'     },
];

const open  = () => { form.value = { fecha: '', nombre: '', tipo: 'FERIADO' }; errors.value = {}; show.value = true; };
const close = () => { show.value = false; };

const validate = (): boolean => {
    const errs: Record<string, string> = {};

    if (!form.value.fecha) {
        errs.fecha = 'La fecha es obligatoria.';
    } else {
        const d = new Date(form.value.fecha + 'T00:00:00');
        if (isNaN(d.getTime())) errs.fecha = 'Fecha inválida.';
        else if (props.feriadosByDate[form.value.fecha])
            errs.fecha = 'Esta fecha ya está registrada como día no laborable.';
    }

    const nombre = form.value.nombre.trim();
    if (!nombre)              errs.nombre = 'El nombre o motivo es obligatorio.';
    else if (nombre.length < 3)   errs.nombre = 'Debe tener al menos 3 caracteres.';
    else if (nombre.length > 200) errs.nombre = 'Máximo 200 caracteres.';

    if (!form.value.tipo) errs.tipo = 'Seleccione un tipo.';

    errors.value = errs;
    return Object.keys(errs).length === 0;
};

const formatFecha = (iso: string) => {
    const [y, m, d] = iso.split('-');
    return `${d}/${m}/${y}`;
};

const save = async () => {
    if (!validate()) return;

    const tipoLabel = form.value.tipo === 'FERIADO' ? 'Feriado Nacional' : 'No Laborable';
    const result = await window.Swal.fire({
        title: '¿Confirmar registro?',
        html: `<b>${form.value.nombre.trim()}</b><br><span style="color:#64748b;font-size:0.85em">${formatFecha(form.value.fecha)} · ${tipoLabel}</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, guardar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#64748b',
    });

    if (!result.isConfirmed) return;

    saving.value = true;
    try {
        const res = await axios.post('/asistencia/api/feriados', {
            fecha:  form.value.fecha,
            nombre: form.value.nombre.trim(),
            tipo:   form.value.tipo,
        }, { headers: { Accept: 'application/json' } });

        emit('added', res.data);
        show.value = false;
        window.Swal.fire({ icon: 'success', title: '¡Guardado!', text: 'Día no laborable registrado correctamente.', timer: 2000, showConfirmButton: false });
    } catch (err: any) {
        const status = err.response?.status;
        const data   = err.response?.data;
        if (data?.errors) {
            const mapped: Record<string, string> = {};
            for (const [k, v] of Object.entries(data.errors as Record<string, string[]>))
                mapped[k] = v[0];
            errors.value = mapped;
        } else {
            const msg = data?.message ?? (typeof data === 'string' ? `Error ${status}: respuesta inesperada` : `Error ${status ?? 'de red'}`);
            window.Swal.fire({ icon: 'error', title: `Error ${status ?? ''}`, text: msg });
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
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="close"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">

                <div class="bg-gradient-to-r from-amber-500 to-amber-600 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white">Agregar día no laborable</h3>
                        <p class="text-amber-100 text-sm mt-0.5">Complete los datos del día festivo o no laborable</p>
                    </div>
                    <button @click="close" class="p-1.5 rounded-lg text-amber-200 hover:text-white hover:bg-amber-700/40 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Fecha -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            Fecha <span class="text-red-500">*</span>
                        </label>
                        <input type="date" v-model="form.fecha"
                            :class="['w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none transition-colors',
                                errors.fecha ? 'border-red-400 bg-red-50' : 'border-slate-200']" />
                        <p v-if="errors.fecha" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <AlertTriangle class="w-3 h-3 flex-shrink-0" />{{ errors.fecha }}
                        </p>
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            Nombre / Motivo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" v-model="form.nombre"
                            placeholder="Ej: Año Nuevo, Día del Trabajo, Navidad..."
                            maxlength="200"
                            :class="['w-full px-3 py-2.5 border rounded-xl text-sm focus:ring-2 focus:ring-amber-500 outline-none transition-colors',
                                errors.nombre ? 'border-red-400 bg-red-50' : 'border-slate-200']" />
                        <div class="flex items-start justify-between mt-1.5">
                            <p v-if="errors.nombre" class="text-xs text-red-500 flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3 flex-shrink-0" />{{ errors.nombre }}
                            </p>
                            <span class="text-xs text-slate-400 ml-auto">{{ form.nombre.length }}/200</span>
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button v-for="opt in tipoOptions" :key="opt.value" type="button"
                                @click="form.tipo = opt.value"
                                :class="['px-4 py-3 rounded-xl border-2 text-sm font-semibold transition-all',
                                    form.tipo === opt.value
                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                        : 'border-slate-200 text-slate-600 hover:border-slate-300']">
                                {{ opt.label }}
                            </button>
                        </div>
                        <p v-if="errors.tipo" class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                            <AlertTriangle class="w-3 h-3 flex-shrink-0" />{{ errors.tipo }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="close"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="button" @click="save" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 disabled:opacity-50 transition-all shadow-md shadow-amber-500/20">
                            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
                            <Star v-else class="w-4 h-4" />
                            {{ saving ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </Teleport>
</template>
