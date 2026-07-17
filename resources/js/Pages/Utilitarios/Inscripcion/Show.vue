<template>
    <div class="min-h-screen bg-gradient-to-br from-[var(--accent-05)] via-white to-[var(--accent-05)] py-6 sm:py-8 md:py-12 px-3 sm:px-4 md:px-6 lg:px-8" :style="accentStyle">
        <div class="max-w-2xl mx-auto">
            <!-- Header Card -->
            <div class="relative overflow-hidden rounded-3xl sm:rounded-[2rem] text-white mb-6 sm:mb-8 shadow-2xl shadow-black/20">
                <img :src="evento.imagen_fondo_url || '/images/fondo_eventos.jpg'" alt=""
                    class="absolute inset-0 w-full h-full object-cover" />
                <div class="absolute inset-0 bg-gradient-to-r from-stone-950 via-stone-950/85 to-stone-950/40"></div>
                <div class="absolute inset-0 bg-gradient-to-t from-stone-950/70 via-transparent to-stone-950/20"></div>

                <button type="button" @click="showImagenCompleta = true"
                    class="absolute bottom-3 right-3 sm:bottom-5 sm:right-5 z-20 inline-flex items-center gap-1.5 px-3 py-2 rounded-full bg-black/40 backdrop-blur-sm border border-white/20 text-white text-xs sm:text-sm font-semibold hover:bg-black/60 transition-colors">
                    <Maximize2 class="w-3.5 h-3.5 flex-shrink-0" />
                    <span class="hidden sm:inline">Ver imagen</span>
                </button>

                <div class="relative z-10 px-6 py-8 sm:px-10 sm:py-10 md:px-14 md:py-12">
                    <div class="flex items-center gap-3 mb-6">
                        <img src="/images/logo.png" alt="DRE Huánuco" class="h-7 sm:h-8 w-auto opacity-90 brightness-0 invert" />
                        <div class="w-px h-5 bg-white/20"></div>
                        <span class="text-[11px] font-bold uppercase tracking-[0.15em] text-[var(--accent-light)]">
                            {{ tipoLabel(evento.tipo) }}
                        </span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight leading-tight max-w-xl">
                        {{ evento.titulo }}
                    </h1>
                    <div v-if="evento.descripcion" class="rich-description text-white/60 text-sm sm:text-base mt-3 max-w-lg"
                        v-html="sanitizedDescripcion"></div>

                    <div class="flex flex-col gap-2 text-xs sm:text-sm text-white/70 mt-6">
                        <span class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <Calendar class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Fecha:</span>
                            <span>{{ formatFecha(evento.fecha_inicio) }}<template v-if="evento.fecha_fin !== evento.fecha_inicio"> al {{ formatFecha(evento.fecha_fin) }}</template></span>
                        </span>
                        <span v-if="evento.hora_inicio" class="inline-flex items-center gap-1.5 whitespace-nowrap">
                            <Clock class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Hora:</span>
                            <span>{{ formatHora(evento.hora_inicio) }}<template v-if="evento.hora_fin"> - {{ formatHora(evento.hora_fin) }}</template></span>
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <component :is="modalidadIcon(evento.modalidad)" class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">{{ modalidadLabel(evento.modalidad) }}</span><template v-if="evento.modalidad !== 'virtual' && evento.lugar"> · {{ evento.lugar }}</template>
                        </span>
                        <a v-if="evento.modalidad !== 'presencial' && evento.enlace_virtual" :href="evento.enlace_virtual"
                            target="_blank" rel="noopener noreferrer"
                            class="inline-flex items-center gap-1.5 min-w-0 underline decoration-dotted hover:text-white transition-colors">
                            <Video class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90 flex-shrink-0">Enlace de reunión:</span>
                            <span class="truncate">{{ evento.enlace_virtual }}</span>
                        </a>
                        <span v-if="evento.expositores.length" class="inline-flex items-center gap-1.5">
                            <Mic class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span class="font-semibold text-white/90">Ponente:</span>
                            <span v-for="(exp, i) in evento.expositores" :key="i">
                                {{ exp.nombre }}<span v-if="i < evento.expositores.length - 1">, </span>
                            </span>
                        </span>
                        <span v-if="evento.cupo_maximo" class="inline-flex items-center gap-1.5">
                            <Users class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span>{{ Math.max(evento.cupo_maximo - evento.inscritos_count, 0) }} cupo(s) disponible(s) de {{ evento.cupo_maximo }}</span>
                        </span>
                        <span v-if="evento.horas_educativas" class="inline-flex items-center gap-1.5">
                            <Clock class="w-3.5 h-3.5 text-[var(--accent-light)] flex-shrink-0" />
                            <span>{{ evento.horas_educativas }} horas educativas</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <Teleport to="body">
            <div v-if="showImagenCompleta" @click.self="showImagenCompleta = false"
                class="fixed inset-0 z-[9999] bg-black/90 flex items-center justify-center p-4 sm:p-8">
                <button type="button" @click="showImagenCompleta = false"
                    class="absolute top-4 right-4 sm:top-6 sm:right-6 p-2 rounded-full bg-white/10 hover:bg-white/20 text-white transition-colors">
                    <X class="w-5 h-5 sm:w-6 sm:h-6" />
                </button>
                <img :src="evento.imagen_fondo_url || '/images/fondo_eventos.jpg'" alt="Imagen del evento"
                    class="max-w-full max-h-full object-contain rounded-lg shadow-2xl" />
            </div>
        </Teleport>

        <div class="max-w-2xl mx-auto">
            <!-- No disponible -->
            <div v-if="!disponible" class="bg-white rounded-3xl shadow-xl p-8 text-center border border-slate-100">
                <AlertCircle class="w-14 h-14 text-amber-500 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-slate-800 mb-2">Inscripciones no disponibles</h2>
                <p class="text-slate-500">{{ motivo }}</p>
            </div>

            <!-- Confirmación de éxito -->
            <div v-else-if="enviado" class="bg-white rounded-3xl shadow-xl p-8 text-center border border-slate-100">
                <CheckCircle2 class="w-14 h-14 text-green-500 mx-auto mb-4" />
                <h2 class="text-xl font-bold text-slate-800 mb-2">¡Inscripción registrada!</h2>
                <p class="text-slate-500">Gracias por registrarte, {{ nombres }}. Tu inscripción a este evento ha sido confirmada.</p>
                <p v-if="emailEnviado" class="text-slate-400 text-sm mt-2">Te enviamos un correo de confirmación a {{ correo }}.</p>
            </div>

            <!-- Formulario de inscripción -->
            <div v-else class="bg-white rounded-3xl shadow-xl p-5 sm:p-8 border border-slate-100">
                <h2 class="text-lg sm:text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <UserPlus class="w-5 h-5 text-[var(--accent)]" />
                    Formulario de Inscripción
                </h2>

                <form @submit.prevent="onSubmit" class="space-y-4">
                    <div class="bg-gradient-to-br from-[var(--accent-05)] to-[var(--accent-10)] rounded-xl p-4 border border-[var(--accent-15)] mb-2">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1.5">DNI <span class="text-red-500">*</span></label>
                                <div class="flex">
                                    <div class="flex-1 relative">
                                        <input v-model="numeroDocumento" v-bind="numeroDocumentoProps" type="text"
                                            :maxlength="8" placeholder="########"
                                            @input="handleDocumentoInput" @keypress.enter.prevent="buscarPorDni"
                                            class="w-full px-4 py-3 border-2 text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none font-mono tracking-wider rounded-l-xl border-r-0"
                                            :class="formErrors.numero_documento ? 'border-red-400' : 'border-[var(--accent-30)]'" />
                                        <div v-if="isConsultandoDni" class="absolute right-3 top-1/2 -translate-y-1/2">
                                            <Loader2 class="w-4 h-4 animate-spin text-[var(--accent)]" />
                                        </div>
                                    </div>
                                    <button type="button" @click="buscarPorDni"
                                        :disabled="numeroDocumento?.length !== 8 || isConsultandoDni"
                                        class="px-4 py-3 bg-gradient-to-r from-[var(--accent)] to-[var(--accent-dark)] text-white rounded-r-xl hover:brightness-110 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1 flex-shrink-0"
                                        title="Buscar datos por DNI">
                                        <Search class="w-4 h-4" />
                                    </button>
                                </div>
                                <p v-if="formErrors.numero_documento" class="mt-1 text-sm text-red-600">{{ formErrors.numero_documento }}</p>
                            </div>
                        </div>

                        <p v-if="dniConsultaMessage" class="mt-3 text-sm px-3 py-2 rounded-lg"
                            :class="dniConsultaSuccess ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'">
                            {{ dniConsultaMessage }}
                        </p>

                        <button v-if="nombreAutocompletado && !camposEditables" type="button" @click="camposEditables = true"
                            class="mt-2 text-sm text-[var(--accent-dark)] hover:opacity-80 font-medium underline">
                            ¿Necesita editar los nombres manualmente?
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Nombres <span class="text-red-500">*</span></label>
                            <input v-model="nombres" v-bind="nombresProps" type="text" placeholder="Ingrese sus nombres"
                                :disabled="nombreAutocompletado && !camposEditables"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none disabled:bg-slate-50 disabled:text-slate-500"
                                :class="formErrors.nombres ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.nombres" class="mt-1 text-sm text-red-600">{{ formErrors.nombres }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Apellidos <span class="text-red-500">*</span></label>
                            <input v-model="apellidos" v-bind="apellidosProps" type="text" placeholder="Ingrese sus apellidos"
                                :disabled="nombreAutocompletado && !camposEditables"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none disabled:bg-slate-50 disabled:text-slate-500"
                                :class="formErrors.apellidos ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.apellidos" class="mt-1 text-sm text-red-600">{{ formErrors.apellidos }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Género <span class="text-red-500">*</span></label>
                            <select v-model="genero" v-bind="generoProps"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.genero ? 'border-red-400' : 'border-slate-200'">
                                <option value="" disabled>Seleccionar...</option>
                                <option value="Masculino">Masculino</option>
                                <option value="Femenino">Femenino</option>
                            </select>
                            <p v-if="formErrors.genero" class="mt-1 text-sm text-red-600">{{ formErrors.genero }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Correo electrónico <span class="text-red-500">*</span></label>
                            <input v-model="correo" v-bind="correoProps" type="email" placeholder="correo@ejemplo.com"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none"
                                :class="formErrors.correo ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.correo" class="mt-1 text-sm text-red-600">{{ formErrors.correo }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Celular <span class="text-red-500">*</span></label>
                            <input v-model="celular" v-bind="celularProps" type="text" placeholder="999 999 999" maxlength="9"
                                @input="handleCelularInput"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none"
                                :class="formErrors.celular ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.celular" class="mt-1 text-sm text-red-600">{{ formErrors.celular }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Dirección <span class="text-red-500">*</span></label>
                            <div v-if="selectedDirectionData"
                                class="flex items-center gap-2 px-4 py-3 border-2 border-[var(--accent-30)] bg-[var(--accent-05)] rounded-xl">
                                <Check class="w-4 h-4 text-[var(--accent)] flex-shrink-0" />
                                <p class="flex-1 text-sm font-semibold text-[var(--accent-dark)] truncate">{{ selectedDirectionData.nombre }}</p>
                                <button type="button" @click="clearDirection" class="flex-shrink-0 p-0.5 rounded-full hover:bg-[var(--accent-15)] transition-colors text-[var(--accent)]">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div v-else class="relative">
                                <input type="text" v-model="directionQuery" @focus="showDirectionDropdown = true"
                                    @blur="closeDirectionDropdown" placeholder="Buscar dirección..."
                                    class="w-full px-4 py-3 border-2 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] transition-all duration-200 outline-none pr-10"
                                    :class="formErrors.direction_id ? 'border-red-400' : 'border-slate-200'" />
                                <ChevronDown class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <div v-if="showDirectionDropdown && filteredDirections.length > 0"
                                    class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                    <button type="button" v-for="d in filteredDirections" :key="d.id" @click="selectDirection(d)"
                                        class="w-full text-left px-4 py-2.5 hover:bg-[var(--accent-05)] transition-colors text-sm font-medium text-slate-700 hover:text-[var(--accent-dark)] border-b border-slate-50 last:border-0">
                                        {{ d.nombre }}
                                    </button>
                                </div>
                            </div>
                            <p v-if="formErrors.direction_id" class="mt-1 text-sm text-red-600">{{ formErrors.direction_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Oficina <span class="text-red-500">*</span></label>
                            <div v-if="selectedOfficeData"
                                class="flex items-center gap-2 px-4 py-3 border-2 border-[var(--accent-30)] bg-[var(--accent-05)] rounded-xl">
                                <Check class="w-4 h-4 text-[var(--accent)] flex-shrink-0" />
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-[var(--accent-dark)] truncate">{{ selectedOfficeData.nombre }}</p>
                                    <p v-if="selectedOfficeData.direction?.nombre" class="text-xs text-[var(--accent)] truncate">{{ selectedOfficeData.direction.nombre }}</p>
                                </div>
                                <button type="button" @click="clearOffice" class="flex-shrink-0 p-0.5 rounded-full hover:bg-[var(--accent-15)] transition-colors text-[var(--accent)]">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                            <div v-else class="relative">
                                <input type="text" v-model="officeQuery" @focus="showOfficeDropdown = true"
                                    @blur="closeOfficeDropdown" placeholder="Buscar oficina..."
                                    class="w-full px-4 py-3 border-2 rounded-xl bg-white text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] transition-all duration-200 outline-none pr-10"
                                    :class="formErrors.office_id ? 'border-red-400' : 'border-slate-200'" />
                                <ChevronDown class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                                <div v-if="showOfficeDropdown && filteredOffices.length > 0"
                                    class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-xl max-h-48 overflow-y-auto">
                                    <button type="button" v-for="off in filteredOffices" :key="off.id" @click="selectOffice(off)"
                                        class="w-full text-left px-4 py-2.5 hover:bg-[var(--accent-05)] transition-colors flex flex-col group border-b border-slate-50 last:border-0">
                                        <span class="text-sm font-bold text-slate-700 group-hover:text-[var(--accent-dark)]">{{ off.nombre }}</span>
                                        <span class="text-[10px] text-slate-400 font-medium">{{ off.direction?.nombre || 'Sin Dirección' }}</span>
                                    </button>
                                </div>
                            </div>
                            <p v-if="formErrors.office_id" class="mt-1 text-sm text-red-600">{{ formErrors.office_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Profesión <span class="text-red-500">*</span></label>
                            <input v-model="profesion" v-bind="profesionProps" type="text" placeholder="Ingrese su profesión"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none"
                                :class="formErrors.profesion ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.profesion" class="mt-1 text-sm text-red-600">{{ formErrors.profesion }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Cargo / Ocupación <span class="text-red-500">*</span></label>
                            <input v-model="cargo" v-bind="cargoProps" type="text" placeholder="Ingrese su cargo"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none"
                                :class="formErrors.cargo ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.cargo" class="mt-1 text-sm text-red-600">{{ formErrors.cargo }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Régimen <span class="text-red-500">*</span></label>
                            <select v-model="contractTypeId" v-bind="contractTypeIdProps"
                                class="w-full px-4 py-3 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-[var(--accent-20)] focus:border-[var(--accent)] bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.contract_type_id ? 'border-red-400' : 'border-slate-200'">
                                <option value="" disabled>Seleccione su régimen...</option>
                                <option v-for="regimen in regimenes" :key="regimen.id" :value="regimen.id">
                                    {{ regimen.nombre }}
                                </option>
                            </select>
                            <p v-if="formErrors.contract_type_id" class="mt-1 text-sm text-red-600">{{ formErrors.contract_type_id }}</p>
                        </div>
                    </div>

                    <button type="submit" :disabled="submitting"
                        class="w-full py-3.5 bg-gradient-to-r from-[var(--accent)] to-[var(--accent-dark)] text-white font-bold rounded-xl hover:brightness-110 transition-all shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
                        <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                        {{ submitting ? 'Enviando...' : 'Confirmar Inscripción' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { Calendar, MapPin, Video, Users, Mic, UserPlus, Loader2, AlertCircle, CheckCircle2, Search, Check, X, ChevronDown, Clock, Maximize2 } from 'lucide-vue-next';

const props = defineProps({
    evento: {
        type: Object,
        required: true
    },
    disponible: {
        type: Boolean,
        default: false
    },
    motivo: {
        type: String,
        default: null
    },
    regimenes: {
        type: Array,
        default: () => []
    },
    directions: {
        type: Array,
        default: () => []
    },
    offices: {
        type: Array,
        default: () => []
    }
});

const submitting = ref(false);
const showImagenCompleta = ref(false);

const sanitizedDescripcion = computed(() => DOMPurify.sanitize(props.evento.descripcion || '', {
    ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'u', 's', 'ul', 'ol', 'li', 'a', 'span'],
    ALLOWED_ATTR: ['href', 'target', 'rel'],
}));
const enviado = ref(false);
const emailEnviado = ref(false);

const hexToRgb = (hex) => {
    const clean = (hex || '#d97706').replace('#', '');
    const bigint = parseInt(clean, 16);
    return { r: (bigint >> 16) & 255, g: (bigint >> 8) & 255, b: bigint & 255 };
};

const accentRgba = (hex, alpha) => {
    const { r, g, b } = hexToRgb(hex);
    return `rgba(${r}, ${g}, ${b}, ${alpha})`;
};

const accentShade = (hex, factor) => {
    const { r, g, b } = hexToRgb(hex);
    const clamp = (v) => Math.max(0, Math.min(255, Math.round(v)));
    return `rgb(${clamp(r * factor)}, ${clamp(g * factor)}, ${clamp(b * factor)})`;
};

const accentStyle = computed(() => {
    const color = props.evento.color_primario || '#d97706';
    return {
        '--accent': color,
        '--accent-dark': accentShade(color, 0.75),
        '--accent-light': accentShade(color, 1.25),
        '--accent-05': accentRgba(color, 0.05),
        '--accent-10': accentRgba(color, 0.1),
        '--accent-15': accentRgba(color, 0.15),
        '--accent-20': accentRgba(color, 0.2),
        '--accent-30': accentRgba(color, 0.3),
    };
});

const TIPO_LABELS = {
    curso: 'Curso',
    seminario: 'Seminario',
    capacitacion: 'Capacitación',
    taller: 'Taller',
    conferencia: 'Conferencia',
    otro: 'Evento',
};

const MODALIDAD_LABELS = {
    presencial: 'Presencial',
    virtual: 'Virtual',
    hibrida: 'Híbrida',
};

const tipoLabel = (tipo) => TIPO_LABELS[tipo] || tipo;
const modalidadLabel = (modalidad) => MODALIDAD_LABELS[modalidad] || modalidad;
const modalidadIcon = (modalidad) => modalidad === 'virtual' ? Video : MapPin;

const formatFecha = (fecha) => {
    if (!fecha) return '-';
    const [year, month, day] = fecha.split('-');
    return `${day}/${month}/${year}`;
};

const formatHora = (hora) => hora ? hora.slice(0, 5) : hora;

const inscripcionSchema = toTypedSchema(
    yup.object({
        nombres: yup.string().required('Los nombres son obligatorios').min(2),
        apellidos: yup.string().required('Los apellidos son obligatorios').min(2),
        genero: yup.string().required('Debe seleccionar un género').oneOf(['Masculino', 'Femenino'], 'Debe seleccionar un género'),
        numero_documento: yup.string().required('El DNI es obligatorio').matches(/^\d{8}$/, 'El DNI debe tener exactamente 8 dígitos'),
        correo: yup.string().required('El correo es obligatorio').email('Ingrese un correo válido'),
        celular: yup.string().required('El celular es obligatorio').matches(/^\d{9}$/, 'El celular debe tener 9 dígitos'),
        direction_id: yup.string().required('Debe seleccionar una dirección'),
        office_id: yup.string().required('Debe seleccionar una oficina'),
        cargo: yup.string().required('El cargo es obligatorio'),
        profesion: yup.string().required('La profesión es obligatoria'),
        contract_type_id: yup.string().required('Debe seleccionar un régimen'),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm } = useForm({
    validationSchema: inscripcionSchema,
    initialValues: {
        nombres: '',
        apellidos: '',
        genero: '',
        numero_documento: '',
        correo: '',
        celular: '',
        direction_id: '',
        office_id: '',
        cargo: '',
        profesion: '',
        contract_type_id: '',
    }
});

const [nombres, nombresProps] = defineField('nombres');
const [apellidos, apellidosProps] = defineField('apellidos');
const [genero, generoProps] = defineField('genero');
const [numeroDocumento, numeroDocumentoProps] = defineField('numero_documento');
const [correo, correoProps] = defineField('correo');
const [celular, celularProps] = defineField('celular');
const [directionId, directionIdProps] = defineField('direction_id');
const [officeId, officeIdProps] = defineField('office_id');
const [cargo, cargoProps] = defineField('cargo');
const [profesion, profesionProps] = defineField('profesion');
const [contractTypeId, contractTypeIdProps] = defineField('contract_type_id');

const isConsultandoDni = ref(false);
const dniConsultaMessage = ref('');
const dniConsultaSuccess = ref(false);
const nombreAutocompletado = ref(false);
const camposEditables = ref(true);

const directionQuery = ref('');
const showDirectionDropdown = ref(false);
const selectedDirectionData = ref(null);

const officeQuery = ref('');
const showOfficeDropdown = ref(false);
const selectedOfficeData = ref(null);

const normalizeText = (text) => {
    if (!text) return '';
    return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
};

const filteredDirections = computed(() => {
    if (!directionQuery.value) return props.directions.slice(0, 10);
    const q = normalizeText(directionQuery.value);
    return props.directions.filter((d) => normalizeText(d.nombre).includes(q)).slice(0, 10);
});

const filteredOffices = computed(() => {
    if (!officeQuery.value) return props.offices.slice(0, 10);
    const q = normalizeText(officeQuery.value);
    return props.offices.filter((o) => normalizeText(o.nombre).includes(q)
        || (o.direction?.nombre && normalizeText(o.direction.nombre).includes(q))).slice(0, 10);
});

const selectDirection = (d) => {
    selectedDirectionData.value = d;
    directionId.value = d.id;
    directionQuery.value = d.nombre;
    showDirectionDropdown.value = false;
};

const clearDirection = () => {
    selectedDirectionData.value = null;
    directionId.value = '';
    directionQuery.value = '';
};

const closeDirectionDropdown = () => {
    setTimeout(() => {
        showDirectionDropdown.value = false;
    }, 200);
};

const selectOffice = (o) => {
    selectedOfficeData.value = o;
    officeId.value = o.id;
    officeQuery.value = o.nombre;
    showOfficeDropdown.value = false;
};

const clearOffice = () => {
    selectedOfficeData.value = null;
    officeId.value = '';
    officeQuery.value = '';
};

const closeOfficeDropdown = () => {
    setTimeout(() => {
        showOfficeDropdown.value = false;
    }, 200);
};

const handleCelularInput = (event) => {
    celular.value = event.target.value.replace(/\D/g, '');
};

const handleDocumentoInput = (event) => {
    const cleanValue = event.target.value.replace(/\D/g, '');
    numeroDocumento.value = cleanValue;
    dniConsultaMessage.value = '';
    nombreAutocompletado.value = false;
    camposEditables.value = true;

    if (cleanValue.length === 8) {
        setTimeout(() => {
            if (numeroDocumento.value?.length === 8) buscarPorDni();
        }, 150);
    }
};

const buscarPorDni = async () => {
    if (numeroDocumento.value?.length !== 8) return;

    isConsultandoDni.value = true;
    dniConsultaMessage.value = '';

    try {
        const { data } = await axios.get('/utilitarios/inscripcion/api/consultar-dni', {
            params: { dni: numeroDocumento.value }
        });

        if (data.success && data.data) {
            const persona = data.data;
            nombres.value = persona.nombres;
            if (persona.apellido_paterno || persona.apellido_materno) {
                apellidos.value = `${persona.apellido_paterno || ''} ${persona.apellido_materno || ''}`.trim();
            }
            dniConsultaMessage.value = `Datos encontrados: ${persona.nombre_completo || (nombres.value + ' ' + apellidos.value)}`;
            dniConsultaSuccess.value = true;
            nombreAutocompletado.value = true;
            camposEditables.value = false;
        } else {
            dniConsultaMessage.value = data.message || 'DNI no encontrado. Ingrese los datos manualmente.';
            dniConsultaSuccess.value = false;
            camposEditables.value = true;
        }
    } catch (error) {
        dniConsultaMessage.value = 'Error al consultar DNI. Ingrese los datos manualmente.';
        dniConsultaSuccess.value = false;
        camposEditables.value = true;
    } finally {
        isConsultandoDni.value = false;
    }
};

const onSubmit = validateForm(async (values) => {
    submitting.value = true;
    try {
        const { data } = await axios.post(`/utilitarios/inscripcion/${props.evento.slug}`, values);
        emailEnviado.value = Boolean(data.email_enviado);
        enviado.value = true;
    } catch (error) {
        window.Swal?.fire?.('Error', error.response?.data?.message || 'No se pudo registrar la inscripción.', 'error');
    } finally {
        submitting.value = false;
    }
});
</script>

<style scoped>
.rich-description :deep(p) {
    margin: 0 0 0.5em 0;
}

.rich-description :deep(p:last-child) {
    margin-bottom: 0;
}

.rich-description :deep(ul) {
    list-style: disc;
    padding-left: 1.25em;
    margin: 0 0 0.5em 0;
}

.rich-description :deep(ol) {
    list-style: decimal;
    padding-left: 1.25em;
    margin: 0 0 0.5em 0;
}

.rich-description :deep(a) {
    color: var(--accent-light);
    text-decoration: underline;
}

.rich-description :deep(strong) {
    font-weight: 700;
    color: white;
}
</style>
