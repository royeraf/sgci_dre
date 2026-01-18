<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 py-6 sm:py-8 md:py-12 px-3 sm:px-4 md:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto">
            <!-- Premium Header Card -->
            <div
                class="relative overflow-hidden rounded-3xl sm:rounded-[2rem] p-6 sm:p-8 md:p-12 text-white mb-6 sm:mb-8 md:mb-10 shadow-2xl shadow-blue-500/20 border border-white/10 ring-1 ring-white/5">
                <!-- Background Image with Premium Overlay -->
                <img src="/images/login-bg.png" alt="Background"
                    class="absolute inset-0 w-full h-full object-cover transform scale-105" />
                <div class="absolute inset-0 bg-gradient-to-r from-blue-900/95 via-blue-900/75 to-indigo-900/40"></div>

                <div
                    class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4 sm:gap-6 md:gap-8">
                    <div>
                        <h2
                            class="text-3xl sm:text-4xl md:text-5xl font-black mb-2 sm:mb-4 tracking-tight drop-shadow-lg text-center md:text-left">
                            Portal de <span
                                class="bg-gradient-to-r from-blue-300 to-indigo-200 bg-clip-text text-transparent">Citas</span>
                        </h2>
                        <p class="text-blue-100/90 text-base sm:text-lg font-semibold text-center md:text-left">
                            Dirección Regional de Educación Huánuco
                        </p>
                    </div>

                    <div class="flex justify-center">
                        <div
                            class="bg-white/10 backdrop-blur-2xl p-3 sm:p-4 rounded-2xl sm:rounded-3xl border border-white/20 shadow-2xl">
                            <img src="/images/logo.png" alt="Logo"
                                class="w-16 h-16 sm:w-20 sm:h-20 object-contain brightness-0 invert opacity-90" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content Container -->
            <div
                class="bg-white/80 backdrop-blur-md pb-6 sm:pb-8 px-3 sm:px-4 md:px-10 shadow-2xl rounded-3xl sm:rounded-[2.5rem] border-2 border-white relative">
                <!-- Tabs -->
                <div class="border-b border-slate-100 mb-6 sm:mb-8 z-10 rounded-t-3xl sm:rounded-t-[2.5rem]">
                    <nav class="-mb-px flex justify-center space-x-4 sm:space-x-8 md:space-x-12" aria-label="Tabs">
                        <button @click="activeTab = 'register'"
                            :class="[activeTab === 'register' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600 hover:border-slate-300', 'whitespace-nowrap py-4 sm:py-5 md:py-6 px-1 border-b-3 sm:border-b-4 font-bold text-sm sm:text-base flex items-center gap-1 sm:gap-2 transition-all duration-200']"
                            type="button">
                            <Plus class="w-4 h-4 sm:w-5 sm:h-5" />
                            Nueva Cita
                        </button>
                        <button @click="activeTab = 'consult'"
                            :class="[activeTab === 'consult' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-600 hover:border-slate-300', 'whitespace-nowrap py-4 sm:py-5 md:py-6 px-1 border-b-3 sm:border-b-4 font-bold text-sm sm:text-base flex items-center gap-1 sm:gap-2 transition-all duration-200']"
                            type="button">
                            <Search class="w-4 h-4 sm:w-5 sm:h-5" />
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

