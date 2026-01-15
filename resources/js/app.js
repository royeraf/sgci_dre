import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import '../css/app.css';

import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

createInertiaApp({
    title: (title) => `${title} - SGCI DRE Huánuco`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Define standard sweetalert configurations globaly if needed, or just attach
        window.Swal = Swal; // Make it available on window for ease if needed
        app.config.globalProperties.$swal = Swal;

        return app
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#0ea5e9',
    },
});
