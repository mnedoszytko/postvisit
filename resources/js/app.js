import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from '@/router';
import App from '@/App.vue';
import { useToastStore } from '@/stores/toast';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

app.config.errorHandler = (err) => {
    console.error('[PostVisit] Unhandled error:', err);
    const toast = useToastStore();
    toast.error('An unexpected error occurred. Please try again.');
};

app.mount('#app');
