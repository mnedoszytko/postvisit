import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import router from '@/router';
import App from '@/App.vue';
import { useToastStore } from '@/stores/toast';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            prefix: 'p',
            darkModeSelector: false,
        },
    },
});

app.config.errorHandler = (err) => {
    console.error('[PostVisit] Unhandled error:', err);
    const toast = useToastStore();
    toast.error('An unexpected error occurred. Please try again.');
};

app.mount('#app');
