import { defineStore } from 'pinia';
import { useApi } from '@/composables/useApi';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: null,
        token: null,
        initialized: false,
    }),

    getters: {
        isAuthenticated: (state) => !!state.user,
        isDoctor: (state) => state.user?.role === 'doctor',
        isPatient: (state) => state.user?.role === 'patient',
    },

    actions: {
        async register(name, email, password, passwordConfirmation, role) {
            const api = useApi();
            const response = await api.post('/auth/register', {
                name,
                email,
                password,
                password_confirmation: passwordConfirmation,
                role,
            });
            const payload = response.data.data;
            this.token = payload.token;
            this.user = payload.user;
        },

        async login(email, password) {
            const api = useApi();
            const response = await api.post('/auth/login', { email, password });
            const payload = response.data.data;
            this.token = payload.token;
            this.user = payload.user;
        },

        async logout() {
            const api = useApi();
            await api.post('/auth/logout');
            this.user = null;
            this.token = null;
        },

        async fetchUser() {
            const api = useApi();
            const { data } = await api.get('/auth/user');
            this.user = data.data;
        },

        async init() {
            if (this.initialized) return;
            this.initialized = true;
            try {
                await this.fetchUser();
            } catch {
                this.user = null;
                this.token = null;
            }
        },

        setDemoUser(role) {
            this.user = {
                id: 'demo',
                name: role === 'doctor' ? 'Dr. Demo' : 'Demo Patient',
                email: `demo-${role}@postvisit.ai`,
                role,
            };
            this.token = 'demo-token';
        },
    },
});
