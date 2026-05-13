<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { Loader2, Plus, Minus } from 'lucide-vue-next';

const props = defineProps<{ employeeId: number | string | null }>();
const emit  = defineEmits<{ saved: [marca: any]; }>();

const show       = ref(false);
const isEdit     = ref(false);
const submitting = ref(false);
const errors     = ref<Record<string, string[]>>({});

const emptyForm = () => ({
    fecha: '', observaciones: '',
    entrada: '', salida_mediodia: '',
    retorno_mediodia: '', salida: '',
    entrada_3: '', salida_3: '',
    entrada_4: '', salida_4: '',
});

const form    = ref(emptyForm());
const visiblePairs = ref(2); // cuántos pares se muestran

// Pares de campos: [entrada, salida, label]
const pairs = [
    { e: 'entrada',          s: 'salida_mediodia',  label: 'Par 1',  colorE: 'bg-emerald-500', colorS: 'bg-orange-400' },
    { e: 'retorno_mediodia', s: 'salida',            label: 'Par 2',  colorE: 'bg-blue-500',    colorS: 'bg-rose-500'   },
    { e: 'entrada_3',        s: 'salida_3',          label: 'Par 3',  colorE: 'bg-violet-500',  colorS: 'bg-pink-500'   },
    { e: 'entrada_4',        s: 'salida_4',          label: 'Par 4',  colorE: 'bg-teal-500',    colorS: 'bg-amber-500'  },
] as const;

const open = (fecha: string, marca?: any) => {
    isEdit.value  = !!marca;
    errors.value  = {};
    form.value    = emptyForm();
    form.value.fecha = fecha;

    if (marca) {
        for (const key of Object.keys(emptyForm())) {
            (form.value as any)[key] = marca[key]?.substring?.(0, 5) ?? marca[key] ?? '';
        }
    }

    // Calcular cuántos pares mostrar
    visiblePairs.value = 2;
    if (marca?.entrada_3 || marca?.salida_3) visiblePairs.value = 3;
    if (marca?.entrada_4 || marca?.salida_4) visiblePairs.value = 4;

    show.value = true;
};

const addPair    = () => { if (visiblePairs.value < 4) visiblePairs.value++; };
const removePair = () => {
    if (visiblePairs.value <= 2) return;
    const p = pairs[visiblePairs.value - 1];
    (form.value as any)[p.e] = '';
    (form.value as any)[p.s] = '';
    visiblePairs.value--;
};

const handleSubmit = async () => {
    submitting.value = true;
    errors.value = {};
    try {
        const res = await axios.post('/asistencia/api/marcas', {
            employee_id: props.employeeId,
            ...form.value,
        });
        emit('saved', res.data);
        show.value = false;
    } catch (err: any) {
        if (err.response?.data?.errors) errors.value = err.response.data.errors;
    } finally {
        submitting.value = false;
    }
};

defineExpose({ open });
</script>

<template>
    <Teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="show = false"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

                <div class="bg-gradient-to-r from-cyan-600 to-blue-600 px-6 py-4">
                    <h3 class="text-lg font-bold text-white">{{ isEdit ? 'Editar Marcas' : 'Registrar Marcas' }}</h3>
                    <p class="text-cyan-100 text-sm">{{ form.fecha }}</p>
                </div>

                <form @submit.prevent="handleSubmit" class="p-5 space-y-3">

                    <!-- Pares entrada/salida -->
                    <div v-for="(pair, idx) in pairs.slice(0, visiblePairs)" :key="pair.label"
                        class="bg-slate-50 rounded-xl p-3 border border-slate-200">
                        <p class="text-xs font-bold text-slate-500 mb-2 uppercase tracking-wide">{{ pair.label }}</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span :class="['w-2 h-2 rounded-full inline-block', pair.colorE]"></span>
                                    Entrada
                                </label>
                                <input type="time" v-model="(form as any)[pair.e]"
                                    class="w-full px-2.5 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-cyan-500 outline-none" />
                                <p v-if="errors[pair.e]" class="mt-0.5 text-xs text-red-500">{{ errors[pair.e][0] }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center gap-1.5">
                                    <span :class="['w-2 h-2 rounded-full inline-block', pair.colorS]"></span>
                                    Salida
                                </label>
                                <input type="time" v-model="(form as any)[pair.s]"
                                    class="w-full px-2.5 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-cyan-500 outline-none" />
                            </div>
                        </div>
                    </div>

                    <!-- Botones agregar/quitar par -->
                    <div class="flex gap-2">
                        <button v-if="visiblePairs < 4" type="button" @click="addPair"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-cyan-600 border border-cyan-200 hover:bg-cyan-50 transition-colors">
                            <Plus class="w-3.5 h-3.5" /> Agregar par de marcas
                        </button>
                        <button v-if="visiblePairs > 2" type="button" @click="removePair"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-red-500 border border-red-200 hover:bg-red-50 transition-colors">
                            <Minus class="w-3.5 h-3.5" /> Quitar último par
                        </button>
                    </div>

                    <!-- Observaciones -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">Observaciones</label>
                        <textarea v-model="form.observaciones" rows="2" maxlength="500"
                            class="w-full px-3 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-cyan-500 outline-none resize-none"
                            placeholder="Comisión de servicios, permiso personal, recuperación..."></textarea>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="show = false"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 disabled:opacity-50 transition-all shadow-md">
                            <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                            {{ submitting ? 'Guardando...' : 'Guardar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
