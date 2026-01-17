<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Premium Header Card -->
            <div
                class="relative overflow-hidden rounded-[2rem] p-8 md:p-12 text-white mb-10 shadow-2xl shadow-blue-500/20 border border-white/10 ring-1 ring-white/5">
                <!-- Background Image with Premium Overlay -->
                <img src="/images/login-bg.png" alt="Background"
                    class="absolute inset-0 w-full h-full object-cover transform scale-105" />
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/95 via-blue-900/75 to-indigo-900/40"></div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-8">
                    <div>
                        <h2
                            class="text-4xl md:text-5xl font-black mb-4 tracking-tight drop-shadow-lg text-center md:text-left">
                            Portal de <span
                                class="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">Citas</span>
                        </h2>
                        <p class="text-blue-100/90 text-lg font-semibold text-center md:text-left">
                            Dirección Regional de Educación Huánuco
                        </p>
                    </div>

                    <div class="flex justify-center">
                        <div class="bg-white/10 backdrop-blur-2xl p-4 rounded-3xl border border-white/20 shadow-2xl">
                            <img src="/images/logo.png" alt="Logo"
                                class="w-20 h-20 object-contain brightness-0 invert opacity-90" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Container -->
            <div
                class="bg-white/80 backdrop-blur-md pb-8 px-4 shadow-2xl sm:rounded-[2.5rem] sm:px-10 border-2 border-white relative">
                <!-- Tabs -->
                <div class="border-b border-slate-100 mb-8 z-10 rounded-t-[2.5rem]">
                    <nav class="-mb-px flex justify-center space-x-12" aria-label="Tabs">
                        <button @click="activeTab = 'register'"
                            :class="[activeTab === 'register' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600 hover:border-slate-300', 'whitespace-nowrap py-6 px-1 border-b-4 font-bold text-base flex items-center transition-all duration-200']">
                            <Plus class="w-5 h-5 mr-2" />
                            Nueva Cita
                        </button>
                        <button @click="activeTab = 'consult'"
                            :class="[activeTab === 'consult' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600 hover:border-slate-300', 'whitespace-nowrap py-6 px-1 border-b-4 font-bold text-base flex items-center transition-all duration-200']">
                            <Search class="w-5 h-5 mr-2" />
                            Consultar Estado
                        </button>
                    </nav>
                </div>

                <!-- Content -->
                <div class="relative z-0">
                    <!-- Tab Content: Nueva Cita -->
                    <div v-if="activeTab === 'register'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <NewAppointmentForm @success="generateVoucher" />
                    </div>

                    <!-- Tab Content: Consultar Estado -->
                    <div v-if="activeTab === 'consult'" class="animate-in fade-in slide-in-from-bottom-4 duration-500">
                        <StatusConsultation @generate-voucher="generateVoucher" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Plus, Search } from 'lucide-vue-next';
import jsPDF from 'jspdf';
import 'jspdf-autotable';

import NewAppointmentForm from '@/Components/Appointments/Portal/NewAppointmentForm.vue';
import StatusConsultation from '@/Components/Appointments/Portal/StatusConsultation.vue';

const activeTab = ref('register');
const logoUrl = '/images/logo.png';

const generateVoucher = (cita) => {
    const doc = new jsPDF();
    const imgId = new Image();
    imgId.src = logoUrl;
    imgId.onload = function () {
        try {
            doc.addImage(imgId, 'PNG', 20, 10, 25, 25);
        } catch (e) {
            console.warn(e);
        }
        generatePdfContent(doc, cita);
    }
    imgId.onerror = function () {
        console.warn("Could not load logo");
        generatePdfContent(doc, cita);
    }
};

const generatePdfContent = (doc, cita) => {
    doc.setFontSize(22);
    doc.setTextColor(40, 50, 100);
    doc.text('DRE Huánuco', 105, 20, { align: 'center' });

    doc.setFontSize(12);
    doc.setTextColor(100);
    doc.text('Constancia de Cita Electrónica', 105, 30, { align: 'center' });

    const estado = cita.estado || 'PENDIENTE';
    let estadoColor = [200, 150, 0];
    if (estado === 'ATENDIDO') estadoColor = [0, 150, 50];
    if (estado === 'CANCELADO') estadoColor = [200, 50, 50];

    doc.setFillColor(...estadoColor);
    doc.roundedRect(75, 35, 60, 10, 3, 3, 'F');
    doc.setFontSize(10);
    doc.setTextColor(255, 255, 255);
    doc.text(`Estado: ${estado}`, 105, 42, { align: 'center' });

    doc.setDrawColor(200);
    doc.line(20, 50, 190, 50);

    const startY = 60;
    const lineHeight = 12;

    doc.setFontSize(11);
    doc.setTextColor(50);

    const addRow = (label, value, y) => {
        doc.setFont('helvetica', 'bold');
        doc.text(`${label}:`, 30, y);
        doc.setFont('helvetica', 'normal');
        doc.text(String(value || 'N/A'), 85, y);
    };

    addRow('ID de Cita', String(cita.id), startY);
    addRow('Fecha', cita.fecha, startY + lineHeight);
    addRow('Hora', cita.hora, startY + lineHeight * 2);
    addRow('Oficina', cita.oficina, startY + lineHeight * 3);
    addRow('Solicitante', `${cita.nombres || ''} ${cita.apellidos || ''}`.trim() || 'N/A', startY + lineHeight * 4);
    addRow('DNI', cita.dni, startY + lineHeight * 5);
    addRow('Celular', cita.celular, startY + lineHeight * 6);
    addRow('Correo', cita.correo, startY + lineHeight * 7);

    doc.setFont('helvetica', 'bold');
    doc.text('Asunto:', 30, startY + lineHeight * 8);
    doc.setFont('helvetica', 'normal');
    const asuntoLines = doc.splitTextToSize(String(cita.asunto || 'N/A'), 100);
    doc.text(asuntoLines, 85, startY + lineHeight * 8);

    doc.setFontSize(8);
    doc.setTextColor(150);
    doc.text('Este documento sirve como constancia de su reserva de cita.', 105, 270, { align: 'center' });
    doc.text('Presente este documento el día de su cita.', 105, 275, { align: 'center' });
    doc.text(`Generado el: ${new Date().toLocaleString()}`, 105, 280, { align: 'center' });

    const pdfUrl = doc.output('bloburl');
    window.open(pdfUrl, '_blank');
};
</script>
