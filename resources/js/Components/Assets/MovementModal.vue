<script setup>
import { ref, shallowRef, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import {
    X,
    Search,
    Loader2,
    Check,
    ChevronDown,
    Trash2,
    ScanBarcode,
    ArrowRightLeft,
    PackageOpen,
    CircleCheck,
    CircleX,
    User,
    ShoppingCart,
} from 'lucide-vue-next';
import axios from 'axios';
import Swal from 'sweetalert2';

// ===== PROPS & EMITS =====
const props = defineProps({
    states:        { type: Array, default: () => [] },
    offices:       { type: Array, default: () => [] },
    employees:     { type: Array, default: () => [] },
    movementTypes: { type: Array, default: () => [] },
    initialMode:   { type: String, default: 'individual' },
});

const emit = defineEmits(['close', 'success']);

// ===== MODE TOGGLE =====
const mode = shallowRef(props.initialMode); // 'individual' | 'masiva'

// ===== SHARED FORM =====
const form = ref({
    tipo_movimiento_id: '',
    fecha:              new Date().toISOString().split('T')[0],
    estado_id:          '',
    oficina_id:         '',
    responsable_id:     '',
    observaciones:      '',
});
const formErrors    = ref({});
const isSubmitting  = shallowRef(false);

// ===== EMPLOYEE SEARCH =====
const empQuery        = shallowRef('');
const showEmpDropdown = shallowRef(false);
const selectedEmployee = ref(null);
const empDropdownRef  = ref(null);

const filteredEmployees = computed(() => {
    const q = empQuery.value.trim();
    if (!q) return props.employees.slice(0, 10);
    const norm  = q.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    const terms = norm.split(' ').filter(t => t.length > 0);
    return props.employees.filter(emp => {
        const name = (emp.nombre_completo || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const dni  = emp.dni || '';
        if (dni.includes(q)) return true;
        return terms.every(t => name.includes(t));
    }).slice(0, 10);
});

const onEmpInput = () => {
    showEmpDropdown.value   = true;
    selectedEmployee.value  = null;
    form.value.responsable_id = '';
};

const selectEmployee = async (emp) => {
    selectedEmployee.value  = emp;
    empQuery.value          = emp.nombre_completo;
    showEmpDropdown.value   = false;
    try {
        const res = await axios.post('/assets/responsibles', { employee_id: emp.id });
        form.value.responsable_id = res.data.id;
    } catch {
        form.value.responsable_id = '';
    }
};

// ===== INDIVIDUAL: ASSET SEARCH =====
const selectedAsset       = ref(null);
const assetSearch         = shallowRef('');
const assetSearchResults  = ref([]);
const searchingAssets     = shallowRef(false);
const showAssetDropdown   = shallowRef(false);
const assetDropdownRef    = ref(null);

let assetSearchTimeout = null;
watch(assetSearch, (val) => {
    if (selectedAsset.value) return;
    clearTimeout(assetSearchTimeout);
    if (val.length < 2) { assetSearchResults.value = []; return; }
    assetSearchTimeout = setTimeout(async () => {
        searchingAssets.value = true;
        try {
            const res = await axios.get('/assets/list', { params: { search: val, per_page: 8 } });
            assetSearchResults.value = res.data.data;
        } catch {
            assetSearchResults.value = [];
        } finally {
            searchingAssets.value = false;
        }
    }, 300);
});

const selectAsset = (asset) => {
    selectedAsset.value       = asset;
    assetSearch.value         = `${asset.codigo_patrimonio} — ${asset.denominacion}`;
    showAssetDropdown.value   = false;
    assetSearchResults.value  = [];
    delete formErrors.value.asset_id;
    if (asset.latest_movement?.state?.id) {
        form.value.estado_id = asset.latest_movement.state.id;
    }
};

const clearSelectedAsset = () => {
    selectedAsset.value      = null;
    assetSearch.value        = '';
    assetSearchResults.value = [];
};

// ===== MASIVA: BARCODE CART =====
const cartItems          = ref([]);
const barcodeValue       = shallowRef('');
const barcodeInputRef    = ref(null);
const isSearchingBarcode = shallowRef(false);
const scanStatus         = shallowRef(null); // 'added' | 'duplicate' | 'notfound'
let scanStatusTimer = null;

const setScanStatus = (status) => {
    clearTimeout(scanStatusTimer);
    scanStatus.value = status;
    scanStatusTimer  = setTimeout(() => { scanStatus.value = null; }, 2000);
};

const handleScan = async () => {
    const code = barcodeValue.value.trim();
    if (!code || isSearchingBarcode.value) return;
    isSearchingBarcode.value = true;
    try {
        const res = await axios.get('/assets/lookup-barcode', { params: { code } });
        if (!res.data.found) { setScanStatus('notfound'); return; }
        const asset = res.data.asset;
        if (cartItems.value.some(i => i.id === asset.id)) { setScanStatus('duplicate'); return; }
        // Cada ítem tiene su propio estado_id, pre-poblado con el estado actual del bien
        cartItems.value.push({ ...asset, estado_id: asset.latest_movement?.state?.id || '' });
        barcodeValue.value = '';
        setScanStatus('added');
    } catch {
        setScanStatus('notfound');
    } finally {
        isSearchingBarcode.value = false;
        await nextTick();
        barcodeInputRef.value?.focus();
    }
};

const removeCartItem = (id) => {
    cartItems.value = cartItems.value.filter(i => i.id !== id);
};

// Focus barcode input when switching to masiva mode
watch(mode, async (val) => {
    if (val === 'masiva') {
        await nextTick();
        barcodeInputRef.value?.focus();
    }
});

// ===== VALIDATION & SUBMIT =====
const validate = () => {
    const errors = {};
    if (!form.value.tipo_movimiento_id) errors.tipo_movimiento_id = 'Seleccione el tipo de movimiento';
    if (!form.value.fecha) errors.fecha = 'Ingrese la fecha';
    if (mode.value === 'individual') {
        if (!form.value.estado_id) errors.estado_id = 'Seleccione el estado del bien';
        if (!selectedAsset.value)  errors.asset_id  = 'Debe seleccionar un bien';
    }
    if (mode.value === 'masiva') {
        if (cartItems.value.length === 0)
            errors.cart = 'Agregue al menos un bien escaneando su código';
        else if (cartItems.value.some(i => !i.estado_id))
            errors.cart = 'Seleccione el estado de cada bien en el carrito';
    }
    formErrors.value = errors;
    return Object.keys(errors).length === 0;
};

const submit = async () => {
    if (!validate() || isSubmitting.value) return;
    isSubmitting.value = true;
    try {
        if (mode.value === 'individual') {
            await axios.post(`/assets/${selectedAsset.value.id}/movements`, {
                tipo_movimiento_id: form.value.tipo_movimiento_id,
                fecha:              form.value.fecha,
                estado_id:          form.value.estado_id,
                oficina_id:         form.value.oficina_id     || null,
                responsable_id:     form.value.responsable_id || null,
                observaciones:      form.value.observaciones  || null,
            });
            emit('success', 1);
            emit('close');
            Swal.fire({ icon: 'success', title: 'Movimiento registrado', toast: true, position: 'top-end', showConfirmButton: false, timer: 3000 });
        } else {
            const res = await axios.post('/assets/movements/batch', {
                tipo_movimiento_id: form.value.tipo_movimiento_id,
                fecha:              form.value.fecha,
                oficina_id:         form.value.oficina_id     || null,
                responsable_id:     form.value.responsable_id || null,
                observaciones:      form.value.observaciones  || null,
                items:              cartItems.value.map(i => ({ asset_id: i.id, estado_id: i.estado_id })),
            });
            emit('success', res.data.created);
            emit('close');
            Swal.fire({ icon: 'success', title: `${res.data.created} movimientos registrados`, text: res.data.message, toast: true, position: 'top-end', showConfirmButton: false, timer: 3500 });
        }
    } catch (error) {
        const serverErrors = error.response?.data?.errors;
        if (serverErrors) {
            formErrors.value = { ...formErrors.value, ...serverErrors };
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el movimiento. Intente nuevamente.' });
        }
    } finally {
        isSubmitting.value = false;
    }
};

const submitLabel = computed(() => {
    if (isSubmitting.value) return 'Registrando...';
    if (mode.value === 'masiva') {
        const n = cartItems.value.length;
        return n > 0 ? `Registrar ${n} movimiento${n !== 1 ? 's' : ''}` : 'Registrar';
    }
    return 'Registrar Movimiento';
});

// ===== HELPERS =====
const getStateClass = (nombre) => ({
    BUENO:   'bg-gradient-to-r from-green-500 to-green-600 text-white',
    REGULAR: 'bg-gradient-to-r from-yellow-500 to-yellow-600 text-white',
    MALO:    'bg-gradient-to-r from-red-500 to-red-600 text-white',
}[nombre] || 'bg-gradient-to-r from-gray-500 to-gray-600 text-white');

// ===== CLICK OUTSIDE =====
const handleClickOutside = (e) => {
    if (assetDropdownRef.value && !assetDropdownRef.value.contains(e.target))
        showAssetDropdown.value = false;
    if (empDropdownRef.value && !empDropdownRef.value.contains(e.target))
        showEmpDropdown.value = false;
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    clearTimeout(assetSearchTimeout);
    clearTimeout(scanStatusTimer);
});
</script>

<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-start justify-center min-h-screen px-4 py-8">
            <div class="fixed inset-0 bg-black/60 transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-3xl w-full z-10 overflow-hidden flex flex-col">

                <!-- ===== HEADER ===== -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4 flex justify-between items-center shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <ArrowRightLeft class="w-6 h-6" />
                            Registrar Movimiento
                        </h3>
                        <p class="text-blue-100 text-sm mt-0.5">Complete los datos y seleccione los bienes</p>
                    </div>
                    <button @click="$emit('close')" class="text-blue-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- ===== MODE TOGGLE ===== -->
                <div class="px-6 py-3 bg-slate-50 border-b border-slate-200 shrink-0">
                    <div class="inline-flex bg-white border border-slate-200 rounded-xl p-1 gap-1 shadow-sm">
                        <button type="button" @click="mode = 'individual'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200"
                            :class="mode === 'individual'
                                ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-600/20'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'">
                            <ArrowRightLeft class="w-4 h-4" />
                            Individual
                        </button>
                        <button type="button" @click="mode = 'masiva'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-bold transition-all duration-200"
                            :class="mode === 'masiva'
                                ? 'bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md shadow-emerald-600/20'
                                : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'">
                            <ScanBarcode class="w-4 h-4" />
                            Masiva con Lector
                            <span v-if="mode === 'masiva' && cartItems.length > 0"
                                class="px-1.5 py-0.5 text-[10px] bg-white/30 rounded-full font-black leading-none">
                                {{ cartItems.length }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- ===== BODY ===== -->
                <div class="overflow-y-auto max-h-[70vh]">

                    <!-- MOVEMENT FORM (shared) -->
                    <div class="px-6 pt-5 pb-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                            Datos del Movimiento
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tipo -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                    Tipo <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.tipo_movimiento_id"
                                    class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none text-sm transition-colors"
                                    :class="formErrors.tipo_movimiento_id ? 'border-red-400' : 'border-slate-200'">
                                    <option value="">Seleccione tipo</option>
                                    <option v-for="mt in movementTypes" :key="mt.id" :value="mt.id">
                                        {{ mt.nombre }}
                                    </option>
                                </select>
                                <p v-if="formErrors.tipo_movimiento_id" class="mt-1 text-xs text-red-600">{{ formErrors.tipo_movimiento_id }}</p>
                            </div>

                            <!-- Fecha -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                    Fecha <span class="text-red-500">*</span>
                                </label>
                                <input type="date" v-model="form.fecha"
                                    class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none text-sm transition-colors"
                                    :class="formErrors.fecha ? 'border-red-400' : 'border-slate-200'" />
                                <p v-if="formErrors.fecha" class="mt-1 text-xs text-red-600">{{ formErrors.fecha }}</p>
                            </div>

                            <!-- Estado (solo en modo individual; en masiva se asigna por bien) -->
                            <div v-if="mode === 'individual'">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                    Estado del Bien <span class="text-red-500">*</span>
                                </label>
                                <select v-model="form.estado_id"
                                    class="w-full px-3 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none text-sm transition-colors"
                                    :class="formErrors.estado_id ? 'border-red-400' : 'border-slate-200'">
                                    <option value="">Seleccione estado</option>
                                    <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                                </select>
                                <p v-if="formErrors.estado_id" class="mt-1 text-xs text-red-600">{{ formErrors.estado_id }}</p>
                            </div>

                            <!-- Oficina -->
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Ubicación / Oficina</label>
                                <select v-model="form.oficina_id"
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white outline-none text-sm transition-colors">
                                    <option value="">Sin especificar</option>
                                    <option v-for="office in offices" :key="office.id" :value="office.id">
                                        {{ office.nombre }}{{ office.direction ? ` (${office.direction.nombre})` : '' }}
                                    </option>
                                </select>
                            </div>

                            <!-- Responsable -->
                            <div class="sm:col-span-2 relative" ref="empDropdown">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Responsable</label>
                                <div class="relative">
                                    <input type="text" v-model="empQuery"
                                        @focus="showEmpDropdown = true"
                                        @input="onEmpInput"
                                        placeholder="Buscar por nombre o DNI..."
                                        class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-colors pr-9" />
                                    <ChevronDown class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                </div>
                                <div v-if="showEmpDropdown && filteredEmployees.length > 0"
                                    class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                    <button type="button" v-for="emp in filteredEmployees" :key="emp.id"
                                        @click="selectEmployee(emp)"
                                        class="w-full text-left px-4 py-2 hover:bg-blue-50 transition-colors flex items-center justify-between group border-b border-slate-50 last:border-0">
                                        <div>
                                            <p class="font-medium text-slate-700 group-hover:text-slate-900 text-sm">{{ emp.nombre_completo }}</p>
                                            <p class="text-xs text-slate-400">{{ emp.dni }}</p>
                                        </div>
                                        <Check v-if="selectedEmployee?.id === emp.id" class="w-4 h-4 text-blue-600 shrink-0" />
                                    </button>
                                </div>
                                <div v-if="selectedEmployee"
                                    class="mt-2 flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-xl">
                                    <div class="w-7 h-7 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-xs font-bold text-white shrink-0">
                                        {{ (selectedEmployee.nombre_completo || '?').charAt(0) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-blue-800">{{ selectedEmployee.nombre_completo }}</p>
                                        <p class="text-xs text-blue-500">DNI: {{ selectedEmployee.dni }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">Observaciones</label>
                                <textarea v-model="form.observaciones" rows="2"
                                    placeholder="Detalle o motivo del movimiento (opcional)..."
                                    class="w-full px-3 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none outline-none text-sm transition-colors"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- DIVIDER -->
                    <div class="mx-6 border-t border-slate-200"></div>

                    <!-- ===== INDIVIDUAL: ASSET SEARCH ===== -->
                    <div v-if="mode === 'individual'" class="px-6 py-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                            Bien Patrimonial
                        </p>

                        <div class="relative" ref="assetDropdown">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">
                                Seleccionar Bien <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                <input type="text" v-model="assetSearch"
                                    @focus="showAssetDropdown = true"
                                    placeholder="Buscar por código patrimonial, denominación, serie..."
                                    class="w-full pl-9 pr-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none text-sm transition-colors"
                                    :class="formErrors.asset_id ? 'border-red-400' : 'border-slate-200'" />
                            </div>
                            <p v-if="formErrors.asset_id" class="mt-1 text-xs text-red-600">{{ formErrors.asset_id }}</p>

                            <!-- Search results dropdown -->
                            <div v-if="showAssetDropdown && (assetSearchResults.length > 0 || searchingAssets)"
                                class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-56 overflow-y-auto">
                                <div v-if="searchingAssets" class="p-4 text-center">
                                    <Loader2 class="w-5 h-5 mx-auto text-slate-400 animate-spin" />
                                </div>
                                <button type="button" v-for="asset in assetSearchResults" :key="asset.id"
                                    @click="selectAsset(asset)"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 transition-colors flex items-center justify-between group border-b border-slate-50 last:border-0">
                                    <div>
                                        <p class="font-mono font-bold text-slate-700 group-hover:text-blue-700 text-sm">
                                            {{ asset.codigo_patrimonio }}
                                            <span class="text-slate-400 font-normal text-xs ml-1">{{ asset.codigo_interno }}</span>
                                        </p>
                                        <p class="text-xs text-slate-500 line-clamp-1">{{ asset.denominacion }}</p>
                                    </div>
                                    <span v-if="asset.latest_movement?.state"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-md ml-2 shrink-0"
                                        :class="getStateClass(asset.latest_movement.state.nombre)">
                                        {{ asset.latest_movement.state.nombre }}
                                    </span>
                                </button>
                            </div>

                            <div v-if="showAssetDropdown && assetSearch.length >= 2 && assetSearchResults.length === 0 && !searchingAssets"
                                class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl p-4 text-center text-sm text-slate-500">
                                No se encontraron bienes con ese criterio
                            </div>
                        </div>

                        <!-- Selected asset preview -->
                        <div v-if="selectedAsset"
                            class="mt-3 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start justify-between">
                            <div>
                                <p class="font-mono font-bold text-blue-800 text-sm">
                                    {{ selectedAsset.codigo_patrimonio }}{{ selectedAsset.codigo_interno }}
                                </p>
                                <p class="text-sm text-blue-700 mt-0.5">{{ selectedAsset.denominacion }}</p>
                                <p v-if="selectedAsset.brand" class="text-xs text-blue-500 mt-0.5">
                                    {{ selectedAsset.brand.nombre }}{{ selectedAsset.modelo ? ` — ${selectedAsset.modelo}` : '' }}
                                </p>
                            </div>
                            <button type="button" @click="clearSelectedAsset"
                                class="text-blue-400 hover:text-blue-600 transition-colors p-1 shrink-0">
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- ===== MASIVA: BARCODE CART ===== -->
                    <div v-if="mode === 'masiva'" class="px-6 py-4 space-y-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Escanear Bienes
                        </p>

                        <!-- Barcode input -->
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5 flex items-center gap-2">
                                <ScanBarcode class="w-4 h-4 text-emerald-600" />
                                Código de Barras
                            </label>
                            <div class="relative">
                                <input ref="barcodeInput" v-model="barcodeValue" type="text"
                                    @keydown.enter.prevent="handleScan"
                                    placeholder="Apunte el lector al código de barras del bien..."
                                    class="w-full px-4 py-3 border-2 rounded-xl font-mono font-bold text-slate-800 text-sm outline-none transition-all duration-200 placeholder:font-normal placeholder:text-slate-400"
                                    :class="{
                                        'border-emerald-400 bg-emerald-50 ring-2 ring-emerald-100': scanStatus === 'added',
                                        'border-red-400 bg-red-50 ring-2 ring-red-100': scanStatus === 'notfound',
                                        'border-amber-400 bg-amber-50 ring-2 ring-amber-100': scanStatus === 'duplicate',
                                        'border-slate-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-100': !scanStatus,
                                    }" />
                                <div class="absolute right-3 top-1/2 -translate-y-1/2">
                                    <Loader2 v-if="isSearchingBarcode" class="w-5 h-5 text-blue-400 animate-spin" />
                                    <CircleCheck v-else-if="scanStatus === 'added'" class="w-5 h-5 text-emerald-500" />
                                    <CircleX v-else-if="scanStatus === 'notfound' || scanStatus === 'duplicate'" class="w-5 h-5 text-red-500" />
                                </div>
                            </div>
                            <div class="mt-1.5 h-4">
                                <p v-if="scanStatus === 'added'" class="text-xs font-bold text-emerald-600 flex items-center gap-1">
                                    <CircleCheck class="w-3.5 h-3.5" /> Bien agregado al carrito
                                </p>
                                <p v-else-if="scanStatus === 'notfound'" class="text-xs font-bold text-red-600 flex items-center gap-1">
                                    <CircleX class="w-3.5 h-3.5" /> No se encontró ningún bien con ese código
                                </p>
                                <p v-else-if="scanStatus === 'duplicate'" class="text-xs font-bold text-amber-600 flex items-center gap-1">
                                    <CircleX class="w-3.5 h-3.5" /> Este bien ya está en el carrito
                                </p>
                                <p v-else class="text-xs text-slate-400">Presione Enter o el lector lo hará automáticamente</p>
                            </div>
                            <p v-if="formErrors.cart" class="mt-1 text-xs text-red-600 font-semibold">{{ formErrors.cart }}</p>
                        </div>

                        <!-- Cart items -->
                        <div v-if="cartItems.length === 0"
                            class="py-8 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                            <PackageOpen class="w-10 h-10 text-slate-300 mx-auto mb-2" />
                            <p class="text-sm font-semibold text-slate-400">El carrito está vacío</p>
                            <p class="text-xs text-slate-300 mt-0.5">Escanee el código de cada bien para agregarlos</p>
                        </div>

                        <div v-else class="rounded-xl border border-slate-200 overflow-hidden">
                            <div class="bg-slate-50 px-4 py-2 flex items-center justify-between border-b border-slate-200">
                                <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                                    {{ cartItems.length }} {{ cartItems.length === 1 ? 'bien' : 'bienes' }} en el carrito
                                </span>
                                <span class="text-[10px] text-slate-400 font-medium">Seleccione el estado de cada bien</span>
                            </div>
                            <table class="min-w-full divide-y divide-slate-100">
                                <thead class="bg-slate-50/50">
                                    <tr>
                                        <th class="pl-4 pr-2 py-2 text-left text-[10px] font-bold text-slate-400 uppercase w-8">#</th>
                                        <th class="px-2 py-2 text-left text-[10px] font-bold text-slate-400 uppercase">Código</th>
                                        <th class="px-2 py-2 text-left text-[10px] font-bold text-slate-400 uppercase">Denominación</th>
                                        <th class="px-2 py-2 text-left text-[10px] font-bold text-slate-400 uppercase">Estado <span class="text-red-400">*</span></th>
                                        <th class="pr-3 py-2 w-8"></th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-50">
                                    <tr v-for="(item, idx) in cartItems" :key="item.id"
                                        class="hover:bg-blue-50/40 transition-colors"
                                        :class="{ 'bg-red-50/30': formErrors.cart && !item.estado_id }">
                                        <td class="pl-4 pr-2 py-2 text-xs text-slate-400 font-bold">{{ idx + 1 }}</td>
                                        <td class="px-2 py-2 whitespace-nowrap">
                                            <span class="font-mono font-bold text-slate-800 text-xs">
                                                {{ item.codigo_completo || item.codigo_patrimonio }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2">
                                            <p class="text-sm text-slate-700 line-clamp-1">{{ item.denominacion }}</p>
                                            <p v-if="item.brand" class="text-xs text-slate-400">{{ item.brand.nombre }}</p>
                                        </td>
                                        <td class="px-2 py-2 min-w-[130px]">
                                            <select v-model="item.estado_id"
                                                class="w-full px-2 py-1.5 border rounded-lg text-xs bg-white outline-none focus:ring-2 focus:ring-emerald-500 transition-colors"
                                                :class="!item.estado_id && formErrors.cart ? 'border-red-400' : 'border-slate-200'">
                                                <option value="">— Estado —</option>
                                                <option v-for="st in states" :key="st.id" :value="st.id">{{ st.nombre }}</option>
                                            </select>
                                        </td>
                                        <td class="pr-3 py-2 text-right">
                                            <button type="button" @click="removeCartItem(item.id)"
                                                class="text-slate-300 hover:text-red-500 transition-colors p-1 rounded-lg hover:bg-red-50">
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ===== FOOTER ===== -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end gap-3 shrink-0">
                    <button type="button" @click="$emit('close')"
                        class="px-5 py-2.5 border-2 border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-100 transition-all text-sm">
                        Cancelar
                    </button>
                    <button type="button" @click="submit" :disabled="isSubmitting"
                        class="px-6 py-2.5 font-bold rounded-xl text-white transition-all shadow-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        :class="mode === 'masiva'
                            ? 'bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 shadow-emerald-600/20'
                            : 'bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 shadow-blue-600/20'">
                        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                        <ShoppingCart v-else-if="mode === 'masiva'" class="w-4 h-4" />
                        <ArrowRightLeft v-else class="w-4 h-4" />
                        {{ submitLabel }}
                    </button>
                </div>

            </div>
        </div>
    </div>
</template>