// Format date from ISO to dd/mm/yyyy
const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}/${month}/${year}`;
};

const generateVoucher = (cita) => {
    const doc = new jsPDF();
    const imgId = new Image();
    imgId.src = logoUrl;
    imgId.onload = function () {
        try {
            doc.addImage(imgId, 'PNG', 160, 8, 30, 30);
        } catch (e) {
            console.warn('Error loading logo:', e);
        }
        generatePdfContent(doc, cita);
    }
    imgId.onerror = function () {
        console.warn("Could not load logo");
        generatePdfContent(doc, cita);
    }
};

const generatePdfContent = (doc, cita) => {
    const pageWidth = 210;

    // Header with blue background
    doc.setFillColor(30, 64, 175);
    doc.rect(0, 0, pageWidth, 45, 'F');

    doc.setFontSize(24);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text('DRE HUÁNUCO', 105, 18, { align: 'center' });

    doc.setFontSize(12);
    doc.setFont('helvetica', 'normal');
    doc.text('Dirección Regional de Educación', 105, 26, { align: 'center' });
    doc.text('VOUCHER DE CITA', 105, 34, { align: 'center' });

    // Status Badge
    const estado = cita.estado || 'PENDIENTE';
    let estadoColor, estadoText;

    if (estado === 'ATENDIDO') {
        estadoColor = [34, 197, 94];
        estadoText = 'ATENDIDO';
    } else if (estado === 'CANCELADO') {
        estadoColor = [239, 68, 68];
        estadoText = 'CANCELADO';
    } else {
        estadoColor = [251, 191, 36];
        estadoText = 'PENDIENTE';
    }

    doc.setFillColor(...estadoColor);
    doc.roundedRect(65, 50, 80, 14, 3, 3, 'F');
    doc.setFontSize(12);
    doc.setTextColor(255, 255, 255);
    doc.setFont('helvetica', 'bold');
    doc.text(estadoText, 105, 59, { align: 'center' });

    // ID Badge - Destacado
    doc.setFillColor(241, 245, 249);
    doc.roundedRect(15, 70, 180, 18, 2, 2, 'F');
    doc.setFontSize(14);
    doc.setTextColor(30, 64, 175);
    doc.setFont('helvetica', 'bold');
    doc.text(`ID DE CITA: ${String(cita.id).padStart(6, '0')}`, 105, 81, { align: 'center' });

    // Main Info Section
    let yPos = 100;

    // Fecha y Hora destacadas
    doc.setFillColor(219, 234, 254);
    doc.roundedRect(15, yPos, 85, 24, 2, 2, 'F');
    doc.setFillColor(254, 226, 226);
    doc.roundedRect(110, yPos, 85, 24, 2, 2, 'F');

    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.setFont('helvetica', 'bold');
    doc.text('FECHA DE CITA', 57.5, yPos + 8, { align: 'center' });
    doc.text('HORA', 152.5, yPos + 8, { align: 'center' });

    doc.setFontSize(13);
    doc.setTextColor(30, 64, 175);
    doc.setFont('helvetica', 'bold');
    doc.text(formatDate(cita.fecha), 57.5, yPos + 18, { align: 'center' });
    doc.setTextColor(220, 38, 38);
    doc.text(cita.hora || 'N/A', 152.5, yPos + 18, { align: 'center' });

    yPos += 32;

    // Oficina destacada
    doc.setFillColor(236, 254, 255);
    doc.roundedRect(15, yPos, 180, 16, 2, 2, 'F');
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.setFont('helvetica', 'bold');
    doc.text('OFICINA / ÁREA', 20, yPos + 7);
    doc.setFontSize(11);
    doc.setTextColor(20, 20, 20);
    doc.setFont('helvetica', 'bold');
    doc.text(cita.oficina || 'N/A', 105, yPos + 12, { align: 'center' });

    yPos += 24;

    // Datos del Solicitante
    doc.setFontSize(11);
    doc.setTextColor(30, 64, 175);
    doc.setFont('helvetica', 'bold');
    doc.text('DATOS DEL SOLICITANTE', 15, yPos);

    doc.setDrawColor(200, 200, 200);
    doc.line(15, yPos + 2, 195, yPos + 2);

    yPos += 10;

    const addInfoRow = (label, value, y) => {
        doc.setFontSize(9);
        doc.setTextColor(100, 100, 100);
        doc.setFont('helvetica', 'bold');
        doc.text(label, 20, y);
        doc.setFontSize(10);
        doc.setTextColor(40, 40, 40);
        doc.setFont('helvetica', 'normal');
        const maxWidth = 120;
        const lines = doc.splitTextToSize(String(value || 'N/A'), maxWidth);
        doc.text(lines, 75, y);
        return y + (lines.length * 5);
    };

    yPos = addInfoRow('Nombres y Apellidos:', `${cita.nombres || ''} ${cita.apellidos || ''}`.trim() || 'N/A', yPos);
    yPos = addInfoRow('DNI:', cita.dni || 'N/A', yPos + 2);
    yPos = addInfoRow('Celular:', cita.celular || 'N/A', yPos + 2);
    yPos = addInfoRow('Correo:', cita.correo || 'N/A', yPos + 2);

    yPos += 6;

    // Asunto
    doc.setFontSize(9);
    doc.setTextColor(100, 100, 100);
    doc.setFont('helvetica', 'bold');
    doc.text('ASUNTO:', 20, yPos);
    doc.setFontSize(9);
    doc.setTextColor(40, 40, 40);
    doc.setFont('helvetica', 'normal');
    const asuntoLines = doc.splitTextToSize(String(cita.asunto || 'N/A'), 170);
    doc.text(asuntoLines, 20, yPos + 5);

    // QR Code placeholder
    yPos = 230;
    doc.setFillColor(245, 245, 245);
    doc.rect(15, yPos, 35, 35, 'F');
    doc.setFontSize(28);
    doc.setTextColor(180, 180, 180);
    doc.setFont('helvetica', 'bold');
    doc.text('QR', 32.5, yPos + 23, { align: 'center' });

    // Footer with instructions
    doc.setFontSize(8);
    doc.setTextColor(100, 100, 100);
    doc.setFont('helvetica', 'bold');
    doc.text('IMPORTANTE:', 55, yPos + 5);
    doc.setFontSize(7);
    doc.setFont('helvetica', 'normal');
    doc.text('• Presente este voucher al vigilante en la entrada', 55, yPos + 11);
    doc.text('• Llegue 10 minutos antes de su hora programada', 55, yPos + 16);
    doc.text('• Portar DNI original para su identificación', 55, yPos + 21);

    // Footer final
    yPos = 273;
    doc.setDrawColor(200, 200, 200);
    doc.line(15, yPos, 195, yPos);
    doc.setFontSize(7);
    doc.setTextColor(150, 150, 150);
    doc.text('Este documento es válido como constancia de reserva de cita.', 105, yPos + 5, { align: 'center' });
    doc.text(`Generado: ${new Date().toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' })}`, 105, yPos + 10, { align: 'center' });

    const pdfUrl = doc.output('bloburl');
    window.open(pdfUrl, '_blank');
};
</script>
