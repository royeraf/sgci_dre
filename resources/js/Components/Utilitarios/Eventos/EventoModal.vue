<template>
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

            <div class="relative bg-white rounded-2xl shadow-2xl max-w-2xl w-full z-10 overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-amber-600 to-orange-600 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-white flex items-center gap-2">
                            <GraduationCap v-if="!isEditing" class="w-6 h-6" />
                            <Pencil v-else class="w-6 h-6" />
                            {{ isEditing ? 'Editar Evento' : 'Nuevo Evento' }}
                        </h3>
                        <p class="text-amber-50 text-sm mt-1">
                            {{ isEditing ? 'Modifique los datos del curso/seminario' : 'Complete la información del curso, seminario o capacitación' }}
                        </p>
                    </div>
                    <button @click="$emit('close')" class="text-amber-100 hover:text-white transition-colors p-1">
                        <X class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="onSubmit" class="p-6 space-y-6 max-h-[80vh] overflow-y-auto custom-scrollbar">
                    <!-- Datos básicos -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-100 space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Título / Tema <span class="text-red-500">*</span>
                            </label>
                            <input v-model="titulo" v-bind="tituloProps" type="text"
                                placeholder="Ej. Taller de Excel Avanzado"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none"
                                :class="formErrors.titulo ? 'border-red-400 focus:border-red-500 focus:ring-red-500/20 bg-red-50' : 'border-amber-200'" />
                            <p v-if="formErrors.titulo" class="mt-1 text-sm text-red-600">{{ formErrors.titulo }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Tipo <span class="text-red-500">*</span>
                                </label>
                                <select v-model="tipo" v-bind="tipoProps"
                                    class="w-full px-4 py-2.5 border-2 border-amber-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer">
                                    <option value="curso">Curso</option>
                                    <option value="seminario">Seminario</option>
                                    <option value="capacitacion">Capacitación</option>
                                    <option value="taller">Taller</option>
                                    <option value="conferencia">Conferencia</option>
                                    <option value="otro">Otro</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">
                                    Modalidad <span class="text-red-500">*</span>
                                </label>
                                <select v-model="modalidad" v-bind="modalidadProps"
                                    class="w-full px-4 py-2.5 border-2 border-amber-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer">
                                    <option value="presencial">Presencial</option>
                                    <option value="virtual">Virtual</option>
                                    <option value="hibrida">Híbrida</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Descripción</label>
                            <RichTextEditor v-model="descripcion" placeholder="Detalles adicionales sobre el evento..." />
                        </div>
                    </div>

                    <!-- Fechas y lugar -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de inicio <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaInicio" v-bind="fechaInicioProps" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.fecha_inicio ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_inicio" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_inicio }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Fecha de fin <span class="text-red-500">*</span>
                            </label>
                            <input v-model="fechaFin" v-bind="fechaFinProps" type="date"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer"
                                :class="formErrors.fecha_fin ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.fecha_fin" class="mt-1 text-sm text-red-600">{{ formErrors.fecha_fin }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Hora de inicio</label>
                            <input v-model="horaInicio" v-bind="horaInicioProps" type="time"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Hora de fin</label>
                            <input v-model="horaFin" v-bind="horaFinProps" type="time"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none cursor-pointer" />
                        </div>

                        <div v-if="modalidad !== 'virtual'">
                            <label class="block text-sm font-bold text-slate-700 mb-2">
                                Lugar <span class="text-red-500">*</span>
                            </label>
                            <input v-model="lugar" v-bind="lugarProps" type="text" placeholder="Ej. Auditorio DRE Huánuco"
                                class="w-full px-4 py-2.5 border-2 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none"
                                :class="formErrors.lugar ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="formErrors.lugar" class="mt-1 text-sm text-red-600">{{ formErrors.lugar }}</p>
                        </div>

                        <div v-if="modalidad !== 'presencial'">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Enlace virtual</label>
                            <input v-model="enlaceVirtual" v-bind="enlaceVirtualProps" type="text"
                                placeholder="https://meet.google.com/..."
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Cupo máximo</label>
                            <input v-model="cupoMaximo" v-bind="cupoMaximoProps" type="number" min="1"
                                placeholder="Ilimitado"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none" />
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-2">Horas educativas</label>
                            <input v-model="horasEducativas" v-bind="horasEducativasProps" type="number" min="1"
                                placeholder="Ej. 40"
                                class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none" />
                        </div>
                    </div>

                    <!-- Estilo del formulario público -->
                    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-4 border border-amber-100">
                        <label class="block text-sm font-bold text-slate-700 mb-3">Estilo del formulario público</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-2">Color del tema</label>
                                <div class="flex items-center gap-2.5">
                                    <button v-for="color in COLORES_TEMA" :key="color" type="button"
                                        @click="colorPrimario = color" :title="color"
                                        class="w-9 h-9 rounded-full flex items-center justify-center transition-transform hover:scale-110 ring-2 ring-offset-2"
                                        :style="{ backgroundColor: color, '--tw-ring-color': colorPrimario === color ? color : 'transparent' }">
                                        <Check v-if="colorPrimario === color" class="w-4 h-4 text-white drop-shadow" />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-500 mb-2">Imagen de fondo</label>
                                <div class="flex items-center gap-3">
                                    <div class="w-16 h-11 rounded-lg overflow-hidden border-2 border-amber-200 bg-white flex-shrink-0">
                                        <img :src="imagenFondoPreview" alt="" class="w-full h-full object-cover" />
                                    </div>
                                    <label class="flex-1 px-4 py-2.5 border-2 border-dashed border-amber-300 rounded-xl text-slate-500 text-sm text-center cursor-pointer hover:bg-amber-50/50 transition-colors truncate">
                                        {{ imagenFondoNombre || 'Elegir imagen...' }}
                                        <input type="file" accept="image/*" class="hidden" @change="handleImagenFondoChange" />
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expositores -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-bold text-slate-700">
                                Expositor(es) <span class="text-red-500">*</span>
                            </label>
                            <button type="button" @click="agregarExpositor"
                                class="text-sm font-semibold text-amber-600 hover:text-amber-800 flex items-center gap-1">
                                <Plus class="w-4 h-4" /> Agregar
                            </button>
                        </div>
                        <div class="space-y-2">
                            <div v-for="(expositor, index) in expositores" :key="index"
                                class="flex items-start gap-2">
                                <div class="flex-1">
                                    <input v-model="expositor.nombre" type="text" placeholder="Nombre del expositor"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none" />
                                </div>
                                <div class="flex-1">
                                    <input v-model="expositor.entidad" type="text" placeholder="Institución (opcional)"
                                        class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-slate-900 placeholder:text-slate-400 focus:ring-4 focus:ring-amber-500/20 focus:border-amber-500 bg-white transition-all duration-200 outline-none" />
                                </div>
                                <button type="button" @click="quitarExpositor(index)" :disabled="expositores.length === 1"
                                    class="p-2.5 text-red-500 hover:bg-red-50 rounded-xl transition-colors disabled:opacity-30 disabled:cursor-not-allowed">
                                    <Trash2 class="w-5 h-5" />
                                </button>
                            </div>
                        </div>
                        <p v-if="expositoresError" class="mt-1 text-sm text-red-600">{{ expositoresError }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 font-bold">
                        <button type="button" @click="$emit('close')"
                            class="px-6 py-2.5 border-2 border-slate-300 text-slate-600 rounded-xl hover:bg-slate-50 transition-all">
                            Cancelar
                        </button>
                        <button type="submit" :disabled="submitting"
                            class="px-6 py-2.5 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-xl hover:from-amber-700 hover:to-orange-700 transition-all shadow-lg shadow-amber-600/20 disabled:opacity-50 flex items-center gap-2">
                            <Loader2 v-if="submitting" class="w-5 h-5 animate-spin" />
                            {{ submitting ? 'Guardando...' : (isEditing ? 'Actualizar Evento' : 'Registrar Evento') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/yup';
import * as yup from 'yup';
import { X, GraduationCap, Pencil, Loader2, Plus, Trash2, Check } from 'lucide-vue-next';
import RichTextEditor from '@/Components/Common/RichTextEditor.vue';

const COLORES_TEMA = ['#d97706', '#2563eb', '#059669', '#dc2626', '#7c3aed', '#0891b2'];

const props = defineProps({
    evento: {
        type: Object,
        default: null
    },
    isEditing: {
        type: Boolean,
        default: false
    },
    submitting: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close', 'submit']);

const eventoSchema = toTypedSchema(
    yup.object({
        titulo: yup.string().required('El título es obligatorio').max(200),
        tipo: yup.string().required(),
        descripcion: yup.string().transform((v) => v || null).nullable(),
        modalidad: yup.string().required(),
        lugar: yup.string().when('modalidad', {
            is: (val) => val !== 'virtual',
            then: (schema) => schema.required('El lugar es obligatorio para esta modalidad'),
            otherwise: (schema) => schema.transform((v) => v || null).nullable(),
        }),
        enlace_virtual: yup.string().transform((v) => v || null).nullable(),
        fecha_inicio: yup.string().required('La fecha de inicio es obligatoria'),
        fecha_fin: yup.string().required('La fecha de fin es obligatoria')
            .test('after-inicio', 'La fecha de fin debe ser igual o posterior a la de inicio', function (value) {
                return !this.parent.fecha_inicio || !value || value >= this.parent.fecha_inicio;
            }),
        hora_inicio: yup.string().transform((v) => v || null).nullable(),
        hora_fin: yup.string().transform((v) => v || null).nullable(),
        cupo_maximo: yup.string().transform((v) => v || null).nullable(),
        horas_educativas: yup.string().transform((v) => v || null).nullable(),
    })
);

const { errors: formErrors, defineField, handleSubmit: validateForm, setValues, resetForm } = useForm({
    validationSchema: eventoSchema,
    initialValues: {
        titulo: '',
        tipo: 'curso',
        descripcion: '',
        modalidad: 'presencial',
        lugar: '',
        enlace_virtual: '',
        fecha_inicio: '',
        fecha_fin: '',
        hora_inicio: '',
        hora_fin: '',
        cupo_maximo: '',
        horas_educativas: '',
    }
});

const [titulo, tituloProps] = defineField('titulo');
const [tipo, tipoProps] = defineField('tipo');
const [descripcion] = defineField('descripcion');
const [modalidad, modalidadProps] = defineField('modalidad');
const [lugar, lugarProps] = defineField('lugar');
const [enlaceVirtual, enlaceVirtualProps] = defineField('enlace_virtual');
const [fechaInicio, fechaInicioProps] = defineField('fecha_inicio');
const [fechaFin, fechaFinProps] = defineField('fecha_fin');
const [horaInicio, horaInicioProps] = defineField('hora_inicio');
const [horaFin, horaFinProps] = defineField('hora_fin');
const [cupoMaximo, cupoMaximoProps] = defineField('cupo_maximo');
const [horasEducativas, horasEducativasProps] = defineField('horas_educativas');

const expositores = ref([{ nombre: '', entidad: '' }]);
const expositoresError = ref('');

const DEFAULT_COLOR = '#d97706';
const DEFAULT_FONDO = '/images/fondo_eventos.jpg';
const colorPrimario = ref(DEFAULT_COLOR);
const imagenFondoFile = ref(null);
const imagenFondoPreview = ref(DEFAULT_FONDO);
const imagenFondoNombre = ref('');

const handleImagenFondoChange = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    imagenFondoFile.value = file;
    imagenFondoNombre.value = file.name;
    imagenFondoPreview.value = URL.createObjectURL(file);
};

const agregarExpositor = () => {
    expositores.value.push({ nombre: '', entidad: '' });
};

const quitarExpositor = (index) => {
    if (expositores.value.length > 1) {
        expositores.value.splice(index, 1);
    }
};

watch(() => props.evento, (evento) => {
    if (evento && props.isEditing) {
        setValues({
            titulo: evento.titulo || '',
            tipo: evento.tipo || 'curso',
            descripcion: evento.descripcion || '',
            modalidad: evento.modalidad || 'presencial',
            lugar: evento.lugar || '',
            enlace_virtual: evento.enlace_virtual || '',
            fecha_inicio: evento.fecha_inicio || '',
            fecha_fin: evento.fecha_fin || '',
            hora_inicio: evento.hora_inicio || '',
            hora_fin: evento.hora_fin || '',
            cupo_maximo: evento.cupo_maximo ?? '',
            horas_educativas: evento.horas_educativas ?? '',
        });
        expositores.value = evento.expositores && evento.expositores.length
            ? evento.expositores.map((e) => ({ nombre: e.nombre, entidad: e.entidad || '' }))
            : [{ nombre: '', entidad: '' }];
        colorPrimario.value = evento.color_primario || DEFAULT_COLOR;
        imagenFondoPreview.value = evento.imagen_fondo_url || DEFAULT_FONDO;
        imagenFondoFile.value = null;
        imagenFondoNombre.value = '';
    } else {
        resetForm();
        expositores.value = [{ nombre: '', entidad: '' }];
        colorPrimario.value = DEFAULT_COLOR;
        imagenFondoPreview.value = DEFAULT_FONDO;
        imagenFondoFile.value = null;
        imagenFondoNombre.value = '';
    }
}, { immediate: true });

const onSubmit = validateForm((values) => {
    const expositoresValidos = expositores.value.filter((e) => e.nombre.trim() !== '');

    if (expositoresValidos.length === 0) {
        expositoresError.value = 'Debe registrar al menos un expositor';
        return;
    }
    expositoresError.value = '';

    emit('submit', {
        ...values,
        cupo_maximo: values.cupo_maximo || null,
        horas_educativas: values.horas_educativas || null,
        color_primario: colorPrimario.value || DEFAULT_COLOR,
        imagen_fondo: imagenFondoFile.value,
        expositores: expositoresValidos,
    });
});
</script>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #e2e8f0;
    border-radius: 20px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background-color: #cbd5e1;
}
</style>
