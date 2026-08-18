import { reactive, readonly } from 'vue';

/**
 * Estado global (singleton) del visor de PDF in-app.
 * Cualquier componente puede llamar a `openPdf` para mostrar un PDF
 * dentro de la aplicación, sin salir a una pestaña nueva del navegador.
 *
 * Uso:
 *   import { usePdfViewer } from '@/composables/usePdfViewer';
 *   const { openPdf } = usePdfViewer();
 *   openPdf({ src: '/ruta/al/reporte.pdf', filename: 'reporte.pdf' });
 */
const state = reactive({
    isOpen: false,
    src: null,
    filename: 'documento.pdf',
});

export function usePdfViewer() {
    const openPdf = ({ src, filename = 'documento.pdf' }) => {
        state.src = src;
        state.filename = filename;
        state.isOpen = true;
    };

    const closePdf = () => {
        state.isOpen = false;
        state.src = null;
    };

    return {
        state: readonly(state),
        openPdf,
        closePdf,
    };
}
