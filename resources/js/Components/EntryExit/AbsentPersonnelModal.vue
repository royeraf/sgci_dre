<template>
    <div v-if="show" class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/75 backdrop-blur-sm transition-opacity" @click="$emit('close')">
            </div>

            <!-- Modal Panel -->
            <div
                class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-10 overflow-hidden transform transition-all">
                <!-- Header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <UserMinus class="w-6 h-6 text-white" />
                            Personal Ausente Hoy
                        </h3>
                        <p class="text-emerald-100 text-sm mt-1">
                            {{ formattedDate }}
                        </p>
                    </div>
                    <button @click="$emit('close')"
                        class="text-emerald-100 hover:text-white transition-colors p-1 rounded-full hover:bg-white/20">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6 bg-white min-h-[300px]">
                    <div v-if="loading" class="flex flex-col items-center justify-center py-12">
                        <Loader2 class="w-12 h-12 text-emerald-500 animate-spin mb-4" />
                        <p class="text-slate-500 font-medium text-lg">Cargando lista de personal...</p>
                    </div>

                    <div v-else-if="absentPersonnel.length === 0"
                        class="flex flex-col items-center justify-center py-12">
                        <div class="bg-emerald-50 p-6 rounded-full shadow-sm mb-4">
                            <UserCheck class="w-16 h-16 text-emerald-500" />
                        </div>
                        <h4 class="text-xl font-bold text-slate-800">Todo el personal disponible</h4>
                        <p class="text-slate-500 mt-2 text-center max-w-sm">No se han registrado vacaciones ni licencias
                            activas para la fecha de hoy.</p>
                    </div>

                    <div v-else class="overflow-hidden rounded-xl border border-slate-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                            Personal
                                        </th>
                                        <th
                                            class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">
                                            Tipo
                                        </th>
                                        <th
                                            class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">
                                            Periodo
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100">
                                    <tr v-for="person in absentPersonnel" :key="person.id"
                                        class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    class="h-10 w-10 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center text-slate-600 font-bold border border-slate-200 text-sm">
                                                    {{ person.nombres?.charAt(0) || '?' }}
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-bold text-slate-900">{{ person.nombres }}
                                                    </div>
                                                    <div class="text-xs text-slate-500 font-mono">DNI: {{ person.dni }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span
                                                :class="['px-3 py-1 inline-flex text-xs leading-5 font-bold rounded-full border', person.class]">
                                                {{ person.type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex flex-col">
                                                <span class="text-sm font-bold text-slate-700">
                                                    {{ person.desde }} - {{ person.hasta }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-slate-50 px-6 py-4 flex justify-end border-t border-slate-200">
                    <button type="button" @click="$emit('close')"
                        class="inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-6 py-2.5 bg-white text-sm font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { X, UserMinus, Loader2, UserCheck } from 'lucide-vue-next';

const props = defineProps({
    show: Boolean
});

const emit = defineEmits(['close']);

const loading = ref(true);
const absentPersonnel = ref([]);

const formattedDate = computed(() => {
    return new Date().toLocaleDateString('es-PE', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
});

const fetchAbsentPersonnel = async () => {
    loading.value = true;
    try {
        const response = await axios.get('/entry-exits/api/absent');
        absentPersonnel.value = response.data;
    } catch (error) {
        console.error('Error fetching absent personnel:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    if (props.show) {
        fetchAbsentPersonnel();
    }
});
</script>
