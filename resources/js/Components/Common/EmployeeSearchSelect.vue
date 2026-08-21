<template>
    <div class="relative" ref="containerRef">
        <div class="relative">
            <input type="text" :value="searchQuery" @input="onInput" @focus="onFocus" :placeholder="placeholder"
                :disabled="disabled"
                class="w-full px-4 py-2.5 pr-16 border-2 rounded-xl text-sm text-slate-900 placeholder:text-slate-400 bg-white transition-all duration-200 outline-none"
                :class="[accentClasses, invalid ? 'border-red-400' : 'border-slate-200', disabled ? 'opacity-60 cursor-not-allowed' : '']" />
            <button v-if="modelValue" type="button" @click="clear"
                class="absolute right-8 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-0.5">
                <X class="w-3.5 h-3.5" />
            </button>
            <ChevronDown class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        </div>

        <div v-if="showDropdown" class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-60 overflow-y-auto">
            <button v-if="allowEmpty" type="button" @click="select(null)"
                class="w-full text-left px-4 py-2 hover:bg-slate-50 transition-colors text-sm text-slate-500 italic">
                {{ emptyLabel }}
            </button>
            <button type="button" v-for="emp in filteredEmployees" :key="emp.id" @click="select(emp)"
                class="w-full text-left px-4 py-2 hover:bg-slate-50 transition-colors flex items-center justify-between gap-2 group">
                <div class="min-w-0">
                    <p class="font-medium text-slate-700 group-hover:text-slate-900 text-sm truncate">
                        {{ emp.dni }} - {{ emp.nombres }} {{ emp.apellidos }}
                    </p>
                    <!-- Aviso de certificado RENIEC comentado por ahora.
                    <p v-if="emp.tiene_certificado === false" class="flex items-center gap-1 text-[10px] text-amber-600 font-bold">
                        <AlertTriangle class="w-3 h-3 shrink-0" /> Sin certificado RENIEC vigente
                    </p>
                    -->
                </div>
                <Check v-if="modelValue === emp.id" class="w-4 h-4 text-indigo-600 shrink-0" />
            </button>
            <div v-if="filteredEmployees.length === 0 && !allowEmpty" class="px-4 py-3 text-xs text-slate-400 text-center">
                Sin resultados
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDown, Check, X, AlertTriangle } from 'lucide-vue-next';

const props = defineProps({
    employees: { type: Array, default: () => [] },
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Buscar por nombre o DNI...' },
    allowEmpty: { type: Boolean, default: true },
    emptyLabel: { type: String, default: 'Sin asignar' },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
    // Color de foco/borde, para calzar con el resto de campos del formulario
    // donde se use: 'indigo' (por defecto), 'sky', 'emerald'.
    accent: { type: String, default: 'indigo' },
});

const emit = defineEmits(['update:modelValue']);

const ACCENT_CLASSES = {
    indigo: 'focus:ring-indigo-500/20 focus:border-indigo-500',
    sky: 'focus:ring-sky-500/20 focus:border-sky-500',
    emerald: 'focus:ring-emerald-500/20 focus:border-emerald-500',
};

const accentClasses = computed(() => `focus:ring-4 ${ACCENT_CLASSES[props.accent] || ACCENT_CLASSES.indigo}`);

const searchQuery = ref('');
const showDropdown = ref(false);
const containerRef = ref(null);

const normalize = (text) => (text || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/\s+/g, ' ')
    .trim();

const labelFor = (emp) => `${emp.dni} - ${emp.nombres} ${emp.apellidos}`;

const syncFromModel = () => {
    if (!props.modelValue) {
        searchQuery.value = '';
        return;
    }
    const found = props.employees.find(e => e.id === props.modelValue);
    searchQuery.value = found ? labelFor(found) : '';
};

watch(() => props.modelValue, syncFromModel);
watch(() => props.employees, syncFromModel);
syncFromModel();

const filteredEmployees = computed(() => {
    const raw = searchQuery.value.trim();
    if (!raw) return props.employees.slice(0, 15);

    const search = normalize(raw);
    const terms = search.split(' ').filter(Boolean);

    return props.employees.filter(emp => {
        if ((emp.dni || '').includes(raw)) return true;
        const name = normalize(`${emp.nombres} ${emp.apellidos}`);
        return terms.every(term => name.includes(term));
    }).slice(0, 15);
});

const select = (emp) => {
    emit('update:modelValue', emp ? emp.id : '');
    searchQuery.value = emp ? labelFor(emp) : '';
    showDropdown.value = false;
};

const clear = () => select(null);

const onInput = (e) => {
    searchQuery.value = e.target.value;
    showDropdown.value = true;
    if (!e.target.value) emit('update:modelValue', '');
};

const onFocus = () => {
    showDropdown.value = true;
};

const handleClickOutside = (event) => {
    if (containerRef.value && !containerRef.value.contains(event.target)) {
        showDropdown.value = false;
        syncFromModel();
    }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>
