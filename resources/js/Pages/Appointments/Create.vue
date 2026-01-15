<template>
    <div class="min-h-screen bg-slate-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-4xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="/images/logo.png" alt="DRE Huánuco" class="mx-auto h-20 w-auto">
                <h2 class="mt-6 text-3xl font-extrabold text-slate-900">
                    Portal de Citas en Línea
                </h2>
                <p class="mt-2 text-sm text-slate-600">
                    Dirección Regional de Educación Huánuco
                </p>
            </div>

            <!-- Card -->
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-xl sm:px-10 border border-slate-100">
                <!-- Tabs -->
                <div class="border-b border-gray-200 mb-6">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'register'"
                            :class="[activeTab === 'register' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center']">
                            <Plus class="w-5 h-5 mr-2" />
                            Nueva Cita
                        </button>
                        <button @click="activeTab = 'consult'"
                            :class="[activeTab === 'consult' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300', 'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm flex items-center']">
                            <Search class="w-5 h-5 mr-2" />
                            Consultar Estado
                        </button>
                    </nav>
                </div>

                <!-- Tab Content: Nueva Cita -->
                <div v-if="activeTab === 'register'">
                    <NewAppointmentForm @success="generateVoucher" />
                </div>

                <!-- Tab Content: Consultar Estado -->
                <div v-if="activeTab === 'consult'">
                    <StatusConsultation @generate-voucher="generateVoucher" />
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
    imgId.onload = function() {
        try {
            doc.addImage(imgId, 'PNG', 20, 10, 25, 25);
        } catch(e) {
            console.warn(e);
        }
        generatePdfContent(doc, cita);
    }
    imgId.onerror = function() {
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
