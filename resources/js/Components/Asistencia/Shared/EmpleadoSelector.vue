<script setup lang="ts">
import { ref, computed } from 'vue';
import { Search } from 'lucide-vue-next';

const props = defineProps<{
    employees: any[];
    modelValue: number | null;
    accentColor?: string; // cyan | blue
}>();

const emit = defineEmits<{ 'update:modelValue': [id: number | null] }>();

const search = ref('');

const filtered = computed(() => {
    if (!search.value) return props.employees.slice(0, 20);
    const q = search.value.toLowerCase();
    return props.employees
        .filter(e => e.nombre_completo.toLowerCase().includes(q) || e.dni.includes(q))
        .slice(0, 20);
});

const select = (id: number) => {
    emit('update:modelValue', id);
    search.value = '';
};

const accent = computed(() => props.accentColor === 'blue' ? 'blue' : 'cyan');
</script>

<template>
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <label class="block text-sm font-bold text-slate-700 mb-2">Empleado</label>
        <div class="relative max-w-sm">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input v-model="search" type="text" placeholder="Buscar por nombre o DNI..."
                :class="`pl-10 pr-4 py-2.5 w-full border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-${accent}-500 focus:border-${accent}-500 outline-none`" />
        </div>
        <div class="mt-2 flex flex-wrap gap-2">
            <button v-for="emp in filtered" :key="emp.id" @click="select(emp.id)"
                :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all',
                    modelValue === emp.id
                        ? `bg-${accent}-600 text-white border-${accent}-600 shadow-md`
                        : `bg-white text-slate-600 border-slate-200 hover:border-${accent}-300 hover:text-${accent}-700`
                ]">
                {{ emp.nombre_completo }}
                <span class="opacity-60 ml-1">{{ emp.dni }}</span>
            </button>
        </div>
    </div>
</template>
